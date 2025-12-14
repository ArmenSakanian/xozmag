<?php
header("Content-Type: application/json; charset=utf-8");
require_once __DIR__ . "/../../db.php";

/* === 1. Категории === */
$cats = $pdo->query("
  SELECT id, name, parent_id, code
  FROM categories
")->fetchAll(PDO::FETCH_ASSOC);

$catMap = [];
foreach ($cats as $c) {
  $catMap[$c["id"]] = $c;
}

function buildCategoryPath($id, $map) {
  $names = [];
  while ($id && isset($map[$id])) {
    $names[] = $map[$id]["name"];
    $id = $map[$id]["parent_id"];
  }
  return implode(" / ", array_reverse($names));
}

/* === 2. Товары === */
$products = $pdo->query("
  SELECT
    p.id,
    p.name,
    p.article,
    p.brand,
    p.type,
    p.price,
    p.quantity,
    p.barcode,
    p.description,
    p.photo,
    p.category_id
  FROM products p
  ORDER BY p.id DESC
")->fetchAll(PDO::FETCH_ASSOC);

/* === 3. Характеристики === */
$attrs = $pdo->query("
  SELECT
    pav.product_id,
    pa.id AS attribute_id,
    pa.name,
    pav.option_id,
    o.value
  FROM product_attribute_values pav
  JOIN product_attributes pa ON pa.id = pav.attribute_id
  LEFT JOIN product_attribute_options o ON o.id = pav.option_id
")->fetchAll(PDO::FETCH_ASSOC);

$attrMap = [];
foreach ($attrs as $a) {
  $attrMap[$a["product_id"]][] = [
    "attribute_id" => (int)$a["attribute_id"],
    "option_id"    => $a["option_id"] ? (int)$a["option_id"] : null,
    "name"         => $a["name"],
    "value"        => $a["value"]
  ];
}

/* === 4. Финал === */
foreach ($products as &$p) {
  if ($p["category_id"] && isset($catMap[$p["category_id"]])) {
    $p["category_path"] = buildCategoryPath($p["category_id"], $catMap);
    $p["category_code"] = $catMap[$p["category_id"]]["code"];
  } else {
    $p["category_path"] = "—";
    $p["category_code"] = null;
  }

  $p["attributes"] = $attrMap[$p["id"]] ?? [];
}

echo json_encode($products, JSON_UNESCAPED_UNICODE);
