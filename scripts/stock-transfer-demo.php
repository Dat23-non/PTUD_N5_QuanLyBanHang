<?php
/** Tiện ích local: --check chỉ đọc; --seed khởi tạo/cập nhật danh mục sản phẩm sữa. */
declare(strict_types=1);
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require dirname(__DIR__) . '/app/Core/autoload.php';
use App\Core\Database;

if (!in_array($argv[1] ?? '', ['--check', '--seed'], true)) {
    echo "Usage: php scripts/stock-transfer-demo.php --check | --seed\n";
    exit(1);
}
try {
    $db = Database::connection();
    if ($argv[1] === '--check') {
        foreach (['tai_khoan', 'kho', 'hang_hoa', 'ton_kho', 'phieu_chuyen_kho'] as $table) {
            echo $table . ': ' . $db->query('SELECT COUNT(*) FROM ' . $table)->fetchColumn() . PHP_EOL;
        }
        $model = new App\Models\StockTransfer($db);
        echo 'Active warehouse accounts: ' . count($model->demoActors()) . PHP_EOL;
        exit;
    }
    $db->beginTransaction();
    // Tên bảng/cột đều là hằng từ mã nguồn, giá trị luôn dùng prepared statement.
    $insert = static function (string $table, array $data) use ($db): int {
        $sql = 'INSERT INTO ' . $table . ' (' . implode(',', array_keys($data)) . ') VALUES ('
            . implode(',', array_fill(0, count($data), '?')) . ')';
        $db->prepare($sql)->execute(array_values($data));
        return (int) $db->lastInsertId();
    };
    $existing = $db->query("SELECT nha_phan_phoi_id FROM nha_phan_phoi WHERE ma_npp='ST_DEMO' FOR UPDATE")->fetchColumn();
    if (!$existing) {
        $nppId = $insert('nha_phan_phoi', ['ma_npp' => 'ST_DEMO', 'ten_npp' => 'Nhà phân phối A&D']);
        $roleId = $db->query("SELECT nhom_quyen_id FROM nhom_quyen WHERE ma_nhom_quyen='WAREHOUSE'")->fetchColumn();
        if (!$roleId) {
            $roleId = $insert('nhom_quyen', ['ma_nhom_quyen' => 'WAREHOUSE', 'ten_nhom_quyen' => 'Thủ kho']);
        }
        $warehouseEmployee = $insert('nhan_vien', ['nha_phan_phoi_id' => $nppId, 'ma_nhan_vien' => 'ST_DEMO_TK', 'ho_ten' => 'Đỗ Văn Quang']);
        $salesEmployee = $insert('nhan_vien', ['nha_phan_phoi_id' => $nppId, 'ma_nhan_vien' => 'ST_DEMO_BH', 'ho_ten' => 'Nguyễn Văn An']);
        $insert('nhan_vien_ban_hang', ['nhan_vien_id' => $salesEmployee]);
        $insert('tai_khoan', ['ma_nguoi_dung' => 'ST_DEMO_USER', 'nhan_vien_id' => $warehouseEmployee,
            'nhom_quyen_id' => $roleId, 'ten_dang_nhap' => 'st_demo_warehouse',
            'mat_khau_hash' => password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT)]);
        $insert('kho', ['nha_phan_phoi_id' => $nppId, 'ma_kho' => 'ST_TONG', 'ten_kho' => 'Kho Tổng', 'loai_kho' => 'KHO_TONG']);
        $insert('kho', ['nha_phan_phoi_id' => $nppId, 'ma_kho' => 'ST_NVBH', 'ten_kho' => 'Kho NVBH Nguyễn Văn An',
            'loai_kho' => 'KHO_KY_GUI', 'nhan_vien_phu_trach_id' => $salesEmployee]);
    }
    foreach (['seed_dairy_catalog.sql', 'update_stock_transfer_labels.sql', 'seed_stock_transfer_inventory.sql'] as $file) {
        $sql = file_get_contents(dirname(__DIR__) . '/database/' . $file);
        // Script CLI bọc cả ba file trong một transaction; import tay thì mỗi file tự có transaction.
        $sql = str_replace(['START TRANSACTION;', 'COMMIT;'], '', $sql);
        $db->exec($sql);
    }
    $db->commit();
    echo "Dairy catalog and initial warehouse inventory are ready. Select Do Van Quang in local mode.\n";
    echo "Existing stock quantities and transfer documents were preserved.\n";
} catch (Throwable $e) {
    if (isset($db) && $db->inTransaction()) { $db->rollBack(); }
    fwrite(STDERR, 'Cannot prepare stock-transfer demo. Check database/configuration or duplicate ST_DEMO codes. (' . get_class($e) . ")\n");
    exit(1);
}
