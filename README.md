# PTUD - He thong quan ly ban hang

Project hoc phan PTUD, su dung PHP thuần + MySQL/MariaDB (XAMPP) + HTML/CSS.

## Yeu cau
- Git
- XAMPP (Apache + MySQL)
- Trinh duyet web

## Cai dat lan dau
1. Clone repository ve `C:\xampp\htdocs\ptud-quan-ly-ban-hang` (hoac thu muc `htdocs` cua XAMPP).
2. Tao file cau hinh local bang cach copy `config/database.example.php` thanh `config/database.php`.
3. Mo XAMPP Control Panel va Start Apache, MySQL.
4. Trong phpMyAdmin, import `database/schema_v1_1.sql` de tao database `ptud_quan_ly_ban_hang`, sau do import `database/seed.sql` neu dung du lieu nen cua nhom.
5. Mo `http://localhost/ptud-quan-ly-ban-hang/public/`.

## Cau hinh database
`config/database.php` la file local va khong duoc commit. Xem `config/database.example.php` de biet dinh dang cau hinh. XAMPP mac dinh thuong dung user `root`, password rong tren may ca nhan; hay kiem tra cai dat tren may cua ban.

## Quy uoc Git
- `main`: ban on dinh.
- `develop`: tich hop code.
- `chore/project-skeleton`: xay dung nen tang ban dau.
- `feature/<ten-chuc-nang>`: moi Use Case lam tren branch rieng.
- Moi thay doi gui Pull Request vao `develop`; khong push truc tiep vao `main`.

## Trang thai
Du an dang duoc phat trien; mot so trang Use Case van la khung giao dien. Schema database phai duoc doi chieu va thong nhat theo Domain Model truoc khi phat trien tinh nang.

## Lap phieu chuyen kho
Da co luong lap phieu, luu nhap va xem chi tiet qua `index.php?page=stock-transfer`. Luu nhap khong cap nhat ton kho. Don gia tam nhap theo tung dong, cho nhom chot nguon gia. Xem [huong dan chay, che do thu local va kiem thu](docs/stock-transfer.md).

Danh muc san pham sua va NCC duoc khoi tao bang `database/seed_dairy_catalog.sql`; xem [thu tu import SQL, cap nhat du lieu va ban giao Git](docs/dairy-data.md).
