<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class SalesReport
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function getCustomerDebts(
        string $tuNgay,
        string $denNgay,
        string $maKH,
        string $tenKH,
        string $trangThai
    ): array
    {

    /**
     * Lấy danh sách công nợ khách hàng từ cơ sở dữ liệu thật của nhóm
     */
        // Câu lệnh SQL sửa đổi: Kết nối từ hóa đơn bán vòng qua phiếu xuất kho và đơn bán hàng để tìm đúng khach_hang_id
        $sql = "SELECT
    kh.khach_hang_id,
    kh.ma_khach_hang AS ma_kh,
    kh.ten_khach_hang AS ten_kh,
    kh.so_ngay_no,
                    IFNULL(kh.han_muc_no, 0) AS no_dau_ky, 
                    
                    -- 1. Tính tổng tiền phát sinh tăng nợ (JOIN qua phieu_xuat_kho và don_ban_hang)
                    IFNULL((
                        SELECT SUM(hd.tong_thanh_toan) 
                        FROM hoa_don_ban hd
                        JOIN phieu_xuat_kho pxk ON hd.phieu_xuat_kho_id = pxk.phieu_xuat_kho_id
                        JOIN don_ban_hang dbh ON pxk.don_ban_hang_id = dbh.don_ban_hang_id
                        WHERE dbh.khach_hang_id = kh.khach_hang_id 
                          AND hd.trang_thai = 'DA_PHAT_HANH'
                          AND DATE(hd.ngay_phat_hanh) BETWEEN :tu_ngay1 AND :den_ngay1
                    ), 0) AS phat_sinh,
                    
                    -- 2. Tính tổng số tiền giảm nợ từ các phiếu thu
                    IFNULL((
                        SELECT SUM(pt.so_tien_thu) 
                        FROM phieu_thu pt
                        WHERE pt.khach_hang_id = kh.khach_hang_id 
                          AND pt.trang_thai = 'DA_XAC_NHAN'
                          AND DATE(pt.ngay_thu) BETWEEN :tu_ngay2 AND :den_ngay2
                    ), 0) AS da_thu,
                    
                    CONCAT('Trong vòng ', kh.so_ngay_no, ' ngày') AS han_thanh_toan,

(
    SELECT MIN(hd.ngay_phat_hanh)
    FROM hoa_don_ban hd
    JOIN phieu_xuat_kho pxk
        ON hd.phieu_xuat_kho_id = pxk.phieu_xuat_kho_id
    JOIN don_ban_hang dbh
        ON pxk.don_ban_hang_id = dbh.don_ban_hang_id
    WHERE dbh.khach_hang_id = kh.khach_hang_id
) AS ngay_phat_sinh_dau_tien,

(
    SELECT MAX(hd.ngay_phat_hanh)
    FROM hoa_don_ban hd
    JOIN phieu_xuat_kho pxk
        ON hd.phieu_xuat_kho_id = pxk.phieu_xuat_kho_id
    JOIN don_ban_hang dbh
        ON pxk.don_ban_hang_id = dbh.don_ban_hang_id
    WHERE dbh.khach_hang_id = kh.khach_hang_id
) AS ngay_hoa_don_gan_nhat,

(
    SELECT MAX(pt.ngay_thu)
    FROM phieu_thu pt
    WHERE pt.khach_hang_id = kh.khach_hang_id
) AS ngay_thu_gan_nhat,

(
    SELECT COUNT(*)
    FROM hoa_don_ban hd
    JOIN phieu_xuat_kho pxk
        ON hd.phieu_xuat_kho_id = pxk.phieu_xuat_kho_id
    JOIN don_ban_hang dbh
        ON pxk.don_ban_hang_id = dbh.don_ban_hang_id
    WHERE dbh.khach_hang_id = kh.khach_hang_id
) AS tong_hoa_don

                FROM khach_hang kh
                
                WHERE 1=1";

        $params = [
            ':tu_ngay1'  => $tuNgay,
            ':den_ngay1' => $denNgay,
            ':tu_ngay2'  => $tuNgay,
            ':den_ngay2' => $denNgay
        ];

        if (!empty($maKH)) {
    $sql .= " AND kh.ma_khach_hang LIKE :ma_kh";
    $params[':ma_kh'] = '%' . $maKH . '%';
}

if (!empty($tenKH)) {
    $sql .= " AND kh.ten_khach_hang LIKE :ten_kh";
    $params[':ten_kh'] = '%' . $tenKH . '%';
}

        $sql .= " GROUP BY kh.khach_hang_id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Tính toán nợ cuối kỳ tự động
        foreach ($results as &$row) {

    $row['no_cuoi_ky'] =
        (float)$row['no_dau_ky']
        + (float)$row['phat_sinh']
        - (float)$row['da_thu'];

    // Ngày đến hạn
    if (!empty($row['ngay_hoa_don_gan_nhat'])) {

        $row['ngay_den_han'] = date(
            'Y-m-d',
            strtotime(
                $row['ngay_hoa_don_gan_nhat']
                . ' + '
                . $row['so_ngay_no']
                . ' days'
            )
        );

        $row['so_ngay_tre'] = max(
    0,
    (int) floor(
        (
            strtotime(date('Y-m-d'))
            - strtotime($row['ngay_den_han'])
        ) / 86400
    )
);
    } else {

        $row['ngay_den_han'] = null;
        $row['so_ngay_tre'] = 0;
    }

    $row['trang_thai'] =
    ($row['so_ngay_tre'] > 0)
    ? 'qua_han'
    : 'trong_han';
}

        // Lọc trạng thái nợ chọn từ thanh điều hướng giao diện
        if ($trangThai !== 'tat_ca') {
            $results = array_filter($results, function ($item) use ($trangThai) {
                return $item['trang_thai'] === $trangThai;
            });
        }

        return array_values($results);
    }
}
