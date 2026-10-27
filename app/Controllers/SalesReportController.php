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

        // Lấy nhân viên được chọn.
        $employeeValue = $_GET['employee_id'] ?? '';

        $employeeId = null;
        $error = null;
        $reports = [];
        $employees = [];

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
                $error = 'Mã nhân viên không hợp lệ.';
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

        // Lấy dữ liệu từ Model.
        $model = new SalesReport();

        $employees = $model->getEmployees();

        if ($error === null) {
            $reports = $model->getRevenueByEmployee(
                $fromDate,
                $toDate,
                $employeeId
            );
        }

        // Truyền dữ liệu sang giao diện.
        $this->view('owner/sales-report.php', [
            'pageTitle' => 'Báo cáo doanh số theo nhân viên',
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'employeeId' => $employeeId,
            'employees' => $employees,
            'reports' => $reports,
            'error' => $error,
        ]);
    }
}
