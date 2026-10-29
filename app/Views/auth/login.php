<section class="page-header"><div><p class="eyebrow">TÀI KHOẢN</p><h1>Đăng nhập</h1><p class="page-description">Sử dụng tài khoản được cấp trong hệ thống.</p></div></section>
<section class="panel form-panel">
    <?php if ($error): ?><div class="notice notice-info" role="alert"><?= e($error) ?></div><?php endif; ?>
    <form method="post" action="index.php?page=login">
        <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
        <?php if (isset($_SESSION['tai_khoan_id'])): ?>
        <p>Bạn đang đăng nhập. <a href="index.php?page=stock-transfer">Mở chuyển kho</a></p>
        <button class="button button-primary" name="operation" value="logout">Đăng xuất</button>
        <?php else: ?>
        <label for="username">Tên đăng nhập</label><input id="username" name="username" maxlength="80" autocomplete="username" value="<?= e($username) ?>" required>
        <label for="password">Mật khẩu</label><input id="password" name="password" type="password" autocomplete="current-password" required>
        <button class="button button-primary" type="submit">Đăng nhập</button>
        <?php endif; ?>
    </form>
</section>
