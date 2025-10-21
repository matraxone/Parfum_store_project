<?php
require_once __DIR__ . '/../src/config.php';
require_once __DIR__ . '/../src/auth.php';
require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/helpers.php';
require_once __DIR__ . '/../src/bootstrap.php';
require_once __DIR__ . '/../src/audit.php';
bootstrap_once();
if (!is_admin()) { redirect('../public/login.php'); }
$pdo = db();

if ($_SERVER['REQUEST_METHOD']==='POST') {
  require_csrf();
  $id = (int)($_POST['id'] ?? 0);
  $status = $_POST['status'] ?? 'pending';
  $stmt = $pdo->prepare('UPDATE orders SET status=:s WHERE id=:id');
  $stmt->execute([':s'=>$status, ':id'=>$id]);
  audit_log('order.status_update', json_encode(['id'=>$id,'status'=>$status]));
}

$orders = $pdo->query('SELECT o.*, u.email FROM orders o JOIN users u ON u.id=o.user_id ORDER BY o.id DESC')->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="it">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Admin Ordini - Profumeria</title>
  <link rel="stylesheet" href="../public/assets/css/style.css">
</head>
<body>
<header>
  <h1><a href="index.php">Admin Ordini</a></h1>
  <nav>
    <a href="products.php">Prodotti</a>
  <a href="users.php">Utenti</a>
  <a href="email_config.php">Config Email</a>
    <a href="../public/index.php">Shop</a>
    <button id="themeToggle" class="theme-btn" type="button" aria-label="Toggle theme">🌙</button>
  </nav>
</header>
<main class="container">
  <h1>Ordini</h1>
  <table class="table">
    <thead><tr><th>ID</th><th>Utente</th><th>Totale</th><th>Status</th><th>Azioni</th></tr></thead>
    <tbody>
    <?php foreach ($orders as $o): ?>
      <tr>
        <td><?=$o['id']?></td>
        <td><?=htmlspecialchars($o['email'])?></td>
        <td>€ <?=number_format($o['total_amount'],2,',','.')?></td>
        <td><?=htmlspecialchars($o['status'])?></td>
        <td>
          <form method="post" style="display:inline-flex;gap:6px;align-items:center">
            <?=csrf_field()?>
            <input type="hidden" name="id" value="<?=$o['id']?>">
            <select name="status">
              <?php foreach (['pending','paid','shipped','cancelled'] as $s): ?>
                <option <?=$o['status']===$s?'selected':''?>><?=$s?></option>
              <?php endforeach; ?>
            </select>
            <button type="submit">Aggiorna</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</main>
<script src="../public/assets/js/main.js"></script>
</body>
</html>
