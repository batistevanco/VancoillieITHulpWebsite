<?php header('X-Robots-Tag: noindex, nofollow, noarchive', true);
session_start(); if(empty($_SESSION['ok'])){ header('Location: login.php'); exit; }
require_once __DIR__.'/../config.php';

if($_SERVER['REQUEST_METHOD']==='POST'){
  // Verwijderen (heeft voorrang als een per-rij knop werd geklikt)
  if(isset($_POST['delete'])){
    $id = (int)$_POST['delete'];
    $s = db()->prepare("DELETE FROM categories WHERE id=:id");
    $s->execute([':id'=>$id]);
  }
  // Opslaan van wijzigingen
  elseif(isset($_POST['save'])){
    if(!empty($_POST['id']) && is_array($_POST['id'])){
      foreach($_POST['id'] as $i=>$id){
        $s=db()->prepare("UPDATE categories SET name_nl=:nl, name_en=:en WHERE id=:id");
        $s->execute([
          ':nl'=>$_POST['name_nl'][$i] ?? '',
          ':en'=>$_POST['name_en'][$i] ?? '',
          ':id'=>$id
        ]);
      }
    }
    // Indien in de onderste rij nieuwe waarden zijn ingevuld en je klikt op Opslaan, voeg dan ook toe
    if(isset($_POST['name_nl'], $_POST['name_en'])){
      $newNl = trim($_POST['name_nl']);
      $newEn = trim($_POST['name_en']);
      if($newNl !== '' && $newEn !== ''){
        $s=db()->prepare("INSERT INTO categories(name_nl,name_en) VALUES(:nl,:en)");
        $s->execute([':nl'=>$newNl, ':en'=>$newEn]);
      }
    }
  }
  // Nieuwe categorie via de expliciete knop "add"
  elseif(isset($_POST['add'])){
    $newNl = trim($_POST['name_nl'] ?? '');
    $newEn = trim($_POST['name_en'] ?? '');
    if($newNl !== '' && $newEn !== ''){
      $s=db()->prepare("INSERT INTO categories(name_nl,name_en) VALUES(:nl,:en)");
      $s->execute([':nl'=>$newNl, ':en'=>$newEn]);
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
<thead><tr><th>ID</th><th>NL</th><th>EN</th><th>Acties</th></tr></thead>
<tbody>
<?php foreach($cats as $c): ?>
<tr>
  <td><input type="hidden" name="id[]" value="<?= (int)$c['id'] ?>"><?= (int)$c['id'] ?></td>
  <td><input name="name_nl[]" value="<?= htmlspecialchars($c['name_nl']) ?>"></td>
  <td><input name="name_en[]" value="<?= htmlspecialchars($c['name_en']) ?>"></td>
  <td>
    <button name="delete" value="<?= (int)$c['id'] ?>" onclick="return confirm('Verwijderen?')">Verwijderen</button>
  </td>
</tr>
<?php endforeach; ?>
<tr>
  <td>+</td>
  <td><input name="name_nl"></td>
  <td><input name="name_en"></td>
  <td></td>
</tr>
</tbody>
</table>
<button name="save">Wijzigingen opslaan</button>
<button name="add">Nieuwe categorie toevoegen</button>
</form>
</body></html>