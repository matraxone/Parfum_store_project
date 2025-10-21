<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

function current_user(): ?array {
  return $_SESSION['user'] ?? null;
}
function is_logged_in(): bool { return !!current_user(); }
function require_login(): void {
  if (!is_logged_in()) {
    $next = $_SERVER['REQUEST_URI'] ?? BASE_URL;
    // Normalize next to avoid open redirects
    if (!is_string($next) || $next === '') { $next = BASE_URL; }
    $nextParam = urlencode($next);
    header('Location: ' . BASE_URL . 'login.php?next=' . $nextParam);
    exit;
  }
}
function is_admin(): bool { return (current_user()['role'] ?? '') === 'admin'; }

function age_from_birthdate(?string $birthdate): ?int {
  if (!$birthdate) return null;
  try {
    $b = new DateTime($birthdate);
    $now = new DateTime('today');
    return (int)$b->diff($now)->y;
  } catch (Throwable $e) { return null; }
}
function age_allowed(array $user): bool {
  $age = age_from_birthdate($user['birthdate'] ?? null);
  if ($age === null) return false;
  if ($age >= 18) return true;
  return !empty($user['parent_consent']);
}

function login_identity(string $identity, string $password): bool {
  $pdo = db();
  $u = null;
  // If it looks like an email, fetch by email
  if (filter_var($identity, FILTER_VALIDATE_EMAIL)) {
    $stmt = $pdo->prepare('SELECT * FROM users WHERE email=:e LIMIT 1');
    $stmt->execute([':e'=>$identity]);
    $u = $stmt->fetch(PDO::FETCH_ASSOC);
  } else {
    // Favor a deterministic lookup for the common 'admin' case
    if ($identity === 'admin') {
      $stmt = $pdo->prepare("SELECT * FROM users WHERE email='admin@local' LIMIT 1");
      $stmt->execute();
      $u = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    // If not found yet, try exact name match (collation may be CI but prefer latest record)
    if (!$u) {
      $stmt = $pdo->prepare('SELECT * FROM users WHERE name=:n ORDER BY id DESC LIMIT 1');
      $stmt->execute([':n'=>$identity]);
      $u = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    // As last resort, case-insensitive name match, latest first
    if (!$u) {
      $stmt = $pdo->prepare('SELECT * FROM users WHERE LOWER(name)=LOWER(:n) ORDER BY id DESC LIMIT 1');
      $stmt->execute([':n'=>$identity]);
      $u = $stmt->fetch(PDO::FETCH_ASSOC);
    }
  }
  if (!$u) return false;
  if (!password_verify($password, $u['password_hash'])) return false;
  unset($u['password_hash']);
  $_SESSION['user'] = $u;
  return true;
}

// Backward-compat: keep original signature
function login(string $email, string $password): bool { return login_identity($email, $password); }

function logout(): void { unset($_SESSION['user']); }

function register(string $name, string $email, string $password, string $birthdate, int $parent_consent=0): bool {
  $pdo = db();
  try {
    $stmt = $pdo->prepare('INSERT INTO users (name,email,password_hash,role,birthdate,parent_consent,created_at) VALUES (:n,:e,:p,\'customer\',:b,:pc,NOW())');
    $stmt->execute([
      ':n'=>$name,
      ':e'=>$email,
      ':p'=>password_hash($password, PASSWORD_DEFAULT),
      ':b'=>$birthdate,
      ':pc'=>$parent_consent
    ]);
    return true;
  } catch (PDOException $e) {
    return false;
  }
}
