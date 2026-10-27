<?php
declare(strict_types=1);

// Endpoint POST riêng cho UC-NVBH06.
// Không sửa routes/web.php.
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

date_default_timezone_set('Asia/Ho_Chi_Minh');

require_once dirname(__DIR__) . '/app/Core/autoload.php';
require_once dirname(__DIR__) . '/app/Helpers/functions.php';

$controller = new \App\Controllers\SalesOrderController();
$controller->store();