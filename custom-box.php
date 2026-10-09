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
    $action = (string) ($_POST['action'] ?? 'order_now');
    if (!in_array($shape, ['square', 'heart', 'round'], true)) $shape = 'square';
    if (!preg_match('/^#[0-9a-fA-F]{6}$/', $color)) $color = '#f4a7b9';
    $giftIds = array_values(array_unique(array_filter(array_map('intval', $_POST['gift_ids'] ?? []))));
    $message = mb_substr(trim((string) ($_POST['message'] ?? '')), 0, 500);

    $basePrice = 89000.0;
    $giftRows = [];
    if ($giftIds) {
        $placeholders = implode(',', array_fill(0, count($giftIds), '?'));
        $stmt = $pdo->prepare("SELECT id, name, price, image_url FROM products WHERE id IN ($placeholders) AND category = 'gift' AND is_active = 1");
        $stmt->execute($giftIds);
        $giftRows = $stmt->fetchAll();
    }
    $estimated = $basePrice + array_sum(array_map(fn($g) => (float) $g['price'], $giftRows));

    if ($action === 'add_to_cart') {
        $stmt = $pdo->prepare('INSERT INTO custom_cart_items (user_id, box_shape, box_color, gift_items, message, estimated_price) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([current_user_id(), $shape, $color, json_encode($giftRows, JSON_UNESCAPED_UNICODE), $message, $estimated]);
        flash('success', 'Đã thêm hộp quà tự thiết kế vào giỏ hàng.');
        redirect('cart.php');
    }

    $stmt = $pdo->prepare('INSERT INTO custom_cart_items (user_id, box_shape, box_color, gift_items, message, estimated_price) VALUES (?, ?, ?, ?, ?, ?)');
    $stmt->execute([current_user_id(), $shape, $color, json_encode($giftRows, JSON_UNESCAPED_UNICODE), $message, $estimated]);
    redirect('checkout.php?custom_id=' . (int) $pdo->lastInsertId());
}

$pageTitle = 'Tự thiết kế hộp quà';
require_once __DIR__ . '/includes/header.php';
?>
<section class="section custom-builder-section">
    <div class="container">
        <div class="builder-intro"><span class="eyebrow">Tự thiết kế hộp quà</span><h1>Biến ý tưởng của bạn thành một món quà</h1><p>Trang này chia đúng 2 phần: chọn <strong>hộp quà</strong> và chọn <strong>quà bên trong</strong>. Chọn cách thanh toán ở bước xác nhận đơn.</p></div>
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
                            <?php $giftIcon = match (basename($gift['image_url'])) {
                                'candle.svg' => '🕯️',
                                'scrapbook.svg' => '📔',
                                'card.svg' => '💌',
                                'flower.svg' => '💐',
                                default => '🎁',
                            }; ?>
                            <label class="gift-choice-card"><input type="checkbox" name="gift_ids[]" value="<?= (int) $gift['id'] ?>" data-gift-price="<?= (float) $gift['price'] ?>" data-gift-name="<?= e($gift['name']) ?>" data-gift-icon="<?= e($giftIcon) ?>" data-gift-image="<?= e($gift['image_url']) ?>?v=gift-art-2"><img src="<?= e($gift['image_url']) ?>?v=gift-art-2" alt=""><span><strong><?= e($gift['name']) ?></strong><small><?= money($gift['price']) ?></small></span><span class="checkmark" aria-hidden="true">✓</span></label>
                        <?php endforeach; ?>
                    </div>
                    <div class="chosen-gifts" data-chosen-gifts aria-live="polite"><span class="chosen-gifts-label">Món quà bạn đã chọn</span><div class="chosen-gifts-list" data-chosen-gifts-list><p>Chưa có món quà nào. Chọn một món ở trên để bắt đầu nhé.</p></div></div>
                    <label>Lời nhắn cho người nhận<textarea name="message" rows="4" maxlength="500" placeholder="Ví dụ: Chúc cậu luôn vui và gặp nhiều điều dễ thương 🌷"></textarea></label>
                </section>
            </div>

            <aside class="builder-preview">
                <span class="eyebrow">Xem trước</span>
                    <div class="preview-box" data-preview-box data-shape="square" aria-label="Mô phỏng hộp quà đã chọn">
                    <div class="preview-box-scene">
                        <div class="preview-lid" aria-hidden="true"><span></span></div>
                        <div class="preview-container">
                            <div class="preview-items" data-preview-items aria-live="polite"><span class="preview-empty">Quà bạn chọn sẽ<br>xuất hiện ở đây</span></div>
                            <div class="preview-front" aria-hidden="true"><span></span></div>
                        </div>
                    </div>
                </div>
                <h3>Hộp quà của bạn</h3>
                <div class="preview-line"><span>Giá hộp cơ bản</span><strong><?= money(89000) ?></strong></div>
                <div class="preview-line"><span>Quà đã chọn</span><strong data-selected-count>0 món</strong></div>
                <div class="preview-total"><span>Tạm tính</span><strong data-builder-total><?= money(89000) ?></strong></div>
                <?php if (current_user_id()): ?>
                    <div class="preview-actions">
                        <button class="btn btn-secondary btn-full" type="submit" name="action" value="add_to_cart">Thêm vào giỏ hàng</button>
                        <button class="btn btn-primary btn-full" type="submit" name="action" value="order_now">Thanh toán hộp quà này</button>
                    </div>
                <?php else: ?>
                    <a class="btn btn-primary btn-full" href="login.php">Đăng nhập để thêm vào giỏ</a>
                    <small class="preview-login-note">Bạn cần đăng nhập để lưu thiết kế hoặc đặt hộp quà.</small>
                <?php endif; ?>
                <small>Giá demo, chưa gồm vận chuyển. Giao dịch thanh toán chưa được tích hợp.</small>
            </aside>
        </form>
    </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
