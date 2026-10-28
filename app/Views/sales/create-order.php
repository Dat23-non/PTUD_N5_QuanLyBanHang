<?php

declare(strict_types=1);
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (
    !isset($_SESSION['sales_order_csrf'])
    || !is_string($_SESSION['sales_order_csrf'])
) {
    $_SESSION['sales_order_csrf'] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION['sales_order_csrf'];

$flashMessage = $_SESSION['sales_order_flash'] ?? null;
unset($_SESSION['sales_order_flash']);

/*
 * UC-NVBH06 - Lap don hang ban
 *
 * View chi hien thi du lieu duoc Controller truyen vao.
 * Khong truy van CSDL truc tiep trong file nay.
 * Phan tinh tien JavaScript chi la xem truoc; may chu phai tinh lai
 * va kiem tra ton kho, cong no truoc khi luu don hang that.
 */

$loadError = is_string($loadError ?? null) ? $loadError : null;

// Nếu Controller đã truyền dữ liệu thì dùng dữ liệu đó.
// Nếu route mở trực tiếp View, tải dữ liệu qua Model như phương án tạm.
$hasControllerData = is_array($customers ?? null)
    && is_array($warehouses ?? null)
    && is_array($products ?? null);

if (!$hasControllerData) {
    $customers = [];
    $warehouses = [];
    $products = [];
    $selectedWarehouseId = null;

    try {
        $salesOrderModel = new \App\Models\SalesOrder();

        // Lấy khách hàng và kho từ database.
        $customers = $salesOrderModel->getActiveCustomers();
        $warehouses = $salesOrderModel->getActiveWarehouses();

        // Ưu tiên kho được chọn trên URL nếu mã kho hợp lệ.
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
                (int) ($warehouse['kho_id'] ?? 0),
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

        // Lấy hàng hóa theo kho được chọn.
        if ($selectedWarehouseId !== null) {
            $products = $salesOrderModel->getProductsWithStock(
                $selectedWarehouseId
            );
        }
    } catch (\Throwable $exception) {
        error_log(
            'Sales order data loading error: '
            . $exception->getMessage()
        );

        $loadError =
            'Không thể tải dữ liệu. Vui lòng kiểm tra kết nối database.';
    }
}

// Đảm bảo các biến có đúng kiểu trước khi hiển thị.
$customers = is_array($customers ?? null) ? $customers : [];
$warehouses = is_array($warehouses ?? null) ? $warehouses : [];
$products = is_array($products ?? null) ? $products : [];

$selectedWarehouseId = isset($selectedWarehouseId)
    ? (int) $selectedWarehouseId
    : null;

$salesEscape = static fn(mixed $value): string =>
    htmlspecialchars(
        (string) ($value ?? ''),
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );

$salesFormatMoney = static fn(mixed $value): string =>
    number_format((float) ($value ?? 0), 0, ',', '.');

$salesFormatQuantity = static function (mixed $value): string {
    $formatted = number_format((float) ($value ?? 0), 3, ',', '.');

    return rtrim(rtrim($formatted, '0'), ',');
};

$selectedWarehouseName = 'Chưa xác định';

foreach ($warehouses as $warehouse) {
    if (
        (int) ($warehouse['kho_id'] ?? 0)
        === $selectedWarehouseId
    ) {
        $selectedWarehouseName = (string) $warehouse['ten_kho'];
        break;
    }
}
?>

<style>
    .sales-order-page {
        max-width: 1250px;
    }

    .sales-order-page .sales-steps {
        display: grid;
        grid-template-columns: repeat(5, minmax(0, 1fr));
        gap: 9px;
        padding: 0;
        margin: 0 0 20px;
        list-style: none;
    }

    .sales-order-page .sales-steps li {
        margin: 0;
        padding: 9px 5px;
        border: 1px solid #cbd5e1;
        border-radius: 999px;
        background: #f1f5f9;
        color: #64748b;
        text-align: center;
        font-size: 12px;
    }

    .sales-order-page .sales-steps .active {
        border-color: #2864ed;
        background: #2864ed;
        color: #fff;
    }

    .sales-order-page .customer-panel {
        padding: 14px;
        border: 1px solid #6b98ff;
        background: #dceaff;
        box-shadow: none;
    }

    .sales-order-page .section-kicker {
        margin-bottom: 9px;
        color: #2864ed;
        font-size: 12px;
        font-weight: 700;
    }

    .sales-order-page .customer-grid {
        display: grid;
        grid-template-columns: minmax(240px, 2.2fr)
            repeat(3, minmax(100px, 1fr));
        align-items: center;
        gap: 16px;
    }

    .sales-order-page .customer-grid label,
    .sales-order-page .customer-grid .info-label {
        display: block;
        margin: 0 0 4px;
        color: #64748b;
        font-size: 12px;
        font-weight: 400;
    }

    .sales-order-page .customer-grid strong {
        font-size: 13px;
        font-weight: 500;
    }

    .sales-order-page .customer-grid select {
        min-height: 36px;
        padding: 6px 9px;
        background: #fff;
    }

    .sales-order-page .section-heading {
        margin: 20px 0 8px;
        font-size: 14px;
        font-weight: 700;
    }

    .sales-order-page .table-wrap {
        overflow-x: auto;
        border: 1px solid #cbd5e1;
        border-radius: 5px;
    }

    .sales-order-page .sales-table {
        min-width: 900px;
        font-size: 12px;
    }

    .sales-order-page .sales-table th {
        padding: 8px 7px;
        background: #1e293b;
        color: #fff;
        text-transform: none;
        white-space: nowrap;
    }

    .sales-order-page .sales-table td {
        padding: 7px;
        border: 1px solid #cbd5e1;
        text-align: center;
        vertical-align: middle;
    }

    .sales-order-page .sales-table .text-left {
        text-align: left;
    }

    .sales-order-page .sales-table input {
        width: 78px;
        min-width: 65px;
        min-height: 32px;
        padding: 4px 6px;
    }

    .sales-order-page .sales-table .stock-warning {
        background: #fee2e2;
    }

    .sales-order-page .line-total {
        white-space: nowrap;
        font-weight: 600;
    }

    .sales-order-page .stock-notice {
        margin-top: 7px;
        padding: 9px 11px;
        border: 1px solid #f59e0b;
        border-radius: 6px;
        background: #fff4ce;
        color: #92400e;
        font-size: 12px;
    }

    .sales-order-page .button-outline {
        margin-top: 8px;
        border: 1px solid #2864ed;
        background: #fff;
        color: #2864ed;
    }

    .sales-order-page .order-bottom {
        display: grid;
        grid-template-columns: minmax(280px, 1fr)
            minmax(280px, 1fr);
        gap: 14px;
        margin-top: 14px;
    }

    .sales-order-page .payment-panel,
    .sales-order-page .total-panel {
        padding: 12px;
        border: 1px solid #b6c4d8;
        border-radius: 5px;
    }

    .sales-order-page .payment-title {
        margin-bottom: 8px;
        font-size: 12px;
        font-weight: 700;
    }

    .sales-order-page .payment-option {
        display: flex;
        align-items: center;
        gap: 7px;
        margin: 8px 0;
        font-size: 13px;
    }

    .sales-order-page .payment-option input {
        width: 14px;
        min-height: auto;
        height: 14px;
        margin: 0;
    }

    .sales-order-page .payment-amount {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-top: 8px;
    }

    .sales-order-page .payment-amount input {
        width: 160px;
        min-height: 32px;
        padding: 5px 8px;
    }

    .sales-order-page .payment-amount span {
        color: #64748b;
        font-size: 12px;
    }

    .sales-order-page .total-panel {
        border-color: #6b98ff;
        background: #dceaff;
    }

    .sales-order-page .total-row {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        margin: 0 0 7px;
        font-size: 13px;
    }

    .sales-order-page .total-row:last-child {
        margin-bottom: 0;
    }

    .sales-order-page .total-amount {
        color: #2864ed;
        font-size: 15px;
        font-weight: 700;
    }

    .sales-order-page .debt-amount {
        color: #dc2626;
        font-size: 15px;
        font-weight: 700;
    }

    .sales-order-page .actions {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        margin-top: 14px;
    }

    .sales-order-page .cancel-button {
        min-width: 100px;
        border: 1px solid #94a3b8;
        background: #fff;
        color: #334155;
    }

    .sales-order-page .create-button {
        min-width: 175px;
    }

    .sales-order-page .small-note {
        margin-top: 10px;
        color: #64748b;
        font-size: 12px;
    }

    .sales-order-page .empty-row {
        padding: 24px 12px;
        color: #64748b;
        text-align: center;
    }

    .sales-order-page .validation-error {
        color: #b91c1c;
        font-weight: 700;
    }

    @media (max-width: 800px) {
        .sales-order-page .sales-steps {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .sales-order-page .customer-grid,
        .sales-order-page .order-bottom {
            grid-template-columns: 1fr;
        }
    }
</style>

<section class="page-header">
    <div>
        <p class="eyebrow">SALES</p>
        <h1>Lập đơn hàng bán</h1>
        <p class="page-description">
            Lập đơn hàng theo yêu cầu của khách hàng.
        </p>
    </div>
</section>

<div class="sales-order-page">

    <?php if ($loadError !== null): ?>
        <div class="notice notice-info" role="alert">
            <?= $salesEscape($loadError) ?>
        </div>
    <?php endif; ?>
    <?php if (
    is_array($flashMessage)
    && is_string($flashMessage['message'] ?? null)
): ?>
    <div class="notice notice-info" role="status">
        <?= $salesEscape($flashMessage['message']) ?>
    </div>
<?php endif; ?>

    <div class="notice notice-info">
        Kho đang xem:
        <strong><?= $salesEscape($selectedWarehouseName) ?></strong>
    </div>
    <form
    id="sales-order-form"
    method="post"
    action="sales-order-save.php"
>
    <input
        type="hidden"
        name="csrf_token"
        value="<?= $salesEscape($csrfToken) ?>"
    >

    <input
        type="hidden"
        name="warehouse_id"
        value="<?= (int) ($selectedWarehouseId ?? 0) ?>"
    >

    <ol class="sales-steps" aria-label="Các bước lập đơn hàng">
        <li class="active">1. Chọn khách hàng</li>
        <li class="active">2. Chọn hàng</li>
        <li class="active">3. Tính tiền</li>
        <li>4. Thanh toán</li>
        <li>5. Xác nhận</li>
    </ol>

    <!-- THONG TIN KHACH HANG -->
    <section class="panel customer-panel">
        <div class="section-kicker">KHÁCH HÀNG</div>

        <div class="customer-grid">
            <div>
                <label for="customer_id">Khách hàng</label>

                <select id="customer_id" name="customer_id">
                    <option value="">-- Chọn khách hàng --</option>

                    <?php foreach ($customers as $customer): ?>
                        <option
                            value="<?= (int) $customer['khach_hang_id'] ?>"
                            data-credit-limit="<?= $salesEscape(
                                $customer['han_muc_no'] ?? 0
                            ) ?>"
                            data-credit-days="<?= (int) (
                                $customer['so_ngay_no'] ?? 0
                            ) ?>"
                        >
                            <?= $salesEscape(
                                $customer['ten_khach_hang'] ?? ''
                            ) ?>
                            —
                            <?= $salesEscape(
                                $customer['ma_khach_hang'] ?? ''
                            ) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <?php if ($customers === []): ?>
                    <small class="muted">
                        Chưa có khách hàng hoặc chưa tải được dữ liệu.
                    </small>
                <?php endif; ?>
            </div>

            <div>
                <span class="info-label">Hạn nợ</span>
                <strong id="customer-credit-days">—</strong>
            </div>

            <div>
                <span class="info-label">Công nợ hiện tại</span>
                <strong id="customer-current-debt">Chưa tính</strong>
            </div>

            <div>
                <span class="info-label">Hạn mức nợ</span>
                <strong id="customer-credit-limit">—</strong>
            </div>
        </div>
    </section>

    <!-- CHI TIET HANG BAN -->
    <h2 class="section-heading">CHI TIẾT HÀNG BÁN</h2>

    <div class="table-wrap">
        <table class="sales-table">
            <thead>
                <tr>
                    <th>STT</th>
                    <th>Mã hàng</th>
                    <th>Tên hàng</th>
                    <th>Tồn khả dụng</th>
                    <th>SL bán</th>
                    <th>Đơn giá</th>
                    <th>CK%</th>
                    <th>Thuế</th>
                    <th>Thành tiền</th>
                </tr>
            </thead>

            <tbody id="sales-order-items">
                <?php if ($products === []): ?>
                    <tr>
                        <td colspan="9" class="empty-row">
                            Chưa có hàng hóa tại kho này.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($products as $index => $product): ?>
                        <?php
                        $productId = (int) $product['hang_hoa_id'];

                        $availableStock = (float) (
                            $product['so_luong_kha_dung'] ?? 0
                        );

                        $unitPrice = (float) (
                            $product['gia_le'] ?? 0
                        );

                        $taxRate = (float) (
                            $product['thue_suat'] ?? 0
                        );
                        ?>

                        <tr
                            data-product-row
                            data-stock="<?= $salesEscape(
                                $availableStock
                            ) ?>"
                            data-price="<?= $salesEscape($unitPrice) ?>"
                            data-tax-rate="<?= $salesEscape($taxRate) ?>"
                        >
                            <td><?= $index + 1 ?></td>

                            <td>
                                <?= $salesEscape(
                                    $product['ma_hang'] ?? ''
                                ) ?>
                            </td>

                            <td class="text-left">
                                <?= $salesEscape(
                                    $product['ten_hang'] ?? ''
                                ) ?>
                                <?php if (!empty($product['don_vi_nho'])): ?>
                                    <div class="muted">
                                        ĐVT:
                                        <?= $salesEscape(
                                            $product['don_vi_nho']
                                        ) ?>
                                    </div>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?= $salesFormatQuantity(
                                    $availableStock
                                ) ?>
                            </td>

                            <td>
                                <input
                                    type="number"
                                    class="js-quantity"
                                    name="items[<?= $productId ?>][so_luong]"
                                    min="0"
                                    max="<?= $salesEscape(
                                        $availableStock
                                    ) ?>"
                                    step="0.001"
                                    value="0"
                                    aria-label="Số lượng bán"
                                >
                            </td>

                            <td>
                                <?= $salesFormatMoney($unitPrice) ?>
                            </td>

                            <td>
                                <input
                                    type="number"
                                    class="js-discount"
                                    name="items[<?= $productId ?>][chiet_khau_phan_tram]"
                                    min="0"
                                    max="100"
                                    step="0.01"
                                    value="0"
                                    aria-label="Chiết khấu phần trăm"
                                >
                            </td>

                            <td>
                                <?= number_format(
                                    $taxRate * 100,
                                    2,
                                    ',',
                                    '.'
                                ) ?>%
                            </td>

                            <td>
                                <span class="line-total">—</span>
                                <div
                                    class="validation-error js-row-error"
                                    hidden
                                ></div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div
        class="stock-notice"
        id="stock-notice"
        role="alert"
        hidden
    >
        Số lượng yêu cầu vượt tồn khả dụng tại kho. Vui lòng điều chỉnh.
    </div>

    <button
        type="button"
        class="button button-outline"
        id="add-product-button"
    >
        + Thêm mặt hàng
    </button>

    <!-- THANH TOAN VA TONG TIEN -->
    <div class="order-bottom">
        <section class="payment-panel">
            <div class="payment-title">
                PHƯƠNG THỨC THANH TOÁN
            </div>

            <label class="payment-option">
                <input
                    type="radio"
                    name="payment_method"
                    value="full"
                >
                Thanh toán toàn bộ
            </label>

            <label class="payment-option">
                <input
                    type="radio"
                    name="payment_method"
                    value="partial"
                    checked
                >
                Thanh toán một phần
            </label>

            <label class="payment-option">
                <input
                    type="radio"
                    name="payment_method"
                    value="debt"
                >
                Bán nợ hoàn toàn
            </label>

            <div class="payment-amount">
                <input
                    type="number"
                    id="payment_amount"
                    name="payment_amount"
                    min="0"
                    step="1"
                    value="0"
                    aria-label="Số tiền khách thanh toán"
                >
                <span>đ thanh toán</span>
            </div>

            <div
                id="payment-warning"
                class="validation-error"
                role="alert"
                hidden
            >
                Số tiền thanh toán không được vượt tổng tiền đơn hàng.
            </div>

            <p class="small-note">
                Công nợ hiện tại và hạn mức nợ phải được kiểm tra
                lại ở phía máy chủ trước khi xác nhận đơn hàng.
            </p>
        </section>

        <section class="total-panel">
            <div class="total-row">
                <span>Tiền hàng:</span>
                <span id="total-goods">0 đ</span>
            </div>

            <div class="total-row">
                <span>Chiết khấu:</span>
                <span id="total-discount">0 đ</span>
            </div>

            <div class="total-row">
                <span>Tiền thuế:</span>
                <span id="total-tax">0 đ</span>
            </div>

            <div class="total-row">
                <span>TỔNG TIỀN:</span>
                <strong class="total-amount" id="grand-total">
                    0 đ
                </strong>
            </div>

            <div class="total-row">
                <span>Còn phải thu của đơn này:</span>
                <strong class="debt-amount" id="order-balance">
                    0 đ
                </strong>
            </div>
        </section>
    </div>

    <div class="actions">
        <button
            type="button"
            class="button cancel-button"
            id="cancel-order-button"
        >
            HỦY
        </button>

        <button
    type="submit"
    class="button button-primary create-button"
    id="create-order-button"
>
    LẬP ĐƠN HÀNG
</button>
    </div>

    <p class="small-note">
    Tổng tiền trên màn hình là giá trị xem trước.
    Máy chủ sẽ kiểm tra lại dữ liệu trước khi lưu đơn hàng.
</p>

</form>
</div>

<script>
(() => {
    'use strict';

    const customerSelect = document.getElementById('customer_id');

    const creditDays = document.getElementById(
        'customer-credit-days'
    );

    const currentDebt = document.getElementById(
        'customer-current-debt'
    );

    const creditLimit = document.getElementById(
        'customer-credit-limit'
    );

    const paymentInput = document.getElementById('payment_amount');

    const paymentWarning = document.getElementById(
        'payment-warning'
    );

    const stockNotice = document.getElementById('stock-notice');

    const formatMoney = (value) => {
        return new Intl.NumberFormat('vi-VN', {
            maximumFractionDigits: 0
        }).format(Math.round(value)) + ' đ';
    };

    function updateCustomerInfo() {
        const option = customerSelect.options[
            customerSelect.selectedIndex
        ];

        if (!option || !option.value) {
            creditDays.textContent = '—';
            currentDebt.textContent = 'Chưa tính';
            creditLimit.textContent = '—';
            return;
        }

        const days = Number(option.dataset.creditDays || 0);
        const limit = Number(option.dataset.creditLimit || 0);

        creditDays.textContent = days + ' ngày';
        currentDebt.textContent = 'Chưa tính';
        creditLimit.textContent = formatMoney(limit);
    }

    function calculatePreview() {
        const rows = document.querySelectorAll('[data-product-row]');

        let goodsTotal = 0;
        let discountTotal = 0;
        let taxTotal = 0;
        let grandTotal = 0;
        let stockError = false;

        rows.forEach((row) => {
            const quantityInput = row.querySelector('.js-quantity');
            const discountInput = row.querySelector('.js-discount');

            const quantity = Math.max(
                Number(quantityInput.value || 0),
                0
            );

            const discountRate = Math.min(
                Math.max(Number(discountInput.value || 0), 0),
                100
            );

            const stock = Number(row.dataset.stock || 0);
            const price = Number(row.dataset.price || 0);
            const taxRate = Number(row.dataset.taxRate || 0);

            const rowError = row.querySelector('.js-row-error');
            const lineTotalElement = row.querySelector('.line-total');

            const quantityInvalid =
                quantity > stock ||
                Number(quantityInput.value || 0) < 0;

            row.classList.toggle('stock-warning', quantityInvalid);

            if (quantityInvalid) {
                stockError = true;
                rowError.hidden = false;
                rowError.textContent = 'Vượt tồn';
                lineTotalElement.textContent = 'Không hợp lệ';

                quantityInput.setCustomValidity(
                    'Số lượng vượt tồn khả dụng.'
                );
            } else {
                rowError.hidden = true;
                rowError.textContent = '';

                quantityInput.setCustomValidity('');
            }

            const lineGoods = quantity * price;
            const lineDiscount = lineGoods * discountRate / 100;
            const taxableAmount = Math.max(
                lineGoods - lineDiscount,
                0
            );

            const lineTax = taxableAmount * taxRate;
            const lineGrandTotal = taxableAmount + lineTax;

            goodsTotal += lineGoods;
            discountTotal += lineDiscount;
            taxTotal += lineTax;
            grandTotal += lineGrandTotal;

            if (!quantityInvalid && quantity > 0) {
                lineTotalElement.textContent =
                    formatMoney(lineGrandTotal);
            } else if (!quantityInvalid) {
                lineTotalElement.textContent = '—';
            }
        });

        document.getElementById('total-goods').textContent =
            formatMoney(goodsTotal);

        document.getElementById('total-discount').textContent =
            formatMoney(discountTotal);

        document.getElementById('total-tax').textContent =
            formatMoney(taxTotal);

        document.getElementById('grand-total').textContent =
            formatMoney(grandTotal);

        stockNotice.hidden = !stockError;

        const paymentMethod = document.querySelector(
            'input[name="payment_method"]:checked'
        )?.value || 'partial';

        if (paymentMethod === 'full') {
            paymentInput.value = String(Math.round(grandTotal));
            paymentInput.disabled = true;
        } else if (paymentMethod === 'debt') {
            paymentInput.value = '0';
            paymentInput.disabled = true;
        } else {
            paymentInput.disabled = false;
        }

        const paymentValue = Math.max(
            Number(paymentInput.value || 0),
            0
        );

        const paymentInvalid = paymentValue > grandTotal;

        paymentWarning.hidden = !paymentInvalid;

        const actualPaymentForPreview = Math.min(
            paymentValue,
            grandTotal
        );

        const orderBalance = Math.max(
            grandTotal - actualPaymentForPreview,
            0
        );

        document.getElementById('order-balance').textContent =
            formatMoney(orderBalance);
    }

    customerSelect.addEventListener('change', updateCustomerInfo);

    document.querySelectorAll(
        '.js-quantity, .js-discount'
    ).forEach((input) => {
        input.addEventListener('input', calculatePreview);
        input.addEventListener('change', calculatePreview);
    });

    document.querySelectorAll(
        'input[name="payment_method"]'
    ).forEach((input) => {
        input.addEventListener('change', calculatePreview);
    });

    paymentInput.addEventListener('input', calculatePreview);

    document.getElementById('add-product-button')
        .addEventListener('click', () => {
            const emptyQuantity = Array.from(
                document.querySelectorAll('.js-quantity')
            ).find((input) => Number(input.value || 0) === 0);

            if (emptyQuantity) {
                emptyQuantity.focus();
                emptyQuantity.scrollIntoView({
                    behavior: 'smooth',
                    block: 'center'
                });
            } else {
                alert(
                    'Không còn dòng hàng nào có số lượng bằng 0. ' +
                    'Danh sách hiện tại đã hiển thị các mặt hàng của kho.'
                );
            }
        });

    document.getElementById('cancel-order-button')
        .addEventListener('click', () => {
            if (!confirm('Bạn có muốn xóa các thông tin đang nhập?')) {
                return;
            }

            customerSelect.value = '';

            document.querySelectorAll('.js-quantity').forEach((input) => {
                input.value = '0';
            });

            document.querySelectorAll('.js-discount').forEach((input) => {
                input.value = '0';
            });

            document.querySelector(
                'input[name="payment_method"][value="partial"]'
            ).checked = true;

            paymentInput.value = '0';

            updateCustomerInfo();
            calculatePreview();
        });

    const orderForm = document.getElementById('sales-order-form');

orderForm.addEventListener('submit', (event) => {
    calculatePreview();

    if (!customerSelect.value) {
        event.preventDefault();
        alert('Vui lòng chọn khách hàng.');
        customerSelect.focus();
        return;
    }

    const selectedItems = Array.from(
        document.querySelectorAll('.js-quantity')
    ).filter((input) => Number(input.value || 0) > 0);

    if (selectedItems.length === 0) {
        event.preventDefault();
        alert('Vui lòng nhập số lượng cho ít nhất một mặt hàng.');
        return;
    }

    const invalidQuantity = selectedItems.some((input) => {
        const quantity = Number(input.value || 0);
        const stock = Number(input.max || 0);

        return quantity > stock || !input.checkValidity();
    });

    if (invalidQuantity) {
        event.preventDefault();
        alert('Có mặt hàng vượt tồn khả dụng hoặc có số lượng không hợp lệ.');
        return;
    }

    const paymentMethod = document.querySelector(
        'input[name="payment_method"]:checked'
    )?.value;

    if (
        paymentMethod === 'partial'
        && Number(paymentInput.value || 0) <= 0
    ) {
        event.preventDefault();
        alert('Vui lòng nhập số tiền thanh toán lớn hơn 0.');
        paymentInput.focus();
        return;
    }

    if (!paymentWarning.hidden) {
        event.preventDefault();
        alert('Số tiền thanh toán không được vượt tổng tiền đơn hàng.');
        paymentInput.focus();
        return;
    }

    if (!orderForm.reportValidity()) {
        event.preventDefault();
        return;
    }
});

    updateCustomerInfo();
    calculatePreview();
})();
</script>