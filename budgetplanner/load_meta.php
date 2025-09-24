<?php
declare(strict_types=1);
session_start();
header('Content-Type: application/json');
require __DIR__ . '/config.php';

if (!isset($_SESSION['user_id'])) {
  http_response_code(401);
  echo json_encode(['ok'=>false,'message'=>'Niet ingelogd']); exit;
}

$stmt = $mysqli->prepare('SELECT data_json FROM user_meta WHERE user_id=? LIMIT 1');
$stmt->bind_param('i', $_SESSION['user_id']);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();
$stmt->close();

$defaults = [
  'networth' => [],
  'goals' => [],
  'goalsAutoDistribute' => false,
  'projectionAdjust' => 0,
  'investmentsHistory' => [],
  'futureInvestments' => 0,
  'view' => null
];

if (!$row) {
  echo json_encode(['ok'=>true,'data'=>$defaults]); exit;
}
$data = json_decode($row['data_json'], true);
if (!is_array($data)) $data = [];
echo json_encode(['ok'=>true,'data'=>array_merge($defaults, $data)]);