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