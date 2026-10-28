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

## Cách cập nhật trên máy hiện tại

Nếu đã chạy script sau bản cập nhật này và được thông báo thành công, chỉ cần tải lại trang. Không cần import lại SQL.

Cách thuận tiện cho máy mới hoặc khi cần đồng bộ danh mục:

```powershell
cd E:\xampp\htdocs\CK_PTUD\PTUD_N5_QuanLyBanHang
& E:\xampp\php\php.exe scripts/stock-transfer-demo.php --seed
& E:\xampp\php\php.exe scripts/stock-transfer-demo.php --check
```

Script tự chuẩn bị tài khoản/kho nếu chưa có, rồi chạy lần lượt **catalog → đổi nhãn → tồn đầu kỳ** trong cùng transaction. Không tạo thêm tài khoản/kho nếu bộ `ST_DEMO` đã tồn tại. Nếu lỗi sẽ rollback. Script không đặt lại tồn đang có, không ghi đè giá/tên của sản phẩm đã tồn tại và không xóa phiếu.

Nếu muốn import bằng giao diện phpMyAdmin và bộ tài khoản/kho local đã có:

1. Trên máy hiện tại mở `http://localhost:88/phpmyadmin/` (Apache cổng 88), chọn database `ptud_quan_ly_ban_hang`. Máy khác đổi cổng theo cấu hình.
2. Chọn **Import**, import `database/seed_dairy_catalog.sql`.
3. Import `database/update_stock_transfer_labels.sql` nếu máy có dữ liệu cũ.
4. Import `database/seed_stock_transfer_inventory.sql` để hàng xuất hiện trong tồn Kho tổng.
5. Tải lại `public/index.php?page=stock-transfer`, chọn tài khoản **Đỗ Văn Quang** nếu cần.

Chỉ thêm vào `hang_hoa` chưa đủ để hàng xuất hiện trên form: chức năng đọc hàng đã có dòng `ton_kho` tại Kho tổng. File tồn đầu kỳ chỉ nhắm tới hai kho local nói trên. Nếu tích hợp vào NPP/kho thật khác, đối chiếu `ma_npp`/`ma_kho` trong file và phối hợp người quản lý dữ liệu; không thay bằng ID đoán.

Tồn 100 là số liệu khởi tạo phục vụ đồ án; không thay thế chứng từ nhập hàng thực tế. Hai hàng cũ vẫn còn trong database với trạng thái ngừng kinh doanh để giữ liên kết phiếu đã lưu, nên không xuất hiện khi lập phiếu mới.

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

`config/database.php` chứa cấu hình máy; `stock-transfer.local.php` là bật/tắt chế độ local của từng người. Bản export/backup DB có thể chứa dữ liệu tài khoản: lưu dưới `database/backups/`, không commit. SQL riêng cho một máy có thể đặt tên `database/ten-file.local.sql`. Không thêm quy tắc `*.sql` vì schema và seed dùng chung phải được đưa lên Git. Hai file `*.example.php` phải được commit.

## Bàn giao trên nhánh feature/stock-transfer

Đọc diff, chạy test rồi stage các file cụ thể:

```powershell
& E:\xampp\php\php.exe tests/stock-transfer.php --browser
git status --short
git diff --check
git add .gitignore README.md docs/stock-transfer.md docs/dairy-data.md
git add app/Controllers/StockTransferController.php app/Models/StockTransfer.php app/Services/StockTransferService.php
git add app/Views/warehouse/stock-transfer.php public/assets/css/stock-transfer.css public/assets/js/stock-transfer.js
git add config/stock-transfer.example.php routes/web.php scripts/stock-transfer-demo.php
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
