<?php
require_once __DIR__ . '/../src/config.php';
require_once __DIR__ . '/../src/auth.php';
require_once __DIR__ . '/../src/bootstrap.php';
require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/helpers.php';
bootstrap_once();
if (!is_admin()) { redirect('../public/login.php'); }
$pdo = db();
$users = $pdo->query('SELECT id,name,email,role,birthdate,parent_consent,created_at FROM users ORDER BY id DESC')->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="it">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Admin Utenti - Profumeria</title>
  <link rel="stylesheet" href="../public/assets/css/style.css">
</head>
<body>
<header>
  <h1><a href="index.php">Admin Utenti</a></h1>
  <nav>
    <a href="products.php">Prodotti</a>
    <a href="orders.php">Ordini</a>
    <a href="../public/index.php">Shop</a>
    <a href="email_config.php">Config Email</a>
    <button id="themeToggle" class="theme-btn" type="button" aria-label="Toggle theme">🌙</button>
  </nav>
</header>
<main class="container">
  <h1>Utenti</h1>
  <form method="get" class="search" style="margin:12px 0">
    <input type="text" name="q" value="<?=htmlspecialchars($_GET['q'] ?? '')?>" placeholder="Cerca per nome o email">
    <button type="submit">Cerca</button>
  </form>
  <?php if (!empty($_GET['q'])): 
    $q = '%'.$_GET['q'].'%';
    $stmt = $pdo->prepare('SELECT id,name,email,role,birthdate,parent_consent,created_at FROM users WHERE name LIKE :q OR email LIKE :q ORDER BY id DESC');
    $stmt->execute([':q'=>$q]);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
  endif; ?>
  <table class="table">
    <thead><tr><th>ID</th><th>Nome</th><th>Email</th><th>Ruolo</th><th>Età/Consenso</th><th>Creato</th></tr></thead>
    <tbody>
    <?php foreach ($users as $u): $age = age_from_birthdate($u['birthdate']); ?>
      <tr>
        <td><a href="crm_user.php?id=<?=$u['id']?>"><?=$u['id']?></a></td>
        <td><a href="crm_user.php?id=<?=$u['id']?>"><?=
          htmlspecialchars($u['name'])?></a></td>
        <td><a href="crm_user.php?id=<?=$u['id']?>"><?=
          htmlspecialchars($u['email'])?></a></td>
        <td><?=htmlspecialchars($u['role'])?></td>
        <td><?=($age!==null?$age.' anni':'n/d')?><?= $u['parent_consent']? ' (consenso)' : '' ?></td>
        <td><?=htmlspecialchars($u['created_at'])?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</main>
<script src="../public/assets/js/main.js"></script>
</body>
</html>
