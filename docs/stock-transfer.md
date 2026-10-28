# Lập phiếu chuyển kho — Quang

Phạm vi UC: Kho tổng → kho NVBH cùng nhà phân phối; lưu phiếu và chi tiết ở trạng thái `NHAP`, sau đó xem phiếu vừa lập. Không xác nhận, không sửa/xóa phiếu và không cập nhật hay giữ chỗ tồn kho trong UC này.

## Chạy trên máy

1. Bật Apache/MySQL trong XAMPP. Tạo `config/database.php` từ `config/database.example.php` nếu chưa có.
2. Dùng database đã thống nhất của nhóm. Nếu cài mới, import `database/schema_v1_1.sql`, rồi `database/seed.sql`. Không có file `database/schema.sql` như README cũ đề cập.
3. Trên máy hiện tại Apache chạy cổng **88**: mở `http://localhost:88/CK_PTUD/PTUD_N5_QuanLyBanHang/public/index.php?page=stock-transfer`. Máy khác đổi cổng/đường dẫn theo cấu hình XAMPP.

Chức năng nhận tài khoản đăng nhập từ `$_SESSION['tai_khoan_id']`. Khi tích hợp đăng nhập, người phụ trách xác thực phải gán ID sau khi kiểm tra thông tin đăng nhập, không lấy ID từ query/form. Phía server kiểm tra lại tài khoản, nhân viên, nhóm `WAREHOUSE`, NPP còn hoạt động và giới hạn xem phiếu theo NPP. Không mặc định tài khoản đầu tiên hoặc dùng ID người lập do trình duyệt gửi.

### Chế độ local (mặc định tắt)

Do chức năng đăng nhập chung còn là giao diện mẫu, có chế độ chọn một tài khoản thủ kho **có thật trong DB**. Chỉ bật trên máy cá nhân:

```powershell
Copy-Item config/stock-transfer.example.php config/stock-transfer.local.php
```

Sửa file local thành:

```php
<?php
return ['demo_enabled' => true];
```

File local được Git bỏ qua. Selector chỉ xuất hiện khi cấu hình bật và địa chỉ kết nối trực tiếp là `127.0.0.1` hoặc `::1`; không đọc `X-Forwarded-For`. Không dùng chế độ này trên máy chủ triển khai hoặc phía sau reverse proxy. Phiên thử dùng khóa riêng `stock_transfer_demo_actor_id`, không đăng nhập vào các chức năng khác, và hết hiệu lực khi tắt chế độ thử. Tài khoản đăng nhập thật luôn được ưu tiên.

Cần tài khoản thủ kho đang hoạt động, đúng một Kho tổng, ít nhất một kho `KHO_KY_GUI` gắn NVBH đang làm việc trong cùng NPP, hàng hóa/đơn vị và `ton_kho` tại Kho tổng. Có thể kiểm tra số lượng dữ liệu bằng:

```powershell
& E:\xampp\php\php.exe scripts/stock-transfer-demo.php --check
```

Khởi tạo hoặc nâng cấp dữ liệu sản phẩm sữa:

```powershell
& E:\xampp\php\php.exe scripts/stock-transfer-demo.php --seed
```

Lệnh này **ghi dữ liệu khởi tạo** vào database đã cấu hình: nhà phân phối A&D (giữ mã `ST_DEMO` để tương thích), thủ kho “Đỗ Văn Quang”, NVBH “Nguyễn Văn An”, hai kho, 3 nhà cung cấp và 12 sản phẩm sữa. Tồn đầu kỳ minh họa là 100 đơn vị nhỏ/mặt hàng ở kho tổng và 0 ở kho nhận, chỉ thêm nếu chưa có dòng tồn; chạy lại không đặt lại tồn hoặc giá đã chỉnh. Khi nâng cấp dữ liệu cũ, script đổi đúng các nhãn gốc chứa “thử”, ngừng sử dụng hai mặt hàng giữ chỗ và giữ nguyên ID/phiếu đã lập. Tài khoản local có hash mật khẩu ngẫu nhiên, không cung cấp mật khẩu đăng nhập; chỉ dùng selector local. Không import `tests/fixtures/stock-transfer.sql` vào DB ứng dụng vì fixture dùng ID cố định cho DB test riêng.

Xem [hướng dẫn SQL, danh mục sữa và Git](dairy-data.md) để biết thứ tự import, cách bổ sung sản phẩm và các file cần commit/bỏ qua.

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
