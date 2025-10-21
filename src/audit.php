<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

function audit_log(string $action, string $details = '', ?int $userId = null): void {
  try {
    $pdo = db();
    if ($userId === null) { $userId = current_user()['id'] ?? null; }
    $stmt = $pdo->prepare('INSERT INTO audit_logs (user_id, action, details, created_at) VALUES (:u,:a,:d,NOW())');
    $stmt->execute([':u'=>$userId, ':a'=>$action, ':d'=>$details]);
  } catch (Throwable $e) {
    // non bloccare il flusso per errori di audit
  }
}
