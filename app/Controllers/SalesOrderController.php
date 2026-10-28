<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\SalesOrder;
use DomainException;
use Throwable;

final class SalesOrderController extends Controller
{
    public function index(): void
    {
        $customers = [];
        $warehouses = [];
        $products = [];
        $selectedWarehouseId = null;
        $loadError = null;

        try {
            $model = new SalesOrder();
            $customers = $model->getActiveCustomers();
            $warehouses = $model->getActiveWarehouses();

            $requestedWarehouse = $_GET['warehouse_id'] ?? null;
            $requestedWarehouseId = is_scalar($requestedWarehouse)
                ? filter_var(
                    (string) $requestedWarehouse,
                    FILTER_VALIDATE_INT,
                    ['options' => ['min_range' => 1]]
                )
                : false;

            $activeWarehouseIds = array_map(
                static fn(array $warehouse): int =>
                    (int) $warehouse['kho_id'],
                $warehouses
            );

            if (
                $requestedWarehouseId !== false
                && $requestedWarehouseId !== null
                && $requestedWarehouseId > 0
                && in_array(
                    (int) $requestedWarehouseId,
                    $activeWarehouseIds,
                    true
                )
            ) {
                $selectedWarehouseId = (int) $requestedWarehouseId;
            } elseif ($warehouses !== []) {
                $selectedWarehouseId = (int) $warehouses[0]['kho_id'];
            }

            if ($selectedWarehouseId !== null) {
                $products = $model->getProductsWithStock(
                    $selectedWarehouseId
                );
            }
        } catch (Throwable $exception) {
            error_log(
                'SalesOrderController::index - '
                . $exception->getMessage()
            );

            $loadError =
                'Không thể tải dữ liệu lập đơn hàng. '
                . 'Vui lòng kiểm tra cấu hình database.';
        }

        $this->view('sales/create-order.php', [
            'pageTitle' => 'Lập đơn hàng bán',
            'customers' => $customers,
            'warehouses' => $warehouses,
            'products' => $products,
            'selectedWarehouseId' => $selectedWarehouseId,
            'loadError' => $loadError,
        ]);
    }

    public function store(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            $this->setFlash('error', 'Yêu cầu không hợp lệ.');
            $this->redirect('index.php?page=sales-order');
        }

        $submittedToken = $_POST['csrf_token'] ?? null;
        $sessionToken = $_SESSION['sales_order_csrf'] ?? null;

        if (
            !is_string($submittedToken)
            || !is_string($sessionToken)
            || $sessionToken === ''
            || !hash_equals($sessionToken, $submittedToken)
        ) {
            $this->setFlash(
                'error',
                'Phiên gửi biểu mẫu không hợp lệ hoặc đã hết hạn. '
                . 'Hãy tải lại trang và thử lại.'
            );

            $this->redirect('index.php?page=sales-order');
        }

        try {
            $accountId = $this->resolveAccountId();

            if ($accountId === null) {
                throw new DomainException(
                    'Chưa xác định được tài khoản lập đơn. '
                    . 'Hãy đăng nhập bằng tài khoản bán hàng; '
                    . 'khi kiểm thử local, hãy cấu hình '
                    . 'config/sales-order.local.php.'
                );
            }

            $customerId = $this->readPositiveInt(
                $_POST['customer_id'] ?? null,
                'Vui lòng chọn khách hàng.'
            );

            $warehouseId = $this->readPositiveInt(
                $_POST['warehouse_id'] ?? null,
                'Kho xuất không hợp lệ.'
            );

            $items = $_POST['items'] ?? null;
            $paymentMethod = $_POST['payment_method'] ?? null;
            $paymentValue = $_POST['payment_amount'] ?? 0;
            $receiptMethod = $_POST['receipt_method'] ?? 'TIEN_MAT';

            if (!is_array($items)) {
                throw new DomainException(
                    'Dữ liệu mặt hàng không hợp lệ.'
                );
            }

            if (!is_string($paymentMethod)) {
                throw new DomainException(
                    'Phương thức thanh toán không hợp lệ.'
                );
            }

            if (!is_string($receiptMethod)) {
                throw new DomainException(
                    'Hình thức nhận tiền không hợp lệ.'
                );
            }

            if (
                !is_scalar($paymentValue)
                || !is_numeric((string) $paymentValue)
            ) {
                throw new DomainException(
                    'Số tiền thanh toán không hợp lệ.'
                );
            }

            $model = new SalesOrder();

            $result = $model->createOrder(
                $accountId,
                $customerId,
                $warehouseId,
                $items,
                $paymentMethod,
                (float) $paymentValue,
                $receiptMethod
            );

            $message = 'Lập đơn hàng thành công: '
                . $result['order_no']
                . '. Tổng tiền '
                . number_format(
                    (float) $result['grand_total'],
                    0,
                    ',',
                    '.'
                )
                . ' đ.';

            if (!empty($result['receipt_no'])) {
                $message .= ' Khoản thu '
                    . $result['receipt_no']
                    . ' đang ở trạng thái chờ nộp/đối soát; '
                    . 'chưa gạch công nợ chính thức.';
            }

            $message .= ' Đơn hàng đang chờ duyệt; '
                . 'tồn kho chưa bị trừ.';

            unset($_SESSION['sales_order_csrf']);

            $this->setFlash('success', $message);
        } catch (DomainException $exception) {
            $this->setFlash('error', $exception->getMessage());
        } catch (Throwable $exception) {
            error_log(
                'SalesOrderController::store - '
                . $exception->getMessage()
            );

            $this->setFlash(
                'error',
                'Không thể lưu đơn hàng. Hãy kiểm tra log PHP/database.'
            );
        }

        $this->redirect('index.php?page=sales-order');
    }

    private function readPositiveInt(
        mixed $value,
        string $errorMessage
    ): int {
        if (!is_scalar($value)) {
            throw new DomainException($errorMessage);
        }

        $result = filter_var(
            (string) $value,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );

        if ($result === false) {
            throw new DomainException($errorMessage);
        }

        return (int) $result;
    }

    private function resolveAccountId(): ?int
    {
        $candidates = [
            $_SESSION['tai_khoan_id'] ?? null,
            $_SESSION['user']['tai_khoan_id'] ?? null,
            $_SESSION['account']['tai_khoan_id'] ?? null,
            $_SESSION['auth']['tai_khoan_id'] ?? null,
            $_SESSION['account_id'] ?? null,
        ];

        foreach ($candidates as $candidate) {
            if (is_scalar($candidate)) {
                $id = filter_var(
                    (string) $candidate,
                    FILTER_VALIDATE_INT,
                    ['options' => ['min_range' => 1]]
                );

                if ($id !== false) {
                    return (int) $id;
                }
            }
        }

        // Chỉ dùng cấu hình riêng khi chạy trên máy local.
        $host = strtolower((string) parse_url(
            'http://' . (string) ($_SERVER['HTTP_HOST'] ?? ''),
            PHP_URL_HOST
        ));

        if (!in_array($host, ['localhost', '127.0.0.1'], true)) {
            return null;
        }

        $localConfigPath =
            dirname(__DIR__, 2) . '/config/sales-order.local.php';

        if (!is_file($localConfigPath)) {
            return null;
        }

        $localConfig = require $localConfigPath;

        if (!is_array($localConfig)) {
            return null;
        }

        $candidate = $localConfig['tai_khoan_id'] ?? null;

        if (!is_scalar($candidate)) {
            return null;
        }

        $id = filter_var(
            (string) $candidate,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );

        return $id === false ? null : (int) $id;
    }

    private function setFlash(
        string $type,
        string $message
    ): void {
        $_SESSION['sales_order_flash'] = [
            'type' => $type,
            'message' => $message,
        ];
    }
}