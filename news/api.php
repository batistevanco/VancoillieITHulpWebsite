<?php header('X-Robots-Tag: noindex, nofollow, noarchive', true);
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__.'/config.php';

$action = $_GET['action'] ?? 'articles';
$lang   = ($_GET['lang'] ?? 'nl') === 'en' ? 'en' : 'nl';

try {
  if ($action === 'categories') {
    $stmt = db()->query("SELECT id, 
      CASE WHEN :lang='en' THEN name_en ELSE name_nl END AS name
      FROM categories ORDER BY name_nl");
    $stmt->execute([':lang'=>$lang]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC)); exit;
  }

  if ($action === 'articles') {
    $where = "WHERE is_published=1";
    $params = [':lang'=>$lang];
    if (!empty($_GET['category_id'])) {
      $where .= " AND category_id = :cid";
      $params[':cid'] = (int)$_GET['category_id'];
    }
    $q = db()->prepare("
      SELECT a.id,
        CASE WHEN :lang='en' THEN a.title_en ELSE a.title_nl END AS title,
        CASE WHEN :lang='en' THEN a.description_en ELSE a.description_nl END AS description,
        a.image_path,
        a.date_published,
        a.category_id,
        CASE WHEN :lang='en' THEN c.name_en ELSE c.name_nl END AS categoryName
      FROM articles a
      JOIN categories c ON c.id=a.category_id
      $where
      ORDER BY a.date_published DESC
      LIMIT 100
    ");
    $q->execute($params);
    $rows = array_map(function($r){
      return [
        'id'=>(int)$r['id'],
        'title'=>$r['title'],
        'description'=>$r['description'],
        'imageURL'=> $r['image_path'] ? absoluteUrl($r['image_path']) : null,
        'date'=> gmdate('c', strtotime($r['date_published'])),
        'categoryID'=>(int)$r['category_id'],
        'categoryName'=>$r['categoryName']
      ];
    }, $q->fetchAll(PDO::FETCH_ASSOC));
    echo json_encode($rows); exit;
  }

  // Mutating endpoints (optional for admin UI via fetch)
  if ($action === 'create' || $action === 'update' || $action === 'delete') {
    requireAuth();
  }

  if ($action === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    $stmt = db()->prepare("DELETE FROM articles WHERE id=:id");
    $stmt->execute([':id'=>$id]);
    echo json_encode(['ok'=>true]); exit;
  }

  http_response_code(400);
  echo json_encode(['error'=>'Unsupported action']);
} catch(Exception $e){
  http_response_code(500);
  echo json_encode(['error'=>'server','detail'=>$e->getMessage()]);
}

function absoluteUrl($path){
  $base = (isset($_SERVER['HTTPS'])?'https':'http').'://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']);
  return rtrim($base,'/').'/'.ltrim($path,'/');
}

function requireAuth(){
  $hdr = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
  if (!str_starts_with($hdr, 'Bearer ')) { http_response_code(401); exit; }
  $tok = substr($hdr, 7);
  if (!hash_equals(ADMIN_API_TOKEN, $tok)) { http_response_code(403); exit; }
}