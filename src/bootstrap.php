<?php
require_once __DIR__ . '/db.php';

function bootstrap_once(): void {
  static $done = false; if ($done) return; $done = true;
  $pdo = db();
  // Ensure admin user exists
  $st = $pdo->prepare("SELECT id FROM users WHERE email=:e");
  $st->execute([':e'=>'admin@example.com']);
  if (!$st->fetch()) {
    $hash = password_hash('admin123', PASSWORD_DEFAULT);
    $ins = $pdo->prepare("INSERT INTO users (name,email,password_hash,role,birthdate,parent_consent,created_at) VALUES ('Admin','admin@example.com',:p,'admin','1990-01-01',0,NOW())");
    try { $ins->execute([':p'=>$hash]); } catch (Throwable $e) { /* ignore */ }
  }

  // Normalize admin@example.com password to admin123 in dev
  if (DEV_SEED) {
    try {
      $chk = $pdo->prepare("SELECT id,password_hash FROM users WHERE email='admin@example.com' LIMIT 1");
      $chk->execute();
      if ($row = $chk->fetch(PDO::FETCH_ASSOC)) {
        if (!password_verify('admin123', $row['password_hash'])) {
          $up = $pdo->prepare('UPDATE users SET password_hash=:p WHERE id=:id');
          $up->execute([':p'=>password_hash('admin123', PASSWORD_DEFAULT), ':id'=>$row['id']]);
        }
      }
    } catch (Throwable $e) { /* ignore */ }
  }

  // Ensure simple credentials admin/admin also exist for local use
  $st2 = $pdo->prepare("SELECT id FROM users WHERE LOWER(name)='admin' OR email='admin@local' LIMIT 1");
  $st2->execute();
  if (!$st2->fetch()) {
    $hash2 = password_hash('admin', PASSWORD_DEFAULT);
    $ins2 = $pdo->prepare("INSERT INTO users (name,email,password_hash,role,birthdate,parent_consent,created_at) VALUES ('admin','admin@local',:p,'admin','1990-01-01',0,NOW())");
    try { $ins2->execute([':p'=>$hash2]); } catch (Throwable $e) { /* ignore */ }
  }
}
