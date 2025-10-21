<?php
require_once __DIR__ . '/../src/config.php';
require_once __DIR__ . '/../src/auth.php';
require_once __DIR__ . '/../src/helpers.php';
require_once __DIR__ . '/../src/mailer.php';
require_login();
if (!is_admin()) { redirect('../public/login.php'); }

$cfg = load_email_config();
$message = null; $errors = []; $testResults = null; $singleTest = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  require_csrf();
  $cfg['smtp_server'] = trim($_POST['smtp_server'] ?? $cfg['smtp_server']);
  $cfg['smtp_port'] = (int)($_POST['smtp_port'] ?? $cfg['smtp_port']);
  $cfg['email_sender'] = trim($_POST['email_sender'] ?? $cfg['email_sender']);
  $deliveryIn = $_POST['delivery'] ?? 'log';
  $cfg['delivery'] = in_array($deliveryIn, ['log','smtp'], true) ? $deliveryIn : 'log';
  // Nota: non memorizziamo password reali in chiaro; qui è simulato e opzionale
  if (isset($_POST['email_password'])) {
    $cfg['email_password'] = (string)$_POST['email_password'];
  }
  // Templates
  foreach (['birthday','christmas','new_year','easter','ferragosto','holiday'] as $t) {
    if (isset($_POST['tpl_'.$t])) { $cfg['templates'][$t] = (string)$_POST['tpl_'.$t]; }
  }
  if (save_email_config($cfg)) { $message = 'Configurazione aggiornata.'; } else { $errors[] = 'Salvataggio configurazione fallito.'; }
}

// invio email di prova per ogni occasione
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_all_tests'])) {
  require_csrf();
  $to = trim($_POST['test_email'] ?? '');
  if (!$to) { $errors[] = 'Inserisci una email di test.'; }
  else {
    $testResults = send_all_test_emails($to, 'Test');
    $message = 'Email di prova inviate (vedi log e tabella email_sends).';
  }
}

// invio email di prova per una sola occasione
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_one_test'])) {
  require_csrf();
  $to = trim($_POST['test_email_one'] ?? '');
  $type = trim($_POST['test_type'] ?? 'birthday');
  if (!$to) { $errors[] = 'Inserisci una email di test.'; }
  else {
    $ok = send_test_email($to, $type, 'Test');
    $singleTest = ['type'=>$type, 'ok'=>$ok, 'to'=>$to];
    $message = $ok ? 'Email di prova inviata.' : 'Errore invio email di prova.';
  }
}

?><!doctype html>
<html lang="it">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Config Email - Profumeria</title>
  <link rel="stylesheet" href="../public/assets/css/style.css">
  <style>
    textarea{min-height:120px}
    .grid{display:grid;grid-template-columns:1fr;gap:12px}
    @media (min-width: 900px){.grid{grid-template-columns:1fr 1fr}}
  </style>
  </head>
<body>
<header>
  <h1><a href="index.php">Configurazione Email</a></h1>
  <nav>
    <a href="index.php">Dashboard</a>
    <a href="products.php">Prodotti</a>
    <a href="orders.php">Ordini</a>
    <a href="users.php">Utenti</a>
    <button id="themeToggle" class="theme-btn" type="button" aria-label="Toggle theme">🌙</button>
  </nav>
</header>
<main class="container">
  <?php if ($message): ?><p class="success"><?php echo htmlspecialchars($message); ?></p><?php endif; ?>
  <?php if ($errors): ?><div class="error"><?php foreach ($errors as $e) echo '<p>'.htmlspecialchars($e).'</p>'; ?></div><?php endif; ?>

  <form method="post" class="auth">
    <?php echo csrf_field(); ?>
    <h2>Impostazioni Email</h2>
    <label>Metodo invio
      <select name="delivery">
        <option value="log" <?php echo (($cfg['delivery'] ?? 'log')==='log')?'selected':''; ?>>Solo Log (simulazione)</option>
        <option value="smtp" <?php echo (($cfg['delivery'] ?? 'log')==='smtp')?'selected':''; ?>>SMTP (PHPMailer)</option>
      </select>
    </label>
    <label>Server SMTP
      <input type="text" name="smtp_server" value="<?php echo htmlspecialchars($cfg['smtp_server'] ?? ''); ?>">
    </label>
    <label>Porta SMTP
      <input type="number" name="smtp_port" value="<?php echo (int)($cfg['smtp_port'] ?? 587); ?>">
    </label>
    <label>Email mittente
      <input type="email" name="email_sender" value="<?php echo htmlspecialchars($cfg['email_sender'] ?? ''); ?>">
    </label>
    <label>Password (opzionale)
      <input type="password" name="email_password" value="<?php echo htmlspecialchars($cfg['email_password'] ?? ''); ?>">
    </label>

    <h2 style="margin-top:24px">Template Messaggi</h2>
    <p class="muted">Puoi usare la variabile {name} per inserire il nome del cliente.</p>
    <div class="grid">
      <?php foreach (['birthday'=>'Compleanno','christmas'=>'Natale','new_year'=>'Capodanno','easter'=>'Pasqua','ferragosto'=>'Ferragosto','holiday'=>'Generico'] as $key=>$label): ?>
        <label><?php echo htmlspecialchars($label); ?>
          <textarea name="tpl_<?php echo $key; ?>"><?php echo htmlspecialchars(($cfg['templates'][$key] ?? '')); ?></textarea>
        </label>
      <?php endforeach; ?>
    </div>
    <button type="submit">Salva configurazione</button>
  </form>

  <form method="post" class="auth" style="margin-top:16px">
    <?php echo csrf_field(); ?>
    <h2>Invia email di prova (tutte le occasioni)</h2>
    <label>Destinatario test
      <input type="email" name="test_email" placeholder="nome@example.com" required>
    </label>
    <button type="submit" name="send_all_tests">Invia tutte le email di prova</button>
  </form>

  <form method="post" class="auth" style="margin-top:16px">
    <?php echo csrf_field(); ?>
    <h2>Invia email di prova (una sola occasione)</h2>
    <label>Destinatario test
      <input type="email" name="test_email_one" placeholder="nome@example.com" required>
    </label>
    <label>Occasione
      <select name="test_type">
        <?php foreach (all_template_types() as $k): ?>
          <option value="<?php echo htmlspecialchars($k); ?>"><?php echo htmlspecialchars($k); ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <button type="submit" name="send_one_test">Invia email di prova</button>
  </form>

  <?php if (is_array($singleTest)): ?>
    <div class="muted" style="margin-top:8px">
      <strong>Esito invio (<?php echo htmlspecialchars($singleTest['type']); ?>)</strong>:
      <?php echo $singleTest['ok'] ? 'OK' : 'Errore'; ?>
      <span>(<?php echo htmlspecialchars($singleTest['to']); ?>)</span>
    </div>
  <?php endif; ?>

  <?php if (is_array($testResults)): ?>
    <div class="muted" style="margin-top:8px">
      <strong>Esito invii:</strong>
      <ul>
        <?php foreach ($testResults as $k=>$ok): ?>
          <li><?php echo htmlspecialchars($k) . ': ' . ($ok ? 'OK' : 'Errore'); ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>
</main>
<script src="../public/assets/js/main.js"></script>
</body>
</html>
