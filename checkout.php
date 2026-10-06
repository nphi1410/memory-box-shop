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
if (!$items) {
    flash('error', 'Giỏ hàng đang trống.');
    redirect('cart.php');
}

$total = array_reduce($items, fn($sum, $item) => $sum + ((float) $item['price'] * (int) $item['quantity']), 0.0);
$pdo->beginTransaction();
try {
    $orderStmt = $pdo->prepare('INSERT INTO orders (user_id, order_code, total_amount, status, order_type) VALUES (?, ?, ?, ?, ?)');
    $orderStmt->execute([$userId, make_order_code(), $total, 'placed', 'cart']);
    $orderId = (int) $pdo->lastInsertId();

    $itemStmt = $pdo->prepare('INSERT INTO order_items (order_id, product_id, item_name, unit_price, quantity) VALUES (?, ?, ?, ?, ?)');
    foreach ($items as $item) {
        $itemStmt->execute([$orderId, $item['id'], $item['name'], $item['price'], $item['quantity']]);
    }
    $pdo->prepare('DELETE FROM cart_items WHERE user_id = ?')->execute([$userId]);
    $pdo->commit();
    redirect('order-success.php?id=' . $orderId);
} catch (Throwable $e) {
    $pdo->rollBack();
    flash('error', 'Có lỗi khi tạo đơn hàng.');
    redirect('cart.php');
}
