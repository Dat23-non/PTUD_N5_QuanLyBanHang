<?php
$money = static fn ($value): string => number_format((float) $value, 2, ',', '.');
$quantityText = static fn ($value): string => rtrim(rtrim(number_format((float) $value, 3, ',', '.'), '0'), ',');
$renderLine = static function (array $line, string $index) use ($products): void {
    ?>
    <tr class="st-line">
        <td><label class="st-sr-only" for="st-product-<?= e($index) ?>">Mặt hàng</label>
            <select id="st-product-<?= e($index) ?>" name="lines[<?= e($index) ?>][product_id]" class="st-product" required>
                <option value="">Chọn mặt hàng</option>
                <?php foreach ($products as $product): ?>
                    <option value="<?= e($product['hang_hoa_id']) ?>"
                        data-unit="<?= e($product['ten_don_vi']) ?>" data-stock="<?= e($product['so_luong_ton']) ?>"
                        <?= (string) $line['product_id'] === (string) $product['hang_hoa_id'] ? 'selected' : '' ?>>
                        <?= e($product['ma_hang'] . ' · ' . $product['ten_hang']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </td>
        <td class="st-unit">—</td><td class="st-stock st-number">—</td>
        <td><label class="st-sr-only" for="st-quantity-<?= e($index) ?>">Số lượng chuyển</label>
            <input id="st-quantity-<?= e($index) ?>" class="st-quantity" name="lines[<?= e($index) ?>][quantity]"
                type="number" min="1" max="999999999999" step="1" value="<?= e($line['quantity']) ?>" required></td>
        <td><label class="st-sr-only" for="st-price-<?= e($index) ?>">Đơn giá ghi nhận</label>
            <input id="st-price-<?= e($index) ?>" class="st-price" name="lines[<?= e($index) ?>][unit_price]"
                type="number" min="0" max="9999999999999.99" step="0.01" value="<?= e($line['unit_price']) ?>" required></td>
        <td class="st-amount st-number">0,00</td>
        <td><button class="st-remove button st-button-light" type="button" aria-label="Bỏ dòng hàng">Bỏ</button></td>
    </tr>
    <?php
};
?>
<link rel="stylesheet" href="assets/css/stock-transfer.css">
<div class="st-page">
    <section class="page-header">
        <div><p class="eyebrow">KHO HÀNG / CHUYỂN KHO</p>
            <h1><?= $transfer ? 'Chi tiết phiếu chuyển kho' : 'Lập phiếu chuyển kho' ?></h1>
            <p class="page-description">Điều chuyển hàng từ Kho tổng đến kho nhân viên bán hàng.</p>
        </div>
        <span class="status-pill"><?= $transfer ? e(['NHAP' => 'Nháp', 'DA_XAC_NHAN' => 'Đã xác nhận', 'HUY' => 'Đã hủy'][$transfer['trang_thai']] ?? $transfer['trang_thai']) : 'Phiếu mới' ?></span>
    </section>
    <?php if ($demoEnabled): ?>
        <section class="notice notice-info st-demo" aria-label="Chế độ local">
            <div><strong>Chế độ local · Chọn tài khoản</strong><br>
                <?= $demoActive ? 'Đang dùng tài khoản thủ kho: ' . e($actor['ho_ten']) : 'Chọn tài khoản thủ kho để làm việc trên máy cá nhân.' ?></div>
            <?php if (!$actor || $demoActive): ?>
                <form method="post" action="index.php?page=stock-transfer" class="st-demo-form">
                    <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
                    <input type="hidden" name="operation" value="demo-actor">
                    <label for="st-demo-actor" class="st-sr-only">Tài khoản thủ kho</label>
                    <select name="demo_actor_id" id="st-demo-actor" required>
                        <option value="">Chọn thủ kho</option>
                        <?php foreach ($demoActors as $demoActor): ?>
                            <option value="<?= e($demoActor['tai_khoan_id']) ?>" <?= (string) ($actor['tai_khoan_id'] ?? '') === (string) $demoActor['tai_khoan_id'] ? 'selected' : '' ?>><?= e($demoActor['ho_ten']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button class="button st-button-light" <?= !$demoActors ? 'disabled' : '' ?>>Dùng tài khoản</button>
                </form>
                <?php if (!$demoActors): ?><p>Chưa có tài khoản thủ kho đang hoạt động trong cơ sở dữ liệu.</p><?php endif; ?>
            <?php endif; ?>
        </section>
    <?php endif; ?>
    <?php if ($error): ?><div class="notice st-error" role="alert"><?= e($error) ?></div><?php endif; ?>
    <?php if ($transfer): ?>
        <?php if ($saved ?? false): ?><div class="notice st-success" role="status">Lưu nháp thành công. Phiếu và chi tiết đã được lưu; tồn hai kho chưa thay đổi.</div><?php endif; ?>
        <section class="panel">
            <div class="panel-heading"><h2><?= e($transfer['ma_chuyen_kho']) ?></h2><a class="button button-primary" href="index.php?page=stock-transfer">Lập phiếu mới</a></div>
            <dl class="st-details">
                <div><dt>Kho xuất</dt><dd><?= e($transfer['kho_xuat']) ?></dd></div>
                <div><dt>Kho nhận</dt><dd><?= e($transfer['kho_nhap']) ?></dd></div>
                <div><dt>Ngày chuyển</dt><dd><?= e(date('d/m/Y', strtotime($transfer['ngay_chuyen']))) ?></dd></div>
                <div><dt>Người lập</dt><dd><?= e($transfer['nguoi_lap']) ?></dd></div>
            </dl>
            <p class="st-note"><strong>Ghi chú:</strong> <?= e($transfer['ghi_chu'] ?: 'Không có') ?></p>
        </section>
        <section class="panel">
            <h2>Chi tiết hàng chuyển</h2>
            <div class="table-wrap"><table>
                <thead><tr><th>Mã hàng</th><th>Tên hàng</th><th>Đơn vị</th><th class="st-number">Số lượng</th><th class="st-number">Đơn giá (đ)</th><th class="st-number">Thành tiền (đ)</th></tr></thead>
                <tbody><?php foreach ($transfer['lines'] as $line): ?><tr>
                    <td><?= e($line['ma_hang']) ?></td><td><?= e($line['ten_hang']) ?></td><td><?= e($line['ten_don_vi']) ?></td>
                    <td class="st-number"><?= e($quantityText($line['so_luong'])) ?></td>
                    <td class="st-number"><?= e($money($line['don_gia_ghi_nhan'])) ?></td><td class="st-number"><?= e($money($line['thanh_tien'])) ?></td>
                </tr><?php endforeach; ?></tbody>
            </table></div>
            <div class="st-summary"><span><?= count($transfer['lines']) ?> mặt hàng</span><div>Tổng giá trị tham chiếu <strong><?= e($money($transfer['tong_gia_tri'])) ?> đ</strong></div></div>
            <?php if ($transfer['trang_thai'] === 'NHAP'): ?><div class="notice notice-info">Phiếu đang ở trạng thái Nháp. Tồn kho chỉ thay đổi khi thực hiện chức năng xác nhận phiếu chuyển kho.</div><?php endif; ?>
        </section>
    <?php elseif ($actor && $source): ?>
        <form id="st-form" method="post" action="index.php?page=stock-transfer" autocomplete="off">
            <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
            <input type="hidden" name="submission_token" value="<?= e($submissionToken) ?>">
            <input type="hidden" name="operation" value="save-draft">
            <section class="panel">
                <div class="panel-heading"><h2><span class="st-step">1</span> Thông tin phiếu</h2><span class="muted">Người lập: <?= e($actor['ho_ten']) ?></span></div>
                <div class="st-fields">
                    <div><label for="st-source">Kho xuất</label><input id="st-source" value="<?= e($source['ten_kho']) ?>" readonly><small class="muted">Kho tổng cố định của nhà phân phối.</small></div>
                    <div><label for="st-destination">Kho nhận <span class="st-required">*</span></label>
                        <select id="st-destination" name="destination_id" required><option value="">Chọn kho NVBH nhận hàng</option>
                            <?php foreach ($destinations as $destination): ?><option value="<?= e($destination['kho_id']) ?>" <?= (string) $form['destination_id'] === (string) $destination['kho_id'] ? 'selected' : '' ?>><?= e($destination['ten_kho'] . ' · ' . $destination['ho_ten']) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div><label for="st-date">Ngày chuyển <span class="st-required">*</span></label><input id="st-date" type="date" min="1000-01-01" max="9999-12-31" name="transfer_date" value="<?= e($form['transfer_date']) ?>" required></div>
                    <div class="st-full"><label for="st-note">Ghi chú</label><textarea id="st-note" name="note" rows="2" maxlength="1000" placeholder="Nội dung điều chuyển, lưu ý giao nhận…"><?= e($form['note']) ?></textarea></div>
                </div>
            </section>
            <section class="panel">
                <div class="panel-heading"><h2><span class="st-step">2</span> Hàng hóa chuyển kho</h2><button id="st-add" class="button st-button-light" type="button">+ Thêm mặt hàng</button></div>
                <p class="muted">Nhập số lượng nguyên theo đơn vị nhỏ/cơ sở. Đơn giá ghi nhận là giá trị tham chiếu cho phiếu chuyển, không phải doanh thu.</p>
                <div id="st-client-error" class="notice st-error" role="alert" hidden></div>
                <div class="table-wrap"><table class="st-lines-table">
                    <thead><tr><th>Mặt hàng <span class="st-required">*</span></th><th>Đơn vị</th><th class="st-number">Tồn Kho tổng</th><th>Số lượng <span class="st-required">*</span></th><th>Đơn giá (đ) <span class="st-required">*</span></th><th class="st-number">Thành tiền (đ)</th><th></th></tr></thead>
                    <tbody id="st-lines"><?php foreach ($form['lines'] as $index => $line) { $renderLine($line, (string) $index); } ?></tbody>
                </table></div>
                <?php if (!$destinations): ?><div class="notice st-error">Chưa có kho NVBH đang hoạt động trong cùng nhà phân phối. Hãy bổ sung danh mục trước khi lập phiếu.</div><?php endif; ?>
                <?php if (!$products): ?><div class="notice st-error">Chưa có hàng hóa và dữ liệu tồn tại Kho tổng để lập phiếu.</div><?php endif; ?>
                <div class="st-summary"><span id="st-count" aria-live="polite">0 mặt hàng</span><div>Tổng giá trị tham chiếu <strong id="st-total" aria-live="polite">0,00 đ</strong></div></div>
            </section>
            <div class="st-actions"><p class="muted">Lưu nháp chưa trừ kho xuất và chưa cộng kho nhận.</p><div><a id="st-exit" class="button st-button-light" href="index.php?page=dashboard">Thoát</a><button id="st-save" class="button button-primary" type="submit" <?= !$products || !$destinations ? 'disabled' : '' ?>>Lưu nháp</button></div></div>
        </form>
        <template id="st-row-template"><?php $renderLine(['product_id' => '', 'quantity' => '', 'unit_price' => ''], '__INDEX__'); ?></template>
        <noscript><div class="notice notice-info">Bật JavaScript để thêm nhiều dòng hàng và xem tổng tiền trước khi lưu. Server vẫn kiểm tra dữ liệu và tính tiền khi lưu.</div></noscript>
        <script src="assets/js/stock-transfer.js" defer></script>
    <?php else: ?>
        <section class="panel"><h2>Chưa thể lập phiếu</h2><p class="muted">Cần tài khoản thủ kho hợp lệ và dữ liệu kho, hàng hóa được thiết lập trước khi lập phiếu.</p><a class="button st-button-light" href="index.php?page=dashboard">Về tổng quan</a></section>
    <?php endif; ?>
</div>
