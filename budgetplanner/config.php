<?php
// config.php
declare(strict_types=1);
header('X-Content-Type-Options: nosniff');

$DB_HOST = 'localhost';
$DB_NAME = 'u104619329_budgetplanner';
$DB_USER = 'u104619329_budgetBatiste';   // exact zoals in je screenshot
$DB_PASS = 'Budgetplanner@16';
$DB_PORT = 3306;

$mysqli = @new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME, $DB_PORT);
if ($mysqli->connect_errno) {
  http_response_code(500);
  header('Content-Type: application/json');
  echo json_encode(['ok'=>false,'message'=>'DB-verbinding mislukt.','error'=>$mysqli->connect_error]);
  exit;
}
$mysqli->set_charset('utf8mb4');