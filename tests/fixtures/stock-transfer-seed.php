<?php
/** Chạy script seed thật với PDO trỏ tới database test, không đổi config local. */
declare(strict_types=1);
if (PHP_SAPI !== 'cli' || !preg_match('/\Aptud_st_test_[a-f0-9]{12}\z/', getenv('PTUD_ST_TEST_DB') ?: '')) {
    http_response_code(404);
    exit(1);
}
$root = dirname(__DIR__, 2);
require $root . '/app/Core/autoload.php';
$config = require $root . '/config/database.php';
$pdo = new PDO(sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
    $config['host'], $config['port'], getenv('PTUD_ST_TEST_DB')), $config['username'], $config['password'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false]);
(new ReflectionProperty(App\Core\Database::class, 'connection'))->setValue(null, $pdo);
require $root . '/scripts/stock-transfer-demo.php';
