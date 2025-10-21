<?php
require_once __DIR__ . '/../src/config.php';
require_once __DIR__ . '/../src/auth.php';
require_once __DIR__ . '/../src/bootstrap.php';
require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/helpers.php';
require_once __DIR__ . '/../src/audit.php';
bootstrap_once();
if (!is_admin()) { redirect('../public/login.php'); }
$pdo = db();

$action = $_GET['action'] ?? 'list';
$errors = [];

function handle_upload(string $field): ?string {
  if (empty($_FILES[$field]['name'])) return null;
  if ($_FILES[$field]['error'] !== UPLOAD_ERR_OK) return null;
  if ($_FILES[$field]['size'] > MAX_UPLOAD_SIZE) return null;
  $finfo = new finfo(FILEINFO_MIME_TYPE);
  $mime = $finfo->file($_FILES[$field]['tmp_name']);
  if (!in_array($mime, ALLOWED_IMAGE_TYPES, true)) return null;
  $ext = match($mime){'image/jpeg'=>'jpg','image/png'=>'png','image/gif'=>'gif', default=>'dat'};
  $name = bin2hex(random_bytes(8)) . '.' . $ext;
  $dest = UPLOAD_DIR . $name;
  if (!move_uploaded_file($_FILES[$field]['tmp_name'], $dest)) return null;
  return $name;
}

if ($_SERVER['REQUEST_METHOD']==='POST') {
  require_csrf();
  if ($action==='create') {
    $name = trim($_POST['name'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
  $stock = (int)($_POST['stock'] ?? 0);
  $volume_ml = isset($_POST['volume_ml']) && $_POST['volume_ml'] !== '' ? (float)$_POST['volume_ml'] : null;
    $description = trim($_POST['description'] ?? '');
    $img = handle_upload('image');
    try {
  $stmt = $pdo->prepare("INSERT INTO products (name,description,price,stock,volume_ml,image_path,is_active,created_at) VALUES (:n,:d,:p,:s,:v,:i,1,NOW())");
  $stmt->execute([':n'=>$name,':d'=>$description,':p'=>$price,':s'=>$stock,':v'=>$volume_ml,':i'=>$img]);
  audit_log('product.create', json_encode(['name'=>$name,'price'=>$price,'stock'=>$stock,'volume_ml'=>$volume_ml]));
    } catch (Throwable $e) { $errors[] = 'Errore creazione: '.$e->getMessage(); }
    $action='list';
  }
  if ($action==='edit') {
    $id = (int)($_GET['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
  $stock = (int)($_POST['stock'] ?? 0);
  $volume_ml = isset($_POST['volume_ml']) && $_POST['volume_ml'] !== '' ? (float)$_POST['volume_ml'] : null;
    $description = trim($_POST['description'] ?? '');
    $img = handle_upload('image');
    try {
      if ($img) {
        $stmt = $pdo->prepare("UPDATE products SET name=:n,description=:d,price=:p,stock=:s,volume_ml=:v,image_path=:i WHERE id=:id");
        $stmt->execute([':n'=>$name,':d'=>$description,':p'=>$price,':s'=>$stock,':v'=>$volume_ml,':i'=>$img,':id'=>$id]);
      } else {
        $stmt = $pdo->prepare("UPDATE products SET name=:n,description=:d,price=:p,stock=:s,volume_ml=:v WHERE id=:id");
        $stmt->execute([':n'=>$name,':d'=>$description,':p'=>$price,':s'=>$stock,':v'=>$volume_ml,':id'=>$id]);
      }
      audit_log('product.update', json_encode(['id'=>$id,'name'=>$name,'price'=>$price,'stock'=>$stock,'volume_ml'=>$volume_ml]));
    } catch (Throwable $e) { $errors[] = 'Errore modifica: '.$e->getMessage(); }
    $action='list';
  }
  if ($action==='delete') {
    $id = (int)($_POST['id'] ?? 0);
    $pdo->prepare('DELETE FROM products WHERE id=:id')->execute([':id'=>$id]);
    audit_log('product.delete', json_encode(['id'=>$id]));
    $action='list';
  }
}

if ($action==='create' || $action==='edit') {
  $prod = ['name'=>'','price'=>0,'stock'=>0,'volume_ml'=>null,'description'=>''];
  if ($action==='edit') {
    $id = (int)($_GET['id'] ?? 0);
    $st = $pdo->prepare('SELECT * FROM products WHERE id=:id'); $st->execute([':id'=>$id]); $prod = $st->fetch(PDO::FETCH_ASSOC);
  }
}
?>
<!doctype html>
<html lang="it">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Admin Prodotti - School Toys</title>
  <link rel="stylesheet" href="../public/assets/css/style.css">
</head>
<body>
<header>
  <h1><a href="index.php">Admin Prodotti</a></h1>
  <nav>
    <a href="orders.php">Ordini</a>
  <a href="users.php">Utenti</a>
  <a href="email_config.php">Config Email</a>
    <a href="../public/index.php">Shop</a>
    <button id="themeToggle" class="theme-btn" type="button" aria-label="Toggle theme">🌙</button>
  </nav>
</header>
<main class="container">
  <h1>Prodotti</h1>
  <?php foreach ($errors as $e): ?><div class="alert"><?=htmlspecialchars($e)?></div><?php endforeach; ?>

  <?php if ($action==='list'): ?>
    <p><a class="btn" href="products.php?action=create">Nuovo prodotto</a></p>
    <table class="table">
      <thead><tr><th>ID</th><th>Nome</th><th>Prezzo</th><th>Stock</th><th>Azioni</th></tr></thead>
      <tbody>
      <?php foreach ($pdo->query('SELECT id,name,price,stock FROM products ORDER BY id DESC') as $p): ?>
        <tr>
          <td><?=$p['id']?></td>
          <td><?=htmlspecialchars($p['name'])?></td>
          <td>€ <?=number_format($p['price'],2,',','.')?></td>
          <td><?=$p['stock']?></td>
          <td>
            <a class="btn" href="products.php?action=edit&id=<?=$p['id']?>">Modifica</a>
            <form method="post" action="products.php?action=delete" style="display:inline">
              <?=csrf_field()?>
              <input type="hidden" name="id" value="<?=$p['id']?>">
              <button type="submit" onclick="return confirm('Eliminare il prodotto #<?=$p['id']?>?')">Elimina</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  <?php else: ?>
    <form method="post" enctype="multipart/form-data">
      <?=csrf_field()?>
      <label>Nome<input type="text" name="name" value="<?=sanitize($prod['name'])?>" required></label>
  <label>Prezzo<input type="number" step="0.01" min="0" max="1000000000000" name="price" value="<?=$prod['price']?>" required></label>
      <label>Stock<input type="number" name="stock" value="<?=$prod['stock']?>" required></label>
      <label>Volume (ml)
        <input type="number" name="volume_ml" step="0.1" min="0" max="10000" value="<?=isset($prod['volume_ml']) && $prod['volume_ml']!==null ? htmlspecialchars((string)$prod['volume_ml']) : ''?>" placeholder="es. 50, 75, 100" />
      </label>
      <label>Descrizione<textarea name="description" rows="5"><?=sanitize($prod['description'])?></textarea></label>
      <label>Immagine<input type="file" name="image" accept="image/*"></label>
      <button type="submit">Salva</button>
    </form>
  <?php endif; ?>
</main>
<script src="../public/assets/js/main.js"></script>
</body>
</html>
