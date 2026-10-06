<?php
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/config/database.php';
require_login();

if (!is_post()) {
    redirect('index.php');
}
verify_csrf();
$productId = (int) ($_POST['product_id'] ?? 0);

$exists = $pdo->prepare('SELECT id FROM products WHERE id = ? AND is_active = 1');
$exists->execute([$productId]);
if (!$exists->fetch()) {
    flash('error', 'Sản phẩm không tồn tại.');
    redirect('index.php');
}

$stmt = $pdo->prepare('INSERT INTO cart_items (user_id, product_id, quantity) VALUES (?, ?, 1) ON DUPLICATE KEY UPDATE quantity = quantity + 1');
$stmt->execute([current_user_id(), $productId]);
flash('success', 'Đã thêm sản phẩm vào giỏ hàng.');
redirect($_SERVER['HTTP_REFERER'] ?? 'index.php');
