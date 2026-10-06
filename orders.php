<?php
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/config/database.php';
require_login();

$stmt = $pdo->prepare('SELECT id, order_code, total_amount, status, order_type, created_at FROM orders WHERE user_id = ? ORDER BY id DESC');
$stmt->execute([current_user_id()]);
$orders = $stmt->fetchAll();

$statusMap = ['placed' => 'Đã đặt hàng', 'confirmed' => 'Đã xác nhận', 'completed' => 'Hoàn thành', 'cancelled' => 'Đã hủy'];
$pageTitle = 'Đơn hàng';
require_once __DIR__ . '/includes/header.php';
?>
<section class="section">
    <div class="container">
        <div class="section-head"><div><span class="eyebrow">Lịch sử</span><h1>Đơn hàng của bạn</h1></div><a href="index.php#products">Mua thêm quà →</a></div>
        <?php if (!$orders): ?>
            <div class="empty-state"><div>📦</div><h2>Chưa có đơn hàng</h2><p>Đơn hàng mới sẽ xuất hiện ở đây.</p><a class="btn btn-primary" href="index.php#products">Chọn sản phẩm</a></div>
        <?php else: ?>
            <div class="orders-table-wrap"><table class="orders-table"><thead><tr><th>Mã đơn</th><th>Loại</th><th>Ngày tạo</th><th>Trạng thái</th><th>Tổng</th></tr></thead><tbody>
            <?php foreach ($orders as $order): ?><tr><td><strong><?= e($order['order_code']) ?></strong></td><td><?= e(ucfirst($order['order_type'])) ?></td><td><?= e(date('d/m/Y H:i', strtotime($order['created_at']))) ?></td><td><span class="status-pill"><?= e($statusMap[$order['status']] ?? $order['status']) ?></span></td><td><strong><?= money($order['total_amount']) ?></strong></td></tr><?php endforeach; ?>
            </tbody></table></div>
        <?php endif; ?>
    </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
