<?php
require_once __DIR__ . '/config.php';

function csrf_token(): string {
  if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
  }
  return $_SESSION['csrf'];
}
function csrf_field(): string {
  return '<input type="hidden" name="' . CSRF_TOKEN_NAME . '" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES) . '">';
}
function require_csrf(): void {
  $ok = isset($_POST[CSRF_TOKEN_NAME]) && hash_equals($_SESSION['csrf'] ?? '', $_POST[CSRF_TOKEN_NAME]);
  if (!$ok) { http_response_code(400); exit('Invalid CSRF'); }
}
function sanitize($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function redirect(string $path): never {
  if (str_starts_with($path, 'http')) { header('Location: ' . $path); exit; }
  header('Location: ' . $path);
  exit;
}
function image_url(?string $path): string {
  if (!$path) return 'https://via.placeholder.com/400x300?text=No+Image';
  return 'uploads/' . ltrim($path, '/');
}

// Cart helpers (session based)
function cart_add(int $productId, int $qty=1): void {
  $_SESSION['cart'][$productId] = ($_SESSION['cart'][$productId] ?? 0) + max(1,$qty);
}
function cart_set(int $productId, int $qty): void {
  if ($qty <= 0) { unset($_SESSION['cart'][$productId]); return; }
  $_SESSION['cart'][$productId] = $qty;
}
function cart_clear(): void { unset($_SESSION['cart']); }
function cart_items(PDO $pdo): array {
  $cart = $_SESSION['cart'] ?? [];
  if (!$cart) return [];
  $ids = array_map('intval', array_keys($cart));
  $in = implode(',', array_fill(0, count($ids), '?'));
  $stmt = $pdo->prepare("SELECT id,name,price,stock,image_path FROM products WHERE id IN ($in)");
  $stmt->execute($ids);
  $rows = $stmt->fetchAll();
  foreach ($rows as &$r) { $r['qty'] = $cart[$r['id']] ?? 0; }
  return $rows;
}
