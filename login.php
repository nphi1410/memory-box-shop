<?php
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/config/database.php';

if (current_user_id()) {
    redirect('index.php');
}

$error = '';
if (is_post()) {
    verify_csrf();
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    $stmt = $pdo->prepare('SELECT id, password_hash FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['id'];
        flash('success', 'Đăng nhập thành công. Chào mừng bạn quay lại!');
        redirect('index.php');
    }
    $error = 'Email hoặc mật khẩu chưa đúng.';
}

$pageTitle = 'Đăng nhập';
require_once __DIR__ . '/includes/header.php';
?>
<section class="auth-section">
    <div class="auth-card">
        <div class="auth-icon">💝</div>
        <span class="eyebrow">Chào mừng trở lại</span>
        <h1>Đăng nhập</h1>
        <p>Đăng nhập để lưu giỏ hàng, đặt quà và xem trạng thái đơn.</p>
        <?php if ($error): ?><div class="form-alert error"><?= e($error) ?></div><?php endif; ?>
        <form method="post" class="form-stack">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <label>Email<input type="email" name="email" required autocomplete="email" placeholder="you@example.com"></label>
            <label>Mật khẩu<input type="password" name="password" required autocomplete="current-password" placeholder="••••••••"></label>
            <div class="form-row between"><label class="check"><input type="checkbox" name="remember"> Ghi nhớ</label><a href="forgot-password.php">Quên mật khẩu?</a></div>
            <button class="btn btn-primary btn-full" type="submit">Đăng nhập</button>
        </form>
        <p class="auth-switch">Chưa có tài khoản? <a href="register.php">Đăng ký ngay</a></p>
        <div class="demo-account">Demo: <strong>demo@memorybox.local</strong> / <strong>Demo@123</strong></div>
    </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
