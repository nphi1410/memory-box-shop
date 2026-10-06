<?php
$pageTitle = 'Trang chủ';
require_once __DIR__ . '/includes/header.php';

$stmt = $pdo->query('SELECT id, name, category, price, description, image_url FROM products WHERE is_active = 1 ORDER BY category, id');
$products = $stmt->fetchAll();
?>
<section class="hero">
    <div class="container hero-grid">
        <div class="hero-copy">
            <span class="eyebrow">💌 Gói cảm xúc thành món quà</span>
            <h1>Một chiếc hộp nhỏ,<br><span>mang cả yêu thương.</span></h1>
            <p>Chọn hộp quà có sẵn hoặc tự phối màu, kiểu hộp và món quà bên trong theo cách của riêng bạn.</p>
            <div class="hero-actions">
                <a class="btn btn-primary" href="#products">Khám phá sản phẩm</a>
                <a class="btn btn-secondary" href="custom-box.php">Tự thiết kế hộp quà</a>
            </div>
            <div class="hero-stats">
                <div><strong>20+</strong><span>Mẫu quà</span></div>
                <div><strong>100%</strong><span>Tự phối</span></div>
                <div><strong>0₫</strong><span>Thanh toán online</span></div>
            </div>
        </div>
        <div class="hero-art">
            <div class="gift-orbit orbit-one">🌷</div>
            <div class="gift-orbit orbit-two">🕯️</div>
            <div class="gift-card-big">
                <span class="ribbon">For someone special</span>
                <div class="gift-emoji">🎁</div>
                <h3>Memory Box</h3>
                <p>Chọn hộp • Chọn quà • Gửi lời nhắn</p>
            </div>
        </div>
    </div>
</section>

<section class="section" id="products">
    <div class="container">
        <div class="section-head">
            <div>
                <span class="eyebrow">Sản phẩm nổi bật</span>
                <h2>Chọn món quà bạn thích</h2>
            </div>
            <div class="filter-tabs" data-filter-tabs>
                <button class="active" type="button" data-filter="all">Tất cả</button>
                <button type="button" data-filter="box">Hộp quà</button>
                <button type="button" data-filter="gift">Quà bên trong</button>
            </div>
        </div>

        <div class="product-grid" data-product-grid>
            <?php foreach ($products as $product): ?>
                <article class="product-card" data-category="<?= e($product['category']) ?>">
                    <div class="product-image-wrap">
                        <img src="<?= e($product['image_url']) ?>" alt="<?= e($product['name']) ?>" class="product-image">
                        <span class="product-chip"><?= $product['category'] === 'box' ? 'Hộp quà' : 'Quà tặng' ?></span>
                    </div>
                    <div class="product-body">
                        <h3><?= e($product['name']) ?></h3>
                        <p><?= e($product['description']) ?></p>
                        <div class="product-price"><?= money($product['price']) ?></div>
                        <div class="product-actions">
                            <form action="add-to-cart.php" method="post">
                                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
                                <button class="btn btn-icon" type="submit" title="Thêm vào giỏ">🛒 <span>Thêm giỏ</span></button>
                            </form>
                            <form action="order-now.php" method="post">
                                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
                                <button class="btn btn-primary" type="submit">Đặt hàng</button>
                            </form>
                            <a class="btn btn-ghost" href="feedback.php?product_id=<?= (int) $product['id'] ?>">Feedback</a>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section soft-section">
    <div class="container custom-cta">
        <div>
            <span class="eyebrow">Không thấy đúng mẫu?</span>
            <h2>Tự tạo một hộp quà chỉ của bạn</h2>
            <p>Chọn kiểu hộp, màu sắc, nến thơm, thiệp, scrapbook và lời nhắn riêng.</p>
        </div>
        <a class="btn btn-primary" href="custom-box.php">Bắt đầu thiết kế →</a>
    </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
