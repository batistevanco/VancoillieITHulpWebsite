<?php header('X-Robots-Tag: noindex, nofollow, noarchive', true);
session_start();
require_once __DIR__.'/../config.php';

if ($_SERVER['REQUEST_METHOD']==='POST') {
  $u = $_POST['user'] ?? '';
  $p = $_POST['pass'] ?? '';
  // simpel voorbeeld – vervang door echte users (of .htpasswd)
  if ($u==='admin' && password_verify($p, password_hash('adminBatiste', PASSWORD_DEFAULT))) {
    $_SESSION['ok']=1; header('Location: dashboard.php'); exit;
  }
  $err="Login mislukt";
}
?>
<!doctype html><html lang="nl"><meta charset="utf-8">
<title>News Admin – Login</title>
<link rel="stylesheet" href="admin.css">
<meta name="robots" content="noindex, nofollow, noarchive">
<body>
  <form method="post" class="card">
    <h1>News Admin</h1>
    <?php if(!empty($err)) echo "<p class='err'>$err</p>"; ?>
    <input name="user" placeholder="Gebruiker" required>
    <input name="pass" type="password" placeholder="Wachtwoord" required>
    <button type="submit">Login</button>
  </form>
</body></html>