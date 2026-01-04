<?php
header("Content-Type: application/json; charset=utf-8");
require_once __DIR__ . "/../../db.php";

/* ================= helpers ================= */
function safe_rel_from_url_or_path($p) {
  $p = trim((string)$p);
  if ($p === "") return "";
  $u = parse_url($p);
  $p = $u["path"] ?? $p;
  $p = trim((string)$p);
  if ($p === "") return "";
  if ($p[0] !== "/") $p = "/" . ltrim($p, "/");
  return $p;
}

function decode_photo_to_images($photo) {
  $photo = trim((string)$photo);
  if ($photo === "" || $photo === "[]") return [];
  $decoded = json_decode($photo, true);
  if (is_array($decoded)) {
    $out = [];
    foreach ($decoded as $p) {
      $p = safe_rel_from_url_or_path($p);
      if ($p !== "") $out[] = $p;
    }
    return array_values(array_unique($out));
  }
  $one = safe_rel_from_url_or_path($photo);
  return $one ? [$one] : [];
}

function is_code($s) {
  return preg_match('~^[0-9]+(\.[0-9]+)*$~', (string)$s) === 1;
}

/**
 * Красивое отображение quantity:
 * - "шт" -> целое (1 вместо 1.000)
 * - иначе -> до 3 знаков, без хвостовых нулей (3.8 вместо 3.800)
 */
function format_qty_for_ui($qty, $measureName): string {
  $m = mb_strtolower(trim((string)$measureName), "UTF-8");
  $num = (float)str_replace(",", ".", (string)$qty);

  if ($m === "шт" || $m === "pcs" || $m === "pc") {
    return (string)intval(round($num, 0));
  }

  $s = number_format($num, 3, ".", "");
  $s = rtrim(rtrim($s, "0"), ".");
  if ($s === "" || $s === "-0") $s = "0";
  return $s;
}

/* ================= input ================= */
$catRaw = isset($_GET["cat"]) ? trim((string)$_GET["cat"]) : "";
$limit  = isset($_GET["limit"]) ? (int)$_GET["limit"] : 60;
$offset = isset($_GET["offset"]) ? (int)$_GET["offset"] : 0;

$id   = isset($_GET["id"]) ? (int)$_GET["id"] : 0;               // ✅ добавить
$slug = isset($_GET["slug"]) ? trim((string)$_GET["slug"]) : ""; // ✅ добавить
if ($limit < 1) $limit = 60;
if ($limit > 200) $limit = 200;
if ($offset < 0) $offset = 0;

// можно выключить attrs если надо: ?attrs=0
$includeAttrs = !isset($_GET["attrs"]) || (string)$_GET["attrs"] !== "0";
// можно включить description: ?desc=1
$includeDesc  = isset($_GET["desc"]) && (string)$_GET["desc"] === "1";

// можно отдавать только 1 фото (для карточек): ?img=first
$imgMode = isset($_GET["img"]) ? (string)$_GET["img"] : "all"; // all|first

try {
  /* ================= categories map (маленькая таблица - ок) ================= */
  $cats = $pdo->query("SELECT id, name, parent_id, code FROM categories")->fetchAll(PDO::FETCH_ASSOC);
  $catMap = [];
  foreach ($cats as $c) $catMap[(int)$c["id"]] = $c;

  $bySlug = $pdo->query("SELECT id, slug, code FROM categories")->fetchAll(PDO::FETCH_ASSOC);
  $slugToCode = [];
  foreach ($bySlug as $c) {
    if (!empty($c["slug"])) $slugToCode[(string)$c["slug"]] = (string)$c["code"];
  }

  $buildCategoryPath = function($id) use (&$catMap) {
    $names = [];
    $id = (int)$id;
    while ($id && isset($catMap[$id])) {
      $names[] = $catMap[$id]["name"];
      $id = (int)($catMap[$id]["parent_id"] ?? 0);
    }
    return implode(" / ", array_reverse($names));
  };

  /* ================= resolve cat -> code -> category_ids ================= */
  $catCode = "";
  if ($catRaw !== "") {
    if (is_code($catRaw)) $catCode = $catRaw;
    else $catCode = $slugToCode[$catRaw] ?? "";
  }

  $catIds = [];
  if ($catCode !== "") {
    // категорий мало - можно так
    $st = $pdo->prepare("SELECT id FROM categories WHERE code = ? OR code LIKE CONCAT(?, '.%')");
    $st->execute([$catCode, $catCode]);
    $catIds = array_map(fn($x)=> (int)$x["id"], $st->fetchAll(PDO::FETCH_ASSOC));
  }

  /* ================= single product by id/slug ================= */
  if ($id > 0 || $slug !== "") {
    $fields = "
      p.id, p.name, p.slug, p.article, p.brand, p.type,
      p.price, p.quantity, p.measure_name, p.barcode, p.photo, p.category_id
    ";
    if ($includeDesc) $fields .= ", p.description";

    if ($id > 0) {
      $stP = $pdo->prepare("SELECT $fields FROM products p WHERE p.id=? LIMIT 1");
      $stP->execute([$id]);
    } else {
      $stP = $pdo->prepare("SELECT $fields FROM products p WHERE p.slug=? LIMIT 1");
      $stP->execute([$slug]);
    }

    $p = $stP->fetch(PDO::FETCH_ASSOC);
    if (!$p) {
      http_response_code(404);
      echo json_encode(["error"=>true,"message"=>"not found"], JSON_UNESCAPED_UNICODE);
      exit;
    }

    // attrs для одного товара (если includeAttrs)
    $attrMap = [];
    if ($includeAttrs) {
      $sqlA = "
        SELECT
          pav.product_id,
          pa.name AS attribute_name,
          pa.ui_render,
          pav.value AS raw_value,
          o.value AS option_value,
          o.meta_json
        FROM product_attribute_values pav
        JOIN product_attributes pa ON pa.id = pav.attribute_id
        LEFT JOIN product_attribute_options o ON o.id = pav.option_id
        WHERE pav.product_id = ?
      ";
      $stA = $pdo->prepare($sqlA);
      $stA->execute([(int)$p["id"]]);
      $attrs = $stA->fetchAll(PDO::FETCH_ASSOC);

      foreach ($attrs as $a) {
        $value = $a["option_value"];
        if ($value === null || $value === "") $value = $a["raw_value"];

        $meta = null;
        if (!empty($a["meta_json"])) {
          $tmp = json_decode($a["meta_json"], true);
          if (is_array($tmp)) $meta = $tmp;
        }

        $attrMap[(int)$a["product_id"]][] = [
          "name"      => $a["attribute_name"],
          "ui_render" => $a["ui_render"] ?? "text",
          "value"     => $value,
          "meta"      => $meta,
        ];
      }
    }

    // финализация одного товара
    $cid = (int)($p["category_id"] ?? 0);
    if ($cid && isset($catMap[$cid])) {
      $p["category_path"] = $buildCategoryPath($cid);
      $p["category_code"] = $catMap[$cid]["code"];
    } else {
      $p["category_path"] = "-";
      $p["category_code"] = null;
    }

    // measure + quantity formatting
    $measure = (string)($p["measure_name"] ?? "");
    $p["measureName"] = $measure;
    $p["quantity_value"] = (float)($p["quantity"] ?? 0);
    $p["quantity"] = format_qty_for_ui($p["quantity"] ?? 0, $measure);
    unset($p["measure_name"]);

    $p["attributes"] = $includeAttrs ? ($attrMap[(int)$p["id"]] ?? []) : [];

    $imgs = decode_photo_to_images($p["photo"] ?? "");
    if ($imgMode === "first") {
      $first = $imgs[0] ?? "";
      $p["images"] = $first ? [$first] : [];
      $p["thumb"]  = $first ?: "/img/no-photo.png";
    } else {
      $p["images"] = $imgs;
      $p["thumb"]  = ($imgs[0] ?? "") ?: "/img/no-photo.png";
    }
    unset($p["photo"]);

    echo json_encode(["item" => $p], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
  }

  /* ================= products query ================= */
  $fields = "
    p.id, p.name, p.slug, p.article, p.brand, p.type,
    p.price, p.quantity, p.measure_name, p.barcode, p.photo, p.category_id
  ";
  if ($includeDesc) $fields .= ", p.description";

  $where = "";
  $params = [];
  if (!empty($catIds)) {
    $in = implode(",", array_fill(0, count($catIds), "?"));
    $where = "WHERE p.category_id IN ($in)";
    $params = $catIds;
  }

  // total (для пагинации)
  $total = null;
  if ($where !== "") {
    $stCnt = $pdo->prepare("SELECT COUNT(*) AS c FROM products p $where");
    $stCnt->execute($params);
    $total = (int)($stCnt->fetch(PDO::FETCH_ASSOC)["c"] ?? 0);
  }

  $sql = "
    SELECT $fields
    FROM products p
    $where
    ORDER BY p.id DESC
    LIMIT $limit OFFSET $offset
  ";
  $stP = $pdo->prepare($sql);
  $stP->execute($params);
  $products = $stP->fetchAll(PDO::FETCH_ASSOC);

  /* ================= attrs only for выбранные товары ================= */
  $attrMap = [];
  if ($includeAttrs && !empty($products)) {
    $ids = array_map(fn($p)=> (int)$p["id"], $products);
    $in = implode(",", array_fill(0, count($ids), "?"));

    $sqlA = "
      SELECT
        pav.product_id,
        pa.name AS attribute_name,
        pa.ui_render,
        pav.value AS raw_value,
        o.value AS option_value,
        o.meta_json
      FROM product_attribute_values pav
      JOIN product_attributes pa ON pa.id = pav.attribute_id
      LEFT JOIN product_attribute_options o ON o.id = pav.option_id
      WHERE pav.product_id IN ($in)
    ";
    $stA = $pdo->prepare($sqlA);
    $stA->execute($ids);
    $attrs = $stA->fetchAll(PDO::FETCH_ASSOC);

    foreach ($attrs as $a) {
      $value = $a["option_value"];
      if ($value === null || $value === "") $value = $a["raw_value"];

      $meta = null;
      if (!empty($a["meta_json"])) {
        $tmp = json_decode($a["meta_json"], true);
        if (is_array($tmp)) $meta = $tmp;
      }

      $pid = (int)$a["product_id"];
      $attrMap[$pid][] = [
        "name"      => $a["attribute_name"],
        "ui_render" => $a["ui_render"] ?? "text",
        "value"     => $value,
        "meta"      => $meta,
      ];
    }
  }

  /* ================= финал ================= */
  foreach ($products as &$p) {
    $cid = (int)($p["category_id"] ?? 0);
    if ($cid && isset($catMap[$cid])) {
      $p["category_path"] = $buildCategoryPath($cid);
      $p["category_code"] = $catMap[$cid]["code"];
    } else {
      $p["category_path"] = "-";
      $p["category_code"] = null;
    }

    // measure + quantity formatting
    $measure = (string)($p["measure_name"] ?? "");
    $p["measureName"] = $measure;
    $p["quantity_value"] = (float)($p["quantity"] ?? 0);
    $p["quantity"] = format_qty_for_ui($p["quantity"] ?? 0, $measure);
    unset($p["measure_name"]);

    // attrs
    if ($includeAttrs) $p["attributes"] = $attrMap[(int)$p["id"]] ?? [];
    else $p["attributes"] = [];

    // images
    $imgs = decode_photo_to_images($p["photo"] ?? "");
    if ($imgMode === "first") {
      $first = $imgs[0] ?? "";
      $p["images"] = $first ? [$first] : [];
      $p["thumb"]  = $first ?: "/img/no-photo.png";
    } else {
      $p["images"] = $imgs;
      $p["thumb"]  = ($imgs[0] ?? "") ?: "/img/no-photo.png";
    }

    unset($p["photo"]); // сырой json не отдаём
  }
  unset($p);

  // отдаём либо массив (как раньше), либо объект с meta
  // чтобы совсем не ломать фронт: если cat не задан - вернем как раньше (массив)
  if ($catRaw === "") {
    echo json_encode($products, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
  }

  echo json_encode([
    "items" => $products,
    "limit" => $limit,
    "offset" => $offset,
    "total" => $total,
    "has_more" => ($total !== null) ? ($offset + count($products) < $total) : (count($products) === $limit),
  ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(["error" => true, "message" => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
