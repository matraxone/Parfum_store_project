<?php
require_once __DIR__ . '/../src/config.php';
require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/helpers.php';
require_once __DIR__ . '/../src/auth.php';

$errors = [];
if ($_SERVER['REQUEST_METHOD']=== 'POST') {
  require_csrf();
  $identity = trim($_POST['identity'] ?? '');
  $password = $_POST['password'] ?? '';
  if ($identity === '') { $errors[]='Inserisci email o username'; }
  if (!$errors && !login_identity($identity, $password)) { $errors[]='Credenziali non valide'; }
    if (!$errors) {
      $next = $_GET['next'] ?? '';
      if (is_string($next) && $next !== '') {
        // Only allow relative paths
        if (str_starts_with($next, '/')) {
          header('Location: ' . $next);
          exit;
        }
      }
      redirect('index.php');
    }
}
?>
<!doctype html>
<html lang="it">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Login - Profumeria</title>
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
  <h2>Login</h2>
  <?php if ($errors): ?><div class="alert"><?=htmlspecialchars(implode("\n", $errors))?></div><?php endif; ?>
  <form method="post">
    <?=csrf_field()?>
  <label>Email o username<input type="text" name="identity" required></label>
    <label>Password<input type="password" name="password" required></label>
    <button type="submit">Entra</button>
    <p>Non hai un account? <a href="register.php">Registrati</a></p>
  </form>
</main>
<script src="assets/js/main.js"></script>
</body>
</html>
