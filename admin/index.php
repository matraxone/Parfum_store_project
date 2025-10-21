<?php
require_once __DIR__ . '/../src/config.php';
require_once __DIR__ . '/../src/auth.php';
require_once __DIR__ . '/../src/bootstrap.php';
bootstrap_once();
require_login();
if (!is_admin()) { redirect('../public/login.php'); }
?>
<!doctype html>
<html lang="it">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Admin - Profumeria</title>
  <link rel="stylesheet" href="../public/assets/css/style.css">
</head>
<body>
<header>
  <h1><a href="../public/index.php">Profumeria - Admin</a></h1>
  <nav>
    <a href="products.php">Prodotti</a>
    <a href="orders.php">Ordini</a>
    <a href="users.php">Utenti</a>
    <button id="themeToggle" class="theme-btn" type="button" aria-label="Toggle theme">🌙</button>
      <a href="email_config.php">Config Email</a>
  </nav>
</header>
<main class="container">
  <h1>Dashboard Admin</h1>
  <ul>
    <li><a href="products.php">Prodotti</a></li>
    <li><a href="orders.php">Ordini</a></li>
    <li><a href="users.php">Utenti</a></li>
  </ul>
</main>
<script src="../public/assets/js/main.js"></script>
</body>
</html>
