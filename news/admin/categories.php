<?php
declare(strict_types=1);
require_once __DIR__.'/auth.php';
require_once __DIR__.'/../config.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
  csrf_validate();

  if (isset($_POST['save'])) {
    // update bestaande
    $ids = $_POST['id'] ?? [];
    $nl  = $_POST['name_nl'] ?? [];
    $en  = $_POST['name_en'] ?? [];
    foreach ($ids as $i => $id) {
      $id = (int)$id;
      $nln = trim($nl[$i] ?? '');
      $enn = trim($en[$i] ?? '');
      if ($id > 0) {
        $st = db()->prepare("UPDATE categories SET name_nl=:nl, name_en=:en WHERE id=:id");
        $st->execute([':nl'=>$nln, ':en'=>$enn, ':id'=>$id]);
      }
    }
    // nieuwe
    $new_nl = trim($_POST['new_name_nl'] ?? '');
    $new_en = trim($_POST['new_name_en'] ?? '');
    if ($new_nl !== '' || $new_en !== '') {
      $st = db()->prepare("INSERT INTO categories (name_nl, name_en) VALUES (:nl,:en)");
      $st->execute([':nl'=>$new_nl, ':en'=>$new_en]);
    }
  }

  if (isset($_POST['delete'])) {
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
      $st = db()->prepare("DELETE FROM categories WHERE id=:id");
      $st->execute([':id'=>$id]);
    }
  }

  header('Location: categories.php');
  exit;
}

$rows = db()->query("SELECT id, name_nl, name_en FROM categories ORDER BY name_nl")->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="nl">
<head>
  <meta charset="utf-8">
  <title>Categorieën</title>
  <meta name="robots" content="noindex,nofollow,noarchive">
  <style>
    :root{color-scheme:light dark}
    body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial;margin:0;padding:16px}
    table{width:100%;border-collapse:collapse}
    th,td{padding:10px;border-bottom:1px solid #e5e7eb;text-align:left}
    input{width:100%;padding:.5rem .7rem;border:1px solid #d1d5db;border-radius:8px}
    .row{display:flex;gap:12px}
    .btn{padding:.6rem .9rem;border-radius:8px;border:0;background:#2563eb;color:#fff;cursor:pointer}
    .ghost{background:#f3f4f6;color:#111}
    /* --- Mobile responsive table --- */
    @media (max-width: 640px){
      table.responsive{border:0}
      table.responsive thead{border:0;clip:rect(0 0 0 0);height:1px;margin:-1px;overflow:hidden;padding:0;position:absolute;width:1px}
      table.responsive tr{display:block;margin:0 0 12px;border:1px solid #e5e7eb;border-radius:12px;padding:10px;background:Canvas}
      table.responsive td{display:flex;gap:12px;justify-content:space-between;align-items:center;border:none;border-bottom:1px dashed #e5e7eb;padding:8px 10px}
      table.responsive td:last-child{border-bottom:none}
      table.responsive td::before{content:attr(data-label);font-weight:600;color:#6b7280}
      .row{flex-wrap:wrap}
      .btn{padding:.55rem .8rem;font-size:14px}
    }
  </style>
</head>
<body>
  <h1>Categorieën</h1>

  <form method="post">
    <?= csrf_field() ?>
    <table class="responsive">
      <thead>
        <tr><th>ID</th><th>NL</th><th>EN</th><th>Acties</th></tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td data-label="ID"><input type="text" value="<?= (int)$r['id'] ?>" readonly style="width:60px"></td>
            <td data-label="NL"><input type="text" name="name_nl[]" value="<?= htmlspecialchars($r['name_nl']) ?>"></td>
            <td data-label="EN"><input type="text" name="name_en[]" value="<?= htmlspecialchars($r['name_en']) ?>"></td>
            <td data-label="Acties" class="row actions">
              <input type="hidden" name="id[]" value="<?= (int)$r['id'] ?>">
              <button class="btn ghost" name="delete" value="1" formaction="categories.php" formmethod="post" onclick="return confirm('Verwijderen?')">Verwijderen</button>
            </td>
          </tr>
        <?php endforeach; ?>
        <tr>
          <td data-label="ID">+</td>
          <td data-label="NL"><input type="text" name="new_name_nl" placeholder="Nieuwe NL.."></td>
          <td data-label="EN"><input type="text" name="new_name_en" placeholder="Nieuwe EN.."></td>
          <td data-label="Acties"></td>
        </tr>
      </tbody>
    </table>

    <div style="margin-top:12px" class="row">
      <button class="btn" type="submit" name="save" value="1">Opslaan</button>
      <a class="btn ghost" href="dashboard.php">Terug</a>
    </div>
  </form>
</body>
</html>