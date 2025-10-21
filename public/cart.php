<?php
require_once __DIR__ . '/../src/config.php';
require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/helpers.php';
require_once __DIR__ . '/../src/auth.php';

$pdo = db();
$cart = cart_items($pdo);

if ($_SERVER['REQUEST_METHOD']==='POST') {
  require_csrf();
  if (isset($_POST['update'])) {
    foreach (($_POST['qty'] ?? []) as $pid=>$qty) {
      cart_set((int)$pid, max(0, (int)$qty));
    }
    redirect('cart.php');
  }
  if (isset($_POST['clear'])) {
    cart_clear();
    redirect('cart.php');
  }
}
?>
<!doctype html>
<html lang="it">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Carrello - Profumeria</title>
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
  <h2>Carrello</h2>
  <form method="post">
    <?=csrf_field()?>
    <table class="table">
      <thead>
        <tr><th>Prodotto</th><th>Prezzo</th><th>Qtà</th><th>Totale</th></tr>
      </thead>
      <tbody>
      <?php $grand=0; foreach ($cart as $row): $total=$row['price']*$row['qty']; $grand+=$total; ?>
        <tr>
          <td><?=htmlspecialchars($row['name'])?></td>
          <td>€ <?=number_format($row['price'],2,',','.')?></td>
          <td><input type="number" name="qty[<?=$row['id']?>]" min="0" max="<?=$row['stock']?>" value="<?=$row['qty']?>"></td>
          <td>€ <?=number_format($total,2,',','.')?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
      <tfoot>
        <tr><td colspan="3" class="right">Totale</td><td>€ <?=number_format($grand,2,',','.')?></td></tr>
      </tfoot>
    </table>
    <div class="actions">
      <button type="submit" name="update">Aggiorna</button>
      <button type="submit" name="clear">Svuota</button>
      <a class="btn" href="checkout.php">Checkout</a>
    </div>
  </form>
</main>
<script src="assets/js/main.js"></script>
</body>
</html>
