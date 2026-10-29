<?php
/** Chạy: E:\xampp\php\php.exe tests/stock-transfer.php [--browser]
 * Tạo database riêng có tên ngẫu nhiên; xóa đúng database đó trong finally.
 * Không ghi vào database ứng dụng hoặc thay đổi config/database.php.
 */
declare(strict_types=1);
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require dirname(__DIR__) . '/app/Core/autoload.php';
use App\Models\StockTransfer;
use App\Services\StockTransferService;

$root = dirname(__DIR__);
$config = require $root . '/config/database.php';
$dbName = 'ptud_st_test_' . bin2hex(random_bytes(6));
$server = new PDO(sprintf('mysql:host=%s;port=%s;charset=utf8mb4', $config['host'], $config['port']),
    $config['username'], $config['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$process = null;
$db = null;
$passed = 0;
$check = static function (bool $condition, string $message) use (&$passed): void {
    if (!$condition) {
        throw new RuntimeException('FAIL: ' . $message);
    }
    $passed++;
    echo "PASS: $message\n";
};
$log = tempnam(sys_get_temp_dir(), 'ptud-st-http-');
$sessionDir = sys_get_temp_dir() . '/ptud-st-sessions-' . bin2hex(random_bytes(6));
mkdir($sessionDir);
try {
    $server->exec("CREATE DATABASE `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $server->exec("USE `$dbName`");
    $schema = file_get_contents($root . '/database/schema_v1_1.sql');
    $schema = preg_replace('/CREATE DATABASE IF NOT EXISTS `ptud_quan_ly_ban_hang`\s+CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;/', '', $schema);
    $schema = str_replace('USE `ptud_quan_ly_ban_hang`;', "USE `$dbName`;", $schema);
    $server->exec($schema);
    $server->exec(file_get_contents(__DIR__ . '/fixtures/stock-transfer.sql'));
    $db = $server;
    $service = new StockTransferService($db);
    $model = new StockTransfer($db);
    $input = ['destination_id' => '2', 'transfer_date' => '2026-10-28', 'note' => '<script>test</script>',
        'lines' => [['product_id' => '1', 'quantity' => '3', 'unit_price' => '12.34'],
                    ['product_id' => '2', 'quantity' => '2', 'unit_price' => '0.10']]];
    $stock = $db->query('SELECT * FROM ton_kho ORDER BY kho_id,hang_hoa_id')->fetchAll(PDO::FETCH_ASSOC);
    $id = $service->createDraft(1, $input);
    $draft = $model->find($id, 1);
    $check($draft['trang_thai'] === 'NHAP' && count($draft['lines']) === 2, 'save draft header and two lines');
    $check($draft['tong_gia_tri'] === '37.22' && $draft['lines'][0]['thanh_tien'] === '37.02', 'exact decimal money');
    $check($draft['nguoi_lap_tai_khoan_id'] == 1 && $draft['nguoi_xac_nhan_tai_khoan_id'] === null
        && $draft['kho_xuat_id'] == 1, 'creator and fixed source from server; unconfirmed');
    $check($stock === $db->query('SELECT * FROM ton_kho ORDER BY kho_id,hang_hoa_id')->fetchAll(PDO::FETCH_ASSOC), 'both warehouse stocks unchanged');
    $check($model->find($id, 2) === null, 'detail hidden from other distributor');
    $reject = static function (string $label, array $value, int $actor = 1) use ($service, $db, $check): void {
        $before = $db->query('SELECT COUNT(*) FROM phieu_chuyen_kho')->fetchColumn();
        try {
            $service->createDraft($actor, $value);
            throw new RuntimeException('Expected rejection: ' . $label);
        } catch (DomainException $e) {
            $check($before === $db->query('SELECT COUNT(*) FROM phieu_chuyen_kho')->fetchColumn() && !$db->inTransaction(), $label);
        }
    };
    $bad = $input; $bad['lines'] = []; $reject('reject empty lines', $bad);
    $bad = $input; $bad['lines'][1] = $bad['lines'][0]; $reject('reject duplicate product', $bad);
    foreach (['0', '-1', '1.5', '1e2', '9999999999999', ['3']] as $qty) {
        $bad = $input; $bad['lines'][0]['quantity'] = $qty; $reject('reject invalid quantity ' . json_encode($qty), $bad);
    }
    $bad = $input; $bad['lines'][0]['quantity'] = '101'; $reject('reject excess stock', $bad);
    foreach (['', '-1', '1.234', '1e2', ['3']] as $price) {
        $bad = $input; $bad['lines'][0]['unit_price'] = $price; $reject('reject invalid price ' . json_encode($price), $bad);
    }
    $bad = $input; $bad['lines'][0]['unit_price'] = '9999999999999.99'; $reject('reject money overflow', $bad);
    foreach (['1', '3', '4', '999'] as $destination) {
        $bad = $input; $bad['destination_id'] = $destination; $reject('reject source/foreign/branch/missing destination ' . $destination, $bad);
    }
    $bad = $input; $bad['transfer_date'] = '2026-02-30'; $reject('reject impossible date', $bad);
    $bad = $input; $bad['transfer_date'] = "2026-10-28\0"; $reject('reject malformed date', $bad);
    $reject('reject non-warehouse role', $input, 2);
    $db->exec("UPDATE tai_khoan SET trang_thai='BI_KHOA' WHERE tai_khoan_id=1");
    $reject('reject locked account', $input);
    $db->exec("UPDATE tai_khoan SET trang_thai='HOAT_DONG' WHERE tai_khoan_id=1");
    $db->exec("UPDATE kho SET trang_thai='NGUNG_HOAT_DONG' WHERE kho_id=2");
    $reject('reject inactive destination', $input);
    $db->exec("UPDATE kho SET trang_thai='HOAT_DONG' WHERE kho_id=2");
    $db->exec("UPDATE hang_hoa SET trang_thai='NGUNG_KINH_DOANH' WHERE hang_hoa_id=1");
    $reject('reject stopped product', $input);
    $db->exec("UPDATE hang_hoa SET trang_thai='DANG_KINH_DOANH' WHERE hang_hoa_id=1");
    $bad = $input; $bad['lines'][0]['product_id'] = '999'; $reject('reject missing product', $bad);
    $db->exec("INSERT INTO kho (nha_phan_phoi_id,ma_kho,ten_kho,loai_kho) VALUES (1,'TONG2','Kho tổng thứ hai','KHO_TONG')");
    $reject('reject ambiguous main warehouse', $input);
    $db->exec("DELETE FROM kho WHERE ma_kho='TONG2'");
    $db->exec('UPDATE ton_kho SET so_luong_ton=2 WHERE kho_id=1 AND hang_hoa_id=1');
    $reject('recheck changed stock at save', $input);
    $db->exec('UPDATE ton_kho SET so_luong_ton=100 WHERE kho_id=1 AND hang_hoa_id=1');
    $before = $db->query('SELECT COUNT(*) FROM phieu_chuyen_kho')->fetchColumn();
    $beforeDetails = $db->query('SELECT COUNT(*) FROM chi_tiet_phieu_chuyen_kho')->fetchColumn();
    $db->exec("CREATE TRIGGER test_fail_line BEFORE INSERT ON chi_tiet_phieu_chuyen_kho FOR EACH ROW
        BEGIN IF NEW.hang_hoa_id = 2 THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'test failure'; END IF; END");
    try { $service->createDraft(1, $input); throw new RuntimeException('Expected insert failure'); }
    catch (PDOException $e) {
        $check($before === $db->query('SELECT COUNT(*) FROM phieu_chuyen_kho')->fetchColumn()
            && $beforeDetails === $db->query('SELECT COUNT(*) FROM chi_tiet_phieu_chuyen_kho')->fetchColumn(), 'rollback header and first detail after second detail fails');
    }
    $db->exec('DROP TRIGGER test_fail_line');
    $db->prepare('UPDATE tai_khoan SET mat_khau_hash=? WHERE tai_khoan_id=1')->execute([password_hash('test-password-2026',PASSWORD_DEFAULT)]);
    $httpStock = $db->query('SELECT * FROM ton_kho ORDER BY kho_id,hang_hoa_id')->fetchAll(PDO::FETCH_ASSOC);

    // HTTP kiểm tra route thật, session, CSRF, PRG và chống gửi lặp.
    $socket = stream_socket_server('tcp://127.0.0.1:0', $errno, $error);
    $port = (int) substr(strrchr(stream_socket_get_name($socket, false), ':'), 1);
    fclose($socket);
    $process = proc_open([PHP_BINARY, '-S', '127.0.0.1:' . $port, '-t', $root . '/public', __DIR__ . '/fixtures/stock-transfer-router.php'],
        [0 => ['pipe', 'r'], 1 => ['file', $log, 'a'], 2 => ['file', $log, 'a']], $pipes, $root,
        array_merge(getenv(), ['PTUD_ST_TEST_DB' => $dbName, 'PTUD_ST_TEST_SESSION_DIR' => $sessionDir]));
    fclose($pipes[0]);
    $base = 'http://127.0.0.1:' . $port . '/index.php?page=stock-transfer';
    $curl = curl_init();
    curl_setopt_array($curl, [CURLOPT_RETURNTRANSFER => true, CURLOPT_COOKIEFILE => '', CURLOPT_TIMEOUT => 5]);
    $request = static function (string $url, ?array $post = null) use ($curl): array {
        curl_setopt_array($curl, [CURLOPT_URL => $url, CURLOPT_POST => $post !== null,
            CURLOPT_HEADER => true, CURLOPT_FOLLOWLOCATION => false]);
        if ($post !== null) { curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($post)); }
        $response = curl_exec($curl);
        if ($response === false) { throw new RuntimeException('HTTP request failed: ' . curl_error($curl)); }
        return ['status' => curl_getinfo($curl, CURLINFO_RESPONSE_CODE), 'raw' => $response];
    };
    for ($i = 0; $i < 50; $i++) {
        $ready = @fsockopen('127.0.0.1', $port);
        if ($ready) { fclose($ready); break; }
        usleep(100000);
    }
    $response = $request($base . '&test_actor=none');
    $check($response['status'] === 401, 'HTTP deny unauthenticated access');
    $response = $request($base . '&test_actor=2');
    $check($response['status'] === 403, 'HTTP deny sales role');
    $response = $request($base . '&test_actor=1');
    $check($response['status'] === 200 && str_contains($response['raw'], 'id="st-form"'), 'HTTP form routed through controller');
    preg_match('/name="csrf_token" value="([a-f0-9]+)"/', $response['raw'], $csrf);
    preg_match('/name="submission_token" value="([a-f0-9]+)"/', $response['raw'], $token);
    $post = $input + ['operation' => 'save-draft', 'csrf_token' => $csrf[1], 'submission_token' => $token[1]];
    $forged = $post; $forged['csrf_token'] = 'wrong';
    $check($request($base, $forged)['status'] === 403, 'HTTP reject CSRF');
    $bad = $post; $bad['lines'][0]['quantity'] = '101';
    $response = $request($base, $bad);
    $check($response['status'] === 422 && str_contains($response['raw'], 'value="101"')
        && str_contains($response['raw'], '&lt;script&gt;test&lt;/script&gt;'), 'HTTP retain entered data and escape note after validation error');
    $response = $request($base, $post);
    $check($response['status'] === 303, 'HTTP save redirects to detail');
    preg_match('/Location: ([^\r\n]+)/i', $response['raw'], $location);
    $response = $request('http://127.0.0.1:' . $port . '/' . $location[1]);
    $check($response['status'] === 200 && str_contains($response['raw'], 'Lưu nháp thành công')
        && str_contains($response['raw'], '&lt;script&gt;test&lt;/script&gt;'), 'HTTP detail success and XSS escaping');
    $before = $db->query('SELECT COUNT(*) FROM phieu_chuyen_kho')->fetchColumn();
    $check($request($base, $post)['status'] === 303
        && $before === $db->query('SELECT COUNT(*) FROM phieu_chuyen_kho')->fetchColumn(), 'HTTP duplicate submit creates only one draft');
    $check($request($base . '&id=' . $id . '&test_actor=3')['status'] === 404, 'HTTP detail access limited to distributor');
    $check($request($base . '&id[]=1&test_actor=1')['status'] === 404, 'HTTP malformed detail id safely rejected');
    $response=$request($base.'&test_actor=1');
    $check(str_contains($response['raw'],'id="st-list"') && str_contains($response['raw'],$draft['ma_chuyen_kho']), 'saved draft visible in warehouse list');
    $check(!str_contains($response['raw'],'name="demo_actor_id"'), 'local actor selector removed');
    $db->exec("UPDATE phieu_chuyen_kho SET trang_thai='DA_XAC_NHAN' WHERE phieu_chuyen_kho_id=".$id);
    $response=$request($base.'&status=DA_XAC_NHAN');
    $check(str_contains($response['raw'],$draft['ma_chuyen_kho']), 'confirmed transfer visible in approved filter');
    $response=$request($base.'&status=NHAP');
    $check(!str_contains($response['raw'],$draft['ma_chuyen_kho']), 'draft filter excludes approved transfer');
    $db->exec("UPDATE phieu_chuyen_kho SET trang_thai='NHAP' WHERE phieu_chuyen_kho_id=".$id);
    $check($model->listing(2)===[], 'list excludes other distributor');
    $login='http://127.0.0.1:'.$port.'/index.php?page=login';
    $response=$request($login.'&test_actor=none');
    preg_match('/name="csrf_token" value="([a-f0-9]+)"/',$response['raw'],$authCsrf);
    $credentials=['csrf_token'=>$authCsrf[1],'username'=>'test_warehouse','password'=>'incorrect'];
    $check($request($login,$credentials)['status']===422, 'real login rejects incorrect password');
    $credentials['password']='test-password-2026';
    $check($request($login,$credentials)['status']===303, 'real login verifies password hash');
    $response=$request($base);
    $check($response['status']===200 && str_contains($response['raw'],'id="st-form"'),'shared login opens transfer without local settings');
    preg_match('/name="csrf_token" value="([a-f0-9]+)"/',$response['raw'],$realCsrf);
    preg_match('/name="submission_token" value="([a-f0-9]+)"/',$response['raw'],$realToken);
    $realPost=$input+['operation'=>'save-draft','csrf_token'=>$realCsrf[1],'submission_token'=>$realToken[1]];
    $beforeReal=(int)$db->query('SELECT COUNT(*) FROM phieu_chuyen_kho')->fetchColumn();
    $check($request($base,$realPost)['status']===303 && (int)$db->query('SELECT COUNT(*) FROM phieu_chuyen_kho')->fetchColumn()===$beforeReal+1,
        'real login saves persisted draft header and detail');
    $check($httpStock === $db->query('SELECT * FROM ton_kho ORDER BY kho_id,hang_hoa_id')->fetchAll(PDO::FETCH_ASSOC), 'stock unchanged after all HTTP saves');
    curl_close($curl);
    if (in_array('--browser', $argv, true)) {
        $browser = proc_open(['node', __DIR__ . '/stock-transfer-browser.mjs', $base], [0 => STDIN, 1 => STDOUT, 2 => STDERR], $unused, $root);
        if (proc_close($browser) !== 0) { throw new RuntimeException('Browser checks failed'); }
    }
    $setup=new App\Services\StockTransferSetup($db);
    $passwordBefore=$db->query('SELECT mat_khau_hash FROM tai_khoan WHERE tai_khoan_id=1')->fetchColumn();
    $inventoryBefore=$db->query('SELECT * FROM ton_kho WHERE hang_hoa_id IN (1,2) ORDER BY kho_id,hang_hoa_id')->fetchAll(PDO::FETCH_ASSOC);
    $check($setup->prepare('test_warehouse')===1, 'setup uses existing warehouse login');
    $check($db->query('SELECT mat_khau_hash FROM tai_khoan WHERE tai_khoan_id=1')->fetchColumn()===$passwordBefore,
        'setup preserves existing password');
    $check($inventoryBefore===$db->query('SELECT * FROM ton_kho WHERE hang_hoa_id IN (1,2) ORDER BY kho_id,hang_hoa_id')->fetchAll(PDO::FETCH_ASSOC),
        'setup preserves existing quantities and reserved stock');
    $check(count($model->products(1))===14,'setup stocks catalog in actual account distributor');
    $check($setup->prepare('test_other_warehouse')===3 && count($model->sourceWarehouses(2))===1,
        'setup creates missing source warehouse for existing account');
    $newActor=$setup->prepare('warehouse-new','new-password-2026','TEST_B');
    $check((new App\Models\Account($db))->authenticate('warehouse-new','new-password-2026')===$newActor,
        'setup new account can authenticate using password hash');
    $countBefore=$db->query('SELECT COUNT(*) FROM ton_kho')->fetchColumn();
    $setup->prepare('warehouse-new',null,'TEST_B');
    $check($countBefore===$db->query('SELECT COUNT(*) FROM ton_kho')->fetchColumn(),'setup rerun does not duplicate stock');
    try { $setup->prepare('test_sales'); throw new RuntimeException('Expected role rejection'); }
    catch (DomainException $e) { $check(!$db->inTransaction(),'setup rejects non-warehouse account and rolls back'); }
    $seedProcess = static function () use ($root, $dbName): int {
        $child = proc_open([PHP_BINARY, __DIR__ . '/fixtures/stock-transfer-seed.php', '--seed'],
            [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $seedPipes, $root,
            array_merge(getenv(), ['PTUD_ST_TEST_DB' => $dbName]));
        fclose($seedPipes[0]);
        stream_get_contents($seedPipes[1]); fclose($seedPipes[1]);
        stream_get_contents($seedPipes[2]); fclose($seedPipes[2]);
        return proc_close($child);
    };
    $check($seedProcess() === 0 && $db->query("SELECT COUNT(*) FROM nha_phan_phoi WHERE ma_npp='ST_DEMO'")->fetchColumn() == 1,
        'CLI demo seed creates separate distributor');
    $seedActor = (int) $db->query("SELECT tai_khoan_id FROM tai_khoan WHERE ma_nguoi_dung='ST_DEMO_USER'")->fetchColumn();
    $check($model->actor($seedActor) !== null, 'CLI demo creates a usable warehouse actor');
    $dairyCodes = "'VNM_ST_CD180','VNM_ST_KD180','VNM_SC_CD100','VNM_SCU180','VNM_SD380','VNM_SB400',
        'TH_ST_CD180','TH_ST_KD180','TH_SC100','TH_SCU180','DLF_ST180','DLF_SC100'";
    $check($db->query("SELECT COUNT(*) FROM hang_hoa WHERE ma_hang IN ($dairyCodes)")->fetchColumn() == 12,
        'SQL catalog creates twelve dairy products');
    $check($db->query("SELECT COUNT(*) FROM nha_cung_cap WHERE ma_ncc IN ('NCC_VNM','NCC_TH','NCC_DLF')")->fetchColumn() == 3,
        'SQL catalog creates three supplier entries');
    $demoNpp = (int) $db->query("SELECT nha_phan_phoi_id FROM nha_phan_phoi WHERE ma_npp='ST_DEMO'")->fetchColumn();
    $demoSource = (int) $model->sourceWarehouses($demoNpp)[0]['kho_id'];
    $check(count($model->products($demoSource)) === 12, 'dairy products visible in source warehouse');
    $db->exec("UPDATE hang_hoa SET gia_le=12345.67 WHERE ma_hang='VNM_ST_CD180'");
    $db->exec("UPDATE ton_kho t JOIN hang_hoa h ON h.hang_hoa_id=t.hang_hoa_id SET t.so_luong_ton=73
        WHERE t.kho_id=$demoSource AND h.ma_hang='VNM_ST_CD180'");
    $beforeSeed = $db->query('SELECT * FROM ton_kho ORDER BY kho_id,hang_hoa_id')->fetchAll(PDO::FETCH_ASSOC);
    $check($seedProcess() === 0 && $beforeSeed === $db->query('SELECT * FROM ton_kho ORDER BY kho_id,hang_hoa_id')->fetchAll(PDO::FETCH_ASSOC),
        'CLI demo seed rerun preserves data');
    $check($db->query("SELECT gia_le FROM hang_hoa WHERE ma_hang='VNM_ST_CD180'")->fetchColumn() === '12345.67',
        'catalog reimport preserves edited prices');
    // Gia lap bo du lieu cu da co phieu de kiem tra nang cap nhan ma khong mat lich su.
    $db->exec("UPDATE nha_phan_phoi SET ten_npp='NPP thử chuyển kho' WHERE ma_npp='ST_DEMO'");
    $db->exec("UPDATE nhan_vien SET ho_ten='Quang · Thủ kho thử' WHERE ma_nhan_vien='ST_DEMO_TK'");
    $db->exec("UPDATE kho SET ten_kho='Kho tổng thử' WHERE kho_id=$demoSource");
    $db->exec("UPDATE hang_hoa SET ma_hang='ST_DEMO_A',ten_hang='Hàng thử chuyển kho A' WHERE hang_hoa_id=1");
    $documentBefore = $model->find($id, 1);
    $check($seedProcess() === 0, 'legacy label migration executes');
    $check($model->actor($seedActor)['ho_ten'] === 'Đỗ Văn Quang'
        && $model->sourceWarehouses($demoNpp)[0]['ten_kho'] === 'Kho Tổng', 'legacy account and warehouse labels updated');
    $check($db->query('SELECT trang_thai FROM hang_hoa WHERE hang_hoa_id=1')->fetchColumn() === 'NGUNG_KINH_DOANH'
        && $model->find($id, 1)['lines'][0]['hang_hoa_id'] === $documentBefore['lines'][0]['hang_hoa_id'],
        'retire legacy placeholder without deleting draft references');
    echo "\n$passed assertions passed.\n";
} finally {
    if (is_resource($process)) { proc_terminate($process); proc_close($process); }
    if ($db && $db->inTransaction()) { $db->rollBack(); }
    if (preg_match('/\Aptud_st_test_[a-f0-9]{12}\z/', $dbName)) {
        $server->exec("DROP DATABASE IF EXISTS `$dbName`");
    }
    if (is_file($log)) { unlink($log); }
    foreach (glob($sessionDir . '/sess_*') as $sessionFile) { unlink($sessionFile); }
    rmdir($sessionDir);
}
