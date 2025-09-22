<?php
// save_budget.php
declare(strict_types=1);
session_start();
header('Content-Type: application/json');
require __DIR__ . '/config.php';

if (!isset($_SESSION['user_id'])) {
  http_response_code(401);
  echo json_encode(['ok'=>false,'message'=>'Niet ingelogd']); exit;
}

$raw = file_get_contents('php://input');
$body = json_decode($raw, true);
$ym   = $body['ym']   ?? '';
$data = $body['data'] ?? null;

if (!preg_match('/^\d{4}-\d{2}$/', $ym) || !is_array($data)) {
  http_response_code(400);
  echo json_encode(['ok'=>false,'message'=>'Ongeldige input']); exit;
}

$json = json_encode($data, JSON_UNESCAPED_UNICODE);
$stmt = $mysqli->prepare(
  'INSERT INTO user_budgets (user_id, ym, data_json)
   VALUES (?, ?, ?)
   ON DUPLICATE KEY UPDATE data_json=VALUES(data_json), updated_at=CURRENT_TIMESTAMP'
);
$stmt->bind_param('iss', $_SESSION['user_id'], $ym, $json);
$ok = $stmt->execute();
if(!$ok){ http_response_code(500); echo json_encode(['ok'=>false,'message'=>'Opslaan mislukt','error'=>$stmt->error]); exit; }
$stmt->close();
echo json_encode(['ok'=>true]);