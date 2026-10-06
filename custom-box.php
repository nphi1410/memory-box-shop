<?php
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/config/database.php';

$giftStmt = $pdo->query("SELECT id, name, price, image_url, description FROM products WHERE category = 'gift' AND is_active = 1 ORDER BY id");
$gifts = $giftStmt->fetchAll();

if (is_post()) {
    require_login();
    verify_csrf();
    $shape = (string) ($_POST['box_shape'] ?? 'square');
    $color = (string) ($_POST['box_color'] ?? '#f4a7b9');
    $giftIds = array_values(array_unique(array_filter(array_map('intval', $_POST['gift_ids'] ?? []))));
    $message = trim((string) ($_POST['message'] ?? ''));

    $basePrice = 89000.0;
    $giftRows = [];
    if ($giftIds) {
        $placeholders = implode(',', array_fill(0, count($giftIds), '?'));
        $stmt = $pdo->prepare("SELECT id, name, price FROM products WHERE id IN ($placeholders) AND category = 'gift'");
        $stmt->execute($giftIds);
        $giftRows = $stmt->fetchAll();
    }
    $estimated = $basePrice + array_sum(array_map(fn($g) => (float) $g['price'], $giftRows));
    $pdo->beginTransaction();
    try {
        $custom = $pdo->prepare('INSERT INTO custom_boxes (user_id, box_shape, box_color, gift_items, message, estimated_price) VALUES (?, ?, ?, ?, ?, ?)');
        $custom->execute([current_user_id(), $shape, $color, json_encode($giftRows, JSON_UNESCAPED_UNICODE), $message, $estimated]);
        $customBoxId = (int) $pdo->lastInsertId();

        $order = $pdo->prepare('INSERT INTO orders (user_id, order_code, total_amount, status, order_type, custom_box_id, custom_note) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $order->execute([current_user_id(), make_order_code(), $estimated, 'placed', 'custom', $customBoxId, $message]);
        $orderId = (int) $pdo->lastInsertId();

        $meta = json_encode(['shape' => $shape, 'color' => $color, 'gift_items' => $giftRows], JSON_UNESCAPED_UNICODE);
        $item = $pdo->prepare('INSERT INTO order_items (order_id, product_id, item_name, unit_price, quantity, meta_json) VALUES (?, NULL, ?, ?, 1, ?)');
        $item->execute([$orderId, 'Hộp quà tự thiết kế', $estimated, $meta]);
        $pdo->commit();
        redirect('order-success.php?id=' . $orderId);
    } catch (Throwable $e) {
        $pdo->rollBack();
        flash('error', 'Không thể lưu thiết kế. Vui lòng thử lại.');
        redirect('custom-box.php');
    }
}

$pageTitle = 'Tự thiết kế hộp quà';
require_once __DIR__ . '/includes/header.php';
?>
<section class="section custom-builder-section">
    <div class="container">
        <div class="builder-intro"><span class="eyebrow">Tự thiết kế hộp quà</span><h1>Biến ý tưởng của bạn thành một món quà</h1><p>Trang này chia đúng 2 phần: chọn <strong>hộp quà</strong> và chọn <strong>quà bên trong</strong>. Không cần thanh toán online.</p></div>
        <form method="post" class="builder-grid" data-builder>
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <div class="builder-main">
                <section class="builder-panel">
                    <div class="step-title"><span>01</span><div><h2>Chọn hộp quà</h2><p>Chọn hình dáng và màu bạn thích.</p></div></div>
                    <div class="shape-grid">
                        <label class="choice-card"><input type="radio" name="box_shape" value="square" checked><span class="shape shape-square">□</span><strong>Hộp vuông</strong></label>
                        <label class="choice-card"><input type="radio" name="box_shape" value="heart"><span class="shape shape-heart">♡</span><strong>Hộp trái tim</strong></label>
                        <label class="choice-card"><input type="radio" name="box_shape" value="round"><span class="shape shape-round">○</span><strong>Hộp tròn</strong></label>
                    </div>
                    <label class="color-picker-label">Màu hộp <input type="color" name="box_color" value="#f4a7b9" data-color-input><span data-color-value>#f4a7b9</span></label>
                </section>

                <section class="builder-panel">
                    <div class="step-title"><span>02</span><div><h2>Chọn quà bên trong</h2><p>Nến thơm, thiệp, scrapbook và nhiều món nhỏ khác.</p></div></div>
                    <div class="gift-choice-grid">
                        <?php foreach ($gifts as $gift): ?>
                            <label class="gift-choice-card"><input type="checkbox" name="gift_ids[]" value="<?= (int) $gift['id'] ?>" data-gift-price="<?= (float) $gift['price'] ?>"><img src="<?= e($gift['image_url']) ?>" alt="<?= e($gift['name']) ?>"><span><strong><?= e($gift['name']) ?></strong><small><?= money($gift['price']) ?></small></span><span class="checkmark">✓</span></label>
                        <?php endforeach; ?>
                    </div>
                    <label>Lời nhắn cho người nhận<textarea name="message" rows="4" maxlength="500" placeholder="Ví dụ: Chúc cậu luôn vui và gặp nhiều điều dễ thương 🌷"></textarea></label>
                </section>
            </div>

            <aside class="builder-preview">
                <span class="eyebrow">Xem trước</span>
                <div class="preview-box" data-preview-box><span>🎁</span></div>
                <h3>Hộp quà của bạn</h3>
                <div class="preview-line"><span>Giá hộp cơ bản</span><strong><?= money(89000) ?></strong></div>
                <div class="preview-line"><span>Quà đã chọn</span><strong data-selected-count>0 món</strong></div>
                <div class="preview-total"><span>Tạm tính</span><strong data-builder-total><?= money(89000) ?></strong></div>
                <?php if (current_user_id()): ?>
                    <button class="btn btn-primary btn-full" type="submit">Đặt hộp quà này</button>
                <?php else: ?>
                    <a class="btn btn-primary btn-full" href="login.php">Đăng nhập để đặt</a>
                <?php endif; ?>
                <small>Giá demo, chưa gồm vận chuyển. Không thanh toán online.</small>
            </aside>
        </form>
    </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
