<?php
require_once __DIR__ . '/../src/config.php';
require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/helpers.php';
require_once __DIR__ . '/../src/auth.php';
require_login();
$pdo = db();
$user = current_user();
$orders = $pdo->prepare('SELECT * FROM orders WHERE user_id=:u ORDER BY id DESC');
$orders->execute([':u'=>$user['id']]);
$orders = $orders->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="it">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>I miei ordini - Profumeria</title>
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
  <h2>I miei ordini</h2>
  <table class="table">
    <thead><tr><th>ID</th><th>Data</th><th>Totale</th><th>Status</th></tr></thead>
    <tbody>
    <?php foreach ($orders as $o): ?>
      <tr>
        <td><?=$o['id']?></td>
        <td><?=htmlspecialchars($o['created_at'])?></td>
        <td>€ <?=number_format($o['total_amount'],2,',','.')?></td>
        <td><?=htmlspecialchars($o['status'])?></td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$orders): ?>
      <tr><td colspan="4">Nessun ordine</td></tr>
    <?php endif; ?>
    </tbody>
  </table>
</main>
<script src="assets/js/main.js"></script>
</body>
</html>
