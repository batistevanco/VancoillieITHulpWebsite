<?php
// clear_all.php — verwijdert ALLE data voor de ingelogde gebruiker
declare(strict_types=1);
session_start();
header('Content-Type: application/json');
require __DIR__ . '/config.php';

if (!isset($_SESSION['user_id'])) {
  http_response_code(401);
  echo json_encode(['ok'=>false,'message'=>'Niet ingelogd']);
  exit;
}

$userId = (int)$_SESSION['user_id'];

$mysqli->begin_transaction();
try {
  // Verwijder maanddata
  $stmt1 = $mysqli->prepare('DELETE FROM user_budgets WHERE user_id = ?');
  $stmt1->bind_param('i', $userId);
  if(!$stmt1->execute()){ throw new Exception($stmt1->error ?: 'delete user_budgets failed'); }
  $stmt1->close();

  // Verwijder meta
  $stmt2 = $mysqli->prepare('DELETE FROM user_meta WHERE user_id = ?');
  $stmt2->bind_param('i', $userId);
  if(!$stmt2->execute()){ throw new Exception($stmt2->error ?: 'delete user_meta failed'); }
  $stmt2->close();

  $mysqli->commit();
  echo json_encode(['ok'=>true]);
} catch (Throwable $e) {
  $mysqli->rollback();
  http_response_code(500);
  echo json_encode(['ok'=>false,'message'=>'Verwijderen mislukt','error'=>$e->getMessage()]);
}