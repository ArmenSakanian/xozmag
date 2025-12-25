<?php
header("Content-Type: application/json; charset=utf-8");
require_once __DIR__ . "/../../db.php";

/* === helpers === */
function safe_rel_from_url_or_path($p) {
  $p = trim((string)$p);
  if ($p === "") return "";

  if (preg_match('~^https?://~i', $p)) {
    $u = parse_url($p);
    $p = !empty($u["path"]) ? $u["path"] : "";
  }
  $p = trim((string)$p);
  if ($p === "") return "";

  // нормализуем: всегда начинаем с /
  if ($p[0] !== "/") $p = "/" . ltrim($p, "/");

  return $p;
}

function decode_photo_to_images($photo) {
  $photo = trim((string)$photo);
  if ($photo === "") return [];

  $decoded = json_decode($photo, true);
  if (!is_array($decoded)) return [];

  $out = [];
  foreach ($decoded as $p) {
    $p = safe_rel_from_url_or_path($p);
    if ($p !== "") $out[] = $p;
  }

  $out = array_values(array_unique($out));
  return $out;
}

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

/* === 3. Характеристики (с ui_render и meta) === */
$attrs = $pdo->query("
  SELECT
    pav.product_id,
    pa.id   AS attribute_id,
    pa.name AS attribute_name,
    pa.ui_render,
    pav.option_id,
    pav.value AS raw_value,
    o.value AS option_value,
    o.meta_json
  FROM product_attribute_values pav
  JOIN product_attributes pa ON pa.id = pav.attribute_id
  LEFT JOIN product_attribute_options o ON o.id = pav.option_id
")->fetchAll(PDO::FETCH_ASSOC);

$attrMap = [];
foreach ($attrs as $a) {
  $value = $a["option_value"];
  if ($value === null || $value === "") {
    $value = $a["raw_value"]; // для text/number
  }

  $meta = null;
  if (!empty($a["meta_json"])) {
    $tmp = json_decode($a["meta_json"], true);
    if (is_array($tmp)) $meta = $tmp;
  }

  $attrMap[$a["product_id"]][] = [
    "attribute_id" => (int)$a["attribute_id"],
    "option_id"    => $a["option_id"] ? (int)$a["option_id"] : null,
    "name"         => $a["attribute_name"],
    "ui_render"    => $a["ui_render"] ?? "text",
    "value"        => $value,
    "meta"         => $meta,
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

  // ✅ ВАЖНО: отдаём фотки массивом, чтобы фронт сразу мог использовать
  $p["images"] = decode_photo_to_images($p["photo"] ?? "");
}
unset($p);

echo json_encode($products, JSON_UNESCAPED_UNICODE);
