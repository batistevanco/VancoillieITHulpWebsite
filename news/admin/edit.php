<?php header('X-Robots-Tag: noindex, nofollow, noarchive', true);
session_start(); if(empty($_SESSION['ok'])){ header('Location: login.php'); exit; }
require_once __DIR__.'/../config.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$cats = db()->query("SELECT * FROM categories ORDER BY name_nl")->fetchAll(PDO::FETCH_ASSOC);
$article = ['title_nl'=>'','title_en'=>'','description_nl'=>'','description_en'=>'','category_id'=>'','is_published'=>1,'image_path'=>null];

if ($id) {
  $s=db()->prepare("SELECT * FROM articles WHERE id=:id"); $s->execute([':id'=>$id]);
  $article = $s->fetch(PDO::FETCH_ASSOC);
}

if($_SERVER['REQUEST_METHOD']==='POST'){
  $imgPath = $article['image_path'];
  if(!empty($_FILES['image']['name'])){
    $dir = __DIR__.'/uploads/';
    if(!is_dir($dir)) mkdir($dir, 0775, true);
    $fname = time().'_'.preg_replace('/[^a-zA-Z0-9\._-]/','', $_FILES['image']['name']);
    move_uploaded_file($_FILES['image']['tmp_name'], $dir.$fname);
    $imgPath = 'admin/uploads/'.$fname;
  }

  $params = [
    ':title_nl'=>$_POST['title_nl'],
    ':title_en'=>$_POST['title_en'],
    ':description_nl'=>$_POST['description_nl'],
    ':description_en'=>$_POST['description_en'],
    ':category_id'=> (int)$_POST['category_id'],
    ':is_published'=> isset($_POST['is_published'])?1:0,
    ':image_path'=> $imgPath
  ];

  if($id){
    $sql="UPDATE articles SET title_nl=:title_nl,title_en=:title_en,description_nl=:description_nl,description_en=:description_en,category_id=:category_id,is_published=:is_published,image_path=:image_path WHERE id=$id";
    db()->prepare($sql)->execute($params);
  } else {
    $sql="INSERT INTO articles(title_nl,title_en,description_nl,description_en,category_id,is_published,image_path) VALUES(:title_nl,:title_en,:description_nl,:description_en,:category_id,:is_published,:image_path)";
    db()->prepare($sql)->execute($params);
  }
  header('Location: dashboard.php'); exit;
}
?>
<!doctype html><html lang="nl"><meta charset="utf-8">
<title>Artikel bewerken</title>
<link rel="stylesheet" href="admin.css">
<meta name="robots" content="noindex, nofollow, noarchive">
<body>
<header><h1><?= $id?'Bewerk':'Nieuw' ?> artikel</h1><nav><a href="dashboard.php">Terug</a></nav></header>

<form method="post" enctype="multipart/form-data" class="form">
  <label>Titel (NL)<input name="title_nl" value="<?= htmlspecialchars($article['title_nl']) ?>" required></label>
  <label>Titel (EN)<input name="title_en" value="<?= htmlspecialchars($article['title_en']) ?>" required></label>

  <label>Omschrijving (NL)<textarea name="description_nl" required><?= htmlspecialchars($article['description_nl']) ?></textarea></label>
  <label>Omschrijving (EN)<textarea name="description_en" required><?= htmlspecialchars($article['description_en']) ?></textarea></label>

  <label>Categorie
    <select name="category_id" required>
      <option value="">—</option>
      <?php foreach($cats as $c): ?>
        <option value="<?= (int)$c['id'] ?>" <?= $article['category_id']==$c['id']?'selected':'' ?>>
          <?= htmlspecialchars($c['name_nl']) ?> / <?= htmlspecialchars($c['name_en']) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </label>

  <label>Afbeelding <input type="file" name="image" accept="image/*"></label>
  <?php if(!empty($article['image_path'])): ?>
    <p>Huidig: <a href="../<?= htmlspecialchars($article['image_path']) ?>" target="_blank"><?= htmlspecialchars($article['image_path']) ?></a></p>
  <?php endif; ?>

  <label><input type="checkbox" name="is_published" <?= $article['is_published']?'checked':'' ?>> Gepubliceerd</label>
  <button type="submit">Opslaan</button>
</form>
</body></html>