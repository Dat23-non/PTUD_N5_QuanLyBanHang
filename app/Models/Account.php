<?php
declare(strict_types=1);
namespace App\Models;
use PDO;
final class Account
{
    public function __construct(private PDO $db) {}
    public function authenticate(string $username, string $password): ?int
    {
        $stmt = $this->db->prepare("SELECT t.tai_khoan_id,t.mat_khau_hash FROM tai_khoan t
            JOIN nhan_vien n ON n.nhan_vien_id=t.nhan_vien_id
            JOIN nhom_quyen q ON q.nhom_quyen_id=t.nhom_quyen_id
            JOIN nha_phan_phoi p ON p.nha_phan_phoi_id=n.nha_phan_phoi_id
            WHERE t.ten_dang_nhap=? AND t.trang_thai='HOAT_DONG' AND n.trang_thai='DANG_LAM_VIEC'
              AND q.trang_thai='HOAT_DONG' AND p.trang_thai='HOAT_DONG'");
        $stmt->execute([$username]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $hash = $row['mat_khau_hash'] ?? '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.';
        return password_verify($password, $hash) && $row ? (int) $row['tai_khoan_id'] : null;
    }
}
