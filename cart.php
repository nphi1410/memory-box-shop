<?php
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/config/database.php';
require_login();

$userId = current_user_id();
if (is_post()) {
    verify_csrf();
    $action = $_POST['action'] ?? '';
    $itemId = (int) ($_POST['item_id'] ?? 0);
    if ($action === 'remove') {
        $stmt = $pdo->prepare('DELETE FROM cart_items WHERE id = ? AND user_id = ?');
        $stmt->execute([$itemId, $userId]);
        flash('success', 'Đã xóa sản phẩm khỏi giỏ.');
    } elseif ($action === 'remove_custom') {
        $stmt = $pdo->prepare('DELETE FROM custom_cart_items WHERE id = ? AND user_id = ?');
        $stmt->execute([$itemId, $userId]);
        flash('success', 'Đã xóa hộp quà tự thiết kế khỏi giỏ.');
    } elseif ($action === 'update') {
        $qty = max(1, min(99, (int) ($_POST['quantity'] ?? 1)));
        $stmt = $pdo->prepare('UPDATE cart_items SET quantity = ? WHERE id = ? AND user_id = ?');
        $stmt->execute([$qty, $itemId, $userId]);
        flash('success', 'Đã cập nhật số lượng.');
    }
    redirect('cart.php');
}

$stmt = $pdo->prepare('SELECT c.id AS cart_id, c.quantity, p.id AS product_id, p.name, p.price, p.image_url FROM cart_items c JOIN products p ON p.id = c.product_id WHERE c.user_id = ? ORDER BY c.id DESC');
$stmt->execute([$userId]);
$items = $stmt->fetchAll();
$customStmt = $pdo->prepare('SELECT id, box_shape, box_color, gift_items, message, estimated_price FROM custom_cart_items WHERE user_id = ? ORDER BY id DESC');
$customStmt->execute([$userId]);
$customItems = $customStmt->fetchAll();
$total = array_reduce($items, fn($sum, $item) => $sum + ((float) $item['price'] * (int) $item['quantity']), 0.0)
    + array_sum(array_map(fn($item) => (float) $item['estimated_price'], $customItems));
$shapeLabels = ['square' => 'Hộp vuông', 'heart' => 'Hộp trái tim', 'round' => 'Hộp tròn'];
$shapeIcons = ['square' => '□', 'heart' => '♡', 'round' => '○'];

$pageTitle = 'Giỏ hàng';
require_once __DIR__ . '/includes/header.php';
?>
<section class="section">
    <div class="container">
        <div class="section-head"><div><span class="eyebrow">Giỏ hàng của bạn</span><h1>Những món quà đã chọn</h1></div><a href="index.php#products">← Tiếp tục mua</a></div>
        <?php if (!$items && !$customItems): ?>
            <div class="empty-state"><div>🛒</div><h2>Giỏ hàng đang trống</h2><p>Hãy chọn một món quà thật xinh trước nhé.</p><a class="btn btn-primary" href="index.php#products">Xem sản phẩm</a></div>
        <?php else: ?>
            <div class="cart-layout">
                <div class="cart-list">
                    <?php foreach ($items as $item): ?>
                        <div class="cart-item">
                            <img src="<?= e($item['image_url']) ?>" alt="<?= e($item['name']) ?>">
                            <div class="cart-item-info"><h3><?= e($item['name']) ?></h3><strong><?= money($item['price']) ?></strong></div>
                            <form method="post" class="qty-form">
                                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>"><input type="hidden" name="action" value="update"><input type="hidden" name="item_id" value="<?= (int) $item['cart_id'] ?>">
                                <input type="number" name="quantity" min="1" max="99" value="<?= (int) $item['quantity'] ?>"><button class="btn btn-ghost" type="submit">Cập nhật</button>
                            </form>
                            <form method="post"><input type="hidden" name="csrf_token" value="<?= csrf_token() ?>"><input type="hidden" name="action" value="remove"><input type="hidden" name="item_id" value="<?= (int) $item['cart_id'] ?>"><button class="icon-danger" type="submit" aria-label="Xóa">✕</button></form>
                        </div>
                    <?php endforeach; ?>
                    <?php foreach ($customItems as $item): ?>
                        <?php $giftItems = json_decode($item['gift_items'] ?: '[]', true) ?: []; ?>
                        <div class="cart-item cart-custom-item">
                            <div class="cart-custom-art" style="--box-color: <?= e($item['box_color']) ?>"><span><?= e($shapeIcons[$item['box_shape']] ?? '□') ?></span></div>
                            <div class="cart-item-info">
                                <h3>Hộp quà tự thiết kế</h3>
                                <strong><?= money($item['estimated_price']) ?></strong>
                                <small class="cart-custom-shape"><?= e($shapeLabels[$item['box_shape']] ?? 'Hộp quà') ?> · Màu <?= e($item['box_color']) ?></small>
                                <?php if ($giftItems): ?><div class="cart-custom-gifts"><?php foreach ($giftItems as $gift): ?><span><?= e($gift['name'] ?? 'Quà tặng') ?></span><?php endforeach; ?></div><?php endif; ?>
                                <?php if ($item['message']): ?><small class="cart-custom-message">“<?= e($item['message']) ?>”</small><?php endif; ?>
                            </div>
                            <span class="cart-custom-quantity">1 hộp</span>
                            <form method="post"><input type="hidden" name="csrf_token" value="<?= csrf_token() ?>"><input type="hidden" name="action" value="remove_custom"><input type="hidden" name="item_id" value="<?= (int) $item['id'] ?>"><button class="icon-danger" type="submit" aria-label="Xóa hộp quà tự thiết kế">✕</button></form>
                        </div>
                    <?php endforeach; ?>
                </div>
                <aside class="order-summary">
                    <h3>Tóm tắt đơn hàng</h3>
                    <div><span>Tạm tính</span><strong><?= money($total) ?></strong></div>
                    <div><span>Phí vận chuyển</span><strong>Liên hệ sau</strong></div>
                    <div class="summary-total"><span>Tổng</span><strong><?= money($total) ?></strong></div>
                    <p>Chọn thông tin giao hàng và phương thức thanh toán ở bước tiếp theo.</p>
                    <a class="btn btn-primary btn-full" href="checkout.php">Tiến hành thanh toán →</a>
                </aside>
            </div>
        <?php endif; ?>
    </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
