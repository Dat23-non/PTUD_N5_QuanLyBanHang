<?php
declare(strict_types=1);

namespace App\Models;

use PDO;

/** SQL của chức năng chuyển kho; không cập nhật tồn khi lập nháp. */
final class StockTransfer
{
    public function __construct(private PDO $db)
    {
    }

    public function actor(int $id, bool $lock = false): ?array
    {
        return $this->one("SELECT t.tai_khoan_id, n.ho_ten, n.nha_phan_phoi_id
            FROM tai_khoan t
            JOIN nhan_vien n ON n.nhan_vien_id = t.nhan_vien_id
            JOIN nhom_quyen q ON q.nhom_quyen_id = t.nhom_quyen_id
            JOIN nha_phan_phoi p ON p.nha_phan_phoi_id = n.nha_phan_phoi_id
            WHERE t.tai_khoan_id = ? AND t.trang_thai = 'HOAT_DONG'
              AND n.trang_thai = 'DANG_LAM_VIEC' AND p.trang_thai = 'HOAT_DONG'
              AND q.ma_nhom_quyen = 'WAREHOUSE' AND q.trang_thai = 'HOAT_DONG'"
            . ($lock ? ' LOCK IN SHARE MODE' : ''), [$id]);
    }

    public function demoActors(): array
    {
        $ids = $this->db->query('SELECT tai_khoan_id FROM tai_khoan ORDER BY tai_khoan_id')->fetchAll(PDO::FETCH_COLUMN);
        $actors = [];
        foreach ($ids as $id) {
            if ($actor = $this->actor((int) $id)) {
                $actors[] = $actor;
            }
        }
        return $actors;
    }

    public function sourceWarehouses(int $distributorId, bool $lock = false): array
    {
        return $this->all("SELECT kho_id, ten_kho, ma_kho FROM kho
            WHERE nha_phan_phoi_id = ? AND loai_kho = 'KHO_TONG' AND trang_thai = 'HOAT_DONG'
            ORDER BY kho_id" . ($lock ? ' LOCK IN SHARE MODE' : ''), [$distributorId]);
    }

    public function destinations(int $distributorId, bool $lock = false): array
    {
        return $this->all("SELECT k.kho_id, k.ten_kho, k.ma_kho, n.ho_ten
            FROM kho k JOIN nhan_vien_ban_hang b ON b.nhan_vien_id = k.nhan_vien_phu_trach_id
            JOIN nhan_vien n ON n.nhan_vien_id = b.nhan_vien_id
            WHERE k.nha_phan_phoi_id = ? AND n.nha_phan_phoi_id = k.nha_phan_phoi_id
              AND k.loai_kho = 'KHO_KY_GUI' AND k.trang_thai = 'HOAT_DONG'
              AND n.trang_thai = 'DANG_LAM_VIEC'
            ORDER BY k.kho_id" . ($lock ? ' LOCK IN SHARE MODE' : ''), [$distributorId]);
    }

    public function products(int $sourceId, bool $lock = false): array
    {
        return $this->all("SELECT h.hang_hoa_id, h.ma_hang, h.ten_hang, h.don_vi_nho_id,
                d.ten_don_vi, t.so_luong_ton, t.so_luong_giu_cho
            FROM ton_kho t JOIN hang_hoa h ON h.hang_hoa_id = t.hang_hoa_id
            JOIN don_vi_tinh d ON d.don_vi_tinh_id = h.don_vi_nho_id
            WHERE t.kho_id = ? AND h.trang_thai <> 'NGUNG_KINH_DOANH'
            ORDER BY h.hang_hoa_id" . ($lock ? ' LOCK IN SHARE MODE' : ''), [$sourceId]);
    }

    public function insertDraft(array $header, array $lines): int
    {
        $stmt = $this->db->prepare("INSERT INTO phieu_chuyen_kho
            (ma_chuyen_kho, kho_xuat_id, kho_nhap_id, ngay_chuyen, tong_gia_tri,
             trang_thai, nguoi_lap_tai_khoan_id, ghi_chu)
            VALUES (?, ?, ?, ?, ?, 'NHAP', ?, ?)");
        $stmt->execute($header);
        $id = (int) $this->db->lastInsertId();
        $detail = $this->db->prepare('INSERT INTO chi_tiet_phieu_chuyen_kho
            (phieu_chuyen_kho_id, hang_hoa_id, don_vi_tinh_id, so_luong, he_so_quy_doi,
             don_gia_ghi_nhan, thanh_tien) VALUES (?, ?, ?, ?, 1, ?, ?)');
        foreach ($lines as $line) {
            $detail->execute([$id, $line['product_id'], $line['unit_id'], $line['quantity'],
                $line['unit_price'], $line['amount']]);
        }
        return $id;
    }

    public function find(int $id, int $distributorId): ?array
    {
        $transfer = $this->one('SELECT p.*, x.ten_kho AS kho_xuat, k.ten_kho AS kho_nhap, n.ho_ten AS nguoi_lap
            FROM phieu_chuyen_kho p JOIN kho x ON x.kho_id = p.kho_xuat_id
            JOIN kho k ON k.kho_id = p.kho_nhap_id
            JOIN tai_khoan t ON t.tai_khoan_id = p.nguoi_lap_tai_khoan_id
            JOIN nhan_vien n ON n.nhan_vien_id = t.nhan_vien_id
            WHERE p.phieu_chuyen_kho_id = ? AND x.nha_phan_phoi_id = ? AND k.nha_phan_phoi_id = ?',
            [$id, $distributorId, $distributorId]);
        if ($transfer) {
            $transfer['lines'] = $this->all('SELECT c.*, h.ma_hang, h.ten_hang, d.ten_don_vi
                FROM chi_tiet_phieu_chuyen_kho c JOIN hang_hoa h ON h.hang_hoa_id = c.hang_hoa_id
                JOIN don_vi_tinh d ON d.don_vi_tinh_id = c.don_vi_tinh_id
                WHERE c.phieu_chuyen_kho_id = ? ORDER BY c.chi_tiet_phieu_chuyen_kho_id', [$id]);
        }
        return $transfer;
    }

    private function all(string $sql, array $params): array
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function one(string $sql, array $params): ?array
    {
        return $this->all($sql, $params)[0] ?? null;
    }
}
