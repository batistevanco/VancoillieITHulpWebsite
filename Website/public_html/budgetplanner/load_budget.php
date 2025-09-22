<?php
// load_budget.php
declare(strict_types=1);
session_start();
header('Content-Type: application/json');
require __DIR__ . '/config.php';

if (!isset($_SESSION['user_id'])) {
  http_response_code(401);
  echo json_encode(['ok'=>false,'message'=>'Niet ingelogd']); exit;
}
$ym = $_GET['ym'] ?? '';
if (!preg_match('/^\d{4}-\d{2}$/', $ym)) {
  http_response_code(400);
  echo json_encode(['ok'=>false,'message'=>'Ongeldig ym']); exit;
}

$stmt = $mysqli->prepare('SELECT data_json FROM user_budgets WHERE user_id=? AND ym=? LIMIT 1');
$stmt->bind_param('is', $_SESSION['user_id'], $ym);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();
$stmt->close();

if (!$row) { echo json_encode(['ok'=>true,'data'=>null]); exit; }
echo json_encode(['ok'=>true,'data'=>json_decode($row['data_json'], true)]);