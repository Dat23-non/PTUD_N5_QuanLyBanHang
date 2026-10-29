<?php
declare(strict_types=1);
namespace App\Controllers;
use App\Core\Controller;
use App\Core\Database;
use App\Models\Account;
use Throwable;
final class AuthController extends Controller
{
    public function index(): void
    {
        $_SESSION['auth_csrf'] ??= bin2hex(random_bytes(32));
        $error = null;
        $username = is_string($_POST['username'] ?? null) ? trim($_POST['username']) : '';
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        if (!in_array($method, ['GET','POST'], true)) { header('Allow: GET, POST'); http_response_code(405); return; }
        if ($method === 'POST') {
            $token = $_POST['csrf_token'] ?? '';
            if (!is_string($token) || !hash_equals($_SESSION['auth_csrf'], $token)) {
                http_response_code(403); $error = 'Phiên biểu mẫu không hợp lệ. Hãy tải lại trang.';
            } elseif (($_POST['operation'] ?? '') === 'logout') {
                $_SESSION = []; session_regenerate_id(true);
                header('Location: index.php?page=login', true, 303); return;
            } elseif (($_SESSION['auth_block_until'] ?? 0) > time()) {
                http_response_code(429); $error = 'Bạn đã đăng nhập sai nhiều lần. Vui lòng đợi một phút.';
            } else {
                try {
                    $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
                    $id = strlen($username) <= 80 && strlen($password) <= 1024
                        ? (new Account(Database::connection()))->authenticate($username, $password) : null;
                    if ($id) {
                        $_SESSION = ['tai_khoan_id' => $id]; session_regenerate_id(true);
                        header('Location: index.php?page=stock-transfer', true, 303); return;
                    }
                    $_SESSION['auth_failures'] = ($_SESSION['auth_failures'] ?? 0) + 1;
                    if ($_SESSION['auth_failures'] >= 5) { $_SESSION['auth_block_until'] = time() + 60; $_SESSION['auth_failures'] = 0; }
                    http_response_code(422); $error = 'Thông tin đăng nhập không đúng hoặc tài khoản chưa đủ điều kiện hoạt động.';
                } catch (Throwable $e) {
                    http_response_code(503); $error = 'Chưa kết nối được dữ liệu. Hãy chạy bước khởi tạo theo README.';
                    error_log('Login failed: ' . get_class($e));
                }
            }
        }
        $this->view('auth/login.php', ['pageTitle'=>'Đăng nhập','error'=>$error,'username'=>$username,'csrf'=>$_SESSION['auth_csrf']]);
    }
}
