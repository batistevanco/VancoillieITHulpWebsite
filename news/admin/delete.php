<?php
declare(strict_types=1);
require_once __DIR__.'/auth.php';
require_once __DIR__.'/../config.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
  http_response_code(405);
  echo 'Method Not Allowed';
  exit;
}

csrf_validate();

$id = (int)($_POST['id'] ?? 0);
if ($id > 0) {
  $st = db()->prepare("DELETE FROM articles WHERE id=:id");
  $st->execute([':id'=>$id]);
}

header('Location: dashboard.php');
exit;