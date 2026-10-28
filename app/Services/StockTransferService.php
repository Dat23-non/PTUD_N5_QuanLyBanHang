<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\StockTransfer;
use DateTimeImmutable;
use DomainException;
use PDO;
use Throwable;

final class StockTransferService
{
    private const MAX_CENTS = 999999999999999;
    private StockTransfer $model;

    public function __construct(private PDO $db)
    {
        $this->model = new StockTransfer($db);
    }

    /** Tiền được tính bằng số nguyên xu, không dùng float để ghi giá trị tiền. */
    public static function cents(mixed $value): int
    {
        if (!is_string($value) || !preg_match('/\A(0|[1-9][0-9]{0,12})(?:\.([0-9]{1,2}))?\z/', $value, $m)) {
            throw new DomainException('Đơn giá phải được cung cấp, không âm và có tối đa 2 chữ số thập phân.');
        }
        return (int) $m[1] * 100 + (int) str_pad($m[2] ?? '', 2, '0');
    }

    private static function decimal(int $cents): string
    {
        return intdiv($cents, 100) . '.' . str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);
    }

    public function createDraft(int $actorId, array $input): int
    {
        $destinationId = filter_var($input['destination_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $date = $input['transfer_date'] ?? null;
        $parsedDate = is_string($date) && preg_match('/\A[0-9]{4}-[0-9]{2}-[0-9]{2}\z/', $date)
            ? DateTimeImmutable::createFromFormat('!Y-m-d', $date) : false;
        if (!$destinationId) {
            throw new DomainException('Vui lòng chọn kho NVBH nhận hàng hợp lệ.');
        }
        if (!$parsedDate || $parsedDate->format('Y-m-d') !== $date || $parsedDate->format('Y') < '1000') {
            throw new DomainException('Ngày chuyển không hợp lệ.');
        }
        $note = $input['note'] ?? '';
        if (!is_string($note) || !preg_match('//u', $note) || strlen($note) > 4000) {
            throw new DomainException('Ghi chú phải là văn bản UTF-8, tối đa 4.000 byte.');
        }
        $rows = $input['lines'] ?? null;
        if (!is_array($rows) || count($rows) < 1 || count($rows) > 100) {
            throw new DomainException('Phiếu phải có từ 1 đến 100 dòng hàng.');
        }

        $this->db->beginTransaction();
        try {
            // Đọc và khóa lại dữ liệu trong transaction để kiểm tra đúng thời điểm lưu.
            $actor = $this->model->actor($actorId, true);
            if (!$actor) {
                throw new DomainException('Tài khoản không có quyền thủ kho hoặc đã ngừng hoạt động.');
            }
            $sources = $this->model->sourceWarehouses((int) $actor['nha_phan_phoi_id'], true);
            if (count($sources) !== 1) {
                throw new DomainException('Nhà phân phối phải có đúng một Kho tổng đang hoạt động.');
            }
            $sourceId = (int) $sources[0]['kho_id'];
            $destinations = array_column($this->model->destinations((int) $actor['nha_phan_phoi_id'], true), null, 'kho_id');
            if ($destinationId === $sourceId || !isset($destinations[$destinationId])) {
                throw new DomainException('Kho nhận phải là kho NVBH đang hoạt động, khác Kho tổng và cùng nhà phân phối.');
            }
            $products = array_column($this->model->products($sourceId, true), null, 'hang_hoa_id');
            $lines = [];
            $seen = [];
            $total = 0;
            foreach ($rows as $row) {
                if (!is_array($row)) {
                    throw new DomainException('Dòng hàng không hợp lệ.');
                }
                $id = filter_var($row['product_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
                $quantity = $row['quantity'] ?? null;
                if (!$id || !isset($products[$id])) {
                    throw new DomainException('Mặt hàng không tồn tại, ngừng kinh doanh hoặc chưa có tồn tại Kho tổng.');
                }
                $product = $products[$id];
                if (isset($seen[$id])) {
                    throw new DomainException('Mặt hàng ' . $product['ma_hang'] . ' bị trùng. Hãy gộp thành một dòng.');
                }
                $seen[$id] = true;
                if (!is_string($quantity) || !preg_match('/\A[1-9][0-9]{0,11}\z/', $quantity)) {
                    throw new DomainException('Số lượng của ' . $product['ma_hang'] . ' phải là số nguyên dương.');
                }
                $qty = (int) $quantity;
                // Tồn đang lưu theo đơn vị nhỏ/cơ sở; phiếu này cũng chỉ nhập đơn vị đó.
                if ($qty > (int) $product['so_luong_ton']) {
                    throw new DomainException('Hàng ' . $product['ma_hang'] . ': yêu cầu ' . $quantity
                        . ', tồn Kho tổng hiện tại ' . $product['so_luong_ton'] . '. Vui lòng điều chỉnh.');
                }
                $price = self::cents($row['unit_price'] ?? null);
                if (($price > 0 && $qty > intdiv(self::MAX_CENTS, $price)) || $total > self::MAX_CENTS - $qty * $price) {
                    throw new DomainException('Tổng giá trị phiếu vượt giới hạn lưu trữ.');
                }
                $amount = $qty * $price;
                $total += $amount;
                $lines[] = ['product_id' => $id, 'unit_id' => (int) $product['don_vi_nho_id'],
                    'quantity' => $qty, 'unit_price' => self::decimal($price), 'amount' => self::decimal($amount)];
            }
            $code = 'PCK-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(8)));
            $id = $this->model->insertDraft([$code, $sourceId, $destinationId, $date . ' 00:00:00',
                self::decimal($total), $actorId, trim($note)], $lines);
            $this->db->commit();
            return $id;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }
}
