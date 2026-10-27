<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class SalesReport
{
    // Lấy báo cáo doanh số theo nhân viên và khoảng thời gian.
    public function getRevenueByEmployee(
        string $fromDate,
        string $toDate,
        ?int $employeeId = null
    ): array {
        $sql = "
            SELECT
                nv.nhan_vien_id,
                n.ma_nhan_vien,
                n.ho_ten,
                COUNT(hd.hoa_don_ban_id) AS so_hoa_don,
                COALESCE(SUM(hd.tong_thanh_toan), 0) AS doanh_so
            FROM hoa_don_ban AS hd
            INNER JOIN phieu_xuat_kho AS px
                ON hd.phieu_xuat_kho_id = px.phieu_xuat_kho_id
            INNER JOIN don_ban_hang AS db
                ON px.don_ban_hang_id = db.don_ban_hang_id
            INNER JOIN nhan_vien_ban_hang AS nv
                ON db.nhan_vien_ban_hang_id = nv.nhan_vien_id
            INNER JOIN nhan_vien AS n
                ON nv.nhan_vien_id = n.nhan_vien_id
            WHERE hd.trang_thai = 'DA_PHAT_HANH'
                AND hd.ngay_phat_hanh >= :from_date
                AND hd.ngay_phat_hanh < DATE_ADD(:to_date, INTERVAL 1 DAY)
        ";

        // Chỉ bổ sung điều kiện khi chọn nhân viên cụ thể.
        if ($employeeId !== null) {
            $sql .= "
                AND nv.nhan_vien_id = :employee_id
            ";
        }

        $sql .= "
            GROUP BY
                nv.nhan_vien_id,
                n.ma_nhan_vien,
                n.ho_ten
            ORDER BY doanh_so DESC
        ";

        // Chuẩn bị câu truy vấn.
        $stmt = Database::connection()->prepare($sql);

        // Truyền khoảng thời gian báo cáo.
        $stmt->bindValue(
            ':from_date',
            $fromDate,
            PDO::PARAM_STR
        );

        $stmt->bindValue(
            ':to_date',
            $toDate,
            PDO::PARAM_STR
        );

        // Truyền ID nhân viên nếu có bộ lọc.
        if ($employeeId !== null) {
            $stmt->bindValue(
                ':employee_id',
                $employeeId,
                PDO::PARAM_INT
            );
        }

        // Thực thi truy vấn.
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Lấy danh sách nhân viên bán hàng để hiển thị bộ lọc.
    public function getEmployees(): array
    {
        $sql = "
            SELECT
                n.nhan_vien_id,
                n.ma_nhan_vien,
                n.ho_ten
            FROM nhan_vien AS n
            INNER JOIN nhan_vien_ban_hang AS nv
                ON n.nhan_vien_id = nv.nhan_vien_id
            ORDER BY n.ho_ten ASC
        ";

        $stmt = Database::connection()->query($sql);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
