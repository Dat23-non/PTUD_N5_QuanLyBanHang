<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Models\StockTransfer;
use App\Services\StockTransferService;
use DomainException;
use Throwable;

final class StockTransferController extends Controller
{
    public function index(): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        if (!in_array($method, ['GET', 'POST'], true)) {
            header('Allow: GET, POST');
            http_response_code(405);
            return;
        }
        unset($_SESSION['stock_transfer_demo_actor_id']);
        $_SESSION['stock_transfer_csrf'] ??= bin2hex(random_bytes(32));
        $data = [
            'pageTitle' => 'Lập phiếu chuyển kho', 'actor' => null, 'source' => null,
            'destinations' => [], 'products' => [], 'transfers' => [], 'statusFilter' => '',
            'error' => null, 'transfer' => null,
            'csrf' => $_SESSION['stock_transfer_csrf'], 'submissionToken' => '',
            'form' => ['destination_id' => '', 'transfer_date' => date('Y-m-d'), 'note' => '',
                'lines' => [['product_id' => '', 'quantity' => '', 'unit_price' => '']]],
        ];
        if ($method === 'POST') {
            $data['form'] = $this->formInput($_POST);
            $csrf = $_POST['csrf_token'] ?? null;
            if (!is_string($csrf) || !hash_equals($data['csrf'], $csrf)) {
                http_response_code(403);
                $data['error'] = 'Phiên biểu mẫu không hợp lệ. Hãy tải lại trang và thử lại.';
                $this->view('warehouse/stock-transfer.php', $data);
                return;
            }
        }

        try {
            $db = Database::connection();
            $model = new StockTransfer($db);
            $sharedId = filter_var($_SESSION['tai_khoan_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            $actorId = $sharedId;
            $actorId = filter_var($actorId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            $data['actor'] = $actorId ? $model->actor($actorId) : null;
            if (!$data['actor']) {
                http_response_code($actorId ? 403 : 401);
                $data['error'] = $actorId
                    ? 'Tài khoản không có quyền thủ kho hoặc không còn hoạt động.'
                    : 'Bạn cần đăng nhập bằng tài khoản thủ kho để lập phiếu chuyển kho.';
                $this->view('warehouse/stock-transfer.php', $data);
                return;
            }

            $distributorId = (int) $data['actor']['nha_phan_phoi_id'];
            $status = $_GET['status'] ?? '';
            if (!is_string($status) || !in_array($status, ['', 'NHAP', 'DA_XAC_NHAN', 'HUY'], true)) {
                throw new DomainException('Trạng thái lọc không hợp lệ.');
            }
            $data['statusFilter'] = $status;
            $data['transfers'] = $model->listing($distributorId, $status);
            $sources = $model->sourceWarehouses($distributorId);
            $data['source'] = count($sources) === 1 ? $sources[0] : null;
            $data['destinations'] = $model->destinations($distributorId);
            $data['products'] = $data['source'] ? $model->products((int) $data['source']['kho_id']) : [];

            if ($method === 'POST') {
                if (($_POST['operation'] ?? '') !== 'save-draft') {
                    throw new DomainException('Thao tác không hợp lệ.');
                }
                $token = $_POST['submission_token'] ?? null;
                $submission = is_string($token) ? ($_SESSION['stock_transfer_submissions'][$token] ?? null) : null;
                if (!$submission || $submission['actor_id'] !== $actorId) {
                    throw new DomainException('Biểu mẫu đã hết hạn. Vui lòng kiểm tra lại và lưu bằng biểu mẫu hiện tại.');
                }
                $data['submissionToken'] = $token;
                if ($submission['id'] !== null) {
                    $this->go('index.php?page=stock-transfer&id=' . $submission['id']);
                }
                try {
                    $id = (new StockTransferService($db))->createDraft($actorId, $data['form']);
                    $_SESSION['stock_transfer_submissions'][$token]['id'] = $id;
                    $_SESSION['stock_transfer_saved_id'] = $id;
                    $this->go('index.php?page=stock-transfer&id=' . $id);
                } catch (DomainException $e) {
                    http_response_code(422);
                    $data['error'] = $e->getMessage();
                } catch (Throwable $e) {
                    http_response_code(503);
                    $data['error'] = 'Không thể lưu phiếu chuyển kho. Dữ liệu đã nhập được giữ lại; vui lòng thử lưu lại.';
                    error_log('StockTransfer save failed: ' . $e->getMessage());
                }
                // Tải lại tồn để người dùng có thể điều chỉnh sau lỗi tồn kho thay đổi.
                $data['products'] = $data['source'] ? $model->products((int) $data['source']['kho_id']) : [];
            } elseif (isset($_GET['id'])) {
                $id = filter_var($_GET['id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
                $data['transfer'] = $id ? $model->find($id, $distributorId) : null;
                if (!$data['transfer']) {
                    http_response_code(404);
                    $data['error'] = 'Không tìm thấy phiếu chuyển kho trong phạm vi nhà phân phối của bạn.';
                }
                $data['saved'] = $id && ($_SESSION['stock_transfer_saved_id'] ?? null) === $id;
                unset($_SESSION['stock_transfer_saved_id']);
            }
            if (!$data['source'] && $data['transfer'] === null) {
                $data['error'] ??= 'Nhà phân phối phải có đúng một Kho tổng đang hoạt động để lập phiếu.';
            }
        } catch (DomainException $e) {
            http_response_code(422);
            $data['error'] = $e->getMessage();
        } catch (Throwable $e) {
            http_response_code(503);
            $data['error'] = 'Không thể tải dữ liệu chuyển kho. Vui lòng kiểm tra kết nối và cấu trúc cơ sở dữ liệu.';
            error_log('StockTransfer load failed: ' . get_class($e) . ' (' . $e->getCode() . ')');
        }

        if ($data['actor'] && $data['submissionToken'] === '' && $data['transfer'] === null) {
            $_SESSION['stock_transfer_submissions'] ??= [];
            if (count($_SESSION['stock_transfer_submissions']) >= 20) {
                array_shift($_SESSION['stock_transfer_submissions']);
            }
            $token = bin2hex(random_bytes(24));
            $_SESSION['stock_transfer_submissions'][$token] = ['actor_id' => (int) $data['actor']['tai_khoan_id'], 'id' => null];
            $data['submissionToken'] = $token;
        }
        $this->view('warehouse/stock-transfer.php', $data);
    }

    private function formInput(array $input): array
    {
        $form = [];
        foreach (['destination_id', 'transfer_date', 'note'] as $key) {
            $form[$key] = is_string($input[$key] ?? null) ? $input[$key] : '';
        }
        $form['lines'] = [];
        if (is_array($input['lines'] ?? null)) {
            foreach (array_slice($input['lines'], 0, 101) as $row) {
                $line = [];
                foreach (['product_id', 'quantity', 'unit_price'] as $key) {
                    $line[$key] = is_array($row) && is_string($row[$key] ?? null) ? $row[$key] : '';
                }
                $form['lines'][] = $line;
            }
        }
        return $form;
    }

    private function go(string $url): never
    {
        header('Location: ' . $url, true, 303);
        exit;
    }
}
