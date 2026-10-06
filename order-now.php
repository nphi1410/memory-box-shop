<?php
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/config/database.php';
require_login();

if (!is_post()) redirect('index.php');
verify_csrf();

try {
    $orderId = create_order_from_product($pdo, current_user_id(), (int) ($_POST['product_id'] ?? 0));
    redirect('order-success.php?id=' . $orderId);
} catch (Throwable $e) {
    flash('error', 'Không thể tạo đơn hàng. Vui lòng thử lại.');
    redirect('index.php');
}
