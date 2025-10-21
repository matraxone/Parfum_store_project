<?php
require_once __DIR__ . '/../src/config.php';
require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/helpers.php';
require_once __DIR__ . '/../src/auth.php';

$pdo = db();
$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT p.* FROM products p WHERE p.id=:id AND p.is_active=1");
$stmt->execute([':id'=>$id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$product) { http_response_code(404); echo "Prodotto non trovato"; exit; }

if ($_SERVER['REQUEST_METHOD']==='POST') {
  require_csrf();
  $qty = max(1, (int)($_POST['qty'] ?? 1));
  cart_add($id, $qty);
  redirect('cart.php');
}
?>
<!doctype html>
<html lang="it">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?=htmlspecialchars($product['name'])?> - Profumeria</title>
  <link rel="stylesheet" href="assets/css/style.css" />
</head>
<body>
<header>
  <h1><a href="index.php">Profumeria</a></h1>
  <nav>
  <button id="themeToggle" class="theme-btn" type="button" aria-label="Toggle theme">🌙</button>
  </nav>
</header>
<main class="container">
  <div class="product">
    <img src="<?=image_url($product['image_path'])?>" alt="<?=htmlspecialchars($product['name'])?>" />
    <div>
      <h2><?=htmlspecialchars($product['name'])?></h2>
      <p><?=nl2br(htmlspecialchars($product['description']))?></p>
      <p class="price">€ <?=number_format($product['price'], 2, ',', '.')?></p>
  <?php if (!empty($product['volume_ml'])): ?>
  <p class="muted">Volume: <?=htmlspecialchars((string)$product['volume_ml'])?> ml</p>
  <?php endif; ?>
      <p>Disponibilità: <?=$product['stock']>0? 'In stock' : 'Esaurito'?></p>
      <?php if ($product['stock']>0): ?>
      <form method="post">
        <?=csrf_field()?>
        <input type="number" name="qty" min="1" max="<?=max(1,(int)$product['stock'])?>" value="1" />
        <button type="submit">Aggiungi al carrello</button>
      </form>
      <?php endif; ?>
    </div>
  </div>
</main>
<script src="assets/js/main.js"></script>
</body>
</html>
