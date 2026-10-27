<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use DomainException;
use PDO;
use Throwable;

final class SalesOrder
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::connection();
    }

    public function getActiveCustomers(): array
    {
        $sql = "
            SELECT
                khach_hang_id,
                ma_khach_hang,
                ten_khach_hang,
                dia_chi,
                dien_thoai,
                han_muc_no,
                so_ngay_no,
                trang_thai
            FROM khach_hang
            WHERE trang_thai = :trang_thai
            ORDER BY ten_khach_hang
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['trang_thai' => 'HOAT_DONG']);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getActiveWarehouses(): array
    {
        $sql = "
            SELECT
                kho_id,
                ma_kho,
                ten_kho,
                loai_kho,
                nhan_vien_phu_trach_id
            FROM kho
            WHERE trang_thai = :trang_thai
            ORDER BY ten_kho
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['trang_thai' => 'HOAT_DONG']);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getProductsWithStock(int $warehouseId): array
    {
        if ($warehouseId <= 0) {
            return [];
        }

        $sql = "
            SELECT
                h.hang_hoa_id,
                h.ma_hang,
                h.ten_hang,
                h.gia_si,
                h.gia_le,
                h.thue_suat,
                h.don_vi_nho_id,
                dvt_nho.ten_don_vi AS don_vi_nho,
                h.don_vi_lon_id,
                dvt_lon.ten_don_vi AS don_vi_lon,
                h.ty_le_quy_doi,
                COALESCE(tk.so_luong_ton, 0) AS so_luong_ton,
                COALESCE(tk.so_luong_giu_cho, 0) AS so_luong_giu_cho,
                GREATEST(
                    COALESCE(tk.so_luong_ton, 0)
                    - COALESCE(tk.so_luong_giu_cho, 0),
                    0
                ) AS so_luong_kha_dung
            FROM hang_hoa AS h
            INNER JOIN don_vi_tinh AS dvt_nho
                ON dvt_nho.don_vi_tinh_id = h.don_vi_nho_id
            LEFT JOIN don_vi_tinh AS dvt_lon
                ON dvt_lon.don_vi_tinh_id = h.don_vi_lon_id
            LEFT JOIN ton_kho AS tk
                ON tk.hang_hoa_id = h.hang_hoa_id
                AND tk.kho_id = :kho_id
            WHERE h.trang_thai = :trang_thai
            ORDER BY h.ten_hang
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'kho_id' => $warehouseId,
            'trang_thai' => 'DANG_KINH_DOANH',
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function createOrder(
        int $accountId,
        int $customerId,
        int $warehouseId,
        array $postedItems,
        string $paymentMethod,
        float $postedPaymentAmount,
        string $receiptMethod = 'TIEN_MAT'
    ): array {
        if (
            $accountId <= 0
            || $customerId <= 0
            || $warehouseId <= 0
        ) {
            throw new DomainException(
                'Thông tin tài khoản, khách hàng hoặc kho không hợp lệ.'
            );
        }

        if (!in_array(
            $paymentMethod,
            ['full', 'partial', 'debt'],
            true
        )) {
            throw new DomainException(
                'Phương thức thanh toán không hợp lệ.'
            );
        }

        if (!in_array(
            $receiptMethod,
            ['TIEN_MAT', 'CHUYEN_KHOAN', 'KHAC'],
            true
        )) {
            throw new DomainException(
                'Hình thức nhận tiền không hợp lệ.'
            );
        }

        if ($postedPaymentAmount < 0) {
            throw new DomainException(
                'Số tiền thanh toán không hợp lệ.'
            );
        }

        if ($this->pdo->inTransaction()) {
            throw new DomainException(
                'Không thể tạo đơn khi đang có giao dịch database khác.'
            );
        }

        $this->pdo->beginTransaction();

        try {
            $actor = $this->getSalesActor($accountId);
            $customer = $this->getActiveCustomerForUpdate($customerId);
            $this->assertActiveWarehouse($warehouseId);

            $validatedLines = [];
            $goodsTotal = 0.0;
            $discountTotal = 0.0;
            $taxTotal = 0.0;
            $grandTotal = 0.0;

            foreach ($postedItems as $rawProductId => $item) {
                $productId = filter_var(
                    (string) $rawProductId,
                    FILTER_VALIDATE_INT,
                    ['options' => ['min_range' => 1]]
                );

                if ($productId === false || !is_array($item)) {
                    throw new DomainException(
                        'Dữ liệu mặt hàng không hợp lệ.'
                    );
                }

                $rawQuantity = $item['so_luong'] ?? '0';
                $rawDiscount = $item['chiet_khau_phan_tram'] ?? '0';

                if (
                    !is_scalar($rawQuantity)
                    || !is_numeric((string) $rawQuantity)
                ) {
                    throw new DomainException(
                        'Số lượng mặt hàng không hợp lệ.'
                    );
                }

                if (
                    !is_scalar($rawDiscount)
                    || !is_numeric((string) $rawDiscount)
                ) {
                    throw new DomainException(
                        'Chiết khấu mặt hàng không hợp lệ.'
                    );
                }

                $quantity = (float) $rawQuantity;
                $discountPercent = (float) $rawDiscount;

                if (
                    !is_finite($quantity)
                    || !is_finite($discountPercent)
                ) {
                    throw new DomainException(
                        'Số lượng hoặc chiết khấu không hợp lệ.'
                    );
                }

                if (
                    $quantity < 0
                    || abs(
                        $quantity * 1000
                        - round($quantity * 1000)
                    ) > 0.00001
                ) {
                    throw new DomainException(
                        'Số lượng phải lớn hơn hoặc bằng 0 '
                        . 'và tối đa 3 chữ số thập phân.'
                    );
                }

                if (
                    $discountPercent < 0
                    || $discountPercent > 100
                ) {
                    throw new DomainException(
                        'Chiết khấu phải nằm trong khoảng 0–100%.'
                    );
                }

                if ($quantity <= 0) {
                    continue;
                }

                $product = $this->getProductStockForUpdate(
                    (int) $productId,
                    $warehouseId
                );

                $availableStock = max(
                    (float) $product['so_luong_ton']
                    - (float) $product['so_luong_giu_cho'],
                    0.0
                );

                if ($quantity > $availableStock) {
                    throw new DomainException(
                        sprintf(
                            'Mặt hàng %s vượt tồn khả dụng (%s). '
                            . 'Hãy điều chỉnh số lượng.',
                            (string) $product['ma_hang'],
                            number_format(
                                $availableStock,
                                3,
                                ',',
                                '.'
                            )
                        )
                    );
                }

                $unitPrice = (float) $product['gia_le'];
                $taxRate = (float) $product['thue_suat'];

                $lineGoods = round($quantity * $unitPrice, 2);

                $lineDiscount = round(
                    $lineGoods * $discountPercent / 100,
                    2
                );

                $taxableAmount = max(
                    $lineGoods - $lineDiscount,
                    0.0
                );

                $lineTax = round($taxableAmount * $taxRate, 2);
                $lineTotal = round($taxableAmount + $lineTax, 2);

                $validatedLines[] = [
                    'hang_hoa_id' => (int) $product['hang_hoa_id'],
                    'don_vi_tinh_id' => (int) $product['don_vi_nho_id'],
                    'so_luong_dat' => $quantity,
                    'he_so_quy_doi' => 1.0,
                    'don_gia_du_kien' => $unitPrice,
                    'chiet_khau_du_kien' => $lineDiscount,
                ];

                $goodsTotal += $lineGoods;
                $discountTotal += $lineDiscount;
                $taxTotal += $lineTax;
                $grandTotal += $lineTotal;
            }

            if ($validatedLines === []) {
                throw new DomainException(
                    'Vui lòng nhập số lượng cho ít nhất một mặt hàng.'
                );
            }

            $goodsTotal = round($goodsTotal, 2);
            $discountTotal = round($discountTotal, 2);
            $taxTotal = round($taxTotal, 2);
            $grandTotal = round($grandTotal, 2);

            if ($grandTotal <= 0) {
                throw new DomainException(
                    'Tổng tiền đơn hàng phải lớn hơn 0.'
                );
            }

            if ($paymentMethod === 'full') {
                $paymentAmount = $grandTotal;
                $orderPaymentType = 'THU_NGAY';
            } elseif ($paymentMethod === 'partial') {
                $paymentAmount = round($postedPaymentAmount, 2);

                if (
                    $paymentAmount <= 0
                    || $paymentAmount >= $grandTotal
                ) {
                    throw new DomainException(
                        'Thanh toán một phần phải lớn hơn 0 '
                        . 'và nhỏ hơn tổng tiền đơn hàng.'
                    );
                }

                $orderPaymentType = 'CONG_NO';
            } else {
                $paymentAmount = 0.0;
                $orderPaymentType = 'CONG_NO';
            }

            $newDebt = round($grandTotal - $paymentAmount, 2);

            if ($newDebt > 0) {
                $creditLimit = (float) $customer['han_muc_no'];
                $currentDebt = $this->getCurrentDebt($customerId);

                if ($currentDebt + $newDebt > $creditLimit) {
                    throw new DomainException(
                        'Công nợ dự kiến vượt hạn mức của khách hàng. '
                        . 'Hãy tăng số tiền thanh toán hoặc '
                        . 'kiểm tra công nợ.'
                    );
                }
            }

            $orderNo = $this->generateDocumentNumber('DH');

            $insertOrder = $this->pdo->prepare("
                INSERT INTO don_ban_hang (
                    so_don_hang,
                    khach_hang_id,
                    nhan_vien_ban_hang_id,
                    kho_xuat_id,
                    loai_thanh_toan,
                    trang_thai,
                    nguoi_lap_tai_khoan_id,
                    ghi_chu
                ) VALUES (
                    :so_don_hang,
                    :khach_hang_id,
                    :nhan_vien_ban_hang_id,
                    :kho_xuat_id,
                    :loai_thanh_toan,
                    'CHO_DUYET',
                    :nguoi_lap_tai_khoan_id,
                    :ghi_chu
                )
            ");

            $insertOrder->execute([
                'so_don_hang' => $orderNo,
                'khach_hang_id' => $customerId,
                'nhan_vien_ban_hang_id' =>
                    (int) $actor['nhan_vien_id'],
                'kho_xuat_id' => $warehouseId,
                'loai_thanh_toan' => $orderPaymentType,
                'nguoi_lap_tai_khoan_id' => $accountId,
                'ghi_chu' => 'Tạo từ chức năng UC-NVBH06.',
            ]);

            $orderId = (int) $this->pdo->lastInsertId();

            $insertDetail = $this->pdo->prepare("
                INSERT INTO chi_tiet_don_ban_hang (
                    don_ban_hang_id,
                    hang_hoa_id,
                    don_vi_tinh_id,
                    so_luong_dat,
                    he_so_quy_doi,
                    don_gia_du_kien,
                    chiet_khau_du_kien,
                    chuong_trinh_khuyen_mai_id,
                    ghi_chu
                ) VALUES (
                    :don_ban_hang_id,
                    :hang_hoa_id,
                    :don_vi_tinh_id,
                    :so_luong_dat,
                    :he_so_quy_doi,
                    :don_gia_du_kien,
                    :chiet_khau_du_kien,
                    NULL,
                    NULL
                )
            ");

            foreach ($validatedLines as $line) {
                $insertDetail->execute([
                    'don_ban_hang_id' => $orderId,
                    'hang_hoa_id' => $line['hang_hoa_id'],
                    'don_vi_tinh_id' => $line['don_vi_tinh_id'],
                    'so_luong_dat' => $line['so_luong_dat'],
                    'he_so_quy_doi' => $line['he_so_quy_doi'],
                    'don_gia_du_kien' => $line['don_gia_du_kien'],
                    'chiet_khau_du_kien' =>
                        $line['chiet_khau_du_kien'],
                ]);
            }

            $receiptNo = null;

            if ($paymentAmount > 0) {
                $receiptNo = $this->generateDocumentNumber('TN');

                $insertReceipt = $this->pdo->prepare("
                    INSERT INTO phieu_thu_nvbh (
                        ma_phieu_thu_nvbh,
                        nhan_vien_ban_hang_id,
                        khach_hang_id,
                        so_tien_thuc_thu,
                        phuong_thuc,
                        trang_thai,
                        ghi_chu
                    ) VALUES (
                        :ma_phieu_thu_nvbh,
                        :nhan_vien_ban_hang_id,
                        :khach_hang_id,
                        :so_tien_thuc_thu,
                        :phuong_thuc,
                        'CHO_NOP',
                        :ghi_chu
                    )
                ");

                $insertReceipt->execute([
                    'ma_phieu_thu_nvbh' => $receiptNo,
                    'nhan_vien_ban_hang_id' =>
                        (int) $actor['nhan_vien_id'],
                    'khach_hang_id' => $customerId,
                    'so_tien_thuc_thu' => $paymentAmount,
                    'phuong_thuc' => $receiptMethod,
                    'ghi_chu' => 'Thu khi lập đơn '
                        . $orderNo
                        . '; chờ nộp/đối soát.',
                ]);
            }

            $this->pdo->commit();

            return [
                'order_id' => $orderId,
                'order_no' => $orderNo,
                'receipt_no' => $receiptNo,
                'goods_total' => $goodsTotal,
                'discount_total' => $discountTotal,
                'tax_total' => $taxTotal,
                'grand_total' => $grandTotal,
                'payment_amount' => $paymentAmount,
                'balance' => $newDebt,
            ];
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $exception;
        }
    }

    private function getSalesActor(int $accountId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT
                tk.tai_khoan_id,
                tk.nhan_vien_id,
                n.trang_thai AS trang_thai_nhan_vien
            FROM tai_khoan AS tk
            INNER JOIN nhom_quyen AS q
                ON q.nhom_quyen_id = tk.nhom_quyen_id
            INNER JOIN nhan_vien AS n
                ON n.nhan_vien_id = tk.nhan_vien_id
            INNER JOIN nhan_vien_ban_hang AS nv
                ON nv.nhan_vien_id = n.nhan_vien_id
            WHERE tk.tai_khoan_id = :tai_khoan_id
              AND tk.trang_thai = 'HOAT_DONG'
              AND q.ma_nhom_quyen = 'SALES'
              AND q.trang_thai = 'HOAT_DONG'
              AND n.trang_thai = 'DANG_LAM_VIEC'
            LIMIT 1
            FOR UPDATE
        ");

        $stmt->execute(['tai_khoan_id' => $accountId]);
        $actor = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$actor) {
            throw new DomainException(
                'Tài khoản lập đơn không phải tài khoản '
                . 'nhân viên bán hàng đang hoạt động.'
            );
        }

        return $actor;
    }

    private function getActiveCustomerForUpdate(
        int $customerId
    ): array {
        $stmt = $this->pdo->prepare("
            SELECT khach_hang_id, han_muc_no, so_ngay_no, trang_thai
            FROM khach_hang
            WHERE khach_hang_id = :khach_hang_id
              AND trang_thai = 'HOAT_DONG'
            FOR UPDATE
        ");

        $stmt->execute(['khach_hang_id' => $customerId]);
        $customer = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$customer) {
            throw new DomainException(
                'Khách hàng không tồn tại hoặc không còn hoạt động.'
            );
        }

        return $customer;
    }

    private function assertActiveWarehouse(int $warehouseId): void
    {
        $stmt = $this->pdo->prepare("
            SELECT kho_id
            FROM kho
            WHERE kho_id = :kho_id
              AND trang_thai = 'HOAT_DONG'
            FOR UPDATE
        ");

        $stmt->execute(['kho_id' => $warehouseId]);

        if (!$stmt->fetchColumn()) {
            throw new DomainException(
                'Kho được chọn không tồn tại hoặc không còn hoạt động.'
            );
        }
    }

    private function getProductStockForUpdate(
        int $productId,
        int $warehouseId
    ): array {
        $stmt = $this->pdo->prepare("
            SELECT
                h.hang_hoa_id,
                h.ma_hang,
                h.don_vi_nho_id,
                h.ty_le_quy_doi,
                h.gia_le,
                h.thue_suat,
                tk.so_luong_ton,
                tk.so_luong_giu_cho
            FROM hang_hoa AS h
            INNER JOIN ton_kho AS tk
                ON tk.hang_hoa_id = h.hang_hoa_id
               AND tk.kho_id = :kho_id
            WHERE h.hang_hoa_id = :hang_hoa_id
              AND h.trang_thai = 'DANG_KINH_DOANH'
            FOR UPDATE
        ");

        $stmt->execute([
            'kho_id' => $warehouseId,
            'hang_hoa_id' => $productId,
        ]);

        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$product) {
            throw new DomainException(
                'Một mặt hàng không thuộc kho đã chọn '
                . 'hoặc không còn kinh doanh.'
            );
        }

        return $product;
    }

    private function getCurrentDebt(int $customerId): float
    {
        $countStmt = $this->pdo->prepare("
            SELECT COUNT(*)
            FROM hoa_don_ban AS hd
            INNER JOIN phieu_xuat_kho AS px
                ON px.phieu_xuat_kho_id = hd.phieu_xuat_kho_id
            INNER JOIN don_ban_hang AS db
                ON db.don_ban_hang_id = px.don_ban_hang_id
            WHERE db.khach_hang_id = :khach_hang_id
              AND hd.trang_thai = 'DA_PHAT_HANH'
        ");

        $countStmt->execute(['khach_hang_id' => $customerId]);

        if ((int) $countStmt->fetchColumn() === 0) {
            return 0.0;
        }

        try {
            $stmt = $this->pdo->prepare("
                SELECT COALESCE(
                    SUM(
                        GREATEST(
                            hd.tong_thanh_toan
                            - COALESCE(da_thu.so_tien_da_phan_bo, 0),
                            0
                        )
                    ),
                    0
                )
                FROM hoa_don_ban AS hd
                INNER JOIN phieu_xuat_kho AS px
                    ON px.phieu_xuat_kho_id = hd.phieu_xuat_kho_id
                INNER JOIN don_ban_hang AS db
                    ON db.don_ban_hang_id = px.don_ban_hang_id
                LEFT JOIN (
                    SELECT
                        ctpt.hoa_don_ban_id,
                        SUM(ctpt.so_tien_phan_bo)
                            AS so_tien_da_phan_bo
                    FROM chi_tiet_phieu_thu AS ctpt
                    INNER JOIN phieu_thu AS pt
                        ON pt.phieu_thu_id = ctpt.phieu_thu_id
                    WHERE pt.trang_thai = 'DA_XAC_NHAN'
                    GROUP BY ctpt.hoa_don_ban_id
                ) AS da_thu
                    ON da_thu.hoa_don_ban_id = hd.hoa_don_ban_id
                WHERE db.khach_hang_id = :khach_hang_id
                  AND hd.trang_thai = 'DA_PHAT_HANH'
            ");

            $stmt->execute(['khach_hang_id' => $customerId]);

            return max((float) $stmt->fetchColumn(), 0.0);
        } catch (Throwable $exception) {
            throw new DomainException(
                'Không thể xác định công nợ hiện tại từ '
                . 'các phiếu thu đã xác nhận. Để an toàn, '
                . 'chưa thể lập đơn có công nợ cho khách hàng này.',
                0,
                $exception
            );
        }
    }

    private function generateDocumentNumber(string $prefix): string
    {
        return $prefix
            . '-'
            . date('Ymd-His')
            . '-'
            . strtoupper(bin2hex(random_bytes(3)));
    }
}