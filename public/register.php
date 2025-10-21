<?php
require_once __DIR__ . '/../src/config.php';
require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/helpers.php';
require_once __DIR__ . '/../src/auth.php';

$errors = [];
if ($_SERVER['REQUEST_METHOD']==='POST') {
  require_csrf();
  $name = trim($_POST['name'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $password = $_POST['password'] ?? '';
  $birthdate = $_POST['birthdate'] ?? '';
  $parent_consent = isset($_POST['parent_consent']) ? 1 : 0;

  if ($name==='') $errors[]='Nome richiesto';
  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[]='Email non valida';
  if (strlen($password) < 6) $errors[]='Password minima 6 caratteri';
  if (!$birthdate) $errors[]='Data di nascita richiesta';

  if (!$errors) {
    if (register($name, $email, $password, $birthdate, $parent_consent)) {
      redirect('login.php');
    } else {
      $errors[]='Registrazione fallita (email già in uso?)';
    }
  }
}
?>
<!doctype html>
<html lang="it">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Registrazione - Profumeria</title>
  <link rel="stylesheet" href="assets/css/style.css" />
</head>
<body>
<header>
  <h1><a href="index.php">Profumeria</a></h1>
  <nav>
  <button id="themeToggle" class="theme-btn" type="button" aria-label="Toggle theme">🌙</button>
  </nav>
</header>
<main class="container auth">
  <h2>Registrazione</h2>
  <?php if ($errors): ?><div class="alert"><?=htmlspecialchars(implode("\n", $errors))?></div><?php endif; ?>
  <form method="post">
    <?=csrf_field()?>
    <label>Nome completo<input type="text" name="name" required></label>
    <label>Email<input type="email" name="email" required></label>
    <label>Password<input type="password" name="password" required minlength="6"></label>
    <label>Data di nascita<input type="date" name="birthdate" required></label>
    <label class="checkbox"><input type="checkbox" name="parent_consent"> Consenso genitoriale (se < 18 anni)</label>
    <button type="submit">Crea account</button>
  </form>
</main>
<script src="assets/js/main.js"></script>
</body>
</html>
