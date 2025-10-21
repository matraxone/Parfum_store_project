<?php
require_once __DIR__ . '/../src/config.php';
require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/helpers.php';
require_once __DIR__ . '/../src/auth.php';
require_once __DIR__ . '/../src/mailer.php';
require_login();

$pdo = db();
$cart = cart_items($pdo);
if (!$cart) { redirect('cart.php'); }

$errors = [];
if ($_SERVER['REQUEST_METHOD']==='POST') {
  require_csrf();
  // Controllo età (base)
  $user = current_user();
  if (!age_allowed($user)) {
    $errors[] = 'Occorre essere maggiorenni o avere consenso genitoriale.';
  }

  if (!$errors) {
    try {
      $pdo->beginTransaction();
      $stmt = $pdo->prepare("INSERT INTO orders (user_id,total_amount,status,created_at) VALUES (:uid,:tot,'pending',NOW())");
      $grand = 0; foreach ($cart as $row) { $grand += $row['price']*$row['qty']; }
      $stmt->execute([':uid'=>$user['id'], ':tot'=>$grand]);
      $orderId = (int)$pdo->lastInsertId();

      $oi = $pdo->prepare("INSERT INTO order_items (order_id,product_id,price,quantity) VALUES (:o,:p,:pr,:q)");
      // Use distinct placeholders to avoid HY093 with native prepares when the same name is reused
      $ps = $pdo->prepare("UPDATE products SET stock=stock-:q1 WHERE id=:p AND stock>=:q2");
      foreach ($cart as $row) {
        $oi->execute([':o'=>$orderId, ':p'=>$row['id'], ':pr'=>$row['price'], ':q'=>$row['qty']]);
        $ps->execute([':p'=>$row['id'], ':q1'=>$row['qty'], ':q2'=>$row['qty']]);
        if ($ps->rowCount()===0) { throw new Exception('Stock insufficiente'); }
      }

      $pdo->commit();
      cart_clear();
      send_order_confirmation($user['email'], $orderId);
      redirect('index.php?ok=1');
    } catch (Throwable $e) {
      if ($pdo->inTransaction()) { $pdo->rollBack(); }
      $errors[] = 'Errore ordine: ' . $e->getMessage();
    }
  }
}
?>
<!doctype html>
<html lang="it">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Checkout - Profumeria</title>
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
  <h2>Checkout</h2>
  <?php if ($errors): ?><div class="alert"><?=htmlspecialchars(implode("\n", $errors))?></div><?php endif; ?>
  <form method="post">
    <?=csrf_field()?>
    <p>Pagamento simulato. Cliccando Conferma, l'ordine verrà registrato.</p>
    <button type="submit">Conferma ordine</button>
  </form>
</main>
<script src="assets/js/main.js"></script>
</body>
</html>
