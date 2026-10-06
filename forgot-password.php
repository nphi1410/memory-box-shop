<?php
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/config/database.php';

$message = '';
$error = '';
if (is_post()) {
    verify_csrf();
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 6) {
        $error = 'Vui lòng nhập email hợp lệ và mật khẩu mới từ 6 ký tự.';
    } else {
        $stmt = $pdo->prepare('UPDATE users SET password_hash = ? WHERE email = ?');
        $stmt->execute([password_hash($password, PASSWORD_DEFAULT), $email]);
        $message = $stmt->rowCount() ? 'Đã cập nhật mật khẩu. Bạn có thể đăng nhập lại.' : 'Không tìm thấy tài khoản với email này.';
    }
}

$pageTitle = 'Quên mật khẩu';
require_once __DIR__ . '/includes/header.php';
?>
<section class="auth-section">
    <div class="auth-card">
        <div class="auth-icon">🔐</div>
        <span class="eyebrow">Khôi phục tài khoản</span>
        <h1>Quên mật khẩu</h1>
        <p>Vì đây là bản local demo, hệ thống cho phép đặt lại mật khẩu trực tiếp bằng email, không gửi email reset.</p>
        <?php if ($error): ?><div class="form-alert error"><?= e($error) ?></div><?php endif; ?>
        <?php if ($message): ?><div class="form-alert success"><?= e($message) ?></div><?php endif; ?>
        <form method="post" class="form-stack">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <label>Email<input type="email" name="email" required></label>
            <label>Mật khẩu mới<input type="password" name="password" minlength="6" required></label>
            <button class="btn btn-primary btn-full" type="submit">Đặt lại mật khẩu</button>
        </form>
        <p class="auth-switch"><a href="login.php">← Quay lại đăng nhập</a></p>
    </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
