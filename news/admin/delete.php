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
  // 1) Haal het huidige image_path op
  $st = db()->prepare("SELECT image_path FROM articles WHERE id=:id LIMIT 1");
  $st->execute([':id' => $id]);
  $imagePath = (string)($st->fetchColumn() ?: '');

  // 2) Verwijder het artikel
  $st = db()->prepare("DELETE FROM articles WHERE id=:id");
  $st->execute([':id' => $id]);

  // 3) Verwijder de gekoppelde afbeelding (veilig) indien aanwezig
  if ($imagePath !== '') {
    // Sta alleen verwijderen toe binnen de uploads-map
    $baseDir = realpath(dirname(__DIR__) . '/uploads');
    $candidate = dirname(__DIR__) . '/' . ltrim($imagePath, '/');
    $parent = realpath(dirname($candidate));

    if ($baseDir && $parent && str_starts_with($parent, $baseDir) && is_file($candidate)) {
      @unlink($candidate);
    }
  }
}

header('Location: dashboard.php');
exit;