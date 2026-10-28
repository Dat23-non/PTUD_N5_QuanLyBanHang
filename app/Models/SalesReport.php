<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class SalesReport
{
    // Lấy doanh số theo nhân viên, thời gian và khu vực.
    public function getRevenueByEmployee(
        string $fromDate,
        string $toDate,
        ?int $employeeId = null,
        ?int $areaId = null
    ): array {
        $sql = "
            SELECT
                nv.nhan_vien_id,
                n.ma_nhan_vien,
                n.ho_ten,
                COUNT(DISTINCT hd.hoa_don_ban_id) AS so_hoa_don,
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
            INNER JOIN khach_hang AS kh
                ON db.khach_hang_id = kh.khach_hang_id
            WHERE hd.trang_thai = 'DA_PHAT_HANH'
                AND hd.ngay_phat_hanh >= :from_date
                AND hd.ngay_phat_hanh < DATE_ADD(:to_date, INTERVAL 1 DAY)
        ";

        if ($employeeId !== null) {
            $sql .= " AND nv.nhan_vien_id = :employee_id ";
        }

        if ($areaId !== null) {
            $sql .= " AND kh.khu_vuc_id = :area_id ";
        }

        $sql .= "
            GROUP BY
                nv.nhan_vien_id,
                n.ma_nhan_vien,
                n.ho_ten
            ORDER BY doanh_so DESC
        ";

        $stmt = Database::connection()->prepare($sql);

        $stmt->bindValue(':from_date', $fromDate, PDO::PARAM_STR);
        $stmt->bindValue(':to_date', $toDate, PDO::PARAM_STR);

        if ($employeeId !== null) {
            $stmt->bindValue(':employee_id', $employeeId, PDO::PARAM_INT);
        }

        if ($areaId !== null) {
            $stmt->bindValue(':area_id', $areaId, PDO::PARAM_INT);
        }

        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Lấy danh sách nhân viên bán hàng.
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

        return Database::connection()
            ->query($sql)
            ->fetchAll(PDO::FETCH_ASSOC);
    }

    // Lấy danh sách khu vực cho bộ lọc.
    public function getAreas(): array
    {
        $sql = "
            SELECT
                khu_vuc_id,
                ma_khu_vuc,
                ten_khu_vuc
            FROM khu_vuc
            WHERE trang_thai = 'HOAT_DONG'
            ORDER BY ten_khu_vuc ASC
        ";

        return Database::connection()
            ->query($sql)
            ->fetchAll(PDO::FETCH_ASSOC);
    }

    // Lấy danh sách hóa đơn để xem chi tiết và đối chiếu.
    public function getInvoiceDetails(
        string $fromDate,
        string $toDate,
        ?int $employeeId = null,
        ?int $areaId = null
    ): array {
        $sql = "
            SELECT
                hd.hoa_don_ban_id,
                hd.so_hoa_don,
                hd.ngay_phat_hanh,
                hd.trang_thai,
                hd.tong_thanh_toan,
                n.ma_nhan_vien,
                n.ho_ten,
                kh.ma_khach_hang,
                kh.ten_khach_hang,
                kv.ten_khu_vuc
            FROM hoa_don_ban AS hd
            INNER JOIN phieu_xuat_kho AS px
                ON hd.phieu_xuat_kho_id = px.phieu_xuat_kho_id
            INNER JOIN don_ban_hang AS db
                ON px.don_ban_hang_id = db.don_ban_hang_id
            INNER JOIN nhan_vien AS n
                ON db.nhan_vien_ban_hang_id = n.nhan_vien_id
            INNER JOIN khach_hang AS kh
                ON db.khach_hang_id = kh.khach_hang_id
            LEFT JOIN khu_vuc AS kv
                ON kh.khu_vuc_id = kv.khu_vuc_id
            WHERE hd.trang_thai = 'DA_PHAT_HANH'
                AND hd.ngay_phat_hanh >= :from_date
                AND hd.ngay_phat_hanh < DATE_ADD(:to_date, INTERVAL 1 DAY)
        ";

        if ($employeeId !== null) {
            $sql .= " AND n.nhan_vien_id = :employee_id ";
        }

        if ($areaId !== null) {
            $sql .= " AND kh.khu_vuc_id = :area_id ";
        }

        $sql .= "
            ORDER BY hd.ngay_phat_hanh DESC, hd.hoa_don_ban_id DESC
        ";

        $stmt = Database::connection()->prepare($sql);

        $stmt->bindValue(':from_date', $fromDate, PDO::PARAM_STR);
        $stmt->bindValue(':to_date', $toDate, PDO::PARAM_STR);

        if ($employeeId !== null) {
            $stmt->bindValue(':employee_id', $employeeId, PDO::PARAM_INT);
        }

        if ($areaId !== null) {
            $stmt->bindValue(':area_id', $areaId, PDO::PARAM_INT);
        }

        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
