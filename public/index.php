<?php
require_once __DIR__ . '/../src/config.php';
require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/helpers.php';
require_once __DIR__ . '/../src/auth.php';
require_once __DIR__ . '/../src/bootstrap.php';

$bootstrap = bootstrap_once();
$pdo = db();
$search = trim($_GET['q'] ?? '');
$sql = "SELECT p.* FROM products p WHERE p.is_active=1";
$params = [];
if ($search !== '') { $sql .= " AND (p.name LIKE :q OR p.description LIKE :q)"; $params[':q'] = "%$search%"; }
$sql .= " ORDER BY p.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="it">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Profumeria</title>
  <link rel="stylesheet" href="assets/css/style.css" />
</head>
<body>
<header>
  <h1><a href="index.php">Profumeria</a></h1>
  <nav>
  <button id="themeToggle" class="theme-btn" type="button" aria-label="Toggle theme">🌙</button>
    <?php if (is_logged_in()) : ?>
      <span>Ciao, <?=htmlspecialchars(current_user()['name'] ?? 'Utente')?>!</span>
  <a href="cart.php">Carrello</a>
  <a href="orders.php">Ordini</a>
      <a href="logout.php">Logout</a>
    <?php else: ?>
      <a href="login.php">Login</a>
      <a href="register.php">Registrati</a>
    <?php endif; ?>
  </nav>
</header>
<main class="container">
  <form class="search" method="get" action="index.php">
    <input type="text" name="q" value="<?=htmlspecialchars($search)?>" placeholder="Cerca prodotti..." />
    <button type="submit">Cerca</button>
  </form>

  <div class="grid">
    <?php foreach ($products as $p): ?>
  <a class="card" href="product.php?id=<?=$p['id']?>">
        <img src="<?=image_url($p['image_path'])?>" alt="<?=htmlspecialchars($p['name'])?>" />
        <div class="card-body">
          <h3><?=htmlspecialchars($p['name'])?></h3>
          <p class="price">€ <?=number_format($p['price'], 2, ',', '.')?></p>
        </div>
      </a>
    <?php endforeach; ?>
    <?php if (empty($products)): ?>
      <p>Nessun prodotto trovato.</p>
    <?php endif; ?>
  </div>
</main>
<script src="assets/js/main.js"></script>
</body>
</html>
