<?php
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/config/database.php';
require_login();

if (!is_post()) redirect('cart.php');
verify_csrf();
$userId = current_user_id();

$stmt = $pdo->prepare('SELECT c.quantity, p.id, p.name, p.price FROM cart_items c JOIN products p ON p.id = c.product_id WHERE c.user_id = ?');
$stmt->execute([$userId]);
$items = $stmt->fetchAll();
$customStmt = $pdo->prepare('SELECT id, box_shape, box_color, gift_items, message, estimated_price FROM custom_cart_items WHERE user_id = ? ORDER BY id');
$customStmt->execute([$userId]);
$customItems = $customStmt->fetchAll();
if (!$items && !$customItems) {
    flash('error', 'Giỏ hàng đang trống.');
    redirect('cart.php');
}

$total = array_reduce($items, fn($sum, $item) => $sum + ((float) $item['price'] * (int) $item['quantity']), 0.0)
    + array_sum(array_map(fn($item) => (float) $item['estimated_price'], $customItems));
$pdo->beginTransaction();
try {
    $customBoxStmt = $pdo->prepare('INSERT INTO custom_boxes (user_id, box_shape, box_color, gift_items, message, estimated_price) VALUES (?, ?, ?, ?, ?, ?)');
    $customOrderItems = [];
    foreach ($customItems as $customItem) {
        $customBoxStmt->execute([$userId, $customItem['box_shape'], $customItem['box_color'], $customItem['gift_items'], $customItem['message'], $customItem['estimated_price']]);
        $customOrderItems[] = ['cart_item' => $customItem, 'custom_box_id' => (int) $pdo->lastInsertId()];
    }
    $firstCustomBoxId = $customOrderItems[0]['custom_box_id'] ?? null;
    $orderType = $items ? 'cart' : 'custom';
    $orderNote = $customItems[0]['message'] ?? null;
    $orderStmt = $pdo->prepare('INSERT INTO orders (user_id, order_code, total_amount, status, order_type, custom_box_id, custom_note) VALUES (?, ?, ?, ?, ?, ?, ?)');
    $orderStmt->execute([$userId, make_order_code(), $total, 'placed', $orderType, $firstCustomBoxId, $orderNote]);
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
    $pdo->prepare('DELETE FROM cart_items WHERE user_id = ?')->execute([$userId]);
    $pdo->prepare('DELETE FROM custom_cart_items WHERE user_id = ?')->execute([$userId]);
    $pdo->commit();
    redirect('order-success.php?id=' . $orderId);
} catch (Throwable $e) {
    $pdo->rollBack();
    flash('error', 'Có lỗi khi tạo đơn hàng.');
    redirect('cart.php');
}
