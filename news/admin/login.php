<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';      // levert sessie + csrf helpers
require_once __DIR__ . '/../config.php'; // levert ADMIN_USER/ADMIN_PASS_HASH (zie toevoeging onderaan)

if (session_status() !== PHP_SESSION_ACTIVE) session_start();

// Al ingelogd? Naar dashboard
if (!empty($_SESSION['admin_logged_in'])) {
    header('Location: dashboard.php');
    exit;
}

$err = '';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    csrf_validate();

    $u = trim($_POST['username'] ?? '');
    $p = $_POST['password'] ?? '';

    $okUser = defined('ADMIN_USER') && hash_equals(ADMIN_USER, $u);
    $okPass = defined('ADMIN_PASS_HASH') && password_verify($p, ADMIN_PASS_HASH);

    if ($okUser && $okPass) {
        $_SESSION['admin_logged_in'] = true;
        // Optioneel: regenerate session id
        session_regenerate_id(true);
        header('Location: dashboard.php');
        exit;
    } else {
        $err = 'Ongeldige login.';
    }
}
?>
<!doctype html>
<html lang="nl">
<head>
  <meta charset="utf-8">
  <title>News Admin • Login</title>
  <meta name="robots" content="noindex,nofollow,noarchive">
  <style>
    :root{color-scheme:light dark}
    body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial;display:grid;place-items:center;min-height:100dvh;margin:0}
    .card{width:min(420px,90vw);border:1px solid #e5e7eb;border-radius:12px;padding:24px}
    h1{margin:0 0 12px}
    label{display:block;margin:.75rem 0 .25rem;font-weight:600}
    input{width:100%;padding:.7rem .9rem;border:1px solid #d1d5db;border-radius:10px}
    .row{display:flex;gap:.75rem;align-items:center;justify-content:space-between}
    .btn{padding:.7rem 1rem;border:0;border-radius:10px;cursor:pointer}
    .primary{background:#2563eb;color:#fff}
    .ghost{background:#f3f4f6}
    .err{color:#b91c1c;margin:.5rem 0 0}
    small{color:#6b7280}
    a{color:#2563eb;text-decoration:none}
  </style>
</head>
<body>
  <div class="card">
    <h1>Login</h1>
    <?php if ($err): ?><p class="err"><?= htmlspecialchars($err) ?></p><?php endif; ?>
    <form method="post" autocomplete="off">
      <?= csrf_field() ?>
      <label>Gebruikersnaam</label>
      <input type="text" name="username" required autofocus>

      <label>Wachtwoord</label>
      <input type="password" name="password" required>

      <div class="row" style="margin-top:12px">
        <button class="btn primary" type="submit">Inloggen</button>
      </div>
      <p style="margin-top:12px"><small>Tip: wijzig de admin-gegevens in <code>config.php</code>.</small></p>
    </form>
  </div>
</body>
</html>