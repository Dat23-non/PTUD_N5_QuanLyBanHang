<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$root=dirname(__DIR__);
require $root.'/app/Core/autoload.php';
try {
    if (!is_file($root.'/config/database.php')) { copy($root.'/config/database.example.php',$root.'/config/database.php'); }
    $config=require $root.'/config/database.php';
    if (!preg_match('/\A[a-zA-Z0-9_]+\z/',$config['database'])) { throw new RuntimeException('Invalid database name'); }
    $db=new PDO(sprintf('mysql:host=%s;port=%s;charset=utf8mb4',$config['host'],$config['port']),$config['username'],$config['password'],
        [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]);
    $name=$config['database'];
    $db->exec("CREATE DATABASE IF NOT EXISTS `$name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $db->exec("USE `$name`");
    if (!$db->query("SHOW TABLES LIKE 'tai_khoan'")->fetchColumn()) {
        $schema=file_get_contents($root.'/database/schema_v1_1.sql');
        $schema=str_replace('ptud_quan_ly_ban_hang',$name,$schema); $db->exec($schema);
    }
    $username=$argv[1]??'test-warehouse';
    $stmt=$db->prepare('SELECT 1 FROM tai_khoan WHERE ten_dang_nhap=?');$stmt->execute([$username]);
    $password=null;
    if (!$stmt->fetchColumn()) {
        fwrite(STDOUT,"New account password (8-72 bytes; input is visible in this terminal): ");
        $password=rtrim(fgets(STDIN)?:'',"\r\n");
    }
    (new App\Services\StockTransferSetup($db))->prepare($username,$password,$argv[2]??null);
    echo "Ready. Sign in with $username. Existing passwords and stock quantities were preserved.\n";
} catch (Throwable $e) {
    fwrite(STDERR,'Setup failed: '.($e instanceof DomainException?$e->getMessage():'Check MySQL, config/database.php and schema_v1_1.sql compatibility.').PHP_EOL);
    exit(1);
}
