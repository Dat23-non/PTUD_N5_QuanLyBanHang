<?php
/** Router chỉ cho server kiểm thử, không đặt trong public/. */
declare(strict_types=1);
if (PHP_SAPI !== 'cli-server' || !preg_match('/\Aptud_st_test_[a-f0-9]{12}\z/', getenv('PTUD_ST_TEST_DB') ?: '')) {
    http_response_code(404);
    exit;
}
$root = dirname(__DIR__, 2);
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if (str_starts_with($path, '/assets/')) {
    return false;
}
require $root . '/app/Core/autoload.php';
require $root . '/app/Helpers/functions.php';
$config = require $root . '/config/database.php';
$pdo = new PDO(sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
    $config['host'], $config['port'], getenv('PTUD_ST_TEST_DB')), $config['username'], $config['password'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
     PDO::ATTR_EMULATE_PREPARES => false]);
(new ReflectionProperty(App\Core\Database::class, 'connection'))->setValue(null, $pdo);
session_save_path(getenv('PTUD_ST_TEST_SESSION_DIR'));
session_start();
date_default_timezone_set('Asia/Ho_Chi_Minh');
// Chỉ harness test được giả lập phiên đăng nhập, không đi vào code ứng dụng.
if (isset($_GET['test_actor'])) {
    if ($_GET['test_actor'] === 'none') {
        unset($_SESSION['tai_khoan_id']);
    } else {
        $_SESSION['tai_khoan_id'] = (int) $_GET['test_actor'];
    }
}
(new App\Core\Router())->dispatch(require $root . '/routes/web.php', 'dashboard');