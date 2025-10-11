<?php
declare(strict_types=1);

// --- TEMP debug schakelaar ---
if (isset($_GET['debug'])) {
  ini_set('display_errors', '1');
  ini_set('display_startup_errors', '1');
  error_reporting(E_ALL);
}

// --- Auth (met fallback als auth.php ontbreekt) ---
$__auth = __DIR__ . '/auth.php';
if (file_exists($__auth)) {
  require_once $__auth;              // levert sessie + csrf_field() etc.
} else {
  if (session_status() !== PHP_SESSION_ACTIVE) session_start();
  if (empty($_SESSION['admin_logged_in'])) {
    header('Location: login.php');   // zorg dat login.php bestaat
    exit;
  }
}

// --- Config / DB ---
require_once __DIR__ . '/../config.php';

// --- Data laden ---
try {
  $sql = "
    SELECT a.id,
           a.title_nl, a.title_en, a.is_published, a.date_published, a.full_url,
           c.name_nl AS cat_nl, c.name_en AS cat_en
    FROM articles a
    LEFT JOIN categories c ON c.id = a.category_id
    ORDER BY a.date_published DESC
  ";
  $st = db()->query($sql);
  if ($st === false) {
    $ei = db()->errorInfo();
    throw new RuntimeException('DB query failed: ' . print_r($ei, true));
  }
  $rows = $st->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
  if (!isset($_GET['debug'])) {
    http_response_code(500);
  }
  echo '<pre style="padding:16px;background:#fff3cd;color:#1f2937;border:1px solid #facc15;border-radius:8px;white-space:pre-wrap">';
  echo "Dashboard kon niet laden.\n\n";
  echo htmlspecialchars($e->getMessage());
  if (isset($_GET['debug'])) {
    echo "\n\nStack trace:\n" . htmlspecialchars($e->getTraceAsString());
  }
  echo "</pre>";
  exit;
}
?>
<!doctype html>
<html lang="nl">
<head>
  <meta charset="utf-8">
  <title>News Admin • Dashboard</title>
  <meta name="robots" content="noindex,nofollow,noarchive">
  <style>
    :root{color-scheme:light dark}
    body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial;margin:0;padding:16px}
    header{display:flex;align-items:center;justify-content:space-between;margin-bottom:16px}
    .btn{display:inline-block;padding:.6rem .9rem;border-radius:10px;border:0;background:#2563eb;color:#fff;text-decoration:none}
    .btn.ghost{background:#f3f4f6;color:#111}
    table{width:100%;border-collapse:collapse}
    th,td{padding:10px;border-bottom:1px solid #e5e7eb;text-align:left;vertical-align:top}
    .pill{padding:2px 8px;border-radius:999px;font-size:12px}
    .pill.pub{background:#dcfce7;color:#166534}
    .pill.draft{background:#fee2e2;color:#991b1b}
    form.inline{display:inline}
  </style>
</head>
<body>
  <header>
    <h1 style="margin:0">Artikelen</h1>
    <nav>
      <a class="btn ghost" href="categories.php">Categorieën</a>
      <a class="btn" href="edit.php">+ Nieuw artikel</a>
    </nav>
  </header>

  <table>
    <thead>
      <tr>
        <th>ID</th>
        <th>Titel (NL)</th>
        <th>Categorie</th>
        <th>Datum</th>
        <th>Status</th>
        <th>Link</th>
        <th>Acties</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
      <tr>
        <td><?= (int)$r['id'] ?></td>
        <td><?= htmlspecialchars($r['title_nl'] ?: ($r['title_en'] ?? '—')) ?></td>
        <td><?= htmlspecialchars(($r['cat_nl'] ?? '—') . ' / ' . ($r['cat_en'] ?? '—')) ?></td>
        <td><?= htmlspecialchars($r['date_published'] ?? '—') ?></td>
        <td>
          <?php if ((int)($r['is_published'] ?? 0) === 1): ?>
            <span class="pill pub">Gepubliceerd</span>
          <?php else: ?>
            <span class="pill draft">Concept</span>
          <?php endif; ?>
        </td>
        <td>
          <?php if (!empty($r['full_url'])):
            $url = trim((string)$r['full_url']);
            if ($url !== '' && !preg_match('~^https?://~i', $url)) {
              $proto = (!empty($_SERVER['HTTPS']) ? 'https' : 'http').'://';
              $base  = $proto.$_SERVER['HTTP_HOST'].dirname(dirname($_SERVER['SCRIPT_NAME'])); // /news
              $url   = rtrim($base,'/').'/'.ltrim($url,'/');
            }
          ?>
            <a href="<?= htmlspecialchars($url) ?>" target="_blank" rel="noopener">🔗 open</a>
          <?php else: ?>—<?php endif; ?>
        </td>
        <td>
          <a class="btn ghost" href="edit.php?id=<?= (int)$r['id'] ?>">Bewerken</a>
          <form class="inline" method="post" action="delete.php" onsubmit="return confirm('Verwijderen?')">
            <?= function_exists('csrf_field') ? csrf_field() : '' ?>
            <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
            <button class="btn" type="submit" style="background:#ef4444">Verwijderen</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>

  <p style="margin-top:16px">
    <a class="btn ghost" href="logout.php">Uitloggen</a>
  </p>
</body>
</html>