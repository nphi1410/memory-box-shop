<?php
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/config/database.php';
require_login();

$userId = current_user_id();
$user = get_user($pdo, $userId);
$directProductId = max(0, (int) ($_GET['product_id'] ?? 0));
$directCustomId = max(0, (int) ($_GET['custom_id'] ?? 0));
if ($directProductId && $directCustomId) redirect('cart.php');

$items = [];
$customItems = [];
if ($directProductId) {
    $productStmt = $pdo->prepare('SELECT id, name, price, image_url, 1 AS quantity FROM products WHERE id = ? AND is_active = 1');
    $productStmt->execute([$directProductId]);
    $directProduct = $productStmt->fetch();
    if ($directProduct) $items = [$directProduct];
} elseif ($directCustomId) {
    $customStmt = $pdo->prepare('SELECT id, box_shape, box_color, gift_items, message, estimated_price FROM custom_cart_items WHERE id = ? AND user_id = ?');
    $customStmt->execute([$directCustomId, $userId]);
    $directCustom = $customStmt->fetch();
    if ($directCustom) $customItems = [$directCustom];
} else {
    $cartStmt = $pdo->prepare('SELECT c.quantity, p.id, p.name, p.price, p.image_url FROM cart_items c JOIN products p ON p.id = c.product_id WHERE c.user_id = ? ORDER BY c.id DESC');
    $cartStmt->execute([$userId]);
    $items = $cartStmt->fetchAll();
    $customStmt = $pdo->prepare('SELECT id, box_shape, box_color, gift_items, message, estimated_price FROM custom_cart_items WHERE user_id = ? ORDER BY id');
    $customStmt->execute([$userId]);
    $customItems = $customStmt->fetchAll();
}

if (!$items && !$customItems) {
    flash('error', $directProductId || $directCustomId ? 'Món quà này không còn khả dụng.' : 'Giỏ hàng đang trống.');
    redirect($directCustomId ? 'custom-box.php' : 'cart.php');
}

$total = array_reduce($items, fn($sum, $item) => $sum + ((float) $item['price'] * (int) $item['quantity']), 0.0)
    + array_sum(array_map(fn($item) => (float) $item['estimated_price'], $customItems));
$itemCount = array_sum(array_map(fn($item) => (int) $item['quantity'], $items)) + count($customItems);
$checkoutUrl = 'checkout.php' . ($directProductId ? '?product_id=' . $directProductId : ($directCustomId ? '?custom_id=' . $directCustomId : ''));
$backUrl = $directCustomId ? 'custom-box.php' : ($directProductId ? 'index.php#products' : 'cart.php');
$shapeLabels = ['square' => 'Hộp vuông', 'heart' => 'Hộp trái tim', 'round' => 'Hộp tròn'];
$shapeIcons = ['square' => '□', 'heart' => '♡', 'round' => '○'];
$errors = [];
$shippingName = trim((string) ($user['full_name'] ?? ''));
$shippingPhone = trim((string) ($user['phone'] ?? ''));
$shippingAddress = trim((string) ($user['address'] ?? ''));
$paymentMethod = 'cod';

if (is_post()) {
    verify_csrf();
    $shippingName = trim((string) ($_POST['shipping_name'] ?? ''));
    $shippingPhone = trim((string) ($_POST['shipping_phone'] ?? ''));
    $shippingAddress = trim((string) ($_POST['shipping_address'] ?? ''));
    $paymentMethod = (string) ($_POST['payment_method'] ?? '');

    if ($shippingName === '' || mb_strlen($shippingName) > 120) $errors[] = 'Vui lòng nhập họ tên người nhận (tối đa 120 ký tự).';
    if ($shippingPhone === '' || mb_strlen($shippingPhone) > 30) $errors[] = 'Vui lòng nhập số điện thoại hợp lệ.';
    if ($shippingAddress === '' || mb_strlen($shippingAddress) > 1000) $errors[] = 'Vui lòng nhập địa chỉ giao hàng (tối đa 1.000 ký tự).';
    if (!in_array($paymentMethod, ['cod', 'bank_transfer'], true)) $errors[] = 'Vui lòng chọn phương thức thanh toán.';

    if (!$errors) {
        $pdo->beginTransaction();
        try {
            $customBoxStmt = $pdo->prepare('INSERT INTO custom_boxes (user_id, box_shape, box_color, gift_items, message, estimated_price) VALUES (?, ?, ?, ?, ?, ?)');
            $customOrderItems = [];
            foreach ($customItems as $customItem) {
                $customBoxStmt->execute([$userId, $customItem['box_shape'], $customItem['box_color'], $customItem['gift_items'], $customItem['message'], $customItem['estimated_price']]);
                $customOrderItems[] = ['cart_item' => $customItem, 'custom_box_id' => (int) $pdo->lastInsertId()];
            }
            $firstCustomBoxId = $customOrderItems[0]['custom_box_id'] ?? null;
            $orderType = $directProductId ? 'product' : ($items ? 'cart' : 'custom');
            $orderNote = $customItems[0]['message'] ?? null;
            $orderStmt = $pdo->prepare('INSERT INTO orders (user_id, order_code, total_amount, status, order_type, custom_box_id, custom_note, shipping_name, shipping_phone, shipping_address, payment_method) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
            $orderStmt->execute([$userId, make_order_code(), $total, 'placed', $orderType, $firstCustomBoxId, $orderNote, $shippingName, $shippingPhone, $shippingAddress, $paymentMethod]);
            $orderId = (int) $pdo->lastInsertId();

            $itemStmt = $pdo->prepare('INSERT INTO order_items (order_id, product_id, item_name, unit_price, quantity) VALUES (?, ?, ?, ?, ?)');
            foreach ($items as $item) {
                $itemStmt->execute([$orderId, $item['id'], $item['name'], $item['price'], $item['quantity']]);
            }
            $customItemStmt = $pdo->prepare('INSERT INTO order_items (order_id, product_id, item_name, unit_price, quantity, meta_json) VALUES (?, NULL, ?, ?, 1, ?)');
            foreach ($customOrderItems as $customOrderItem) {
                $customItem = $customOrderItem['cart_item'];
                $meta = json_encode([
                    'custom_box_id' => $customOrderItem['custom_box_id'],
                    'shape' => $customItem['box_shape'],
                    'color' => $customItem['box_color'],
                    'gift_items' => json_decode($customItem['gift_items'] ?: '[]', true) ?: [],
                    'message' => $customItem['message'],
                ], JSON_UNESCAPED_UNICODE);
                $customItemStmt->execute([$orderId, 'Hộp quà tự thiết kế', $customItem['estimated_price'], $meta]);
            }
            if ($directCustomId) {
                $pdo->prepare('DELETE FROM custom_cart_items WHERE id = ? AND user_id = ?')->execute([$directCustomId, $userId]);
            } elseif (!$directProductId) {
                $pdo->prepare('DELETE FROM cart_items WHERE user_id = ?')->execute([$userId]);
                $pdo->prepare('DELETE FROM custom_cart_items WHERE user_id = ?')->execute([$userId]);
            }
            $pdo->commit();
            redirect('order-success.php?id=' . $orderId);
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $errors[] = 'Có lỗi khi tạo đơn hàng. Vui lòng thử lại.';
        }
    }
}

$pageTitle = 'Thanh toán';
require_once __DIR__ . '/includes/header.php';
?>
<section class="section checkout-section">
    <div class="container">
        <div class="checkout-heading">
            <div><span class="eyebrow">Sắp hoàn tất món quà của bạn</span><h1>Thanh toán</h1><p>Kiểm tra thông tin nhận hàng và chọn cách thanh toán phù hợp.</p></div>
            <a href="<?= e($backUrl) ?>">← <?= $directProductId ? 'Quay lại sản phẩm' : ($directCustomId ? 'Quay lại thiết kế' : 'Quay lại giỏ hàng') ?></a>
        </div>
        <div class="checkout-steps" aria-label="Tiến trình đặt hàng"><span class="done"><b>✓</b> Giỏ hàng</span><i></i><span class="active"><b>2</b> Thanh toán</span><i></i><span><b>3</b> Hoàn tất</span></div>

        <?php if ($errors): ?><div class="form-alert error" role="alert"><?php foreach ($errors as $error): ?><div><?= e($error) ?></div><?php endforeach; ?></div><?php endif; ?>

        <form action="<?= e($checkoutUrl) ?>" method="post" class="checkout-layout">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <div class="checkout-main">
                <section class="checkout-card">
                    <div class="checkout-card-title"><span>01</span><div><h2>Thông tin nhận hàng</h2><p>Đơn hàng sẽ được giao đến địa chỉ này.</p></div></div>
                    <div class="checkout-fields">
                        <label>Họ và tên người nhận<input type="text" name="shipping_name" value="<?= e($shippingName) ?>" autocomplete="name" maxlength="120" required></label>
                        <label>Số điện thoại<input type="tel" name="shipping_phone" value="<?= e($shippingPhone) ?>" autocomplete="tel" maxlength="30" required></label>
                        <label class="checkout-address">Địa chỉ giao hàng<textarea name="shipping_address" rows="3" autocomplete="street-address" maxlength="1000" placeholder="Số nhà, đường, phường/xã, quận/huyện, tỉnh/thành" required><?= e($shippingAddress) ?></textarea></label>
                    </div>
                </section>

                <section class="checkout-card">
                    <div class="checkout-card-title"><span>02</span><div><h2>Phương thức thanh toán</h2><p>Chọn cách bạn muốn thanh toán cho đơn hàng.</p></div></div>
                    <div class="payment-options">
                        <label class="payment-option"><input type="radio" name="payment_method" value="cod" <?= $paymentMethod === 'cod' ? 'checked' : '' ?> required><span class="payment-icon">🚚</span><span class="payment-copy"><strong>Thanh toán khi nhận hàng</strong><small>Trả tiền trực tiếp cho đơn vị giao hàng.</small></span><span class="payment-radio"></span></label>
                        <label class="payment-option"><input type="radio" name="payment_method" value="bank_transfer" <?= $paymentMethod === 'bank_transfer' ? 'checked' : '' ?>><span class="payment-icon">🏦</span><span class="payment-copy"><strong>Chuyển khoản ngân hàng</strong><small>Thông tin chuyển khoản sẽ được xác nhận sau khi đặt hàng.</small></span><span class="payment-radio"></span></label>
                    </div>
                    <p class="checkout-demo-note">Đây là giao diện demo local. Chưa có giao dịch hay chuyển tiền tự động.</p>
                </section>
            </div>

            <aside class="checkout-summary">
                <h2>Đơn hàng của bạn <span><?= $itemCount ?> món</span></h2>
                <div class="checkout-items">
                    <?php foreach ($items as $item): ?>
                        <div class="checkout-item"><img src="<?= e($item['image_url']) ?>" alt=""><div><strong><?= e($item['name']) ?></strong><small>Số lượng: <?= (int) $item['quantity'] ?></small></div><b><?= money((float) $item['price'] * (int) $item['quantity']) ?></b></div>
                    <?php endforeach; ?>
                    <?php foreach ($customItems as $item): ?>
                        <div class="checkout-item"><div class="checkout-custom-thumb" style="--box-color: <?= e($item['box_color']) ?>"><?= e($shapeIcons[$item['box_shape']] ?? '□') ?></div><div><strong>Hộp quà tự thiết kế</strong><small><?= e($shapeLabels[$item['box_shape']] ?? 'Hộp quà') ?> · 1 hộp</small></div><b><?= money($item['estimated_price']) ?></b></div>
                    <?php endforeach; ?>
                </div>
                <div class="checkout-total-row"><span>Tạm tính</span><strong><?= money($total) ?></strong></div>
                <div class="checkout-total-row"><span>Phí vận chuyển</span><span>Liên hệ sau</span></div>
                <div class="checkout-grand-total"><span>Tổng cộng</span><strong><?= money($total) ?></strong></div>
                <button class="btn btn-primary btn-full checkout-submit" type="submit">🔒 Xác nhận đặt hàng</button>
                <p class="checkout-secure">Thông tin của bạn được dùng để xử lý đơn hàng.</p>
            </aside>
        </form>
    </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
