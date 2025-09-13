<?php
// login.php
declare(strict_types=1);
session_start();
header('Content-Type: application/json');
require __DIR__ . '/config.php';

function j($ok, $msg, $extra = []) {
  echo json_encode(array_merge(['ok'=>$ok,'message'=>$msg], $extra));
  exit;
}

$username = trim($_POST['username'] ?? '');
$password = (string)($_POST['password'] ?? '');

if ($username === '' || $password === '') {
  j(false, 'Gebruikersnaam en wachtwoord zijn verplicht.');
}

// Zoek user op username of e-mail
$stmt = $mysqli->prepare('SELECT id, username, password_hash FROM users WHERE username=? OR email=? LIMIT 1');
$stmt->bind_param('ss', $username, $username);
$stmt->execute();
$res = $stmt->get_result();
$user = $res->fetch_assoc();
$stmt->close();

if (!$user || !password_verify($password, $user['password_hash'])) {
  j(false, 'Onjuiste login.');
}

// ✅ Bewaar info in sessie
$_SESSION['user_id'] = $user['id'];
$_SESSION['username'] = $user['username'];

j(true, 'Login ok.', ['redirect'=>'app.php']);