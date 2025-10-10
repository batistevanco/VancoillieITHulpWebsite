<?php header('X-Robots-Tag: noindex, nofollow, noarchive', true);
session_start(); if(empty($_SESSION['ok'])){ header('Location: login.php'); exit; }
require_once __DIR__.'/../config.php';

if($_SERVER['REQUEST_METHOD']==='POST'){
  if(isset($_POST['add'])){
    $s=db()->prepare("INSERT INTO categories(name_nl,name_en) VALUES(:nl,:en)");
    $s->execute([':nl'=>$_POST['name_nl'], ':en'=>$_POST['name_en']]);
  } elseif(isset($_POST['save'])){
    foreach($_POST['id'] as $i=>$id){
      $s=db()->prepare("UPDATE categories SET name_nl=:nl, name_en=:en WHERE id=:id");
      $s->execute([':nl'=>$_POST['name_nl'][$i], ':en'=>$_POST['name_en'][$i], ':id'=>$id]);
    }
  }
}
$cats = db()->query("SELECT * FROM categories ORDER BY name_nl")->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html><html lang="nl"><meta charset="utf-8">
<title>Categorieën</title>
<link rel="stylesheet" href="admin.css">
<meta name="robots" content="noindex, nofollow, noarchive">
<body>
<header><h1>Categorieën</h1><nav><a href="dashboard.php">Terug</a></nav></header>

<form method="post">
<table>
<thead><tr><th>ID</th><th>NL</th><th>EN</th></tr></thead>
<tbody>
<?php foreach($cats as $c): ?>
<tr>
  <td><input type="hidden" name="id[]" value="<?= (int)$c['id'] ?>"><?= (int)$c['id'] ?></td>
  <td><input name="name_nl[]" value="<?= htmlspecialchars($c['name_nl']) ?>"></td>
  <td><input name="name_en[]" value="<?= htmlspecialchars($c['name_en']) ?>"></td>
</tr>
<?php endforeach; ?>
<tr>
  <td>+</td>
  <td><input name="name_nl"></td>
  <td><input name="name_en"></td>
</tr>
</tbody>
</table>
<button name="save">Wijzigingen opslaan</button>
<button name="add">Nieuwe categorie toevoegen</button>
</form>
</body></html>