# Danh mục sữa, import SQL và bàn giao Git

## Hiện trạng và phạm vi

Danh mục khởi tạo có 12 mặt hàng: 6 Vinamilk, 4 TH, 2 DalatFarm (tên theo yêu cầu nhóm), gồm sữa tươi, sữa chua ăn/uống, sữa đặc và sữa bột. Mã, tên/quy cách phục vụ đồ án; cần đối chiếu danh mục nhà cung cấp trước khi dùng làm dữ liệu kinh doanh chính thức. Chưa coi đây là SKU/giá bán chính thức.

Schema chưa có cột liên kết trực tiếp `hang_hoa` với `nha_cung_cap`. Bộ dữ liệu thêm cả hai danh mục nhưng không tự thêm khóa ngoại/cột mới. Khi làm nghiệp vụ nhập hàng, NCC sẽ được ghi trên chứng từ theo schema. Nếu nhóm muốn quản lý hàng–NCC trực tiếp, cần thống nhất mô hình trước.

Giá sỉ, giá lẻ và thuế trong dữ liệu khởi tạo đang là 0 vì nhóm chưa chốt. Đây không phải mức giá/thuế áp dụng thực tế. Đơn giá chuyển kho vẫn nhập trên phiếu, không tự dùng các giá này. Đơn vị là Hộp/Hũ/Chai/Lon; chưa tự giả định quy đổi thùng/lốc.

## Các file SQL dùng để làm gì?

| File | Mục đích | Khi dùng |
|---|---|---|
| `database/schema_v1_1.sql` | Tạo bảng, khóa và database | Cài máy mới; không cần chạy lại chỉ để thêm sữa |
| `database/seed.sql` | Danh mục nền chung: NPP, nhóm quyền, đơn vị, Kho tổng | Cài máy mới theo quy ước nhóm |
| `database/seed_dairy_catalog.sql` | Thêm 3 NCC, đơn vị và 12 mặt hàng sữa | File chính để import sản phẩm |
| `database/update_stock_transfer_labels.sql` | Đổi nhãn gốc cũ, ngừng dùng hai hàng giữ chỗ | Nâng cấp dữ liệu đã tạo bằng script phiên bản cũ |
| `database/seed_stock_transfer_inventory.sql` | Thêm tồn đầu kỳ cho danh mục sữa tại hai kho local | Sau khi đã có kho `ST_TONG`/`ST_NVBH` của NPP `ST_DEMO` |
| `tests/fixtures/stock-transfer.sql` | Dữ liệu test với ID cố định | Chỉ bộ test tự dùng, không import vào DB ứng dụng |

Ba file SQL bổ sung không chọn database bằng `USE`: chọn đúng database `ptud_quan_ly_ban_hang` trong phpMyAdmin trước khi import. Mỗi file có transaction; nếu có lỗi, dừng import và xử lý lỗi trước khi dùng dữ liệu.

## Máy khác lấy code về chạy

Merge chỉ cập nhật mã nguồn; mỗi máy cần khởi tạo MySQL một lần. Từ thư mục dự án, bật MySQL rồi chạy (đổi đường dẫn PHP theo máy):

```powershell
& E:\xampp\php\php.exe scripts/setup-stock-transfer.php test-warehouse
```

Script dùng tài khoản `test-warehouse` đã có, giữ mật khẩu và chuẩn bị kho/tồn theo NPP của tài khoản đó, không phụ thuộc mã `ST_DEMO`. Nếu chưa có tài khoản thì yêu cầu nhập mật khẩu để tạo tài khoản thật. Có nhiều NPP thì chỉ rõ mã ở tham số thứ hai. Mở trang đăng nhập chung, sau đó vào Chuyển kho. Bộ chọn local và cấu hình bật local đã được bỏ.

Nếu thiếu config DB, script copy `config/database.example.php`; mặc định XAMPP root/mật khẩu rỗng/cổng 3306. Máy cấu hình khác cần sửa `config/database.php`. Database mới được tạo theo schema; database đã có phải tương thích schema v1.1. Không tự sửa cấu trúc bảng đang có.

Danh sách phía trên form hiển thị Nháp/Đã duyệt/Đã hủy. Lưu nháp thành công có trang chi tiết và phiếu được liệt kê khi tải lại. Chưa bổ sung thao tác duyệt.

Ba SQL cũ vẫn dùng được cho bộ dữ liệu ST_DEMO đã tạo trước đây, nhưng riêng file tồn có điều kiện mã NPP/kho cố định nên không phải bộ cài cho máy mới. Ưu tiên script setup mới. Chỉ import catalog khi muốn thêm danh mục độc lập; bổ sung tồn cần đúng kho của tài khoản.

## Muốn thêm hoặc sửa mặt hàng thì sửa ở đâu?

- **Thêm mặt hàng dùng chung:** thêm dòng mã/tên/loại/đơn vị trong `database/seed_dairy_catalog.sql`; thêm mã tương ứng vào danh sách của `database/seed_stock_transfer_inventory.sql` nếu muốn có tồn đầu kỳ local. Import lại hai file. Dùng mã hàng mới, duy nhất.
- **Sửa mặt hàng đã import:** file catalog chỉ thêm mã còn thiếu, không ghi đè dữ liệu cũ. Viết một script `UPDATE hang_hoa ... WHERE ma_hang = '...'` riêng, có phạm vi rõ ràng và gửi người phụ trách CSDL review. Chỉ sửa file INSERT rồi import lại sẽ không đổi dòng đang có.
- **Đổi lượng tồn đầu kỳ trước lần import đầu:** sửa giá trị `100` trong file inventory. Sau khi đã có dòng tồn, chạy lại không đặt lại số lượng. Tồn đang vận hành cần điều chỉnh qua nghiệp vụ/chứng từ phù hợp.
- **Giao diện:** `app/Views/warehouse/stock-transfer.php` và CSS/JS cùng tên.
- **Quy tắc lưu nháp/giá/số lượng:** `app/Services/StockTransferService.php`.
- **Truy vấn:** `app/Models/StockTransfer.php`.
- **Đăng nhập/chế độ local:** cấu hình local và điểm tích hợp session trong Controller; không hard-code ID người lập.

## File nào vào .gitignore?

Đã có quy tắc bỏ qua:

```gitignore
/config/database.php
/config/stock-transfer.local.php
/database/backups/
/database/*.local.sql
.env
.env.*
```

`config/database.php` chứa cấu hình máy. File `stock-transfer.local.php` cũ không còn được đọc; giữ quy tắc ignore để tránh đẩy nhầm file cũ. Bản export/backup DB có thể chứa dữ liệu tài khoản: lưu dưới `database/backups/`, không commit. SQL riêng cho một máy có thể đặt tên `database/ten-file.local.sql`. Không thêm quy tắc `*.sql` vì schema và seed dùng chung phải được đưa lên Git. File `config/database.example.php` phải được commit.

## Bàn giao trên nhánh feature/stock-transfer

Đọc diff, chạy test rồi stage các file cụ thể:

```powershell
& E:\xampp\php\php.exe tests/stock-transfer.php --browser
git status --short
git diff --check
git add .gitignore README.md docs/stock-transfer.md docs/dairy-data.md
git add app/Controllers/StockTransferController.php app/Models/StockTransfer.php app/Services/StockTransferService.php
git add app/Views/warehouse/stock-transfer.php public/assets/css/stock-transfer.css public/assets/js/stock-transfer.js
git add routes/web.php scripts/stock-transfer-demo.php scripts/setup-stock-transfer.php app/Services/StockTransferSetup.php app/Controllers/AuthController.php app/Models/Account.php app/Views/auth/login.php app/Views/layouts/sidebar.php
git add database/seed_dairy_catalog.sql database/update_stock_transfer_labels.sql database/seed_stock_transfer_inventory.sql
git add tests/stock-transfer.php tests/stock-transfer-browser.mjs tests/fixtures/stock-transfer.sql tests/fixtures/stock-transfer-router.php tests/fixtures/stock-transfer-seed.php
git diff --cached --check
git diff --cached --stat
git diff --cached
```

Khi đã xem staged đúng phạm vi:

```powershell
git commit -m "feat(stock-transfer): add draft transfer and dairy catalog"
git push -u origin feature/stock-transfer
```

Tạo PR vào `develop`. Nêu rõ có một thay đổi entry route dùng chung cần Đạt review; không thay schema/Core. Đính kèm cách chạy seed, test thành công và quyết định đơn giá tạm nhập. Nhóm khác pull code sẽ **không tự nhận dữ liệu trong MySQL của bạn**; phải chạy script hoặc import SQL theo hướng dẫn.
