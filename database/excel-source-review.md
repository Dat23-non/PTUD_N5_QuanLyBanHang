# Rà soát workbook nguồn: QLBAN_HANG(1).xlsx

## Phạm vi đã xem
Workbook gồm 19 sheet về danh mục hàng hóa, khách hàng, đơn vị tính, nhà cung cấp, phiếu thu, chuyển kho và chi tiết chuyển kho, nhập hàng và chi tiết nhập hàng, tài khoản, đơn hàng và chi tiết, loại hóa đơn/giao dịch, nhân viên bán hàng, báo cáo tồn kho, tồn kho NVBH, công nợ khách hàng và công nợ quá hạn.

## Ánh xạ sang mô hình CSDL
| Sheet | Cột/ý nghĩa nhìn thấy trong nguồn | Ánh xạ đề xuất |
|---|---|---|
| Danh mục hàng | InvtID, InvtName, ClassName, UnitID_T, UnitID_L, UnitRate, SalesPriceT, SalesPriceL, QtyStock, SlsTax, Status | `hang_hoa`, `don_vi_tinh`; tồn thực tế theo từng kho nằm ở `ton_kho` |
| Danh sách khách hàng | CustID, CustName, hạn nợ, hạn mức, nợ quá hạn, nợ trong hạn, liên hệ | `khach_hang`; số dư quá hạn/trong hạn tính từ hóa đơn và thanh toán đã xác nhận |
| Đơn vị tính | UnitID, UnitName, phân loại | `don_vi_tinh` |
| Danh mục NCC | VendorID, VendorName, liên hệ, hạn mức và trạng thái | `nha_cung_cap`; công nợ tính từ chứng từ mua và phiếu chi |
| Danh mục Phiếu Thu Tiền | PaymentID, PaymentNo, PaymentDate, PaymentAmt, CustID, SalesPersonID, Description | `phieu_thu` + `chi_tiet_phieu_thu`; NVBH liên quan được lưu tùy chọn |
| Chuyển kho | TransID, TransDate, FromStockID, ToStockID, TotalAmt, Description | `phieu_chuyen_kho` + `chi_tiet_phieu_chuyen_kho` |
| Chi tiết chuyển kho | TransID, InvtID, Qty, Amount | `chi_tiet_phieu_chuyen_kho` |
| Danh mục nhập hàng | OrderNo, OrderDate, OrderType, OverdueDate, DiscAmt, PromAmt, ComAmt, TaxAmt, TotalAmt | `don_nhap_hang`, sau khi xác minh mã và NCC |
| Chi tiết nhập hàng | OrderNo, InvtID, Qty, PurchasePrice, StockID, QtyProm, QtyPromAmt, AmtProm, TaxAmt, Amount | `chi_tiet_don_nhap_hang`; thực nhận tại kho qua `phieu_nhap_kho` |
| Tài khoản | UserID, UserName, GroupID, diễn giải nhóm quyền | `tai_khoan`, `nhom_quyen` |
| Đơn hàng | OrderNo, OrderDate, InvoiceType, CustID, OverdueDate, OrderDisc, TaxAmt, TotalAmt, Payment, Debt, Description | `don_ban_hang`, `chi_tiet_don_ban_hang`, `hoa_don_ban`; không lưu lại số đã trả/dư nợ thành cột gốc |
| Bảng chi tiết hóa đơn | OrderNo, InvtID, Qty, SalesPrice, Discount, TaxAmt, Amount | Chi tiết đơn bán/chi tiết hóa đơn theo đúng vòng đời đơn → thực xuất → hóa đơn |
| Loại HD | NP, NM, ND, IN, CM, gồm cả một số dòng số lượng/tiền âm | Không nhập trực tiếp thành bảng danh mục nếu chưa chốt ý nghĩa từng mã; trả hàng và chuyển kho là chứng từ riêng |
| Danh sách NV bán hàng | mã NVBH, tên, địa chỉ, hạn mức nhận hàng, trạng thái | `nhan_vien` + `nhan_vien_ban_hang` |
| Báo cáo tồn kho | hàng, kho, tồn đầu kỳ, nhập, xuất, tồn cuối | View/query từ `ton_kho` và chứng từ đã xác nhận |
| Báo cáo tồn kho NVBH | tồn đầu, nhận, bán, tồn cuối theo NVBH | Tổng hợp theo kho ký gửi do NVBH phụ trách |
| Báo cáo công nợ | dư nợ hiện tại/quá hạn theo khách hàng và NVBH | Query từ hóa đơn, phiếu thu, phân bổ, trả hàng; không nhập làm giao dịch |

## Bất nhất dữ liệu cần lưu ý trước khi nhập dữ liệu lịch sử

1. **Thuế:** nguồn dùng `0.1`/`0.08` để thể hiện 10%/8%. Quy ước của schema phiên bản 1.1 là tỷ lệ thập phân.
2. **Giá/quy đổi:** `MH001` có tỷ lệ 12, giá lẻ 450.000 nhưng giá lớn 5.100.000 (phép nhân ra 5.400.000); `MH002` có tỷ lệ 10, giá lẻ 32.000 nhưng giá lớn 300.000 (phép nhân ra 320.000).
3. **Trạng thái hàng:** `MH003` hiển thị “Hết hàng” trong khi `QtyStock=40`; tồn kho cần kiểm tra theo kho, trạng thái kinh doanh và tồn khả dụng là các khái niệm riêng.
4. **Mã đơn nhập:** sheet đầu phiếu ghi `HD001`… nhưng sheet chi tiết dùng `PO001`…; thiếu một khóa nối nhất quán trong mẫu.
5. **Mã phiếu chuyển:** chi tiết có `CK004`/`CK005` trong khi sheet đầu phiếu mẫu chỉ có `CK001`–`CK003`.
6. **Ngày:** trường ngày có cả số serial Excel và chuỗi ngày, cần chuẩn hóa.
7. **Công nợ báo cáo:** cùng mã khách hàng xuất hiện ở nhiều báo cáo với số dư khác nhau; không thể tự chọn một số làm số dư đầu kỳ nếu chưa có xác nhận.
8. **Loại giao dịch:** `Loại HD` trộn bán nợ, tiền ngay, ký gửi và trả hàng với số âm. Cần ánh xạ nghiệp vụ trước khi nhập.
9. **Phiếu thu:** mẫu thiếu phân bổ rõ từng hóa đơn; cần xác định phân bổ từ chứng từ gốc thay vì tự tách từ mô tả.

## Quyết định an toàn
Không nhập toàn bộ dữ liệu mẫu Excel thành dữ liệu seed ngay. Dùng workbook làm căn cứ cho trường dữ liệu và quy trình; chỉ nhập giao dịch lịch sử sau khi nhóm đối chiếu mã, ngày, đơn giá/quy đổi, kho và số dư công nợ.
