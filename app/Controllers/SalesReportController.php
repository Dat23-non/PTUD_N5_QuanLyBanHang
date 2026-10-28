<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\SalesReport;
use DateTimeImmutable;

final class SalesReportController extends Controller
{
    public function index(): void
    {
        // Lấy khoảng thời gian báo cáo.
        $fromDate = $_GET['from_date'] ?? date('Y-m-01');
        $toDate = $_GET['to_date'] ?? date('Y-m-d');

        // Lấy nhân viên và khu vực được chọn.
        $employeeValue = $_GET['employee_id'] ?? '';
        $areaValue = $_GET['area_id'] ?? '';

        $employeeId = null;
        $areaId = null;
        $error = null;

        $reports = [];
        $employees = [];
        $areas = [];
        $invoiceDetails = [];

        // Xác thực mã nhân viên.
        if (!is_string($employeeValue)) {
            $error = 'Mã nhân viên không hợp lệ.';
        } elseif ($employeeValue !== '') {
            $employeeId = filter_var(
                $employeeValue,
                FILTER_VALIDATE_INT,
                ['options' => ['min_range' => 1]]
            );

            if ($employeeId === false) {
                $employeeId = null;
                $error = 'Mã nhân viên không hợp lệ.';
            }
        }

        // Xác thực mã khu vực.
        if (!is_string($areaValue)) {
            $error = 'Mã khu vực không hợp lệ.';
        } elseif ($areaValue !== '') {
            $areaId = filter_var(
                $areaValue,
                FILTER_VALIDATE_INT,
                ['options' => ['min_range' => 1]]
            );

            if ($areaId === false) {
                $areaId = null;
                $error = 'Mã khu vực không hợp lệ.';
            }
        }

        // Xác thực khoảng thời gian.
        $from = is_string($fromDate)
            ? DateTimeImmutable::createFromFormat('!Y-m-d', $fromDate)
            : false;

        $to = is_string($toDate)
            ? DateTimeImmutable::createFromFormat('!Y-m-d', $toDate)
            : false;

        if (
            !$from ||
            !$to ||
            $from->format('Y-m-d') !== $fromDate ||
            $to->format('Y-m-d') !== $toDate ||
            $from > $to
        ) {
            $error = 'Khoảng thời gian không hợp lệ.';
        }

        // Khởi tạo Model.
        $model = new SalesReport();

        // Lấy dữ liệu cho các bộ lọc.
        $employees = $model->getEmployees();
        $areas = $model->getAreas();

        // Chỉ truy vấn báo cáo khi dữ liệu đầu vào hợp lệ.
        if ($error === null) {
            $reports = $model->getRevenueByEmployee(
                $fromDate,
                $toDate,
                $employeeId,
                $areaId
            );

            $invoiceDetails = $model->getInvoiceDetails(
                $fromDate,
                $toDate,
                $employeeId,
                $areaId
            );
        }

        // Tính tổng doanh số và số hóa đơn.
        $totalRevenue = 0.0;
        $totalInvoices = 0;

        foreach ($reports as $report) {
            $totalRevenue += (float) $report['doanh_so'];
            $totalInvoices += (int) $report['so_hoa_don'];
        }

        // Đối chiếu doanh số với danh sách hóa đơn.
        $detailRevenue = 0.0;

        foreach ($invoiceDetails as $invoice) {
            $detailRevenue += (float) $invoice['tong_thanh_toan'];
        }

        $isReconciled = (
            round($totalRevenue, 2) === round($detailRevenue, 2)
            && $totalInvoices === count($invoiceDetails)
        );

        // Truyền dữ liệu sang View.
        $this->view('owner/sales-report.php', [
            'pageTitle' => 'Báo cáo doanh số theo nhân viên',
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'employeeId' => $employeeId,
            'areaId' => $areaId,
            'employees' => $employees,
            'areas' => $areas,
            'reports' => $reports,
            'invoiceDetails' => $invoiceDetails,
            'totalRevenue' => $totalRevenue,
            'totalInvoices' => $totalInvoices,
            'detailRevenue' => $detailRevenue,
            'isReconciled' => $isReconciled,
            'error' => $error,
        ]);
    }
}
