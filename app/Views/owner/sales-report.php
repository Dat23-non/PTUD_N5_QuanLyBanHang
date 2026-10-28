<?php
// Dữ liệu bộ lọc.
$fromDate = $fromDate ?? date('Y-m-01');
$toDate = $toDate ?? date('Y-m-d');
$employeeId = $employeeId ?? null;
$areaId = $areaId ?? null;

// Dữ liệu từ Controller.
$employees = $employees ?? [];
$areas = $areas ?? [];
$reports = $reports ?? [];
$invoiceDetails = $invoiceDetails ?? [];
$error = $error ?? null;

// Số liệu tổng hợp và đối chiếu.
$totalRevenue = $totalRevenue ?? 0;
$totalInvoices = $totalInvoices ?? 0;
$detailRevenue = $detailRevenue ?? 0;
$isReconciled = $isReconciled ?? false;

// Hàm hiển thị dữ liệu an toàn.
$escape = static function ($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
};

// Hàm định dạng tiền VNĐ.
$formatMoney = static function ($value): string {
    return number_format((float)$value, 0, ',', '.');
};
?>

<section class="page-header">
    <div>
        <p class="eyebrow">MANAGEMENT</p>
        <h1>Báo cáo doanh số theo nhân viên</h1>
        <p class="page-description">
            Theo dõi doanh số từ các hóa đơn đã phát hành,
            lọc theo thời gian, nhân viên và khu vực bán hàng.
        </p>
    </div>
</section>

<section class="panel">
    <h2>Bộ lọc báo cáo</h2>

    <form class="filter-form" method="GET">
        <!-- Giữ đúng trang báo cáo -->
        <input type="hidden" name="page" value="sales-report">

        <div>
            <label for="from-date">Từ ngày</label>
            <input
                id="from-date"
                type="date"
                name="from_date"
                value="<?= $escape($fromDate) ?>"
                required
            >
        </div>

        <div>
            <label for="to-date">Đến ngày</label>
            <input
                id="to-date"
                type="date"
                name="to_date"
                value="<?= $escape($toDate) ?>"
                required
            >
        </div>

        <div>
            <label for="employee-id">Nhân viên bán hàng</label>
            <select id="employee-id" name="employee_id">
                <option value="">Tất cả nhân viên</option>

                <?php foreach ($employees as $employee): ?>
                    <option
                        value="<?= (int)$employee['nhan_vien_id'] ?>"
                        <?= $employeeId !== null
                            && (int)$employeeId === (int)$employee['nhan_vien_id']
                            ? 'selected' : '' ?>
                    >
                        <?= $escape(
                            $employee['ma_nhan_vien']
                            . ' - '
                            . $employee['ho_ten']
                        ) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Bộ lọc khu vực mới -->
        <div>
            <label for="area-id">Khu vực bán hàng</label>
            <select id="area-id" name="area_id">
                <option value="">Tất cả khu vực</option>

                <?php foreach ($areas as $area): ?>
                    <option
                        value="<?= (int)$area['khu_vuc_id'] ?>"
                        <?= $areaId !== null
                            && (int)$areaId === (int)$area['khu_vuc_id']
                            ? 'selected' : '' ?>
                    >
                        <?= $escape(
                            $area['ma_khu_vuc']
                            . ' - '
                            . $area['ten_khu_vuc']
                        ) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <button class="button button-primary" type="submit">
            Xem báo cáo
        </button>
    </form>

    <?php if ($error): ?>
        <div class="notice notice-info">
            <?= $escape($error) ?>
        </div>

    <?php elseif (empty($reports)): ?>
        <div class="empty-state">
            Không có hóa đơn đã phát hành phù hợp với
            khoảng thời gian, nhân viên và khu vực đã chọn.
        </div>

    <?php else: ?>
        <h2>Báo cáo tổng hợp theo nhân viên</h2>

        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>STT</th>
                        <th>Mã nhân viên</th>
                        <th>Họ tên nhân viên</th>
                        <th>Số hóa đơn</th>
                        <th>Doanh số (VNĐ)</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($reports as $index => $report): ?>
                        <tr>
                            <td><?= $index + 1 ?></td>
                            <td><?= $escape($report['ma_nhan_vien']) ?></td>
                            <td><?= $escape($report['ho_ten']) ?></td>
                            <td>
                                <?= (int)$report['so_hoa_don'] ?>
                            </td>
                            <td>
                                <?= $formatMoney($report['doanh_so']) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>

                <tfoot>
                    <tr>
                        <th colspan="3">Tổng cộng</th>
                        <th><?= (int)$totalInvoices ?></th>
                        <th><?= $formatMoney($totalRevenue) ?></th>
                    </tr>
                </tfoot>
            </table>
        </div>

        <h2>Chi tiết hóa đơn bán hàng</h2>

        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>STT</th>
                        <th>Số hóa đơn</th>
                        <th>Ngày phát hành</th>
                        <th>Nhân viên</th>
                        <th>Khách hàng</th>
                        <th>Khu vực</th>
                        <th>Trạng thái</th>
                        <th>Thành tiền (VNĐ)</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($invoiceDetails as $index => $invoice): ?>
                        <tr>
                            <td><?= $index + 1 ?></td>
                            <td><?= $escape($invoice['so_hoa_don']) ?></td>
                            <td>
                                <?= $escape($invoice['ngay_phat_hanh']) ?>
                            </td>
                            <td>
                                <?= $escape(
                                    $invoice['ma_nhan_vien']
                                    . ' - '
                                    . $invoice['ho_ten']
                                ) ?>
                            </td>
                            <td>
                                <?= $escape(
                                    $invoice['ma_khach_hang']
                                    . ' - '
                                    . $invoice['ten_khach_hang']
                                ) ?>
                            </td>
                            <td>
                                <?= $escape($invoice['ten_khu_vuc'] ?? 'Chưa xác định') ?>
                            </td>
                            <td>
                                <?= $escape($invoice['trang_thai']) ?>
                            </td>
                            <td>
                                <?= $formatMoney($invoice['tong_thanh_toan']) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>

                <tfoot>
                    <tr>
                        <th colspan="7">Tổng doanh số chi tiết</th>
                        <th><?= $formatMoney($detailRevenue) ?></th>
                    </tr>
                </tfoot>
            </table>
        </div>

        <h2>Đối chiếu doanh số</h2>

        <div class="table-responsive">
            <table class="data-table">
                <tbody>
                    <tr>
                        <th>Tổng doanh số theo nhân viên</th>
                        <td><?= $formatMoney($totalRevenue) ?> VNĐ</td>
                    </tr>
                    <tr>
                        <th>Tổng doanh số theo hóa đơn</th>
                        <td><?= $formatMoney($detailRevenue) ?> VNĐ</td>
                    </tr>
                    <tr>
                        <th>Chênh lệch</th>
                        <td>
                            <?= $formatMoney(
                                abs($totalRevenue - $detailRevenue)
                            ) ?> VNĐ
                        </td>
                    </tr>
                    <tr>
                        <th>Kết quả đối chiếu</th>
                        <td>
                            <?php if ($isReconciled): ?>
                                <strong>Khớp số liệu</strong>
                            <?php else: ?>
                                <strong>Có chênh lệch - cần rà soát</strong>
                            <?php endif; ?>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

    <?php endif; ?>
</section>
