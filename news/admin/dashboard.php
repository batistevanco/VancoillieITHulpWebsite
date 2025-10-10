<?php header('X-Robots-Tag: noindex, nofollow, noarchive', true);
session_start(); if(empty($_SESSION['ok'])){ header('Location: login.php'); exit; }
require_once __DIR__.'/../config.php';

$cats = db()->query("SELECT * FROM categories ORDER BY name_nl")->fetchAll(PDO::FETCH_ASSOC);
$arts = db()->query("SELECT a.*, c.name_nl AS cat_nl FROM articles a JOIN categories c ON c.id=a.category_id ORDER BY date_published DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html><html lang="nl"><meta charset="utf-8">
<title>News Admin – Overzicht</title>
<link rel="stylesheet" href="admin.css">
<meta name="robots" content="noindex, nofollow, noarchive">
<body>
<header><h1>Artikels</h1><nav><a href="edit.php">Nieuw artikel</a> <a href="categories.php">Categorieën</a> <a href="logout.php">Uitloggen</a></nav></header>

<table>
<thead><tr><th>ID</th><th>Titel (NL)</th><th>Categorie</th><th>Datum</th><th>Gepubliceerd</th><th></th></tr></thead>
<tbody>
<?php foreach($arts as $a): ?>
<tr>
  <td><?= (int)$a['id'] ?></td>
  <td><?= htmlspecialchars($a['title_nl']) ?></td>
  <td><?= htmlspecialchars($a['cat_nl']) ?></td>
  <td><?= htmlspecialchars($a['date_published']) ?></td>
  <td><?= $a['is_published']?'✔︎':'—' ?></td>
  <td>
    <a href="edit.php?id=<?= (int)$a['id'] ?>">Bewerken</a> |
    <a href="delete.php?id=<?= (int)$a['id'] ?>" onclick="return confirm('Verwijderen?')">Verwijderen</a>
  </td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</body></html>