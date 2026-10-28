<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\SalesReport;

final class SalesReportController extends Controller
{
    public function index(): void
{
    // Mặc định từ đầu tháng đến ngày hiện tại
    $tuNgay = $_GET['tu_ngay'] ?? date('Y-m-01');
    $denNgay = $_GET['den_ngay'] ?? date('Y-m-d');

    // Bộ lọc
    $maKH = trim($_GET['ma_kh'] ?? '');
    $tenKH = trim($_GET['ten_kh'] ?? '');
    $trangThai = $_GET['trang_thai'] ?? 'tat_ca';

    $reportModel = new SalesReport();

    $danhSachCongNo = $reportModel->getCustomerDebts(
        $tuNgay,
        $denNgay,
        $maKH,
        $tenKH,
        $trangThai
    );
    
if (isset($_GET['export']) && $_GET['export'] === 'csv')
{
    die('EXPORT OK');
}

    $this->view('accounting/sales-report.php', [
        'pageTitle' => 'Báo cáo công nợ khách hàng',

        'filters' => [
            'tu_ngay' => $tuNgay,
            'den_ngay' => $denNgay,
            'ma_kh' => $maKH,
            'ten_kh' => $tenKH,
            'trang_thai' => $trangThai
        ],

        'danhSachCongNo' => $danhSachCongNo,

        'tongNoDauKy' => array_sum(array_column($danhSachCongNo, 'no_dau_ky')),
        'tongPhatSinh' => array_sum(array_column($danhSachCongNo, 'phat_sinh')),
        'tongDaThu' => array_sum(array_column($danhSachCongNo, 'da_thu')),
        'tongNoCuoiKy' => array_sum(array_column($danhSachCongNo, 'no_cuoi_ky'))
    ]);
}


}
