<?php
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/config/database.php';
require_login();

$productId = (int) ($_GET['product_id'] ?? $_POST['product_id'] ?? 0);
$stmt = $pdo->prepare('SELECT id, name, image_url FROM products WHERE id = ?');
$stmt->execute([$productId]);
$product = $stmt->fetch();
if (!$product) redirect('index.php');

if (is_post()) {
    verify_csrf();
    $rating = max(1, min(5, (int) ($_POST['rating'] ?? 5)));
    $comment = trim((string) ($_POST['comment'] ?? ''));
    if ($comment === '') {
        flash('error', 'Hãy viết một chút cảm nhận trước khi gửi.');
    } else {
        $insert = $pdo->prepare('INSERT INTO feedback (user_id, product_id, rating, comment) VALUES (?, ?, ?, ?)');
        $insert->execute([current_user_id(), $productId, $rating, $comment]);
        flash('success', 'Cảm ơn bạn đã gửi feedback!');
    }
    redirect('feedback.php?product_id=' . $productId);
}

$reviews = $pdo->prepare('SELECT f.rating, f.comment, f.created_at, u.full_name FROM feedback f JOIN users u ON u.id = f.user_id WHERE f.product_id = ? ORDER BY f.id DESC');
$reviews->execute([$productId]);
$reviews = $reviews->fetchAll();

$pageTitle = 'Feedback';
require_once __DIR__ . '/includes/header.php';
?>
<section class="section">
    <div class="container feedback-layout">
        <div class="panel">
            <div class="feedback-product"><img src="<?= e($product['image_url']) ?>" alt="<?= e($product['name']) ?>"><div><span class="eyebrow">Đánh giá sản phẩm</span><h1><?= e($product['name']) ?></h1></div></div>
            <form method="post" class="form-stack">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>"><input type="hidden" name="product_id" value="<?= $productId ?>">
                <label>Số sao<select name="rating"><option value="5">★★★★★ - Rất thích</option><option value="4">★★★★☆ - Tốt</option><option value="3">★★★☆☆ - Bình thường</option><option value="2">★★☆☆☆ - Chưa ổn</option><option value="1">★☆☆☆☆ - Không thích</option></select></label>
                <label>Cảm nhận<textarea name="comment" rows="5" required placeholder="Bạn thấy sản phẩm này thế nào?"></textarea></label>
                <button class="btn btn-primary" type="submit">Gửi feedback</button>
            </form>
        </div>
        <div class="review-list"><h2>Feedback gần đây</h2>
            <?php if (!$reviews): ?><p class="muted">Chưa có feedback nào.</p><?php endif; ?>
            <?php foreach ($reviews as $review): ?><article class="review-card"><div><strong><?= e($review['full_name']) ?></strong><span><?= str_repeat('★', (int) $review['rating']) ?></span></div><p><?= e($review['comment']) ?></p><small><?= e(date('d/m/Y', strtotime($review['created_at']))) ?></small></article><?php endforeach; ?>
        </div>
    </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
