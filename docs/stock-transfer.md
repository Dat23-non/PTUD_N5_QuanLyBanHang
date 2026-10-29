# Lập phiếu chuyển kho — Quang

Phạm vi UC: Kho tổng → kho NVBH cùng nhà phân phối; lưu phiếu và chi tiết ở trạng thái `NHAP`, sau đó xem phiếu vừa lập. Không xác nhận, không sửa/xóa phiếu và không cập nhật hay giữ chỗ tồn kho trong UC này.

## Chạy trên máy

1. Bật Apache/MySQL trong XAMPP. Tạo `config/database.php` từ `config/database.example.php` nếu chưa có.
2. Dùng database đã thống nhất của nhóm. Nếu cài mới, import `database/schema_v1_1.sql`, rồi `database/seed.sql`. Không có file `database/schema.sql` như README cũ đề cập.
3. Trên máy hiện tại Apache chạy cổng **88**: mở `http://localhost:88/CK_PTUD/PTUD_N5_QuanLyBanHang/public/index.php?page=stock-transfer`. Máy khác đổi cổng/đường dẫn theo cấu hình XAMPP.

Chức năng nhận tài khoản đăng nhập từ `$_SESSION['tai_khoan_id']`. Khi tích hợp đăng nhập, người phụ trách xác thực phải gán ID sau khi kiểm tra thông tin đăng nhập, không lấy ID từ query/form. Phía server kiểm tra lại tài khoản, nhân viên, nhóm `WAREHOUSE`, NPP còn hoạt động và giới hạn xem phiếu theo NPP. Không mặc định tài khoản đầu tiên hoặc dùng ID người lập do trình duyệt gửi.

### Đăng nhập chung và khởi tạo máy mới

Đã bỏ bộ chọn tài khoản local. File `config/stock-transfer.local.php` không còn được đọc. Dùng trang `index.php?page=login` với mật khẩu thật lưu dưới dạng hash trong DB. Phiên dùng khóa `tai_khoan_id` chung; người lập phiếu được lấy từ phiên này.

Sau khi pull/merge, bật MySQL và chạy trong thư mục dự án (đổi đường dẫn PHP theo máy):

```powershell
& E:\xampp\php\php.exe scripts/setup-stock-transfer.php test-warehouse
```

Nếu thiếu `config/database.php`, script tạo từ cấu hình example của XAMPP. MySQL có mật khẩu/cổng riêng thì chỉnh file local đó trước khi chạy. Database mới được tạo theo schema v1.1; database đã có được giữ nguyên, cần tương thích schema v1.1.

Tài khoản đã tồn tại: giữ mật khẩu và NPP, kiểm tra quyền thủ kho, bổ sung kho/danh mục/tồn đầu kỳ còn thiếu theo đúng NPP tài khoản. Tài khoản mới: nhập mật khẩu khi terminal yêu cầu. Nếu có nhiều NPP và tạo tài khoản mới, truyền mã NPP ở tham số thứ hai. Không ghi mật khẩu vào Git hoặc URL. Script không tự đăng nhập hay bỏ kiểm tra quyền.

Ví dụ: `php scripts/setup-stock-transfer.php test-warehouse NPP001`. Tồn đầu kỳ 100 chỉ thêm vào dòng chưa tồn tại, phục vụ đồ án; không thay thế chứng từ nhập hàng thực tế. Giá chuyển kho vẫn nhập trên từng dòng.

### Danh sách và trạng thái

Đầu tab Chuyển kho hiển thị tối đa 200 phiếu mới nhất trong NPP của tài khoản; có bộ lọc Tất cả/Nháp/Đã duyệt/Đã hủy và liên kết xem chi tiết. “Đã duyệt” hiển thị giá trị DB `DA_XAC_NHAN`; chưa có thao tác duyệt trong lần thay đổi này. Lưu nháp ghi header và detail trong transaction, redirect đến chi tiết, và phiếu xuất hiện lại trong danh sách sau khi tải trang.

Các route `login` và `stock-transfer`, cùng liên kết đăng nhập ở sidebar, cần được người tích hợp review. Nếu dự án chung đã có AuthController khác, tích hợp session `tai_khoan_id` với hệ thống đó thay vì tạo cơ chế đăng nhập song song.

## Quyết định nghiệp vụ tạm thời

- **Đơn giá:** theo trao đổi với Quang, tạm nhập đơn giá ghi nhận trực tiếp trên từng dòng; bắt buộc có giá, không âm, tối đa 2 chữ số thập phân. Không tự lấy `gia_si`, `gia_le` hoặc giá nhập. Giá và thành tiền được lưu snapshot vào các cột đã có. Cần chốt lại nguồn giá khi hoàn thiện báo cáo và cập nhật đặc tả/sequence tương ứng.
- **Đơn vị:** dùng `don_vi_nho_id` của hàng hóa, hệ số quy đổi ghi `1`. Số lượng là số nguyên dương theo đặc tả. Chưa hỗ trợ chọn đơn vị lớn/thùng; không tự quy đổi theo giá bán.
- **Tồn:** đối chiếu `ton_kho.so_luong_ton` tại Kho tổng theo đặc tả hiện tại, đọc lại trong transaction lúc lưu. Chưa trừ `so_luong_giu_cho` khỏi giới hạn lập nháp. Trước khi triển khai UC xác nhận cần chốt quy tắc tồn khả dụng, kiểm tra lại tồn/giữ chỗ và xử lý cạnh tranh ở thời điểm xác nhận. Nhiều phiếu nháp có thể cùng tham chiếu một lượng tồn vì chưa đặt giữ chỗ.
- **Ngày:** ngày lịch hợp lệ, năm 1000–9999; chưa cấm ngày quá khứ/tương lai vì đặc tả chưa quy định.
- **Giới hạn biểu mẫu:** tối đa 100 mặt hàng và ghi chú tối đa 4.000 byte UTF-8. Form giới hạn 1.000 ký tự ghi chú để phù hợp giới hạn byte.

## File và điểm tích hợp

- `StockTransferController`: GET form/chi tiết, POST lưu nháp hoặc chọn tài khoản local; CSRF, giữ input khi lỗi, redirect sau lưu.
- `StockTransferService`: kiểm tra nghiệp vụ, tính tiền bằng số nguyên xu, điều phối transaction lưu header + detail. Đọc lại và khóa chia sẻ tài khoản/kho/hàng/tồn trong transaction; rollback nếu bất kỳ bước nào thất bại.
- `StockTransfer`: prepared statements, lấy dữ liệu theo NPP, tạo phiếu `NHAP`, đọc chi tiết. Không có truy vấn cập nhật `ton_kho`.
- View dùng lại `app/Views/warehouse/stock-transfer.php`; CSS/JS riêng có prefix `st-`. Không sao chép layout hay sửa Core/schema.
- `routes/web.php`: chỉ đổi entry `stock-transfer` từ View sang `StockTransferController::index`. Đây là thay đổi route dùng chung cần người tích hợp review trong PR; không tạo route trùng.

Mã phiếu được tạo ở server, dài 29 ký tự, có ràng buộc UNIQUE trong schema. Form có token riêng lưu trong session: gửi lại cùng biểu mẫu đã lưu sẽ trả về cùng phiếu; GET sau lưu không tạo phiếu mới. Session khóa mặc định của PHP tuần tự hóa các POST cùng phiên. CSRF không thay thế kiểm tra quyền; cả hai được kiểm tra ở server.

## Kiểm thử

```powershell
& E:\xampp\php\php.exe tests/stock-transfer.php
& E:\xampp\php\php.exe tests/stock-transfer.php --browser
```

Bộ test cần MySQL đang chạy và user cấu hình có quyền tạo/xóa **database test riêng**, cùng quyền tạo trigger để giả lập lỗi giữa chừng. Mỗi lần chạy tạo database tên `ptud_st_test_<12 ký tự hex>` rồi xóa đúng database đó trong `finally`. Không sửa config hoặc ghi vào database ứng dụng. Nếu cưỡng bức dừng tiến trình trước `finally`, database test có thể còn lại.

`--browser` cần Node.js có `fetch`/`WebSocket` (đã chạy với Node 24) và Chrome đã cài. Có thể đặt `CHROME_PATH` đến Chrome/Edge khác. Browser dùng profile tạm và `--no-sandbox` chỉ cho tiến trình QA localhost trong môi trường thực thi hạn chế; không truy cập website bên ngoài. Profile và server test được dọn sau khi chạy. Có thể đặt `PTUD_ST_SCREENSHOT_DIR` nếu muốn giữ ảnh desktop.

Các ca quan trọng: nhiều dòng và số tiền thập phân; kho nhận sai/trùng kho tổng/khác NPP; hàng trùng/không tồn tại/ngừng kinh doanh; thiếu hàng/số lượng sai/vượt tồn; giá sai/tràn tổng tiền; ngày sai; tài khoản thiếu quyền/bị khóa; tồn thay đổi trước lưu; lỗi dòng thứ hai rollback cả header và dòng đầu; CSRF; giữ input và escape HTML; PRG/chống gửi lặp; xem phiếu khác NPP; chế độ thử tắt/localhost/quyền tài khoản; thao tác desktop/mobile; tồn hai kho giữ nguyên.

PR gửi vào `develop`, phạm vi “Lập phiếu chuyển kho — lưu nháp và xem chi tiết”. UC xác nhận sẽ là phần phát triển tiếp theo sau khi thống nhất phạm vi.
