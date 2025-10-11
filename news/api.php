<?php
header('X-Robots-Tag: noindex, nofollow, noarchive', true);
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__.'/config.php';

$action = $_GET['action'] ?? 'articles';

// Whitelist taal
$langIn = $_GET['lang'] ?? 'nl';
$lang   = ($langIn === 'en') ? 'en' : 'nl';

try {
  if ($action === 'categories') {
    // Kies kolom op basis van taal
    $field = ($lang === 'en') ? 'name_en' : 'name_nl';
    $sql = "SELECT id, $field AS name FROM categories ORDER BY $field";
    $stmt = db()->prepare($sql);
    $stmt->execute();
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC), JSON_UNESCAPED_UNICODE);
    exit;
  }

  if ($action === 'articles') {
    $where  = "WHERE a.is_published = 1";
    $params = [];
    if (!empty($_GET['category_id'])) {
      $where .= " AND a.category_id = :cid";
      $params[':cid'] = (int)$_GET['category_id'];
    }

    // Taalspecifieke velden
    $titleField = ($lang === 'en') ? 'a.title_en'       : 'a.title_nl';
    $descField  = ($lang === 'en') ? 'a.description_en' : 'a.description_nl';
    $catField   = ($lang === 'en') ? 'c.name_en'        : 'c.name_nl';

    $sql = "
      SELECT a.id,
             $titleField AS title,
             $descField  AS description,
             a.image_path,
             a.date_published,
             a.category_id,
             $catField   AS categoryName
      FROM articles a
      JOIN categories c ON c.id = a.category_id
      $where
      ORDER BY a.date_published DESC
      LIMIT 100
    ";

    $q = db()->prepare($sql);
    $q->execute($params);

    $rows = array_map(function($r){
      return [
        'id'          => (int)$r['id'],
        'title'       => $r['title'],
        'description' => $r['description'],
        'imageURL'    => $r['image_path'] ? absoluteUrl($r['image_path']) : null,
        'date'        => gmdate('c', strtotime($r['date_published'])),
        'categoryID'  => (int)$r['category_id'],
        'categoryName'=> $r['categoryName']
      ];
    }, $q->fetchAll(PDO::FETCH_ASSOC));

    echo json_encode($rows, JSON_UNESCAPED_UNICODE);
    exit;
  }

  // (optioneel) mutaties via bearer-token
  if ($action === 'create' || $action === 'update' || $action === 'delete') {
    requireAuth();
  }

  if ($action === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    $stmt = db()->prepare("DELETE FROM articles WHERE id=:id");
    $stmt->execute([':id'=>$id]);
    echo json_encode(['ok'=>true]);
    exit;
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