
<?php
$fromDate = $fromDate ?? date('Y-m-01');
$toDate = $toDate ?? date('Y-m-d');
$reports = $reports ?? [];
$error = $error ?? null;
$employees = $employees ?? [];
$employeeId = $employeeId ?? null;

// Tính tổng doanh số và tổng số hóa đơn.
$tongDoanhSo = array_sum(array_column($reports, 'doanh_so'));
$tongHoaDon = array_sum(array_column($reports, 'so_hoa_don'));
?>

<section class="page-header">
    <div>
        <p class="eyebrow">MANAGEMENT</p>
        <h1>Báo cáo doanh số theo nhân viên</h1>
        <p class="page-description">
            Theo dõi doanh số từ các hóa đơn đã phát hành.
        </p>
    </div>
</section>

<section class="panel">
    <h2>Bộ lọc báo cáo</h2>

    <form class="filter-form" method="GET">

        <!-- Giữ đúng trang báo cáo khi gửi bộ lọc -->
        <input type="hidden" name="page" value="sales-report">

        <!-- Lọc từ ngày -->
        <div>
            <label for="from-date">Từ ngày</label>
            <input
                id="from-date"
                type="date"
                name="from_date"
                value="<?= htmlspecialchars((string)$fromDate, ENT_QUOTES, 'UTF-8') ?>"
                required
            >
        </div>

        <!-- Lọc đến ngày -->
        <div>
            <label for="to-date">Đến ngày</label>
            <input
                id="to-date"
                type="date"
                name="to_date"
                value="<?= htmlspecialchars((string)$toDate, ENT_QUOTES, 'UTF-8') ?>"
                required
            >
        </div>

        <!-- Lọc theo nhân viên bán hàng -->
        <div>
            <label for="employee-id">Nhân viên bán hàng</label>

            <select id="employee-id" name="employee_id">
                <option value="">Tất cả nhân viên</option>

                <?php foreach ($employees as $employee): ?>
                    <option
                        value="<?= (int)$employee['nhan_vien_id'] ?>"
                        <?= $employeeId !== null
                            && (int)$employeeId === (int)$employee['nhan_vien_id']
                            ? 'selected'
                            : '' ?>
                    >
                        <?= htmlspecialchars(
                            (string)$employee['ma_nhan_vien']
                            . ' - '
                            . (string)$employee['ho_ten'],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Nút xem báo cáo -->
        <button class="button button-primary" type="submit">
            Xem báo cáo
        </button>
    </form>

    <!-- Hiển thị thông báo lỗi -->
    <?php if ($error): ?>
        <div class="notice notice-info">
            <?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?>
        </div>

    <!-- Trường hợp không có dữ liệu -->
    <?php elseif (empty($reports)): ?>
        <div class="empty-state">
            Không có hóa đơn đã phát hành trong khoảng thời gian
            và nhân viên đã chọn.
        </div>

    <!-- Hiển thị kết quả báo cáo -->
    <?php else: ?>
        <h2>Kết quả báo cáo</h2>

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

                            <td>
                                <?= htmlspecialchars(
                                    (string)$report['ma_nhan_vien'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    (string)$report['ho_ten'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </td>

                            <td>
                                <?= number_format(
                                    (float)$report['so_hoa_don'],
                                    0,
                                    ',',
                                    '.'
                                ) ?>
                            </td>

                            <td>
                                <?= number_format(
                                    (float)$report['doanh_so'],
                                    0,
                                    ',',
                                    '.'
                                ) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>

                <!-- Tổng cộng -->
                <tfoot>
                    <tr>
                        <th colspan="3">Tổng cộng</th>

                        <th>
                            <?= number_format(
                                (float)$tongHoaDon,
                                0,
                                ',',
                                '.'
                            ) ?>
                        </th>

                        <th>
                            <?= number_format(
                                (float)$tongDoanhSo,
                                0,
                                ',',
                                '.'
                            ) ?>
                        </th>
                    </tr>
                </tfoot>
            </table>
        </div>
    <?php endif; ?>
</section>
