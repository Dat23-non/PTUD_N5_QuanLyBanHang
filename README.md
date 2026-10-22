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
4. Trong phpMyAdmin, tao database `ptud_quan_ly_ban_hang` va import `database/schema.sql`.
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
Cac trang Use Case hien la khung giao dien, chua phai nghiep vu hoan chinh. Schema database phai duoc doi chieu va thong nhat theo Domain Model truoc khi phat trien tinh nang.