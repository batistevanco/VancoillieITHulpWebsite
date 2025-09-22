<?php
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
$data = $body['data'] ?? null;

if (!is_array($data)) {
  http_response_code(400);
  echo json_encode(['ok'=>false,'message'=>'Ongeldige meta']); exit;
}

$json = json_encode($data, JSON_UNESCAPED_UNICODE);
$stmt = $mysqli->prepare(
  'INSERT INTO user_meta (user_id, data_json) VALUES (?, ?)
   ON DUPLICATE KEY UPDATE data_json=VALUES(data_json), updated_at=CURRENT_TIMESTAMP'
);
$stmt->bind_param('is', $_SESSION['user_id'], $json);
$ok = $stmt->execute();
if(!$ok){
  http_response_code(500);
  echo json_encode(['ok'=>false,'message'=>'Opslaan meta mislukt','error'=>$stmt->error]); exit;
}
$stmt->close();
echo json_encode(['ok'=>true]);