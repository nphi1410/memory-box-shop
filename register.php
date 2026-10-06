<?php
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/config/database.php';

if (current_user_id()) {
    redirect('index.php');
}

$error = '';
if (is_post()) {
    verify_csrf();
    $fullName = trim((string) ($_POST['full_name'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $confirm = (string) ($_POST['confirm_password'] ?? '');

    if (mb_strlen($fullName) < 2) {
        $error = 'Họ tên cần có ít nhất 2 ký tự.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Email chưa hợp lệ.';
    } elseif (strlen($password) < 6) {
        $error = 'Mật khẩu cần có ít nhất 6 ký tự.';
    } elseif ($password !== $confirm) {
        $error = 'Mật khẩu nhập lại chưa khớp.';
    } else {
        $exists = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $exists->execute([$email]);
        if ($exists->fetch()) {
            $error = 'Email này đã được đăng ký.';
        } else {
            $stmt = $pdo->prepare('INSERT INTO users (full_name, email, password_hash) VALUES (?, ?, ?)');
            $stmt->execute([$fullName, $email, password_hash($password, PASSWORD_DEFAULT)]);
            $_SESSION['user_id'] = (int) $pdo->lastInsertId();
            flash('success', 'Tạo tài khoản thành công!');
            redirect('index.php');
        }
    }
}

$pageTitle = 'Đăng ký';
require_once __DIR__ . '/includes/header.php';
?>
<section class="auth-section">
    <div class="auth-card">
        <div class="auth-icon">🎀</div>
        <span class="eyebrow">Tạo tài khoản miễn phí</span>
        <h1>Đăng ký</h1>
        <p>Chỉ mất vài giây để bắt đầu tạo hộp quà của riêng bạn.</p>
        <?php if ($error): ?><div class="form-alert error"><?= e($error) ?></div><?php endif; ?>
        <form method="post" class="form-stack">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <label>Họ và tên<input type="text" name="full_name" required autocomplete="name" value="<?= e($_POST['full_name'] ?? '') ?>"></label>
            <label>Email<input type="email" name="email" required autocomplete="email" value="<?= e($_POST['email'] ?? '') ?>"></label>
            <label>Mật khẩu<input type="password" name="password" required autocomplete="new-password"></label>
            <label>Nhập lại mật khẩu<input type="password" name="confirm_password" required autocomplete="new-password"></label>
            <button class="btn btn-primary btn-full" type="submit">Tạo tài khoản</button>
        </form>
        <p class="auth-switch">Đã có tài khoản? <a href="login.php">Đăng nhập</a></p>
    </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
