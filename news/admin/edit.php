<?php
declare(strict_types=1);
require_once __DIR__.'/auth.php';
require_once __DIR__.'/../config.php';

// hulpfunctie categorieën
function allCategories(): array {
  $s = db()->query("SELECT id, name_nl, name_en FROM categories ORDER BY name_nl");
  return $s->fetchAll(PDO::FETCH_ASSOC);
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$article = null;
if ($id > 0) {
  $st = db()->prepare("SELECT * FROM articles WHERE id=:id");
  $st->execute([':id'=>$id]);
  $article = $st->fetch(PDO::FETCH_ASSOC);
  if (!$article) { http_response_code(404); echo "Artikel niet gevonden"; exit; }
}

$cats = allCategories();

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
  csrf_validate();

  $title_nl = trim($_POST['title_nl'] ?? '');
  $title_en = trim($_POST['title_en'] ?? '');
  $desc_nl  = trim($_POST['description_nl'] ?? '');
  $desc_en  = trim($_POST['description_en'] ?? '');
  $cat_id   = (int)($_POST['category_id'] ?? 0);
  $pub      = isset($_POST['is_published']) ? 1 : 0;

  $rawDate  = trim($_POST['date_published'] ?? '');
  if ($rawDate !== '') {
    $date_pub = str_replace('T',' ',$rawDate);
    if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $date_pub)) { $date_pub .= ':00'; }
  } else {
    $date_pub = date('Y-m-d H:i:s');
  }

  $full_url = trim($_POST['full_url'] ?? '');
  if ($full_url === '') $full_url = null;

  $currentImage = (is_array($article) && array_key_exists('image_path',$article)) ? $article['image_path'] : null;
  $image_path = $currentImage;

  if (!empty($_FILES['image']['name'])) {
    $up = $_FILES['image'];
    if ($up['error'] === UPLOAD_ERR_OK) {
      $ext = strtolower(pathinfo($up['name'], PATHINFO_EXTENSION));
      $fn  = 'uploads/'.date('YmdHis').'_'.bin2hex(random_bytes(4)).'.'.$ext;
      $dest = dirname(__DIR__).'/'.$fn;
      if (!is_dir(dirname($dest))) { @mkdir(dirname($dest), 0775, true); }
      if (move_uploaded_file($up['tmp_name'], $dest)) {
        $image_path = $fn;
      }
    }
  }

  if ($id > 0) {
    $sql = "UPDATE articles SET
              title_nl=:title_nl,
              title_en=:title_en,
              description_nl=:desc_nl,
              description_en=:desc_en,
              image_path=:image_path,
              full_url=:full_url,
              date_published=:date_published,
              category_id=:category_id,
              is_published=:is_published
            WHERE id=:id";
    $st = db()->prepare($sql);
    $st->execute([
      ':title_nl'=>$title_nl, ':title_en'=>$title_en,
      ':desc_nl'=>$desc_nl,   ':desc_en'=>$desc_en,
      ':image_path'=>$image_path,
      ':full_url'=>$full_url,
      ':date_published'=>$date_pub,
      ':category_id'=>$cat_id,
      ':is_published'=>$pub,
      ':id'=>$id
    ]);
  } else {
    $sql = "INSERT INTO articles
              (title_nl, title_en, description_nl, description_en,
               image_path, full_url, date_published, category_id, is_published)
            VALUES
              (:title_nl, :title_en, :desc_nl, :desc_en,
               :image_path, :full_url, :date_published, :category_id, :is_published)";
    $st = db()->prepare($sql);
    $st->execute([
      ':title_nl'=>$title_nl, ':title_en'=>$title_en,
      ':desc_nl'=>$desc_nl,   ':desc_en'=>$desc_en,
      ':image_path'=>$image_path,
      ':full_url'=>$full_url,
      ':date_published'=>$date_pub,
      ':category_id'=>$cat_id,
      ':is_published'=>$pub
    ]);
    $id = (int)db()->lastInsertId();
  }

  header('Location: dashboard.php');
  exit;
}
?>
<!doctype html>
<html lang="nl">
<head>
  <meta charset="utf-8">
  <title><?= $id ? 'Artikel bewerken' : 'Nieuw artikel' ?></title>
  <meta name="robots" content="noindex,nofollow,noarchive">
  <style>
    :root{color-scheme:light dark}
    body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial;margin:0;padding:16px}
    .form{max-width:960px;margin:0 auto}
    label{display:block;margin:.75rem 0 .25rem;font-weight:600}
    input,textarea,select{width:100%;padding:.7rem .9rem;border:1px solid #d1d5db;border-radius:10px}
    textarea{min-height:120px}
    .row{display:flex;gap:16px}
    .row>div{flex:1}
    .btn{padding:.7rem 1rem;border:0;border-radius:10px;background:#2563eb;color:#fff;cursor:pointer}
    .ghost{background:#f3f4f6;color:#111;text-decoration:none;padding:.7rem 1rem;border-radius:10px}
    .imgprev{margin-top:8px;max-width:260px;border-radius:10px;display:block}
    small{color:#6b7280}
  </style>
</head>
<body>
  <div class="form">
    <h1><?= $id ? 'Artikel bewerken' : 'Nieuw artikel' ?></h1>

    <form method="post" enctype="multipart/form-data">
      <?= csrf_field() ?>

      <div class="row">
        <div>
          <label>Titel (NL)</label>
          <input type="text" name="title_nl" required value="<?= htmlspecialchars($article['title_nl'] ?? '') ?>">
        </div>
        <div>
          <label>Titel (EN)</label>
          <input type="text" name="title_en" value="<?= htmlspecialchars($article['title_en'] ?? '') ?>">
        </div>
      </div>

      <div class="row">
        <div>
          <label>Beschrijving (NL)</label>
          <textarea name="description_nl" required><?= htmlspecialchars($article['description_nl'] ?? '') ?></textarea>
        </div>
        <div>
          <label>Beschrijving (EN)</label>
          <textarea name="description_en"><?= htmlspecialchars($article['description_en'] ?? '') ?></textarea>
        </div>
      </div>

      <div class="row">
        <div>
          <label>Categorie</label>
          <select name="category_id" required>
            <option value="">— Kies —</option>
            <?php foreach ($cats as $c): ?>
              <option value="<?= (int)$c['id'] ?>" <?= (($article['category_id'] ?? 0) == $c['id']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($c['name_nl'].' / '.$c['name_en']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label>Publicatiedatum</label>
          <?php
            $dt = $article['date_published'] ?? date('Y-m-d H:i:s');
            $dtLocal = str_replace(' ', 'T', substr($dt, 0, 16));
          ?>
          <input type="datetime-local" name="date_published" value="<?= htmlspecialchars($dtLocal) ?>">
        </div>
      </div>

      <div class="row">
        <div>
          <label>Afbeelding</label>
          <input type="file" name="image" accept="image/*">
          <?php if (!empty($article['image_path'])): ?>
            <img class="imgprev" src="../<?= htmlspecialchars($article['image_path']) ?>" alt="">
          <?php endif; ?>
          <small>Laat leeg om de huidige afbeelding te behouden.</small>
        </div>
        <div>
          <label>Volledige artikel-link (optioneel)</label>
          <input type="url" name="full_url" placeholder="https://..." value="<?= htmlspecialchars($article['full_url'] ?? '') ?>">
          <small>Relatieve paden worden automatisch omgezet naar absolute URLs in de API.</small>
        </div>
      </div>

      <label style="display:block;margin-top:12px">
        <input type="checkbox" name="is_published" value="1" <?= !empty($article['is_published']) ? 'checked' : '' ?>>
        Gepubliceerd
      </label>

      <div style="display:flex;gap:12px;margin-top:16px">
        <button class="btn" type="submit">Opslaan</button>
        <a class="ghost" href="dashboard.php">Annuleren</a>
      </div>
    </form>
  </div>
</body>
</html>