<?php
declare(strict_types=1);
session_start();
header('Content-Type: application/json');
require __DIR__ . '/config.php';

if (!isset($_SESSION['user_id'])) { http_response_code(401); echo json_encode(['ok'=>false]); exit; }

$stmt = $mysqli->prepare('SELECT ym, updated_at FROM user_budgets WHERE user_id=? ORDER BY ym DESC');
$stmt->bind_param('i', $_SESSION['user_id']);
$stmt->execute();
$res = $stmt->get_result();
$out = [];
while($r = $res->fetch_assoc()){ $out[] = $r; }
$stmt->close();
echo json_encode(['ok'=>true,'items'=>$out]);