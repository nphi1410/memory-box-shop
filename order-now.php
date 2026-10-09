<?php
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/config/database.php';
require_login();

if (!is_post()) redirect('index.php');
verify_csrf();

$productId = (int) ($_POST['product_id'] ?? 0);
if ($productId < 1) {
    flash('error', 'Sản phẩm không hợp lệ.');
    redirect('index.php');
}
redirect('checkout.php?product_id=' . $productId);
