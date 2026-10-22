# Scaffold for PTUD - Quan ly ban hang (PHP thuần + XAMPP)
# Chay script khi Terminal dang o thu muc goc ptud-quan-ly-ban-hang.
# LUU Y: ban nay se TAO LAI / GHI DE cac file bo khung do script quan ly.
# Khong ghi de config/database.php, vi file nay khong duoc tao boi script.

$ErrorActionPreference = 'Stop'
$ProjectRoot = (Get-Location).Path

function New-ProjectFile {
    param(
        [Parameter(Mandatory = $true)][string]$RelativePath,
        [Parameter(Mandatory = $true)][string]$Content
    )
    $fullPath = Join-Path $ProjectRoot $RelativePath
    $parent = Split-Path -Parent $fullPath
    if (-not (Test-Path $parent)) {
        New-Item -ItemType Directory -Path $parent -Force | Out-Null
    }
    if ($true) {
        $encoding = New-Object System.Text.UTF8Encoding($false)
        [System.IO.File]::WriteAllText($fullPath, $Content, $encoding)
        Write-Host "Created/Updated: $RelativePath" -ForegroundColor Green
    } else {
        Write-Host "Skipped (already exists): $RelativePath" -ForegroundColor Yellow
    }
}

# Directory structure
$directories = @(
    'app/Core',
    'app/Helpers',
    'app/Controllers',
    'app/Models',
    'app/Services',
    'app/Views/layouts',
    'app/Views/auth',
    'app/Views/dashboard',
    'app/Views/sales',
    'app/Views/accounting',
    'app/Views/warehouse',
    'app/Views/owner',
    'app/Views/admin',
    'config',
    'database',
    'docs',
    'public/assets/css',
    'public/assets/js',
    'public/assets/images',
    'routes',
    'storage/logs',
    'tests'
)
foreach ($dir in $directories) {
    New-Item -ItemType Directory -Path (Join-Path $ProjectRoot $dir) -Force | Out-Null
}

New-ProjectFile '.gitignore' @'
# Local configuration and secrets
/config/database.php
.env
.env.*
!.env.example

# Runtime files
/storage/logs/*
!/storage/logs/.gitkeep

# Editor and OS files
.vscode/
.idea/
.DS_Store
Thumbs.db
*.log
'@

New-ProjectFile 'README.md' @'
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
'@

New-ProjectFile 'config/database.example.php' @'
<?php
/**
 * Mau cau hinh. Moi thanh vien copy file nay thanh database.php va sua theo may cua minh.
 * Khong commit config/database.php len GitHub.
 */
return [
    'host' => '127.0.0.1',
    'port' => '3306',
    'database' => 'ptud_quan_ly_ban_hang',
    'username' => 'root',
    'password' => '',
    'charset' => 'utf8mb4',
];
'@

New-ProjectFile 'config/app.php' @'
<?php
return [
    'name' => 'Hệ thống quản lý bán hàng',
    'timezone' => 'Asia/Ho_Chi_Minh',
    'debug' => true, // Chi dung true trong moi truong local khi phat trien.
];
'@

New-ProjectFile 'app/Core/Database.php' @'
<?php

declare(strict_types=1);

final class Database
{
    private static ?PDO $connection = null;

    public static function connection(): PDO
    {
        if (self::$connection instanceof PDO) {
            return self::$connection;
        }

        $configFile = dirname(__DIR__, 2) . '/config/database.php';
        if (!is_file($configFile)) {
            throw new RuntimeException(
                'Chua co config/database.php. Hay copy config/database.example.php thanh config/database.php va cau hinh database.'
            );
        }

        $config = require $configFile;
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            $config['host'],
            $config['port'],
            $config['database'],
            $config['charset']
        );

        self::$connection = new PDO($dsn, $config['username'], $config['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);

        return self::$connection;
    }
}
'@

New-ProjectFile 'app/Helpers/functions.php' @'
<?php

if (!function_exists('e')) {
    /** Escape text before rendering it into HTML. */
    function e(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
'@

New-ProjectFile 'routes/web.php' @'
<?php
/**
 * Danh sach trang cho router mau. Chua phai phan quyen bao mat.
 * Khi them trang moi, khai bao ten trang va view duoc phep o day.
 */
return [
    'dashboard' => [
        'title' => 'Tổng quan',
        'view' => 'dashboard/index.php',
    ],
    'login' => [
        'title' => 'Đăng nhập',
        'view' => 'auth/login.php',
    ],
    'sales-order' => [
        'title' => 'Lập đơn hàng bán',
        'view' => 'sales/create-order.php',
    ],
    'payment-reconciliation' => [
        'title' => 'Phiếu thu và đối soát công nợ',
        'view' => 'accounting/payment-reconciliation.php',
    ],
    'stock-transfer' => [
        'title' => 'Lập phiếu chuyển kho',
        'view' => 'warehouse/stock-transfer.php',
    ],
    'sales-report' => [
        'title' => 'Báo cáo doanh số theo nhân viên',
        'view' => 'owner/sales-report.php',
    ],
    'role-permission' => [
        'title' => 'Phân quyền tài khoản',
        'view' => 'admin/role-permission.php',
    ],
];
'@

New-ProjectFile 'public/index.php' @'
<?php

declare(strict_types=1);

session_start();

date_default_timezone_set('Asia/Ho_Chi_Minh');

require_once dirname(__DIR__) . '/app/Helpers/functions.php';

$routes = require dirname(__DIR__) . '/routes/web.php';
$page = (string) ($_GET['page'] ?? 'dashboard');

if (!isset($routes[$page])) {
    http_response_code(404);
    $pageTitle = 'Không tìm thấy trang';
    $viewFile = null;
} else {
    $pageTitle = $routes[$page]['title'];
    $viewFile = dirname(__DIR__) . '/app/Views/' . $routes[$page]['view'];
}

require dirname(__DIR__) . '/app/Views/layouts/header.php';
require dirname(__DIR__) . '/app/Views/layouts/sidebar.php';
?>
<main class="main-content">
    <?php if ($viewFile !== null && is_file($viewFile)): ?>
        <?php require $viewFile; ?>
    <?php else: ?>
        <section class="page">
            <h1>Không tìm thấy trang</h1>
            <p>Đường dẫn này chưa được khai báo trong routes/web.php.</p>
        </section>
    <?php endif; ?>
</main>
<?php require dirname(__DIR__) . '/app/Views/layouts/footer.php'; ?>
'@

New-ProjectFile 'app/Views/layouts/header.php' @'
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle ?? 'Hệ thống quản lý bán hàng') ?></title>
    <link rel="stylesheet" href="assets/css/variables.css">
    <link rel="stylesheet" href="assets/css/base.css">
    <link rel="stylesheet" href="assets/css/layout.css">
    <link rel="stylesheet" href="assets/css/components.css">
</head>
<body>
<div class="app-shell">
'@

New-ProjectFile 'app/Views/layouts/sidebar.php' @'
<aside class="sidebar">
    <a class="brand" href="index.php?page=dashboard">PTUD · Quản lý bán hàng</a>
    <div class="nav-section-label">TỔNG QUAN</div>
    <nav class="sidebar-nav" aria-label="Điều hướng chính">
        <a href="index.php?page=dashboard">Tổng quan</a>
        <a href="index.php?page=sales-order">Lập đơn hàng bán</a>
        <a href="index.php?page=payment-reconciliation">Phiếu thu / đối soát</a>
        <a href="index.php?page=stock-transfer">Chuyển kho</a>
        <a href="index.php?page=sales-report">Báo cáo doanh số</a>
        <a href="index.php?page=role-permission">Phân quyền tài khoản</a>
        <a href="index.php?page=login">Đăng nhập (mẫu)</a>
    </nav>
    <div class="sidebar-note">Bản khung phát triển · Chức năng đang được xây dựng</div>
</aside>
'@

New-ProjectFile 'app/Views/layouts/footer.php' @'
<footer class="app-footer">Đồ án PTUD · Hệ thống quản lý bán hàng</footer>
</div>
<script src="assets/js/app.js"></script>
</body>
</html>
'@

New-ProjectFile 'app/Views/dashboard/index.php' @'
<section class="page-header">
    <div>
        <p class="eyebrow">WORKSPACE</p>
        <h1>Tổng quan hệ thống</h1>
        <p class="page-description">Bộ khung ban đầu để nhóm phát triển và tích hợp các Use Case.</p>
    </div>
    <span class="status-pill">Đang phát triển</span>
</section>

<section class="stats-grid">
    <article class="stat-card"><span class="stat-label">Use Case trọng tâm</span><strong>05</strong><span class="stat-help">Theo kế hoạch đề xuất</span></article>
    <article class="stat-card"><span class="stat-label">Vai trò nghiệp vụ</span><strong>05</strong><span class="stat-help">NVBH, Kế toán, Thủ kho, Chủ NPP, Quản trị</span></article>
    <article class="stat-card"><span class="stat-label">Môi trường</span><strong>PHP</strong><span class="stat-help">XAMPP + MySQL/MariaDB</span></article>
</section>

<section class="panel">
    <div class="panel-heading"><h2>Use Case được đề xuất triển khai</h2><span class="muted">Chưa thay cho xác nhận chính thức của nhóm</span></div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Actor</th><th>Use Case</th><th>Trạng thái</th></tr></thead>
            <tbody>
                <tr><td>Nhân viên bán hàng</td><td>Lập đơn hàng bán</td><td><span class="tag">Chưa triển khai</span></td></tr>
                <tr><td>Kế toán</td><td>Lập phiếu thu và đối soát công nợ</td><td><span class="tag">Chưa triển khai</span></td></tr>
                <tr><td>Thủ kho</td><td>Lập phiếu chuyển kho</td><td><span class="tag">Chưa triển khai</span></td></tr>
                <tr><td>Chủ nhà phân phối</td><td>Báo cáo doanh số theo nhân viên</td><td><span class="tag">Chưa triển khai</span></td></tr>
                <tr><td>Quản trị hệ thống</td><td>Phân quyền tài khoản</td><td><span class="tag">Chưa triển khai</span></td></tr>
            </tbody>
        </table>
    </div>
</section>
'@

New-ProjectFile 'app/Views/auth/login.php' @'
<section class="page-header"><div><p class="eyebrow">ACCESS</p><h1>Đăng nhập</h1><p class="page-description">Màn hình giữ chỗ. Chưa xử lý xác thực trong bản khung này.</p></div></section>
<section class="panel form-panel">
    <div class="notice notice-info">Chức năng đăng nhập và kiểm tra vai trò sẽ được triển khai trước khi các Use Case nghiệp vụ được tích hợp.</div>
    <form>
        <label for="username">Tên đăng nhập</label><input id="username" name="username" type="text" placeholder="Nhập tên đăng nhập" disabled>
        <label for="password">Mật khẩu</label><input id="password" name="password" type="password" placeholder="Nhập mật khẩu" disabled>
        <button class="button button-primary" type="button" disabled>Đăng nhập (chưa hoạt động)</button>
    </form>
</section>
'@

New-ProjectFile 'app/Views/sales/create-order.php' @'
<section class="page-header"><div><p class="eyebrow">SALES</p><h1>Lập đơn hàng bán</h1><p class="page-description">Use Case của Nhân viên bán hàng.</p></div></section>
<section class="panel"><div class="notice notice-info">Đây là vùng giao diện ban đầu. Trước khi lập trình cần thống nhất các bảng khách hàng, sản phẩm, kho, đơn hàng, chi tiết đơn và quy tắc công nợ/tồn kho.</div><h2>Các bước dự kiến</h2><ol><li>Chọn khách hàng và kiểm tra tình trạng công nợ.</li><li>Chọn sản phẩm, số lượng và chiết khấu nếu được phép.</li><li>Kiểm tra tồn kho và phương thức thanh toán.</li><li>Tính tổng tiền, kiểm tra quy tắc bán nợ.</li><li>Xác nhận và lưu đơn hàng theo giao dịch nhất quán.</li></ol></section>
'@

New-ProjectFile 'app/Views/accounting/payment-reconciliation.php' @'
<section class="page-header"><div><p class="eyebrow">ACCOUNTING</p><h1>Phiếu thu và đối soát công nợ</h1><p class="page-description">Use Case của Kế toán.</p></div></section>
<section class="panel"><div class="notice notice-info">Đây là vùng giao diện ban đầu. Cần tránh ghi giảm công nợ hai lần đối với dòng tiền khách hàng nộp qua Nhân viên bán hàng.</div><h2>Các bước dự kiến</h2><ol><li>Chọn khách hàng và nguồn thu.</li><li>Đối chiếu số tiền thực thu, phiếu nộp tiền của nhân viên nếu có.</li><li>Phân bổ tiền vào khoản nợ theo quy tắc đã thống nhất.</li><li>Xử lý thanh toán thiếu/thừa.</li><li>Lưu phiếu thu và cập nhật công nợ trong một transaction.</li></ol></section>
'@

New-ProjectFile 'app/Views/warehouse/stock-transfer.php' @'
<section class="page-header"><div><p class="eyebrow">WAREHOUSE</p><h1>Lập phiếu chuyển kho</h1><p class="page-description">Use Case của Thủ kho.</p></div></section>
<section class="panel"><div class="notice notice-info">Đây là vùng giao diện ban đầu. Cần xác định kho nguồn, kho đích, sản phẩm, số lượng, trạng thái phiếu và thời điểm cập nhật tồn.</div><h2>Các bước dự kiến</h2><ol><li>Chọn kho nguồn và kho ký gửi/NVBH đích.</li><li>Thêm sản phẩm và số lượng chuyển.</li><li>Kiểm tra tồn khả dụng tại kho nguồn.</li><li>Xác nhận phiếu chuyển.</li><li>Cập nhật giảm kho nguồn và tăng kho đích nhất quán.</li></ol></section>
'@

New-ProjectFile 'app/Views/owner/sales-report.php' @'
<section class="page-header"><div><p class="eyebrow">MANAGEMENT</p><h1>Báo cáo doanh số theo nhân viên</h1><p class="page-description">Use Case của Chủ nhà phân phối.</p></div></section>
<section class="panel"><div class="notice notice-info">Đây là vùng giao diện ban đầu. Dữ liệu báo cáo sẽ lấy từ nguồn chứng từ được nhóm xác nhận; không tạo số liệu giả để thay cho dữ liệu thật.</div><form class="filter-form"><div><label for="from-date">Từ ngày</label><input id="from-date" type="date" disabled></div><div><label for="to-date">Đến ngày</label><input id="to-date" type="date" disabled></div><button class="button button-primary" type="button" disabled>Xem báo cáo (chưa hoạt động)</button></form><div class="empty-state">Chưa có dữ liệu báo cáo. Chức năng sẽ được triển khai sau khi thống nhất database.</div></section>
'@

New-ProjectFile 'app/Views/admin/role-permission.php' @'
<section class="page-header"><div><p class="eyebrow">ADMINISTRATION</p><h1>Phân quyền tài khoản</h1><p class="page-description">Use Case của Quản trị hệ thống.</p></div></section>
<section class="panel"><div class="notice notice-info">Đây là vùng giao diện ban đầu. Phân quyền thật cần được kiểm tra ở phía PHP, không chỉ ẩn/hiện menu.</div><h2>Các bước dự kiến</h2><ol><li>Chọn tài khoản tồn tại trong hệ thống.</li><li>Hiển thị nhóm quyền hiện tại.</li><li>Chọn nhóm quyền được khai báo hợp lệ.</li><li>Xác nhận và lưu thay đổi.</li><li>Ghi nhật ký hoạt động theo quy tắc dự án.</li></ol></section>
'@

New-ProjectFile 'public/assets/css/variables.css' @'
:root {
    --color-primary: #3157d5;
    --color-primary-dark: #2444b2;
    --color-sidebar: #172033;
    --color-background: #f4f6fb;
    --color-surface: #ffffff;
    --color-text: #1f2937;
    --color-muted: #6b7280;
    --color-border: #e5e7eb;
    --color-info-bg: #eef4ff;
    --color-info-text: #2444a5;
    --radius-card: 14px;
    --shadow-card: 0 5px 20px rgb(15 23 42 / 5%);
}
'@

New-ProjectFile 'public/assets/css/base.css' @'
* { box-sizing: border-box; }
html { font-size: 16px; }
body { margin: 0; background: var(--color-background); color: var(--color-text); font-family: Arial, Helvetica, sans-serif; line-height: 1.5; }
a { color: inherit; text-decoration: none; }
button, input, select, textarea { font: inherit; }
h1, h2, p { margin-top: 0; }
h1 { margin-bottom: 8px; font-size: clamp(1.6rem, 3vw, 2rem); letter-spacing: -.03em; }
h2 { margin-bottom: 14px; font-size: 1.08rem; }
'@

New-ProjectFile 'public/assets/css/layout.css' @'
.app-shell { display: flex; min-height: 100vh; }
.sidebar { display: flex; flex-direction: column; width: 258px; flex: 0 0 258px; min-height: 100vh; padding: 24px 16px; background: var(--color-sidebar); color: #f8fafc; }
.brand { display: block; margin: 0 8px 34px; font-size: 1.08rem; font-weight: 700; line-height: 1.4; }
.nav-section-label { margin: 0 10px 10px; color: #a8b3c7; font-size: .7rem; font-weight: 700; letter-spacing: .12em; }
.sidebar-nav { display: grid; gap: 6px; }
.sidebar-nav a { padding: 11px 12px; border-radius: 9px; color: #d7deeb; font-size: .92rem; transition: background .15s ease; }
.sidebar-nav a:hover, .sidebar-nav a:focus-visible { background: #2b3850; color: #fff; outline: none; }
.sidebar-note { margin: auto 8px 0; padding-top: 26px; color: #9ca8bc; font-size: .78rem; }
.main-content { flex: 1; min-width: 0; padding: 32px; }
.app-footer { padding: 22px 32px; color: var(--color-muted); font-size: .8rem; }
@media (max-width: 800px) { .app-shell { flex-direction: column; } .sidebar { width: 100%; min-height: auto; flex-basis: auto; } .sidebar-nav { grid-template-columns: repeat(2, minmax(0, 1fr)); } .sidebar-note { margin-top: 20px; } .main-content { padding: 22px 16px; } .app-footer { padding: 18px 16px; } }
@media (max-width: 460px) { .sidebar-nav { grid-template-columns: 1fr; } }
'@

New-ProjectFile 'public/assets/css/components.css' @'
.page-header { display: flex; align-items: flex-start; justify-content: space-between; gap: 20px; margin-bottom: 26px; }
.eyebrow { margin-bottom: 8px; color: var(--color-primary); font-size: .72rem; font-weight: 700; letter-spacing: .14em; }
.page-description, .muted { color: var(--color-muted); }
.page-description { margin-bottom: 0; }
.status-pill, .tag { display: inline-flex; align-items: center; padding: 5px 9px; border-radius: 999px; background: #eef2ff; color: #3549a9; font-size: .75rem; font-weight: 700; white-space: nowrap; }
.stats-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 16px; margin-bottom: 22px; }
.stat-card, .panel { border: 1px solid var(--color-border); border-radius: var(--radius-card); background: var(--color-surface); box-shadow: var(--shadow-card); }
.stat-card { display: grid; gap: 8px; padding: 20px; }
.stat-label, .stat-help { color: var(--color-muted); font-size: .82rem; }
.stat-card strong { font-size: 1.8rem; }
.panel { padding: 22px; margin-bottom: 22px; }
.panel-heading { display: flex; align-items: baseline; justify-content: space-between; gap: 12px; margin-bottom: 14px; }
.panel-heading h2 { margin-bottom: 0; }
.table-wrap { overflow-x: auto; }
table { width: 100%; border-collapse: collapse; font-size: .9rem; }
th, td { padding: 13px 12px; border-bottom: 1px solid var(--color-border); text-align: left; }
th { color: var(--color-muted); font-size: .76rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; }
tbody tr:last-child td { border-bottom: 0; }
.notice { margin-bottom: 18px; padding: 13px 15px; border-radius: 9px; font-size: .9rem; }
.notice-info { background: var(--color-info-bg); color: var(--color-info-text); }
.form-panel { max-width: 540px; }
form { display: grid; gap: 10px; }
label { margin-top: 5px; font-size: .88rem; font-weight: 700; }
input, select, textarea { width: 100%; min-height: 42px; padding: 10px 12px; border: 1px solid #cfd5df; border-radius: 8px; background: #fff; color: var(--color-text); }
input:disabled, button:disabled { cursor: not-allowed; opacity: .62; }
.button { display: inline-flex; justify-content: center; align-items: center; width: fit-content; min-height: 40px; padding: 9px 15px; border: 0; border-radius: 8px; cursor: pointer; font-weight: 700; }
.button-primary { background: var(--color-primary); color: #fff; }
.button-primary:hover:not(:disabled) { background: var(--color-primary-dark); }
.filter-form { display: flex; align-items: end; flex-wrap: wrap; gap: 14px; }
.filter-form > div { min-width: 180px; }
.empty-state { margin-top: 22px; padding: 36px 16px; border: 1px dashed #cbd5e1; border-radius: 10px; color: var(--color-muted); text-align: center; }
li { margin-bottom: 7px; }
@media (max-width: 800px) { .stats-grid { grid-template-columns: 1fr; } .page-header, .panel-heading { flex-direction: column; } .panel { padding: 16px; } }
'@

New-ProjectFile 'public/assets/js/app.js' @'
// Chi dat cac tuong tac giao dien dung chung tai day.
// Kiem tra quyen va xu ly nghiep vu phai duoc thuc hien o PHP, khong chi o JavaScript.
console.info('PTUD project scaffold loaded.');
'@

New-ProjectFile 'database/schema.sql' @'
-- Khung schema ban dau; CHUA tao cac bang nghiep vu.
-- Truoc khi viet bang, nhom phai doi chieu Domain Model va thong nhat:
-- 1. Tai khoan, nhom quyen va cac quan he.
-- 2. Khach hang, nhan vien, san pham, kho tong/kho ky gui.
-- 3. Don hang va chi tiet don hang.
-- 4. Phieu chuyen kho va chi tiet.
-- 5. Phieu thu, phan bo tien thu va cong no.
-- 6. Nguon du lieu va dinh nghia doanh so cho bao cao.
-- Database: ptud_quan_ly_ban_hang
CREATE DATABASE IF NOT EXISTS ptud_quan_ly_ban_hang
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ptud_quan_ly_ban_hang;
'@

New-ProjectFile 'database/seed.sql' @'
-- Du lieu mau se duoc bo sung sau khi schema duoc thong nhat.
'@

New-ProjectFile 'docs/selected-use-cases.md' @'
# Use Case trong tam (de xuat, can nhom xac nhan)

1. Nhan vien ban hang: Lap don hang ban.
2. Ke toan: Lap phieu thu va doi soat cong no.
3. Thu kho: Lap phieu chuyen kho.
4. Chu nha phan phoi: Xem bao cao doanh so theo nhan vien.
5. Quan tri he thong: Phan quyen tai khoan.

Phai doi chieu lai voi yeu cau cua giang vien truoc khi chot. Moi Use Case can co:
- Tien dieu kien/hau dieu kien.
- Basic flow, alternative flow va exception flow.
- Du lieu dau vao/dau ra.
- Quy tac nghiep vu va cac bang du lieu lien quan.
- Test case thanh cong, du lieu khong hop le va loi he thong.
'@

New-ProjectFile 'docs/coding-conventions.md' @'
# Quy uoc code

- Dung PHP thuần; cac file PHP/HTML luu UTF-8 khong BOM.
- Ten class dung PascalCase; ten bien va ham dung camelCase.
- Dung PDO prepared statements cho truy van co du lieu dau vao.
- Escape du lieu hien thi ra HTML bang helper e().
- Khong ghep SQL truc tiep tu chuoi nhap cua nguoi dung.
- Khong commit config/database.php, mat khau, token hoac du lieu nhay cam.
- CSS dung chung chi do nguoi phu trach giao dien sua; CSS module dung prefix ro rang.
- Moi chuc nang lam tren branch rieng va tao Pull Request vao develop.
- Khong sua schema.sql rieng le khi chua thong nhat voi nguoi quan ly database.
- Cac nghiep vu cap nhat nhieu bang can dung transaction va co xu ly rollback.
'@

New-ProjectFile 'docs/db-design.md' @'
# Thiet ke database

Trang thai: CHUA CHOT.

Can doi chieu Domain Model trong bao cao voi cac Use Case duoc chon truoc khi tao bang va khoa ngoai. Khong tu suy dien schema cuoi cung tu cac ten Use Case.
'@

New-ProjectFile 'storage/logs/.gitkeep' ' '
New-ProjectFile 'tests/.gitkeep' ' '
New-ProjectFile 'public/assets/images/.gitkeep' ' '
New-ProjectFile 'app/Controllers/.gitkeep' ' '
New-ProjectFile 'app/Models/.gitkeep' ' '
New-ProjectFile 'app/Services/.gitkeep' ' '

Write-Host ''
Write-Host 'Hoan tat tao bo khung. File da ton tai duoc giu nguyen, khong bi ghi de.' -ForegroundColor Cyan
Write-Host 'Buoc tiep theo:' -ForegroundColor Cyan
Write-Host '1. Copy config/database.example.php thanh config/database.php.'
Write-Host '2. Bat Apache + MySQL trong XAMPP.'
Write-Host '3. Import database/schema.sql bang phpMyAdmin.'
Write-Host '4. Mo http://localhost/ptud-quan-ly-ban-hang/public/.'
Write-Host '5. Kiem tra git status, sau do commit/push branch cua ban.'
