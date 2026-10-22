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