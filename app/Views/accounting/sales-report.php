<?php
// app/Views/accounting/sales-report.php
?>
<!-- Font Awesome hiển thị icon -->
<link rel="stylesheet" href="https://cloudflare.com">

<div style="background-color: #f8f9fa; padding: 24px; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; width: 100%; box-sizing: border-box; min-height: 100vh;">

    <!-- TIÊU ĐỀ TRANG VÀ NÚT CHỨC NĂNG -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 12px; width: 100%; box-sizing: border-box;">
        <div>
            <span style="font-size: 0.72rem; font-weight: 700; color: #0d6efd; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 4px;">
                📊 BÁO CÁO HỆ THỐNG
            </span>
            <h2 style="font-weight: 700; color: #212529; margin: 0; font-size: 1.75rem;">Báo cáo công nợ khách hàng</h2>
            <p style="color: #6c757d; margin: 4px 0 0 0; font-size: 0.875rem;">Chi tiết tình hình công nợ, phát sinh và thu hồi nợ theo từng đối tượng khách hàng.</p>
        </div>
        <div style="display: flex; gap: 10px;">
            <button onclick="window.print()" style="background-color: #ffffff; color: #212529; border: 1px solid #ced4da; border-radius: 6px; padding: 8px 16px; font-weight: 600; font-size: 0.85rem; cursor: pointer; display: flex; align-items: center; gap: 6px; box-shadow: 0 2px 4px rgba(0,0,0,0.02);">
                <i class="fas fa-print" style="color: #6c757d;"></i> In báo cáo
            </button>
            <button
    type="button"
    onclick="
        const url = new URL(window.location.href);
        url.searchParams.set('export','csv');
        window.location.href = url.toString();
    "
    style="background-color: #0d6efd;
           color: #ffffff;
           border: none;
           border-radius: 6px;
           padding: 8px 16px;
           font-weight: 600;
           font-size: 0.85rem;
           cursor: pointer;
           display: flex;
           align-items: center;
           gap: 6px;
           box-shadow: 0 2px 4px rgba(13,110,253,0.15);">
    <i class="fas fa-download"></i>
    Xuất dữ liệu
</button>

        </div>
    </div>

    <!-- KHỐI 1: ĐIỀU KIỆN LỌC BÁO CÁO -->
    <div style="background: #ffffff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.04); margin-bottom: 24px; border: 1px solid #e9ecef; width: 100%; box-sizing: border-box;">
        <h6 style="margin: 0 0 16px 0; font-weight: 700; color: #212529; text-transform: uppercase; font-size: 0.8rem; letter-spacing: 0.5px;">
            🔍 ĐIỀU KIỆN LỌC BÁO CÁO
        </h6>
        <form method="GET" action="" style="display: flex; align-items: flex-end; gap: 12px; margin: 0; flex-wrap: wrap; width: 100%; box-sizing: border-box;">
            <input type="hidden" name="page" value="payment-reconciliation">
            
            <div style="flex: 1; min-width: 130px;">
                <label style="display: block; font-size: 0.75rem; font-weight: 600; color: #495057; margin-bottom: 6px;">Từ ngày</label>
                <input type="date" name="tu_ngay"
    style="width: 100%; height: 36px; text-align: center; border: 1px solid #ced4da; border-radius: 6px; background-color: #f8f9fa; color: #495057; font-size: 0.85rem; box-sizing: border-box;"
    value="<?= !empty($filters['tu_ngay']) ? $filters['tu_ngay'] : date('Y-m-01') ?>"></div>
            
            <div style="flex: 1; min-width: 130px;">
    <label style="display: block; font-size: 0.75rem; font-weight: 600; color: #495057; margin-bottom: 6px;">
        Đến ngày
    </label>

    <input
    type="date"
    name="den_ngay"
    style="width: 100%; height: 36px; text-align: center; border: 1px solid #ced4da; border-radius: 6px; background-color: #f8f9fa; color: #495057; font-size: 0.85rem; box-sizing: border-box;"
    value="<?= date('Y-m-d') ?>"
>
</div>
            
            <div style="flex: 1; min-width: 140px;">
    <label style="display: block; font-size: 0.75rem; font-weight: 600; color: #495057; margin-bottom: 6px;">
        Mã khách hàng
    </label>

    <input
        type="text"
        name="ma_kh"
        style="width: 100%; height: 36px; border: 1px solid #ced4da; border-radius: 6px; padding: 0 12px; font-size: 0.85rem; box-sizing: border-box;"
        placeholder="KH001..."
        value="<?= htmlspecialchars($filters['ma_kh'] ?? '') ?>"
    >
</div>
            
            <div style="flex: 1.5; min-width: 160px;">
    <label style="display: block; font-size: 0.75rem; font-weight: 600; color: #495057; margin-bottom: 6px;">
        Tên khách hàng
    </label>

    <input
        type="text"
        name="ten_kh"
        style="width: 100%; height: 36px; border: 1px solid #ced4da; border-radius: 6px; padding: 0 12px; font-size: 0.85rem; box-sizing: border-box;"
        placeholder="Nhập tên..."
        value="<?= htmlspecialchars($filters['ten_kh'] ?? '') ?>"
    >
</div>

            
            <div style="flex: 1.2; min-width: 140px;">
                <label style="display: block; font-size: 0.75rem; font-weight: 600; color: #495057; margin-bottom: 6px;">Trạng thái nợ</label>
                <select name="trang_thai" style="width: 100%; height: 36px; border: 1px solid #ced4da; border-radius: 6px; padding: 0 12px; background-color: #ffffff; font-size: 0.85rem; cursor: pointer; box-sizing: border-box;">
                    <option value="tat_ca" <?= ($filters['trang_thai'] ?? '') === 'tat_ca' ? 'selected' : '' ?>>Tất cả</option>
                    <option value="trong_han" <?= ($filters['trang_thai'] ?? '') === 'trong_han' ? 'selected' : '' ?>>Nợ trong hạn</option>
                    <option value="qua_han" <?= ($filters['trang_thai'] ?? '') === 'qua_han' ? 'selected' : '' ?>>Nợ quá hạn</option>
                </select>
            </div>
            
            <div style="display: flex; gap: 8px; align-items: flex-end;">
                <button type="submit" style="height: 36px; background-color: #0d6efd; color: #ffffff; border: none; border-radius: 6px; padding: 0 20px; font-weight: 600; font-size: 0.85rem; cursor: pointer; white-space: nowrap;">
                    Xem báo cáo
                </button>
                <button type="button" onclick="location.reload();" style="width: 36px; height: 36px; background-color: #ffffff; border: 1px solid #ced4da; border-radius: 6px; cursor: pointer; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-filter" style="color: #6c757d; font-size: 0.85rem;"></i>
                </button>
            </div>
        </form>
    </div>


        <!-- KHỐI 2: 4 Ô THỐNG KÊ CHI TIẾT SỐ TIỀN (ĐÃ FIX LỖI THẺ ĐÓNG DIV) -->
    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px; width: 100%; box-sizing: border-box;">
        <!-- Ô 1: TỔNG NỢ ĐẦU KỲ -->
        <div style="background: #ffffff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.04); border-left: 4px solid #0d6efd; display: flex; justify-content: space-between; align-items: flex-start; box-sizing: border-box;">
            <div>
                <span style="font-size: 0.72rem; font-weight: 700; color: #6c757d; text-transform: uppercase;">TỔNG NỢ ĐẦU KỲ</span>
                <h3 style="font-weight: 700; color: #212529; margin: 8px 0 4px 0; font-size: 1.35rem;"><?= number_format($tongNoDauKy ?? 0, 0, ',', '.') ?> đ</h3>
                <span style="font-size: 0.75rem; color: #6c757d;">📉 Dữ liệu kỳ trước</span>
            </div>
            <div style="background-color: #e7f1ff; padding: 10px; border-radius: 50%; display: flex; align-items: center; justify-content: center; width: 38px; height: 38px; flex-shrink: 0;">
                <i class="far fa-clock" style="color: #0d6efd; font-size: 1.1rem;"></i>
            </div>
        </div>

        <!-- Ô 2: PHÁT SINH TRONG KỲ -->
        <div style="background: #ffffff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.04); border-left: 4px solid #0dcaf0; display: flex; justify-content: space-between; align-items: flex-start; box-sizing: border-box;">
            <div>
                <span style="font-size: 0.72rem; font-weight: 700; color: #6c757d; text-transform: uppercase;">PHÁT SINH TRONG KỲ</span>
                <h3 style="font-weight: 700; color: #0dcaf0; margin: 8px 0 4px 0; font-size: 1.35rem;"><?= number_format($tongPhatSinh ?? 0, 0, ',', '.') ?> đ</h3>
                <span style="font-size: 0.75rem; color: #6c757d;"># Hóa đơn mới phát sinh</span>
            </div>
            <div style="background-color: #e0fbfd; padding: 10px; border-radius: 50%; display: flex; align-items: center; justify-content: center; width: 38px; height: 38px; flex-shrink: 0;">
                <i class="fas fa-chart-line" style="color: #0dcaf0; font-size: 1.1rem;"></i>
            </div>
        </div>
        

        <!-- Ô 3: ĐÃ THU -->
        <div style="background: #ffffff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.04); border-left: 4px solid #198754; display: flex; justify-content: space-between; align-items: flex-start; box-sizing: border-box;">
            <div>
                <span style="font-size: 0.72rem; font-weight: 700; color: #6c757d; text-transform: uppercase;">ĐÃ THU</span>
                <h3 style="font-weight: 700; color: #198754; margin: 8px 0 4px 0; font-size: 1.35rem;"><?= number_format($tongDaThu ?? 0, 0, ',', '.') ?> đ</h3>
                <span style="font-size: 0.75rem; color: #198754;">© Thanh toán hoàn tất</span>
            </div>
            <div style="background-color: #e8f5e9; padding: 10px; border-radius: 50%; display: flex; align-items: center; justify-content: center; width: 38px; height: 38px; flex-shrink: 0;">
                <i class="far fa-check-circle" style="color: #198754; font-size: 1.1rem;"></i>
            </div>
        </div>

        <!-- Ô 4: TỔNG NỢ CUỐI KỲ -->
        <div style="background: #ffffff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.04); border-left: 4px solid #dc3545; display: flex; justify-content: space-between; align-items: flex-start; box-sizing: border-box;">
            <div>
                <span style="font-size: 0.72rem; font-weight: 700; color: #6c757d; text-transform: uppercase;">TỔNG NỢ CUỐI KỲ</span>
                <h3 style="font-weight: 700; color: #dc3545; margin: 8px 0 4px 0; font-size: 1.35rem;"><?= number_format($tongNoCuoiKy ?? 0, 0, ',', '.') ?> đ</h3>
                <span style="font-size: 0.75rem; color: #6c757d;">⚠️ Dự kiến thu hồi</span>
            </div>
            <div style="background-color: #f8d7da; padding: 10px; border-radius: 50%; display: flex; align-items: center; justify-content: center; width: 38px; height: 38px; flex-shrink: 0;">
                <i class="fas fa-wallet" style="color: #dc3545; font-size: 1.1rem;"></i>
            </div>
        </div>
    </div>

    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:24px;">

    <div style="background:#fff;padding:18px;border-radius:8px;border:1px solid #e9ecef;">
        <div style="font-size:12px;color:#6c757d;font-weight:600;">
            KHÁCH HÀNG
        </div>
        <div style="font-size:28px;font-weight:700;color:#212529;margin-top:6px;">
            <?= count($danhSachCongNo ?? []) ?>
        </div>
    </div>

    <div style="background:#fff;padding:18px;border-radius:8px;border:1px solid #e9ecef;">
        <div style="font-size:12px;color:#6c757d;font-weight:600;">
            KHÁCH HÀNG CÒN NỢ
        </div>
        <div style="font-size:28px;font-weight:700;color:#dc3545;margin-top:6px;">
            <?= count(array_filter($danhSachCongNo,function($r){
                return ($r['no_cuoi_ky'] ?? 0) > 0;
            })) ?>
        </div>
    </div>

    <div style="background:#fff;padding:18px;border-radius:8px;border:1px solid #e9ecef;">
        <div style="font-size:12px;color:#6c757d;font-weight:600;">
            TỶ LỆ THU HỒI
        </div>
        <div style="font-size:28px;font-weight:700;color:#198754;margin-top:6px;">
            <?= ($tongPhatSinh ?? 0) > 0
                ? round(($tongDaThu/$tongPhatSinh)*100,1)
                : 0 ?>%
        </div>
    </div>

    <div style="background:#fff;padding:18px;border-radius:8px;border:1px solid #e9ecef;">
        <div style="font-size:12px;color:#6c757d;font-weight:600;">
            NỢ CẦN THU
        </div>
        <div style="font-size:28px;font-weight:700;color:#fd7e14;margin-top:6px;">
            <?= number_format($tongNoCuoiKy ?? 0,0,',','.') ?>
        </div>
    </div>

</div>

<?php
$khQuaHan = count(array_filter($danhSachCongNo ?? [], function($row){
    return ($row['trang_thai'] ?? '') === 'qua_han';
}));
?>

<div style="
background:#fff3cd;
border:1px solid #ffe69c;
padding:15px 18px;
border-radius:8px;
margin-bottom:20px;
font-size:14px;
color:#856404;
font-weight:600;
">
⚠️ Hiện có <?= $khQuaHan ?> khách hàng đang quá hạn thanh toán.
</div>

    <!-- KHỐI 3: BẢNG CHI TIẾT DANH SÁCH CÔNG NỢ KHÁCH HÀNG -->
    <div id="print-section" style="background: #ffffff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.04); border: 1px solid #e9ecef; margin-bottom: 24px; width: 100%; box-sizing: border-box;">
        <div style="padding: 16px 20px; border-bottom: 1px solid #e9ecef; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px;">
            <h5 style="margin: 0; font-weight: 700; color: #212529; font-size: 1.05rem; display: flex; align-items: center; gap: 8px;">
                Bảng chi tiết công nợ 
                <span style="background-color: #e9ecef; color: #495057; font-size: 0.7rem; padding: 2px 8px; border-radius: 10px; font-weight: normal;">
                    <?= count($danhSachCongNo ?? []) ?> khách hàng
                </span>
            </h5>
            <small style="color: #6c757d; font-weight: 600; font-size: 0.8rem;">
                Kỳ báo cáo: 
                <span style="font-family: monospace; color: #212529;"><?= htmlspecialchars($filters['tu_ngay']) ?></span> 
                đến 
                <span style="font-family: monospace; color: #212529;"><?= htmlspecialchars($filters['den_ngay']) ?></span>
            </small>
        </div>

        <div style="width: 100%; overflow-x: auto;">
            <?php
$tongNoDauKy = 0;
$tongPhatSinh = 0;
$tongDaThu = 0;
$tongNoCuoiKy = 0;
?>
            <table style="width: 100%; border-collapse: collapse; text-align: center; white-space: nowrap;">
                <thead>
    <tr style="background-color: #f8f9fa; font-size: 0.75rem; text-transform: uppercase; color: #6c757d; font-family: monospace; border-bottom: 1px solid #e9ecef;">

        <th style="padding: 12px 16px; width: 5%; text-align:center;">
            STT
        </th>

        <th style="padding: 12px 16px; width: 9%;">
            Mã KH
        </th>

        <th style="padding: 12px 16px; text-align: left; width: 22%;">
            Tên khách hàng
        </th>

        <th style="padding: 12px 16px;">
            Nợ đầu kỳ
        </th>

        <th style="padding: 12px 16px;">
            Phát sinh
        </th>

        <th style="padding: 12px 16px;">
            Đã thu
        </th>

        <th style="padding: 12px 16px;">
            Nợ cuối kỳ
        </th>

        <th style="padding: 12px 16px;">
            Hạn thanh toán
        </th>

        <th style="padding: 12px 16px;">
            Trạng thái
        </th>
<th style="padding:12px 16px;">
    Chi tiết
</th>
    </tr>
</thead>
                <tbody style="font-size: 0.85rem; color: #212529;">
                    <?php if (!empty($danhSachCongNo)): ?>

    <?php $stt = 1; ?>

<?php foreach ($danhSachCongNo as $row): ?>
    
    <?php
$han = strtotime($row['han_thanh_toan']);
$today = time();
$soNgayTre = max(0, floor(($today - $han) / 86400));
?>

    <?php
    $tongNoDauKy += $row['no_dau_ky'];
    $tongPhatSinh += $row['phat_sinh'];
    $tongDaThu += $row['da_thu'];
    $tongNoCuoiKy += $row['no_cuoi_ky'];
    ?>

                            <tr style="
border-bottom:1px solid #f1f3f5;
<?= $row['no_cuoi_ky'] > 100000000
    ? 'background:#fff8e1;'
    : ''
?>
">

    <td style="
        padding: 14px 16px;
        text-align:center;
        font-weight:bold;
        color:#6c757d;
        ">
        <?= $stt++ ?>
    </td>

    <td style="padding: 14px 16px; font-weight: bold; color: #0d6efd; font-family: monospace;">
        <?= htmlspecialchars($row['ma_kh']) ?>
    </td>
                                <td style="padding: 14px 16px; text-align: left;">
                                    <div style="font-weight: bold; color: #212529; margin-bottom: 2px;">
                                        <?= htmlspecialchars($row['ten_kh']) ?>
                                    </div>
                                    <span style="color: #6c757d; font-family: monospace; font-size: 0.72rem; display: block;">
                                        Mã tham chiếu: ACC-<?= htmlspecialchars($row['ma_kh']) ?>
                                    </span>
                                </td>
                                <td style="padding: 14px 16px; color: #6c757d;">
                                    <?= number_format($row['no_dau_ky'], 0, ',', '.') ?> đ
                                </td>
                                <td style="padding: 14px 16px; color: #0d6efd; font-weight: bold;">
                                    <?= number_format($row['phat_sinh'], 0, ',', '.') ?> đ
                                </td>
                                <td style="padding: 14px 16px; color: #198754; font-weight: bold;">
                                    <?= number_format($row['da_thu'], 0, ',', '.') ?> đ
                                </td>
                                <td style="padding: 14px 16px; color: #dc3545; font-weight: bold;">
                                    <?= number_format($row['no_cuoi_ky'], 0, ',', '.') ?> đ
                                </td>
                                <td style="padding: 14px 16px; font-family: monospace; color: #495057; font-size: 0.8rem;">
                                    <i class="far fa-calendar-alt" style="color: #6c757d; margin-right: 4px;"></i> <?= htmlspecialchars($row['han_thanh_toan'] ?? '2024-05-15') ?>
                                </td>
                                <td style="padding: 14px 16px;">

    <?php if ($row['trang_thai'] === 'trong_han'): ?>

        <span style="
            background-color: #d1e7dd;
            color: #0f5132;
            border: 1px solid #badbcc;
            padding: 4px 10px;
            border-radius: 50px;
            font-size: 0.72rem;
            font-weight: 600;
        ">
            Nợ trong hạn
        </span>

    <?php else: ?>

        <span style="
            background-color: #f8d7da;
            color: #842029;
            border: 1px solid #f5c2c7;
            padding: 4px 10px;
            border-radius: 50px;
            font-size: 0.72rem;
            font-weight: 600;
        ">
            Quá hạn <?= $soNgayTre ?> ngày
        </span>

    <?php endif; ?>

</td>

<td style="padding:14px 16px;text-align:center;">

    <button
        type="button"
        onclick="toggleDetail('detail<?= $stt ?>')"
        style="
            background:#0d6efd;
            color:#fff;
            border:none;
            border-radius:6px;
            padding:6px 12px;
            cursor:pointer;
        ">
        Xem
    </button>

</td>
                            </tr>

<tr id="detail<?= $stt ?>" style="display:none;">

<td colspan="10"
    style="
        background:#f8f9fa;
        padding:20px;
        border-left:4px solid #0d6efd;
    ">

    <div style="
        display:grid;
        grid-template-columns:repeat(3,1fr);
        gap:24px;
    ">

        <div>

            <h4 style="margin-bottom:12px;">
                Thông tin khách hàng
            </h4>

            <p>
                <strong>Mã KH:</strong>
                <?= htmlspecialchars($row['ma_kh']) ?>
            </p>

            <p>
                <strong>Tên KH:</strong>
                <?= htmlspecialchars($row['ten_kh']) ?>
            </p>

            <p>
                <strong>Chính sách nợ:</strong>
                <?= htmlspecialchars($row['han_thanh_toan']) ?>
            </p>

        </div>


        <div>

            <h4 style="margin-bottom:12px;">
                Căn cứ thu hồi công nợ
            </h4>

            <p>
                <strong>Tổng đã thu:</strong>
                <?= number_format($row['da_thu'],0,',','.') ?> đ
            </p>

            <p>
    <strong>Số ngày công nợ:</strong>
    <?= $row['so_ngay_no'] ?> ngày
</p>
<p>
                <strong>Ngày lập hóa đơn đầu tiên:</strong>
                <?= !empty($row['ngay_phat_sinh_dau_tien'])
                    ? date('d/m/Y', strtotime($row['ngay_phat_sinh_dau_tien']))
                    : 'Không có dữ liệu' ?>
            </p>
<p>
    <strong>Ngày hóa đơn gần nhất:</strong>
    <?= !empty($row['ngay_hoa_don_gan_nhat'])
        ? date('d/m/Y', strtotime($row['ngay_hoa_don_gan_nhat']))
        : 'Không có dữ liệu'
    ?>
</p>

<p>
    <strong>Ngày đến hạn:</strong>
    <?= !empty($row['ngay_den_han'])
        ? date('d/m/Y', strtotime($row['ngay_den_han']))
        : 'Chưa xác định'
    ?>
</p>

<p>
    <strong>Ngày thu gần nhất:</strong>
    <?= !empty($row['ngay_thu_gan_nhat'])
        ? date('d/m/Y', strtotime($row['ngay_thu_gan_nhat']))
        : 'Chưa phát sinh'
    ?>
</p>

<p>
    <strong>Số ngày quá hạn:</strong>
    <?= $row['so_ngay_tre'] ?> ngày
</p>

<p>
    <strong>Tổng số hóa đơn:</strong>
    <?= $row['tong_hoa_don'] ?>
</p>

        </div>

    </div>

    <hr style="margin:15px 0;">

    <div>

        <strong>Diễn giải công nợ:</strong>

        <br><br>

        Nợ đầu kỳ:
        <strong>
            <?= number_format($row['no_dau_ky'],0,',','.') ?> đ
        </strong>

        +

        Phát sinh từ hóa đơn:
        <strong>
            <?= number_format($row['phat_sinh'],0,',','.') ?> đ
        </strong>

        -

        Đã thu từ phiếu thu:
        <strong>
            <?= number_format($row['da_thu'],0,',','.') ?> đ
        </strong>

        =

        <span style="color:#dc3545;font-weight:bold;">
            <?= number_format($row['no_cuoi_ky'],0,',','.') ?> đ
        </span>

    </div>

</td>

</tr>
</tr>

<?php $stt++; ?>
                        <?php endforeach; ?>
                        <tr style="
    background:#f8f9fa;
    border-top:2px solid #dee2e6;
    font-weight:bold;
">

    <td></td>

    <td colspan="2">
        TỔNG CỘNG
    </td>

    <td>
        <?= number_format($tongNoDauKy,0,',','.') ?> đ
    </td>

    <td>
        <?= number_format($tongPhatSinh,0,',','.') ?> đ
    </td>

    <td>
        <?= number_format($tongDaThu,0,',','.') ?> đ
    </td>

    <td>
        <?= number_format($tongNoCuoiKy,0,',','.') ?> đ
    </td>

    <td></td>

    <td></td>

    <td></td>

</tr>
                    <?php else: ?>
                        <tr>
                            <td colspan="10" style="padding: 30px; color: #6c757d; text-align: center;">
                                Không tìm thấy dữ liệu công nợ nào phù hợp với bộ lọc.
                            </td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
</table>

<script>
function toggleDetail(id)
{
    const row = document.getElementById(id);

    if (row.style.display === 'none')
    {
        row.style.display = 'table-row';
    }
    else
    {
        row.style.display = 'none';
    }
}
</script>
                </tbody>
            </table>
        </div>
        
        <div style="padding: 14px 20px; border-top: 1px solid #e9ecef; display: flex; justify-content: space-between; align-items: center; font-size: 0.8rem; color: #6c757d; flex-wrap: wrap; gap: 8px;">
            <div style="display: flex; gap: 20px; align-items: center;">
                <span><span style="color: #198754; font-size: 1rem; margin-right: 4px;">●</span> Dữ liệu đã đối soát</span>
                <span><span style="color: #dc3545; font-size: 1rem; margin-right: 4px;">●</span> Cần thu hồi gấp</span>
            </div>
            <div>Báo cáo được khởi tạo lúc: <strong><?= date('H:i:s d/m/Y') ?></strong></div>
        </div>
    </div>

    <!-- KHỐI 4: 2 Ô GHI CHÚ NGHIỆP VỤ Ở CHÂN TRANG -->
    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; width: 100%; box-sizing: border-box;">
        <div style="background: #ffffff; padding: 18px 20px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.04); border: 1px solid #e9ecef; display: flex; gap: 14px; align-items: flex-start; box-sizing: border-box;">
            <div style="background-color: #e7f1ff; color: #0d6efd; width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                <i class="fas fa-info-circle"></i>
            </div>
            <div>
                <h6 style="margin: 0 0 4px 0; font-size: 0.85rem; font-weight: 700; color: #212529;">Ghi chú nghiệp vụ</h6>
                <p style="margin: 0; font-size: 0.8rem; color: #6c757d; line-height: 1.4;">Công nợ cuối kỳ được tính dựa trên số dư đầu kỳ cộng phát sinh tăng, trừ đi các khoản đã thu trong kỳ báo cáo. Các khoản nợ quá hạn được đánh dấu đỏ trên ngày hạn thanh toán so với ngày hiện tại.</p>
            </div>
        </div>

        <div style="background: #ffffff; padding: 18px 20px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.04); border: 1px solid #e9ecef; display: flex; gap: 14px; align-items: flex-start; box-sizing: border-box;">
            <div style="background-color: #e8f5e9; color: #198754; width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                <i class="far fa-check-circle"></i>
            </div>
            <div>
                <h6 style="margin: 0 0 4px 0; font-size: 0.85rem; font-weight: 700; color: #212529;">Tối ưu quy trình thu nợ</h6>
                <p style="margin: 0; font-size: 0.8rem; color: #6c757d; line-height: 1.4;">Sử dụng tính năng "Gửi thông báo nợ" để tự động nhắc lịch thanh toán cho khách hàng qua Email/Zalo. Hệ thống sẽ cập nhật trạng thái ngay khi khách hàng thực hiện chuyển khoản thành công.</p>
            </div>
        </div>
    </div>

</div>

<!-- CẤU HÌNH MEDIA PRINT -->
<style>
@media print {
    body * {
        visibility: hidden;
    }
    #print-section, #print-section * {
        visibility: visible;
    }
    #print-section {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        border: none !important;
        box-shadow: none !important;
    }
}
</style>
