<?php
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/config/database.php';
require_login();

$orderId = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT id, order_code, total_amount, status, payment_method, created_at FROM orders WHERE id = ? AND user_id = ?');
$stmt->execute([$orderId, current_user_id()]);
$order = $stmt->fetch();
if (!$order) redirect('orders.php');

$pageTitle = 'Đặt hàng thành công';
require_once __DIR__ . '/includes/header.php';
?>
<section class="success-section">
    <div class="success-card">
        <div class="success-check">✓</div>
        <span class="eyebrow">Đơn hàng đã được ghi nhận</span>
        <h1>Đặt hàng thành công!</h1>
        <p>Cảm ơn bạn đã chọn Memory Box. Shop sẽ liên hệ để xác nhận đơn hàng và thông tin thanh toán.</p>
        <div class="order-ticket">
            <div><span>Mã đơn</span><strong><?= e($order['order_code']) ?></strong></div>
            <div><span>Trạng thái</span><strong class="status-pill">Đã đặt hàng</strong></div>
            <div><span>Tổng tiền</span><strong><?= money($order['total_amount']) ?></strong></div>
            <?php if ($order['payment_method'] ?? null): ?><div><span>Thanh toán</span><strong><?= $order['payment_method'] === 'cod' ? 'Khi nhận hàng' : 'Chuyển khoản ngân hàng' ?></strong></div><?php endif; ?>
        </div>
        <div class="hero-actions"><a class="btn btn-primary" href="orders.php">Xem đơn hàng</a><a class="btn btn-secondary" href="index.php">Về trang chủ</a></div>
    </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
