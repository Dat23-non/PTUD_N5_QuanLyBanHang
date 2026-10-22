# Thiết kế CSDL – Hệ thống quản lý bán hàng NPP A&D

**Phiên bản:** 1.1 (đã đối chiếu bổ sung với `QLBAN_HANG(1).xlsx`)
**CSDL:** MySQL/MariaDB trên XAMPP
**Tên database:** `ptud_quan_ly_ban_hang`

## 1. Căn cứ thiết kế

Thiết kế này đối chiếu ba nguồn đang có trong tài liệu nhóm:

- Báo cáo đề tài nhóm: năm tác nhân (quản trị hệ thống, nhân viên bán hàng, thủ kho, kế toán, chủ nhà phân phối), quy trình bán hàng–xuất kho–hóa đơn, thu hộ qua nhân viên, nhập hàng, chuyển kho, trả hàng, kiểm kê, báo cáo và phân quyền.
- Các workbook Excel `QLBAN_HANG.xlsx` trong gói tài liệu và `QLBAN_HANG(1).xlsx` mới bổ sung: các sheet Danh mục hàng, Danh sách khách hàng, Đơn vị tính, Danh mục NCC, Phiếu thu tiền, Chuyển kho và chi tiết, Nhập hàng và chi tiết, Tài khoản, Đơn hàng và chi tiết, nhân viên bán hàng, báo cáo tồn kho và công nợ, cùng các cột `SalesPersonID`, `FromStockID`, `ToStockID`, `UnitRate`, `SlsTax` trong workbook mới.
- Domain Model trong báo cáo: `NhaPhanPhoi`, `NhanVien`, `NhanVienBanHang`, `TaiKhoan`, `NhomQuyen`, `KhuVuc`, `Kho`, `TonKho`, `HangHoa`, `KhachHang`, `NhaCungCap`, `DonNhapHang`, `DonBanHang`, các lớp chi tiết và chứng từ nhập/chuyển kho.

Các tên bảng dưới đây được chuẩn hóa thành chữ thường, không dấu, dùng `snake_case`; mã nghiệp vụ như `ma_hang`, `ma_khach_hang` vẫn được giữ để đối chiếu Excel.

## 2. Các điểm chỉnh so với Domain Model/Excel

| Điểm thấy trong mô hình/dữ liệu cũ | Thiết kế CSDL đề xuất | Lý do |
|---|---|---|
| `HangHoa.TongTonKho` và `HangHoa.QtyStock` cùng với `TonKho.SoLuongTon` | Bỏ tồn kho tổng khỏi `hang_hoa`; lưu số lượng theo từng kho trong `ton_kho` với khóa ghép (`kho_id`, `hang_hoa_id`) | Tồn kho tổng là tổng tồn các kho; lưu nhiều nơi dễ lệch số liệu. |
| `KhachHang.NoQuaHan`, `NoTrongHan`; `NhaCungCap.NoQuaHan`, `NoTrongHan` | Không lưu thành cột trên danh mục; tính từ các hóa đơn còn dư nợ, ngày đến hạn và khoản thu/chi đã xác nhận | Đây là số dư biến động theo thời gian, không phải thuộc tính tĩnh của đối tác. |
| `DonBanHang.DaTra`, `ConNo`; `DonNhapHang.DaThanhToan`, `ConNo` | Không lưu số đã thu/đã trả và dư nợ trên đơn; phân bổ qua `chi_tiet_phieu_thu` và `chi_tiet_phieu_chi` | Tránh tổng tiền bị nhân đôi hoặc sai khi nhiều lần thanh toán. Tổng hóa đơn tại thời điểm chốt vẫn được lưu trên chứng từ tài chính. |
| Một `DonBanHang` đang chứa thông tin đơn, hóa đơn, thanh toán, hàng trả | Tách thành `don_ban_hang`, `phieu_xuat_kho`, `hoa_don_ban`, `phieu_thu`, `phieu_tra_hang` và các bảng chi tiết | Mỗi chứng từ có trạng thái và thời điểm ghi nhận riêng; khớp quy trình duyệt đơn → thực xuất → lập hóa đơn → thu tiền. |
| `LoaiHD` trong Excel trộn bán nợ, thu ngay, ký gửi, khách trả hàng, trả hàng đơn tiền ngay | Dùng loại thanh toán trên đơn bán; chuyển kho và trả hàng là chứng từ độc lập | Trả hàng và chuyển kho không phải loại hóa đơn bán hàng thông thường. |
| `PhieuChuyenKho` thể hiện kho xuất nhưng kho nhập chưa được mô hình hóa rõ | `phieu_chuyen_kho` có cả `kho_xuat_id` và `kho_nhap_id` | Cần ghi nhận giảm kho nguồn và tăng kho đích trong cùng giao dịch. Excel đã có `FromStockID` và `ToStockID`. |
| `ChungTu` tổng quát chứa số chứng từ/ngày nhưng chưa ràng buộc rõ loại chứng từ | Mỗi chứng từ nghiệp vụ có mã và ngày riêng | Tránh cấu trúc chứng từ đa hình không có khóa ngoại rõ ràng; truy vết dữ liệu và ràng buộc dễ hơn. |
| Một đơn vị tính chung trong Domain Model nhưng Excel có đơn vị lớn/nhỏ và tỷ lệ quy đổi | `don_vi_tinh` + đơn vị lớn/nhỏ, `ty_le_quy_doi` trên hàng hóa; từng dòng chứng từ lưu lại đơn vị và hệ số áp dụng | Cho phép bán/nhập theo thùng hoặc đơn vị nhỏ, vẫn thống nhất số tồn theo đơn vị cơ sở. |
| Số tiền khách trả NVBH và tiền NVBH nộp NPP có thể bị tính thành hai lần giảm nợ | `phieu_thu_nvbh` ghi nhận thu hộ; `dot_nop_tien_nvbh` + chi tiết ghi nhận bàn giao; `phieu_thu` chính thức và dòng phân bổ hóa đơn chỉ phát sinh khi kế toán xác nhận | Đúng quy tắc báo cáo: chỉ gạch nợ khi kế toán đối soát và lập phiếu thu. |

## 2.1. Đối chiếu bổ sung với workbook `QLBAN_HANG(1).xlsx`

Workbook bổ sung có 19 sheet: `Danh mục hàng`, `Danh sách khách hàng`, `Đơn vị tính`, `Danh mục NCC`, `Danh mục Phiếu Thu Tiền`, `Chuyển kho`, `Chi tiet chuyển kho`, `Danh mục nhập hàng`, `Chi tiết nhập hàng`, `Tài khoản`, `Đơn hàng`, `Bảng chi tiết hoa don`, `Loại HD`, `Danh sách NV bán hàng`, `Bao cao tồn kho`, `Báo cáo công nợ khách hàng`, `Baó cáo tồn kho của NV bán hàng`, `Báo cáo công nợ quá hạn`, `Bao cao cong no cua KH.`.

Ánh xạ được xác nhận từ các cột thực tế:
- `InvtID`, `InvtName`, `ClassName`, `UnitID_T`, `UnitID_L`, `UnitRate`, `SalesPriceT`, `SalesPriceL`, `SlsTax` → `hang_hoa`, `don_vi_tinh`; không nhập `QtyStock` thành tồn kho chung không phân kho.
- `CustID`, `CustName`, `Overdue`, `Amount`, `Address`, `Phone`, `Email`, `Status`, `Description` → `khach_hang`; `OverdueAmt` và `DueAmt` nên được tính từ chứng từ đã xác nhận, không lưu làm số dư gốc trên danh mục.
- `VendorID`, `VendorName`, `Amount` và thông tin liên hệ → `nha_cung_cap`; khoản nợ phát sinh phải dựa trên chứng từ mua và phiếu chi.
- `OrderNo` và chi tiết hàng bán → `don_ban_hang`, `chi_tiet_don_ban_hang`; dữ liệu thực xuất/hóa đơn theo quy trình của báo cáo được tách sang `phieu_xuat_kho` và `hoa_don_ban`.
- `PaymentID`, `PaymentNo`, `PaymentDate`, `PaymentAmt`, `CustID`, `SalesPersonID`, `Description` → `phieu_thu`; phiên bản 1.1 bổ sung FK tùy chọn `nhan_vien_ban_hang_id` để bảo toàn thông tin NVBH gắn với phiếu thu.
- `TransID`, `TransDate`, `FromStockID`, `ToStockID`, `TotalAmt` → `phieu_chuyen_kho`; giá trị dòng được snapshot ở `chi_tiet_phieu_chuyen_kho`, không coi điều chuyển là doanh thu.
- `StockID`, `Qty`, `PurchasePrice`, `QtyProm`, `QtyPromAmt`, `AmtProm`, `TaxAmt`, `Amount` → chi tiết đơn nhập/phiếu nhập theo ngữ cảnh. Kho nhận thực tế được ghi trên phiếu nhập kho đã xác nhận.
- Các sheet báo cáo tồn kho/công nợ là báo cáo tổng hợp, không nên nhập chúng thành bản ghi giao dịch mới hoặc dùng thay số liệu sổ gốc.

### Chất lượng dữ liệu cần làm sạch trước khi import dữ liệu mẫu

Workbook chứa dữ liệu mẫu chưa nhất quán hoàn toàn. Vì vậy, bộ `seed.sql` chỉ giữ dữ liệu nền danh mục được kiểm soát, không tự động nhập toàn bộ các dòng Excel:

1. Thuế trong danh mục hàng dùng tỷ lệ thập phân `0.1` và `0.08`, tức 10% và 8%. Schema 1.1 lưu `thue_suat` theo cùng quy ước: `0.1000 = 10%`, không phải `10.00 = 10%`.
2. Giá và quy đổi của một số mặt hàng không khớp phép nhân. Ví dụ `MH001`: `UnitRate=12`, giá lẻ `450.000` × 12 = `5.400.000`, trong khi `SalesPriceT=5.100.000`. `MH002`: `UnitRate=10`, giá lẻ `32.000` × 10 = `320.000`, trong khi `SalesPriceT=300.000`. Cần xác nhận đơn giá/quy đổi trước khi nạp giá chính thức.
3. `MH003` có trạng thái “Hết hàng” nhưng `QtyStock=40`; báo cáo khác còn cho thấy tồn phân kho. Không dùng cột trạng thái thay cho việc kiểm tra tồn khả dụng; cần xác minh ý nghĩa của trạng thái này khi làm sạch dữ liệu.
4. Cột ngày có cả giá trị ngày Excel dạng số và chuỗi ngày; phải chuẩn hóa thành `DATE`/`DATETIME` khi nạp.
5. `Danh mục nhập hàng` có mã `HD001`… trong cột `OrderNo`, trong khi sheet `Chi tiết nhập hàng` liên kết qua `PO001`…; không thể tự nối hai sheet chỉ bằng mã hiện có. Cần bảng đối chiếu mã phiếu.
6. Sheet chuyển kho có dòng chi tiết `CK004`, `CK005` nhưng sheet đầu phiếu mẫu chỉ có `CK001`–`CK003`. Cần bổ sung/đối chiếu đầu phiếu trước khi nhập chi tiết.
7. Các báo cáo công nợ có số dư khác nhau cho cùng khách hàng (ví dụ `KH001`, `KH003`, `KH004`). Các sheet này có thể là snapshot/mẫu khác thời điểm hoặc phiên bản; không seed số dư công nợ từ các báo cáo này.
8. Sheet `Loại HD` trộn giao dịch bán nợ, thu ngay, ký gửi và các dòng trả hàng âm. Schema tách trả hàng thành `phieu_tra_hang`, điều chuyển thành `phieu_chuyen_kho`; không nhập dòng trả hàng như đơn bán dương/âm nếu chưa có quy tắc chuyển đổi đã xác nhận.
9. Phiếu thu mẫu chỉ ghi diễn giải đơn hàng, không có cột phân bổ tiền theo hóa đơn chi tiết. Schema dùng `chi_tiet_phieu_thu` để phân bổ một phiếu thu cho một hoặc nhiều hóa đơn, tránh đoán phân bổ từ diễn giải văn bản.

## 3. Nhóm bảng

### A. Tổ chức, người dùng, khu vực

- `nha_phan_phoi`: hồ sơ nhà phân phối.
- `nhom_quyen`: năm nhóm quyền dự kiến: ADMIN, SALES, ACCOUNT, WAREHOUSE, MANAGER.
- `nhan_vien`: hồ sơ chung của nhân sự.
- `tai_khoan`: tên đăng nhập, mật khẩu đã băm, trạng thái và nhóm quyền; một nhân viên có tối đa một tài khoản.
- `nhan_vien_ban_hang`: phần thuộc tính riêng của NVBH, gồm hạn mức nhận hàng.
- `khu_vuc`, `phan_cong_khu_vuc`: khu vực và lịch sử phân công (giữ được lịch sử khi đổi tuyến/khu vực).

### B. Danh mục và tồn kho

- `khach_hang`: thông tin đại lý, hạn mức nợ, số ngày nợ, khu vực và NVBH phụ trách hiện tại.
- `nha_cung_cap`: đối tác cung ứng và hạn mức nợ.
- `don_vi_tinh`, `hang_hoa`: danh mục hàng, loại hàng, đơn vị lớn/nhỏ, quy đổi, giá sỉ/lẻ và thuế.
- `kho`: kho tổng, kho chi nhánh hoặc kho ký gửi của NVBH.
- `ton_kho`: số tồn theo từng cặp kho–hàng. Bảng này là số dư hiện hành; phải cập nhật cùng giao dịch xác nhận chứng từ trong transaction, không sửa bằng thao tác CRUD thông thường.

### C. Nhập hàng và kế hoạch nhập

- `ke_hoach_nhap_hang`, `chi_tiet_ke_hoach_nhap_hang`: kế hoạch mua theo nhu cầu tồn kho.
- `don_nhap_hang`, `chi_tiet_don_nhap_hang`: thông tin chứng từ mua hàng/nhập hàng, nhà cung cấp, hạn thanh toán, chiết khấu, khuyến mãi, hoa hồng, thuế và chi tiết hàng.
- `phieu_nhap_kho`, `chi_tiet_phieu_nhap_kho`: số lượng thực nhập đã được thủ kho xác nhận; có thể liên kết chứng từ mua hoặc phiếu trả hàng.

### D. Bán hàng, xuất kho, hóa đơn

- `don_ban_hang`, `chi_tiet_don_ban_hang`: đơn đặt hàng, khách hàng, NVBH, kho dự kiến xuất và trạng thái duyệt.
- `phieu_xuat_kho`, `chi_tiet_phieu_xuat_kho`: số lượng thực xuất; mỗi đơn chỉ có một phiếu xuất theo phạm vi báo cáo hiện tại.
- `hoa_don_ban`, `chi_tiet_hoa_don_ban`: hóa đơn được lập sau khi phiếu xuất đã xác nhận; lưu giá bán, chiết khấu, thuế và tổng tiền tại thời điểm phát hành.
- `chuong_trinh_khuyen_mai`, `chi_tiet_chuong_trinh_khuyen_mai`: cấu hình chương trình và hàng hóa áp dụng. Dòng đơn/hóa đơn phải lưu lại giá và chiết khấu đã áp dụng để không thay đổi lịch sử khi chương trình thay đổi.

### E. Chuyển kho, kiểm kê, trả hàng

- `phieu_chuyen_kho`, `chi_tiet_phieu_chuyen_kho`: điều chuyển từ kho nguồn sang kho đích; khi xác nhận thì giảm kho nguồn và tăng kho đích cùng transaction. Phiên bản 1.1 lưu thêm `tong_gia_tri` và `thanh_tien` làm giá trị tham chiếu snapshot, không ghi nhận doanh thu.
- `phieu_kiem_ke`, `chi_tiet_phieu_kiem_ke`: tồn sổ, tồn thực tế, chênh lệch và phê duyệt điều chỉnh.
- `phieu_tra_hang`, `chi_tiet_phieu_tra_hang`: hàng trả liên kết về hóa đơn/dòng hóa đơn gốc, số lượng và tình trạng hàng; phần tài chính được xử lý giảm công nợ hoặc hoàn tiền.

### F. Thu hộ, phiếu thu/chi, nhật ký

- `phieu_thu_nvbh`: ghi nhận số tiền NVBH đã thu từ từng khách hàng; chưa tự động gạch nợ.
- `dot_nop_tien_nvbh`, `chi_tiet_dot_nop_tien_nvbh`: bàn giao tiền cuối ca và đối chiếu theo từng khoản đã thu.
- `phieu_thu`, `chi_tiet_phieu_thu`: phiếu thu chính thức và phân bổ tiền cho từng hóa đơn bán.
- `phieu_chi`, `chi_tiet_phieu_chi`: chi trả nhà cung cấp theo từng chứng từ mua; cũng hỗ trợ chứng từ hoàn tiền gắn với phiếu trả hàng.
- `nhat_ky_he_thong`: ghi nhận người dùng, hành động và thay đổi dữ liệu để truy vết.

## 4. Quan hệ nghiệp vụ chủ yếu

1. Một nhóm quyền có nhiều tài khoản; một nhân viên có tối đa một tài khoản.
2. Một nhân viên bán hàng có thể được phân công nhiều khu vực theo thời gian và phụ trách nhiều khách hàng.
3. Một kho có nhiều mặt hàng; một mặt hàng tồn tại tại nhiều kho. Quan hệ nhiều-nhiều được hiện thực bằng `ton_kho`.
4. Một đơn bán có nhiều dòng hàng, tối đa một phiếu xuất; một phiếu xuất đã xác nhận có tối đa một hóa đơn bán.
5. Một hóa đơn bán có nhiều dòng hóa đơn và có thể được thanh toán nhiều lần; một phiếu thu có thể phân bổ cho một hoặc nhiều hóa đơn.
6. Một khoản thu hộ của NVBH chỉ là ghi nhận thu tiền mặt ngoài thị trường; giảm dư nợ khi kế toán lập phiếu thu chính thức sau khi đối soát tiền nộp.
7. Một phiếu chuyển kho có một kho xuất, một kho nhập và nhiều dòng hàng.
8. Một phiếu trả hàng gắn với hóa đơn gốc; nhập kho tăng theo lượng thực nhận được xác nhận, còn tác động công nợ/hoàn tiền được ghi nhận riêng.
9. Số dư công nợ khách hàng/NCC, doanh số và báo cáo nhập–xuất–tồn là số liệu tổng hợp từ chứng từ đã xác nhận, không lưu trùng trên bảng danh mục.

## 5. Quy tắc triển khai bắt buộc

- Dùng `password_hash()`/`password_verify()`; trường `mat_khau_hash` không lưu mật khẩu văn bản rõ.
- Không xóa vật lý khách hàng, nhân viên, hàng hóa hoặc chứng từ đã xác nhận; chuyển trạng thái ngừng hoạt động/hủy và ghi nhật ký.
- Chỉ cập nhật `ton_kho` khi xác nhận nhập, xuất, chuyển, trả hoặc kiểm kê; cập nhật số tồn và trạng thái chứng từ trong **cùng một database transaction**.
- Khi lập đơn, kiểm tra tồn khả dụng (`so_luong_ton - so_luong_giu_cho`) và hạn mức/hạn nợ khách hàng. Nếu dùng giữ chỗ tồn kho, phải tăng/giảm `so_luong_giu_cho` theo duyệt, hủy và xuất kho.
- Khi lập hóa đơn, sao chép giá, chiết khấu và thuế áp dụng thành dữ liệu snapshot trên dòng hóa đơn; không đọc lại giá hiện hành để tính lại hóa đơn cũ.
- Với hóa đơn nhiều lần thu tiền, tính dư nợ từ tổng hóa đơn trừ các phân bổ phiếu thu đã xác nhận và khoản giảm trừ đã được kế toán ghi nhận; không dùng cột `DaTra`/`ConNo` lưu tay.
- Không cho chuyển kho một kho sang chính nó; không xác nhận xuất/chuyển vượt tồn khả dụng; không phân bổ tiền vượt số tiền phiếu thu hoặc vượt dư nợ hóa đơn.
- Một số ràng buộc liên bảng (ví dụ NVBH đúng là người phụ trách kho ký gửi, khách hàng khớp với phiếu thu trực tiếp, số lượng trả không vượt số lượng đã bán, chỉ có một nguồn cho phiếu nhập) cần kiểm tra trong PHP service/transaction vì khóa ngoại đơn thuần không diễn đạt đủ được.

## 6. Dữ liệu đã seed

`seed.sql` chỉ thêm dữ liệu nền có thể dùng ngay: NPP A&D, năm nhóm quyền, danh mục đơn vị tính lấy từ workbook và kho tổng. Không tạo tài khoản với mật khẩu mặc định; tài khoản người dùng sẽ được tạo bằng chức năng quản trị hoặc bằng mật khẩu hash an toàn.

## 7. Những điều chưa được xác định rõ trong tài liệu

Bản thiết kế không tự đặt thêm quy tắc ngoài tài liệu; các điểm sau được để ở mức cấu hình/nghiệp vụ do ứng dụng kiểm tra:

- Công thức chính xác để tính số tiền giảm công nợ/hoàn tiền khi hàng trả lại trong các trường hợp hàng hỏng, thiếu số lượng hoặc đã thanh toán toàn bộ.
- Cách xử lý chi tiết cho trường hợp một khoản thu hộ chỉ được nộp một phần hoặc chênh lệch tiền thực nhận.
- Việc giữ chỗ số lượng khi đơn được duyệt: cột hỗ trợ đã có, nhưng nhóm cần dùng nhất quán trong service.
- Chính sách một NVBH có thể được phân công đồng thời nhiều khu vực hay chỉ một khu vực tại một thời điểm; `phan_cong_khu_vuc` lưu được lịch sử, còn kiểm tra trùng thời gian phải làm ở service.

Các điểm này không ngăn cản tạo schema ban đầu; cần chốt quy tắc trước khi triển khai những luồng tương ứng.
