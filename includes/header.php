<?php
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/../config/database.php';

$pageTitle = $pageTitle ?? 'Memory Box';
$userId = current_user_id();
$user = $userId ? get_user($pdo, $userId) : null;
$cartQty = $userId ? cart_count($pdo, $userId) : 0;
$flashes = consume_flashes();
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Memory Box - hộp quà tự thiết kế và quà tặng thủ công cảm xúc">
    <title><?= e($pageTitle) ?> | Memory Box</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css?v=checkout-1">
</head>
<body>
<header class="site-header">
    <div class="container nav-wrap">
        <a class="brand" href="index.php" aria-label="Memory Box - Trang chủ">
            <span class="brand-mark">🎁</span>
            <span><strong>Memory Box</strong><small>Trạm Gói Yêu Thương</small></span>
        </a>
        <button class="mobile-menu" type="button" data-menu-toggle aria-label="Mở menu">☰</button>
        <nav class="nav-links" data-menu>
            <a href="index.php">Trang chủ</a>
            <a href="custom-box.php">Tự thiết kế</a>
            <?php if ($user): ?>
                <a href="orders.php">Đơn hàng</a>
                <a class="cart-link" href="cart.php">🛒 Giỏ hàng <span class="cart-badge"><?= $cartQty ?></span></a>
                <a class="avatar-link" href="profile.php" title="Tài khoản của bạn">
                    <span class="avatar-circle"><?= e(mb_strtoupper(mb_substr($user['full_name'], 0, 1))) ?></span>
                    <span class="desktop-only"><?= e($user['full_name']) ?></span>
                </a>
            <?php else: ?>
                <a href="login.php">Đăng nhập</a>
                <a class="btn btn-small btn-primary" href="register.php">Đăng ký</a>
            <?php endif; ?>
        </nav>
    </div>
</header>

<?php if ($flashes): ?>
<div class="toast-stack" aria-live="polite">
    <?php foreach ($flashes as $flash): ?>
        <div class="toast <?= e($flash['type']) ?>" data-toast><?= e($flash['message']) ?></div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<main>
