<?php
// register.php
declare(strict_types=1);
header('Content-Type: application/json');
require __DIR__ . '/config.php';

function j($ok, $msg, $extra = []) {
  echo json_encode(array_merge(['ok'=>$ok,'message'=>$msg], $extra));
  exit;
}

$first = trim($_POST['first_name'] ?? '');
$last  = trim($_POST['last_name'] ?? '');
$user  = trim($_POST['username'] ?? '');
$email = trim($_POST['email'] ?? '');
$pass  = (string)($_POST['password'] ?? '');

// Basic validatie
if ($first==='' || $last==='' || $user==='' || $email==='' || $pass==='') {
  j(false, 'Alle velden zijn verplicht.');
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
  j(false, 'Ongeldig e-mailadres.');
}
if (strlen($user) < 3) {
  j(false, 'Gebruikersnaam te kort (min. 3).');
}
if (strlen($pass) < 8 || !preg_match('/(?=.*[A-Za-z])(?=.*\d)/', $pass)) {
  j(false, 'Wachtwoord moet min. 8 tekens en letters+cijfers bevatten.');
}

// Bestaat username of email al?
$exists = $mysqli->prepare('SELECT id FROM users WHERE username=? OR email=? LIMIT 1');
$exists->bind_param('ss', $user, $email);
$exists->execute();
$exists->store_result();
if ($exists->num_rows > 0) {
  j(false, 'Gebruikersnaam of e-mail bestaat al.');
}
$exists->close();

// Insert
$hash = password_hash($pass, PASSWORD_DEFAULT);
$stmt = $mysqli->prepare('INSERT INTO users (first_name,last_name,username,email,password_hash) VALUES (?,?,?,?,?)');
$stmt->bind_param('sssss', $first, $last, $user, $email, $hash);
if (!$stmt->execute()) {
  j(false, 'Aanmaken mislukt.', ['error'=>$stmt->error]);
}
$stmt->close();

j(true, 'Account aangemaakt.');