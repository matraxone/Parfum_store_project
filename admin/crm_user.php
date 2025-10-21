<?php
require_once __DIR__ . '/../src/config.php';
require_once __DIR__ . '/../src/auth.php';
require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/helpers.php';
require_once __DIR__ . '/../src/bootstrap.php';
require_once __DIR__ . '/../src/audit.php';
bootstrap_once();
require_login();
if (!is_admin()) { redirect('../public/login.php'); }
$pdo = db();

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT id,name,email,phone,company,crm_status,last_contact_at,created_at FROM users WHERE id=:id');
$stmt->execute([':id'=>$id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user) { http_response_code(404); echo 'Utente non trovato'; exit; }

$errors=[];
if ($_SERVER['REQUEST_METHOD']==='POST') {
  require_csrf();
  if (isset($_POST['update_profile'])) {
    $phone = trim($_POST['phone'] ?? '');
    $company = trim($_POST['company'] ?? '');
    $status = $_POST['crm_status'] ?? 'customer';
    $lastc = $_POST['last_contact_at'] ?? null;
    $upd = $pdo->prepare('UPDATE users SET phone=:ph, company=:co, crm_status=:st, last_contact_at=:lc WHERE id=:id');
    $upd->execute([':ph'=>$phone, ':co'=>$company, ':st'=>$status, ':lc'=>$lastc?:null, ':id'=>$id]);
    audit_log('crm.user_update', json_encode(['id'=>$id,'status'=>$status]));
  }
  if (isset($_POST['add_note'])) {
    $note = trim($_POST['note'] ?? '');
    if ($note !== '') {
      $ins = $pdo->prepare('INSERT INTO crm_notes (user_id,author_id,note) VALUES (:u,:a,:n)');
      $ins->execute([':u'=>$id, ':a'=>current_user()['id'] ?? null, ':n'=>$note]);
      audit_log('crm.note_add', json_encode(['user_id'=>$id]));
    }
  }
  if (isset($_POST['send_greeting'])) {
    require_once __DIR__ . '/../src/mailer.php';
    $sent = send_discount_greeting($user);
    if ($sent) { audit_log('crm.discount_greeting', json_encode(['user_id'=>$id])); }
  }
  if (isset($_POST['send_type'])) {
    require_once __DIR__ . '/../src/mailer.php';
    $type = $_POST['email_type'] ?? 'birthday';
    $ok = send_templated_email($user['email'] ?? '', $user['name'] ?? '', $type);
    if ($ok) { audit_log('crm.send_email_type', json_encode(['user_id'=>$id,'type'=>$type])); }
  }
}

$notes = $pdo->prepare('SELECT n.*, u.name AS author_name FROM crm_notes n LEFT JOIN users u ON u.id=n.author_id WHERE n.user_id=:id ORDER BY n.id DESC');
$notes->execute([':id'=>$id]);
$notes = $notes->fetchAll(PDO::FETCH_ASSOC);

// ultimi invii email
$recentEmails = [];
try {
  $se = $pdo->prepare('SELECT * FROM email_sends WHERE user_id=:u ORDER BY id DESC LIMIT 20');
  $se->execute([':u'=>$id]);
  $recentEmails = $se->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) { $recentEmails = []; }
?>
<!doctype html>
<html lang="it">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>CRM Utente - Profumeria</title>
  <link rel="stylesheet" href="../public/assets/css/style.css">
</head>
<body>
<header>
  <h1><a href="users.php">CRM Utente</a></h1>
  <nav>
    <a href="index.php">Dashboard</a>
    <a href="products.php">Prodotti</a>
    <a href="orders.php">Ordini</a>
    <a href="users.php">Utenti</a>
    <a href="email_config.php">Config Email</a>
    <button id="themeToggle" class="theme-btn" type="button" aria-label="Toggle theme">🌙</button>
  </nav>
</header>
<main class="container">
  <h2><?=htmlspecialchars($user['name'])?> <span class="muted">(<?=htmlspecialchars($user['email'])?>)</span></h2>
  <form method="post" class="auth" style="max-width:600px">
    <?=csrf_field()?>
    <label>Stato CRM
      <select name="crm_status">
        <?php foreach (['lead','prospect','customer','inactive'] as $s): ?>
          <option <?=$user['crm_status']===$s?'selected':''?>><?=$s?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>Telefono<input type="text" name="phone" value="<?=htmlspecialchars($user['phone'] ?? '')?>"></label>
    <label>Azienda<input type="text" name="company" value="<?=htmlspecialchars($user['company'] ?? '')?>"></label>
    <label>Ultimo contatto<input type="datetime-local" name="last_contact_at" value="<?= $user['last_contact_at']? date('Y-m-d\TH:i', strtotime($user['last_contact_at'])) : '' ?>"></label>
    <button type="submit" name="update_profile">Salva</button>
  </form>

  <h3 style="margin-top:24px">Note</h3>
  <form method="post" class="auth" style="max-width:600px">
    <?=csrf_field()?>
    <label>Nuova nota<textarea name="note" rows="3" required></textarea></label>
    <button type="submit" name="add_note">Aggiungi nota</button>
  </form>

  <form method="post" class="auth" style="max-width:600px; margin-top:12px">
    <?=csrf_field()?>
    <p>Invia ora email sconto 20% per compleanno/festività (test manuale)</p>
    <button type="submit" name="send_greeting">Invia email promozionale</button>
  </form>

  <form method="post" class="auth" style="max-width:600px; margin-top:12px">
    <?=csrf_field()?>
    <label>Invia email con template
      <select name="email_type">
        <?php foreach (['birthday'=>'Compleanno','christmas'=>'Natale','new_year'=>'Capodanno','easter'=>'Pasqua','ferragosto'=>'Ferragosto','holiday'=>'Generico'] as $k=>$label): ?>
          <option value="<?=$k?>"><?=$label?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <button type="submit" name="send_type">Invia</button>
  </form>

  <table class="table reveal" style="margin-top:16px">
    <thead><tr><th>Data</th><th>Autore</th><th>Nota</th></tr></thead>
    <tbody>
      <?php foreach ($notes as $n): ?>
        <tr class="show">
          <td><?=htmlspecialchars($n['created_at'])?></td>
          <td><?=htmlspecialchars($n['author_name'] ?? 'N/D')?></td>
          <td><?=nl2br(htmlspecialchars($n['note']))?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$notes): ?>
        <tr><td colspan="3">Nessuna nota</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</main>
<section class="container" style="margin-top:16px">
  <h3>Ultimi invii email</h3>
  <table class="table reveal">
    <thead><tr><th>Data</th><th>Tipo</th><th>Oggetto</th><th>Email</th></tr></thead>
    <tbody>
      <?php foreach ($recentEmails as $em): ?>
        <tr class="show">
          <td><?=htmlspecialchars($em['created_at'])?></td>
          <td><?=htmlspecialchars($em['type'])?></td>
          <td><?=htmlspecialchars($em['subject'])?></td>
          <td><?=htmlspecialchars($em['email'])?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$recentEmails): ?>
        <tr><td colspan="4">Nessun invio registrato</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</section>
<script src="../public/assets/js/main.js"></script>
</body>
</html>
