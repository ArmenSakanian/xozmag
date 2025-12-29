<?php
require_once __DIR__ . "/../../db.php";
function slugify_ru($text) {
  $map = [
    'а'=>'a','б'=>'b','в'=>'v','г'=>'g','д'=>'d','е'=>'e','ё'=>'e','ж'=>'zh','з'=>'z','и'=>'i','й'=>'y',
    'к'=>'k','л'=>'l','м'=>'m','н'=>'n','о'=>'o','п'=>'p','р'=>'r','с'=>'s','т'=>'t','у'=>'u','ф'=>'f',
    'х'=>'h','ц'=>'c','ч'=>'ch','ш'=>'sh','щ'=>'sch','ъ'=>'','ы'=>'y','ь'=>'','э'=>'e','ю'=>'yu','я'=>'ya',
  ];

  $s = mb_strtolower(trim((string)$text), 'UTF-8');
  $s = strtr($s, $map);
  $s = preg_replace('~[^a-z0-9]+~', '-', $s);
  $s = preg_replace('~-+~', '-', $s);
  $s = trim($s, '-');
  return $s ?: 'product';
}

function slug_exists(PDO $pdo, $slug, $excludeId = null) {
  $sql = "SELECT 1 FROM products WHERE slug = :s" . ($excludeId ? " AND id <> :id" : "") . " LIMIT 1";
  $st = $pdo->prepare($sql);
  $st->bindValue(':s', $slug);
  if ($excludeId) $st->bindValue(':id', (int)$excludeId, PDO::PARAM_INT);
  $st->execute();
  return (bool)$st->fetchColumn();
}

$rows = $pdo->query("SELECT id, name FROM products ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);

$updated = 0;

foreach ($rows as $r) {
  $id = (int)$r['id'];
  $base = slugify_ru($r['name']);

  $slug = $base;
  $i = 2;
  while (slug_exists($pdo, $slug, $id)) {
    $slug = $base . '-' . $i;
    $i++;
  }

  $pdo->prepare("UPDATE products SET slug = :slug WHERE id = :id")
      ->execute([':slug' => $slug, ':id' => $id]);

  $updated++;
}

header("Content-Type: text/plain; charset=utf-8");
echo "OK. Updated: {$updated}\n";
