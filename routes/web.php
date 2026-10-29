<?php

declare(strict_types=1);

/**
 * Danh sach trang cua he thong.
 * Router MVC se xu ly cac route co controller va action.
 * Cac route chua chuyen doi van giu view de bao toan giao dien hien tai.
 */
return [
    'dashboard' => [
        'title' => 'Tổng quan',
        'controller' => App\Controllers\DashboardController::class,
        'action' => 'index',
        'view' => 'dashboard/index.php',
    ],

    'login' => [
        'title' => 'Đăng nhập',
        'controller' => App\Controllers\AuthController::class,
        'action' => 'index',
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
        'controller' => App\Controllers\StockTransferController::class,
        'action' => 'index',
    ],

    'sales-report' => [
        'title' => 'Báo cáo doanh số theo nhân viên',
        'controller' => App\Controllers\SalesReportController::class,
        'action' => 'index',
        'view' => 'owner/sales-report.php',
    ],

    'role-permission' => [
        'title' => 'Phân quyền tài khoản',
        'view' => 'admin/role-permission.php',
    ],
];
