<?php
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/config/database.php';
require_login();

$userId = current_user_id();
$error = '';
if (is_post()) {
    verify_csrf();
    $fullName = trim((string) ($_POST['full_name'] ?? ''));
    $phone = trim((string) ($_POST['phone'] ?? ''));
    $address = trim((string) ($_POST['address'] ?? ''));

    if (mb_strlen($fullName) < 2) {
        $error = 'Họ tên chưa hợp lệ.';
    } else {
        $stmt = $pdo->prepare('UPDATE users SET full_name = ?, phone = ?, address = ? WHERE id = ?');
        $stmt->execute([$fullName, $phone, $address, $userId]);
        flash('success', 'Đã cập nhật thông tin cá nhân.');
        redirect('profile.php');
    }
}

$user = get_user($pdo, $userId);
$pageTitle = 'Tài khoản';
require_once __DIR__ . '/includes/header.php';
?>
<section class="section">
    <div class="container profile-layout">
        <aside class="profile-card">
            <div class="profile-avatar"><?= e(mb_strtoupper(mb_substr($user['full_name'], 0, 1))) ?></div>
            <h2><?= e($user['full_name']) ?></h2>
            <p><?= e($user['email']) ?></p>
            <a class="btn btn-secondary btn-full" href="orders.php">Xem đơn hàng</a>
            <a class="btn btn-ghost btn-full" href="logout.php">Đăng xuất</a>
        </aside>
        <div class="panel">
            <span class="eyebrow">Hồ sơ cá nhân</span>
            <h1>Thông tin của bạn</h1>
            <p>Cập nhật thông tin giao nhận cơ bản cho các đơn hàng local demo.</p>
            <?php if ($error): ?><div class="form-alert error"><?= e($error) ?></div><?php endif; ?>
            <form method="post" class="form-stack">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <label>Họ và tên<input type="text" name="full_name" value="<?= e($user['full_name']) ?>" required></label>
                <label>Email<input type="email" value="<?= e($user['email']) ?>" disabled><small>Email dùng để đăng nhập nên không sửa trong demo.</small></label>
                <label>Số điện thoại<input type="text" name="phone" value="<?= e($user['phone']) ?>" placeholder="09xxxxxxxx"></label>
                <label>Địa chỉ<textarea name="address" rows="4" placeholder="Địa chỉ nhận hàng"><?= e($user['address']) ?></textarea></label>
                <button class="btn btn-primary" type="submit">Lưu thay đổi</button>
            </form>
        </div>
    </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
