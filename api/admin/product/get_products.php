<?php
header("Content-Type: application/json; charset=utf-8");
require_once __DIR__ . "/../../db.php";

/*
  1. Загружаем категории
*/
$cats = $pdo->query("
  SELECT id, name, parent_id, level_code
  FROM categories
")->fetchAll(PDO::FETCH_ASSOC);


$catMap = [];
foreach ($cats as $c) {
  $catMap[$c['id']] = $c;
}

function buildCategoryPath($id, $map) {
  $names = [];
  while ($id && isset($map[$id])) {
    $names[] = $map[$id]['name'];
    $id = $map[$id]['parent_id'];
  }
  return implode(" / ", array_reverse($names));
}

/*
  2. Товары
*/
$sql = "
SELECT 
  p.id,
  p.name,
  p.article,
  p.brand,
  p.type,
  p.price,
  p.barcode,
  p.description,
  p.category_id,
  c.name AS category_name
FROM products p
LEFT JOIN categories c ON c.id = p.category_id
ORDER BY p.id DESC
";

$products = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

/*
  3. Характеристики
*/
$attrs = $pdo->query("
SELECT 
  pav.product_id,
  pa.id AS attribute_id,
  pa.name,
  pav.value
FROM product_attribute_values pav
JOIN product_attributes pa ON pa.id = pav.attribute_id
")->fetchAll(PDO::FETCH_ASSOC);

$attrMap = [];
foreach ($attrs as $a) {
  $attrMap[$a['product_id']][] = [
    "attribute_id" => $a["attribute_id"],
    "name" => $a["name"],
    "value" => $a["value"]
  ];
}

/*
  4. Финал
*/
foreach ($products as &$p) {
  if ($p["category_id"] && isset($catMap[$p["category_id"]])) {
    $p["category_path"] = buildCategoryPath($p["category_id"], $catMap);
    $p["category_code"] = $catMap[$p["category_id"]]["level_code"];
  } else {
    $p["category_path"] = "";
    $p["category_code"] = null;
  }

  $p["attributes"] = $attrMap[$p["id"]] ?? [];
}


echo json_encode($products, JSON_UNESCAPED_UNICODE);
