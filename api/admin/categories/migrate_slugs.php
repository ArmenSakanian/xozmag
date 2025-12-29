<?php
require_once $_SERVER['DOCUMENT_ROOT'] . "/api/auth/require_admin.php";
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
  return $s ?: 'cat';
}

function slug_exists(PDO $pdo, $slug, $excludeId = null) {
  $sql = "SELECT 1 FROM categories WHERE slug = :s" . ($excludeId ? " AND id <> :id" : "") . " LIMIT 1";
  $st = $pdo->prepare($sql);
  $st->bindValue(':s', $slug);
  if ($excludeId) $st->bindValue(':id', (int)$excludeId, PDO::PARAM_INT);
  $st->execute();
  return (bool)$st->fetchColumn();
}

// Берём в порядке уровня, чтобы родитель уже имел slug
$rows = $pdo->query("SELECT id, parent_id, name, level FROM categories ORDER BY level ASC, parent_id ASC, id ASC")->fetchAll(PDO::FETCH_ASSOC);

$slugById = [];
$updated = 0;

foreach ($rows as $r) {
  $id = (int)$r['id'];
  $parentId = $r['parent_id'] !== null ? (int)$r['parent_id'] : null;

  $base = slugify_ru($r['name']);

  // ✅ делаем иерархический slug: parent-slug + "-" + slug
  if ($parentId && isset($slugById[$parentId])) {
    $base = $slugById[$parentId] . '-' . $base;
  }

  $slug = $base;
  $i = 2;
  while (slug_exists($pdo, $slug, $id)) {
    $slug = $base . '-' . $i;
    $i++;
  }

  $pdo->prepare("UPDATE categories SET slug = :slug WHERE id = :id")
      ->execute([':slug' => $slug, ':id' => $id]);

  $slugById[$id] = $slug;
  $updated++;
}

echo "OK. Updated: {$updated}\n";
