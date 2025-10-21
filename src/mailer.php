<?php
require_once __DIR__ . '/config.php';

function send_mail(string $to, string $subject, string $body): void {
  // Se configurato SMTP e disponibile PHPMailer, usa SMTP; altrimenti log
  $cfg = [];
  try { $cfg = load_email_config(); } catch (Throwable $e) { $cfg = []; }
  $delivery = $cfg['delivery'] ?? 'log';
  $sent = false;
  if ($delivery === 'smtp') {
    $sent = send_via_smtp($to, $subject, $body, $cfg);
  }
  // Sempre log su file per audit/debug
  $line = date('c') . "\t$to\t$subject\t" . str_replace(["\r","\n"],' ', $body) . ( $sent?"\t[SENT]":"\t[LOG_ONLY]") . "\n";
  file_put_contents(MAIL_LOG, $line, FILE_APPEND | LOCK_EX);
}

function send_order_confirmation(string $to, int $orderId): void {
  $subject = "Conferma ordine #$orderId";
  $body = "Grazie per il tuo ordine #$orderId su Profumeria.";
  send_mail($to, $subject, $body);
}

// Path config email template JSON
function email_config_path(): string {
  return __DIR__ . '/email_config.json';
}

// Carica configurazione email (SMTP fittizio + templates)
function load_email_config(): array {
  $path = email_config_path();
  $default = [
    'delivery' => 'log', // 'log' | 'smtp'
    'smtp_server' => 'smtp.gmail.com',
    'smtp_port' => 587,
    'email_sender' => '',
    'email_password' => '',
    'templates' => [
      'birthday' => '<p>Ciao {name},</p><p>Buon compleanno! Per festeggiare, ecco per te un codice sconto del 20%: <strong>SCHOOL20</strong>.</p><p>Ti aspettiamo sul nostro store!</p>',
      'christmas' => '<p>Ciao {name},</p><p>Buon Natale! Approfitta del 20% di sconto con il codice <strong>SCHOOL20</strong>.</p>',
      'new_year' => '<p>Ciao {name},</p><p>Felice Anno Nuovo! Inizia bene con il 20% di sconto: <strong>SCHOOL20</strong>.</p>',
      'easter' => '<p>Ciao {name},</p><p>Buona Pasqua! Per te il 20% di sconto: <strong>SCHOOL20</strong>.</p>',
      'ferragosto' => '<p>Ciao {name},</p><p>Buon Ferragosto! Rinfresca gli acquisti con il 20% di sconto: <strong>SCHOOL20</strong>.</p>',
      'holiday' => '<p>Ciao {name},</p><p>Auguri! Ti offriamo il 20% di sconto: <strong>SCHOOL20</strong>.</p>'
    ],
  ];
  if (!file_exists($path)) {
    @file_put_contents($path, json_encode($default, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
    return $default;
  }
  $raw = @file_get_contents($path);
  if ($raw === false) return $default;
  $data = json_decode($raw, true);
  if (!is_array($data)) return $default;
  // merge shallow to guarantee keys
  $data['templates'] = array_merge($default['templates'], $data['templates'] ?? []);
  return array_merge($default, $data);
}

function save_email_config(array $cfg): bool {
  $path = email_config_path();
  return (bool)@file_put_contents($path, json_encode($cfg, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
}

// Invio SMTP tramite PHPMailer se disponibile
function send_via_smtp(string $to, string $subject, string $body, array $cfg): bool {
  try {
    // prova a caricare Composer autoload
    $vendor = __DIR__ . '/../vendor/autoload.php';
    if (file_exists($vendor)) { require_once $vendor; }
    if (!class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
      // PHPMailer non disponibile
      return false;
    }
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = $cfg['smtp_server'] ?? 'smtp.gmail.com';
    $mail->Port = (int)($cfg['smtp_port'] ?? 587);
    $mail->SMTPAuth = true;
    $mail->Username = $cfg['email_sender'] ?? '';
    $mail->Password = $cfg['email_password'] ?? '';
    if ($mail->Port === 465) {
      $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
    } else {
      $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
    }
    $from = $cfg['email_sender'] ?? 'noreply@example.com';
  $mail->setFrom($from, 'Profumeria');
    $mail->addAddress($to);
    $mail->Subject = $subject;
    $mail->Body = $body;
    $mail->AltBody = $body;
    $mail->isHTML(false);
    $mail->send();
    return true;
  } catch (Throwable $e) {
    // registra errore sul log per diagnosi
    $err = date('c') . "\t$to\t$subject\tSMTP_ERROR: ".$e->getMessage()."\n";
    @file_put_contents(MAIL_LOG, $err, FILE_APPEND|LOCK_EX);
    return false;
  }
}

function email_subject_for_type(string $type): string {
  $map = [
    'birthday' => "Happy Birthday! Sconto 20% per te",
    'christmas' => 'Buon Natale! Sconto 20% per te',
    'new_year' => 'Buon Anno! Sconto 20% per te',
    'easter' => 'Buona Pasqua! Sconto 20% per te',
    'ferragosto' => 'Buon Ferragosto! Sconto 20% per te',
    'holiday' => 'Auguri! Sconto 20% per te',
  ];
  return $map[$type] ?? ('Promozione: 20% di sconto');
}

function render_email_template(string $template, array $vars): string {
  $replacements = [];
  foreach ($vars as $k=>$v) { $replacements['{'.$k.'}'] = (string)$v; }
  return strtr($template, $replacements);
}

// Invia una email basata su template; ritorna true se inviata (log scritta)
function send_templated_email(string $to, string $name, string $type): bool {
  if (!$to) return false;
  $cfg = load_email_config();
  $templates = $cfg['templates'] ?? [];
  $tpl = $templates[$type] ?? ($templates['holiday'] ?? null);
  if (!$tpl) return false;
  $subject = email_subject_for_type($type);
  $html = render_email_template($tpl, [ 'name' => $name ?: '' ]);
  // per semplicità, inviamo solo la versione testuale ridotta nel log
  $plain = strip_tags($html);
  send_mail($to, $subject, $plain);
  // tenta log su DB se disponibile
  try {
    require_once __DIR__ . '/db.php';
    $pdo = db();
    if ($pdo) {
      // migliore pratica: associare a utente per email se esiste
      $uid = null;
      try {
        $s = $pdo->prepare('SELECT id FROM users WHERE email=:e LIMIT 1');
        $s->execute([':e'=>$to]);
        $uid = $s->fetchColumn() ?: null;
      } catch (Throwable $e) {}
      $ins = $pdo->prepare('INSERT INTO email_sends (user_id,email,type,subject) VALUES (:u,:e,:t,:s)');
      $ins->execute([':u'=>$uid, ':e'=>$to, ':t'=>$type, ':s'=>$subject]);
    }
  } catch (Throwable $e) { /* ignore */ }
  return true;
}

// Elenco standard dei tipi di template supportati
function all_template_types(): array {
  return ['birthday','christmas','new_year','easter','ferragosto','holiday'];
}

// Invia una singola email di prova per il tipo selezionato
function send_test_email(string $to, string $type, string $name='Amico'): bool {
  if (!in_array($type, all_template_types(), true)) return false;
  return send_templated_email($to, $name, $type);
}

// Invia email di prova per ogni occasione al destinatario indicato
// Ritorna mappa [tipo => esito(bool)]
function send_all_test_emails(string $to, string $name='Amico'): array {
  $results = [];
  foreach (all_template_types() as $type) {
    $results[$type] = send_templated_email($to, $name, $type);
  }
  return $results;
}

// Calcolo Pasqua (Calendario Gregoriano, Occidentale)
function easter_date_gregorian(int $year): DateTime {
  $a = $year % 19;
  $b = intdiv($year, 100);
  $c = $year % 100;
  $d = intdiv($b, 4);
  $e = $b % 4;
  $f = intdiv($b + 8, 25);
  $g = intdiv($b - $f + 1, 3);
  $h = (19*$a + $b - $d - $g + 15) % 30;
  $i = intdiv($c, 4);
  $k = $c % 4;
  $l = (32 + 2*$e + 2*$i - $h - $k) % 7;
  $m = intdiv($a + 11*$h + 22*$l, 451);
  $month = intdiv($h + $l - 7*$m + 114, 31); // 3=Marzo, 4=Aprile
  $day = (($h + $l - 7*$m + 114) % 31) + 1;
  return new DateTime(sprintf('%04d-%02d-%02d', $year, $month, $day));
}

function is_holiday(DateTime $date): bool {
  $y = (int)$date->format('Y');
  $md = $date->format('m-d');
  // Feste italiane (fisse)
  $fixed = [
    '01-01', // Capodanno
    '01-06', // Epifania
    '04-25', // Liberazione
    '05-01', // Lavoro
    '06-02', // Repubblica
    '08-15', // Ferragosto
    '11-01', // Ognissanti
    '12-08', // Immacolata
    '12-25', // Natale
    '12-26', // Santo Stefano
  ];
  if (in_array($md, $fixed, true)) return true;
  // Pasqua e Lunedì dell'Angelo (variabili)
  try {
    $easter = easter_date_gregorian($y);
    $easterMonday = (clone $easter)->modify('+1 day');
    return $date->format('Y-m-d') === $easter->format('Y-m-d')
        || $date->format('Y-m-d') === $easterMonday->format('Y-m-d');
  } catch (Throwable $e) { return false; }
}

// Riconosce festività principali; ritorna chiave template oppure null
function detect_holiday_type(DateTime $date): ?string {
  $md = $date->format('m-d');
  if ($md === '12-25') return 'christmas';
  if ($md === '01-01') return 'new_year';
  if ($md === '08-15') return 'ferragosto';
  try {
    $easter = easter_date_gregorian((int)$date->format('Y'));
    if ($date->format('Y-m-d') === $easter->format('Y-m-d')) return 'easter';
  } catch (Throwable $e) { }
  return is_holiday($date) ? 'holiday' : null;
}

// Invia email sconto 20% in occasione di festività o compleanno
// Ritorna true se inviata, false altrimenti
function send_discount_greeting(array $user): bool {
  $email = $user['email'] ?? null;
  if (!$email) return false;
  $now = new DateTime('today');
  $reason = null;
  // compleanno (match mese-giorno, ignora anno)
  if (!empty($user['birthdate'])) {
    try {
      $bd = new DateTime($user['birthdate']);
      if ($bd->format('m-d') === $now->format('m-d')) { $reason = 'birthday'; }
    } catch (Throwable $e) { /* ignore */ }
  }
  // festività (se non già motivazione compleanno)
  if ($reason === null) { $reason = detect_holiday_type($now); }
  if ($reason === null) return false;
  $name = trim((string)($user['name'] ?? ''));
  return send_templated_email($email, $name, $reason);
}
