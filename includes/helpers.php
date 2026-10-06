<?php

declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): never
{
    header('Location: ' . $path);
    exit;
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function current_user_id(): ?int
{
    return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
}

function require_login(): void
{
    if (!current_user_id()) {
        flash('error', 'Bạn cần đăng nhập để sử dụng tính năng này.');
        redirect('login.php');
    }
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function consume_flashes(): array
{
    $flashes = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $flashes;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf(): void
{
    $token = $_POST['csrf_token'] ?? '';
    if (!$token || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(419);
        exit('Phiên làm việc không hợp lệ. Vui lòng tải lại trang và thử lại.');
    }
}

function get_user(PDO $pdo, int $userId): ?array
{
    $stmt = $pdo->prepare('SELECT id, full_name, email, phone, address, avatar_url, created_at FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    return $stmt->fetch() ?: null;
}

function cart_count(PDO $pdo, int $userId): int
{
    $stmt = $pdo->prepare('SELECT COALESCE(SUM(quantity), 0) FROM cart_items WHERE user_id = ?');
    $stmt->execute([$userId]);
    $productCount = (int) $stmt->fetchColumn();
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM custom_cart_items WHERE user_id = ?');
    $stmt->execute([$userId]);
    return $productCount + (int) $stmt->fetchColumn();
}

function money(float|int|string $amount): string
{
    return number_format((float) $amount, 0, ',', '.') . '₫';
}

function make_order_code(): string
{
    return 'MB' . date('ymd') . strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
}

function create_order_from_product(PDO $pdo, int $userId, int $productId): int
{
    $stmt = $pdo->prepare('SELECT id, name, price FROM products WHERE id = ? AND is_active = 1');
    $stmt->execute([$productId]);
    $product = $stmt->fetch();
    if (!$product) {
        throw new RuntimeException('Sản phẩm không tồn tại.');
    }

    $pdo->beginTransaction();
    try {
        $order = $pdo->prepare('INSERT INTO orders (user_id, order_code, total_amount, status, order_type) VALUES (?, ?, ?, ?, ?)');
        $order->execute([$userId, make_order_code(), $product['price'], 'placed', 'product']);
        $orderId = (int) $pdo->lastInsertId();

        $item = $pdo->prepare('INSERT INTO order_items (order_id, product_id, item_name, unit_price, quantity) VALUES (?, ?, ?, ?, 1)');
        $item->execute([$orderId, $product['id'], $product['name'], $product['price']]);
        $pdo->commit();
        return $orderId;
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}
