<?php
declare(strict_types=1);
namespace App\Services;
use App\Models\StockTransfer;
use DomainException;
use PDO;
use Throwable;

/** Chỉ được gọi từ tiện ích CLI; chuẩn bị kho/tồn theo đúng NPP của tài khoản. */
final class StockTransferSetup
{
    public function __construct(private PDO $db) {}
    public function prepare(string $username, ?string $password = null, ?string $distributorCode = null): int
    {
        if (!preg_match('/\A[a-zA-Z0-9_.-]{3,80}\z/', $username)) { throw new DomainException('Tên đăng nhập không hợp lệ.'); }
        $this->db->beginTransaction();
        try {
            $account = $this->query('SELECT tai_khoan_id FROM tai_khoan WHERE ten_dang_nhap=?', [$username])[0] ?? null;
            if ($account) {
                $actor = (new StockTransfer($this->db))->actor((int) $account['tai_khoan_id'], true);
                if (!$actor) { throw new DomainException('Tài khoản hiện có phải là thủ kho đang hoạt động, có nhân viên và NPP hợp lệ.'); }
                $actorId = (int) $actor['tai_khoan_id']; $nppId = (int) $actor['nha_phan_phoi_id'];
                if ($distributorCode !== null && !$this->query('SELECT 1 FROM nha_phan_phoi WHERE nha_phan_phoi_id=? AND ma_npp=?', [$nppId,$distributorCode])) {
                    throw new DomainException('Mã NPP không khớp tài khoản hiện có.');
                }
            } else {
                if ($password === null || strlen($password) < 8 || strlen($password) > 72) { throw new DomainException('Tài khoản mới cần mật khẩu dài 8–72 byte.'); }
                $npps = $distributorCode !== null
                    ? $this->query("SELECT nha_phan_phoi_id FROM nha_phan_phoi WHERE ma_npp=? AND trang_thai='HOAT_DONG'", [$distributorCode])
                    : $this->query("SELECT nha_phan_phoi_id FROM nha_phan_phoi WHERE trang_thai='HOAT_DONG'");
                if (!$npps && $distributorCode === null) {
                    $nppId = $this->insert('nha_phan_phoi', ['ma_npp'=>'NPP_AD','ten_npp'=>'Nhà phân phối A&D']);
                } elseif (count($npps) === 1) { $nppId = (int) $npps[0]['nha_phan_phoi_id']; }
                else { throw new DomainException('Cần chỉ rõ mã NPP đang hoạt động bằng tham số thứ hai.'); }
                $role = $this->query("SELECT nhom_quyen_id,trang_thai FROM nhom_quyen WHERE ma_nhom_quyen='WAREHOUSE'")[0] ?? null;
                if ($role && $role['trang_thai'] !== 'HOAT_DONG') { throw new DomainException('Nhóm quyền thủ kho đang bị ngừng hoạt động.'); }
                $roleId = $role ? $role['nhom_quyen_id'] : $this->insert('nhom_quyen',['ma_nhom_quyen'=>'WAREHOUSE','ten_nhom_quyen'=>'Thủ kho']);
                $employeeId = $this->insert('nhan_vien',['nha_phan_phoi_id'=>$nppId,'ma_nhan_vien'=>'TK_'.bin2hex(random_bytes(6)),'ho_ten'=>'Thủ kho '.$username]);
                $actorId = $this->insert('tai_khoan',['ma_nguoi_dung'=>'TK_'.bin2hex(random_bytes(6)), 'nhan_vien_id'=>$employeeId,
                    'nhom_quyen_id'=>$roleId,'ten_dang_nhap'=>$username,'mat_khau_hash'=>password_hash($password,PASSWORD_DEFAULT), 'doi_mat_khau_lan_dau'=>0]);
            }
            $model = new StockTransfer($this->db);
            $sources = $model->sourceWarehouses($nppId, true);
            if (count($sources) > 1) { throw new DomainException('NPP có nhiều Kho tổng; cần thống nhất kho trước khi khởi tạo.'); }
            $sourceId = $sources ? (int) $sources[0]['kho_id'] : $this->insert('kho',
                ['nha_phan_phoi_id'=>$nppId,'ma_kho'=>'KHO_TONG','ten_kho'=>'Kho Tổng','loai_kho'=>'KHO_TONG']);
            $destinations = $model->destinations($nppId, true);
            if (!$destinations) {
                $salesId = $this->insert('nhan_vien',['nha_phan_phoi_id'=>$nppId,'ma_nhan_vien'=>'BH_'.bin2hex(random_bytes(6)),'ho_ten'=>'Nhân viên bán hàng']);
                $this->insert('nhan_vien_ban_hang',['nhan_vien_id'=>$salesId]);
                $this->insert('kho',['nha_phan_phoi_id'=>$nppId,'ma_kho'=>'NVBH_'.bin2hex(random_bytes(4)),
                    'ten_kho'=>'Kho nhân viên bán hàng','loai_kho'=>'KHO_KY_GUI','nhan_vien_phu_trach_id'=>$salesId]);
                $destinations = $model->destinations($nppId);
            }
            $sql = file_get_contents(dirname(__DIR__,2).'/database/seed_dairy_catalog.sql');
            $this->db->exec(str_replace(['START TRANSACTION;','COMMIT;'],'',$sql));
            $items = $this->query("SELECT hang_hoa_id FROM hang_hoa WHERE ma_hang IN
                ('VNM_ST_CD180','VNM_ST_KD180','VNM_SC_CD100','VNM_SCU180','VNM_SD380','VNM_SB400',
                 'TH_ST_CD180','TH_ST_KD180','TH_SC100','TH_SCU180','DLF_ST180','DLF_SC100')");
            if (count($items) !== 12) { throw new DomainException('Danh mục đơn vị tính không khớp; chưa thể tạo đủ 12 mặt hàng.'); }
            $stock = $this->db->prepare('INSERT INTO ton_kho (kho_id,hang_hoa_id,so_luong_ton)
                SELECT ?,?,? WHERE NOT EXISTS (SELECT 1 FROM ton_kho WHERE kho_id=? AND hang_hoa_id=?)');
            foreach (array_merge([['kho_id'=>$sourceId]],$destinations) as $warehouse) {
                $warehouseId=(int)$warehouse['kho_id'];
                foreach ($items as $item) { $stock->execute([$warehouseId,$item['hang_hoa_id'],$warehouseId===$sourceId?100:0,$warehouseId,$item['hang_hoa_id']]); }
            }
            $this->db->commit(); return $actorId;
        } catch (Throwable $e) { if ($this->db->inTransaction()) { $this->db->rollBack(); } throw $e; }
    }
    private function query(string $sql,array $values=[]): array { $s=$this->db->prepare($sql); $s->execute($values); return $s->fetchAll(PDO::FETCH_ASSOC); }
    private function insert(string $table,array $values): int {
        $s=$this->db->prepare('INSERT INTO '.$table.' ('.implode(',',array_keys($values)).') VALUES ('.implode(',',array_fill(0,count($values),'?')).')');
        $s->execute(array_values($values)); return (int)$this->db->lastInsertId();
    }
}
