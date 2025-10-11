<?php
require_once __DIR__.'/auth.php';
require_once __DIR__.'/../config.php';
ini_set('display_errors', 1);
error_reporting(E_ALL);

$st = db()->query("
  SELECT a.id, a.title_nl, a.title_en, a.is_published, a.date_published, a.full_url,
         c.name_nl AS cat_nl, c.name_en AS cat_en
  FROM articles a
  JOIN categories c ON c.id=a.category_id
  ORDER BY a.date_published DESC
");
$rows = $st->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="nl">
<head>
  <meta charset="utf-8">
  <title>Dashboard</title>
  <meta name="robots" content="noindex,nofollow,noarchive">
  <link rel="stylesheet" href="admin.css">
  <style>
    body { font-family: system-ui,-apple-system,Segoe UI,Roboto; padding: 16px; }
    table { width:100%; border-collapse: collapse; }
    th, td { text-align:left; padding:10px; border-bottom:1px solid #eee; }
    .pill { padding:2px 8px; border-radius:999px; font-size:12px; }
    .pill.pub { background:#dcfce7; color:#166534; }
    .pill.draft { background:#fee2e2; color:#991b1b; }
    .actions a { margin-right:8px; }
    .ext { text-decoration:none; }
  </style>
</head>
<body>
  <h1>Artikelen</h1>

  <p><a href="edit.php" class="ext">+ Nieuw artikel</a></p>

  <table>
    <thead>
      <tr>
        <th>ID</th>
        <th>Titel (NL)</th>
        <th>Categorie</th>
        <th>Datum</th>
        <th>Publicatie</th>
        <th>Link</th>
        <th>Acties</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach($rows as $r): ?>
        <tr>
          <td><?= (int)$r['id'] ?></td>
          <td><?= htmlspecialchars($r['title_nl'] ?: $r['title_en']) ?></td>
          <td><?= htmlspecialchars($r['cat_nl'].' / '.$r['cat_en']) ?></td>
          <td><?= htmlspecialchars($r['date_published']) ?></td>
          <td>
            <?php if ($r['is_published']): ?>
              <span class="pill pub">Gepubliceerd</span>
            <?php else: ?>
              <span class="pill draft">Concept</span>
            <?php endif; ?>
          </td>
          <td>
            <?php if (!empty($r['full_url'])):
              $url = trim($r['full_url']);
              // relatieve → absolute
              if ($url !== '' && !preg_match('~^https?://~i', $url)) {
                $proto = (!empty($_SERVER['HTTPS']) ? 'https' : 'http').'://';
                $base = $proto.$_SERVER['HTTP_HOST'].dirname(dirname($_SERVER['SCRIPT_NAME'])); // /news
                $url = rtrim($base,'/').'/'.ltrim($url,'/');
              }
            ?>
              <a class="ext" href="<?= htmlspecialchars($url) ?>" target="_blank" rel="noopener">
                🔗 open
              </a>
            <?php else: ?>
              —
            <?php endif; ?>
          </td>
          <td class="actions">
            <a href="edit.php?id=<?= (int)$r['id'] ?>">Bewerken</a>
            <a href="delete.php?id=<?= (int)$r['id'] ?>" onclick="return confirm('Verwijderen?')">Verwijderen</a>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</body>
</html>