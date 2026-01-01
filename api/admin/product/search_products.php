<?php
header("Content-Type: application/json; charset=utf-8");
require_once __DIR__ . "/../../db.php";

/* ===== helpers ===== */
function normalize_q($s) {
  $s = mb_strtolower((string)$s, "UTF-8");
  $s = str_replace("ё", "е", $s);
  $s = preg_replace('~[^\p{L}\p{N}]+~u', ' ', $s);
  $s = preg_replace('~\s+~u', ' ', $s);
  return trim($s);
}

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

/* ===== input ===== */
$qRaw = isset($_GET["q"]) ? trim((string)$_GET["q"]) : "";
if ($qRaw === "") { echo json_encode([]); exit; }

$limit = isset($_GET["limit"]) ? (int)$_GET["limit"] : 12;
if ($limit < 1) $limit = 12;
if ($limit > 30) $limit = 30;

$catRaw = isset($_GET["cat"]) ? trim((string)$_GET["cat"]) : "";

$q = normalize_q($qRaw);

// короткие запросы (кроме цифр)
$isDigits = preg_match('~^\d{5,}$~', $q) === 1;
if (!$isDigits && mb_strlen($q, "UTF-8") < 2) {
  echo json_encode([]);
  exit;
}

try {
  /* ===== cat filter (optional) ===== */
  $catIds = [];
  if ($catRaw !== "") {
    $catCode = "";
    if (is_code($catRaw)) $catCode = $catRaw;
    else {
      $st = $pdo->prepare("SELECT code FROM categories WHERE slug = ? LIMIT 1");
      $st->execute([$catRaw]);
      $catCode = (string)($st->fetch(PDO::FETCH_ASSOC)["code"] ?? "");
    }

    if ($catCode !== "") {
      $st = $pdo->prepare("SELECT id FROM categories WHERE code = ? OR code LIKE CONCAT(?, '.%')");
      $st->execute([$catCode, $catCode]);
      $catIds = array_map(fn($x)=> (int)$x["id"], $st->fetchAll(PDO::FETCH_ASSOC));
    }
  }

  $whereCat = "";
  $paramsCat = [];
  if (!empty($catIds)) {
    $in = implode(",", array_fill(0, count($catIds), "?"));
    $whereCat = " AND p.category_id IN ($in) ";
    $paramsCat = $catIds;
  }

  if ($isDigits) {
    $sql = "
SELECT p.id, p.name, p.slug, p.price, p.brand, p.barcode, p.photo, p.quantity, p.measure_name
      FROM products p
      WHERE (
        p.barcode = ?
        OR p.barcode LIKE ?
        OR p.name   LIKE ?
        OR p.brand  LIKE ?
      )
      $whereCat
      ORDER BY
        (p.barcode = ?) DESC,
        (p.barcode LIKE ?) DESC,
        p.id DESC
      LIMIT $limit
    ";
    $st = $pdo->prepare($sql);
    $st->execute(array_merge(
      [$q, $q."%", "%$q%", "%$q%", $q, $q."%"],
      $paramsCat
    ));
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);
  } else {
    $like = "%$q%";
    $sql = "
SELECT p.id, p.name, p.slug, p.price, p.brand, p.barcode, p.photo, p.quantity, p.measure_name
      FROM products p
      WHERE (
        p.name LIKE ?
        OR p.brand LIKE ?
        OR p.barcode LIKE ?
      )
      $whereCat
      ORDER BY p.id DESC
      LIMIT $limit
    ";
    $st = $pdo->prepare($sql);
    $st->execute(array_merge([$like, $like, $like], $paramsCat));
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);
  }

foreach ($rows as &$r) {
  // measure + quantity (для UI)
  $measure = (string)($r["measure_name"] ?? "");
  $r["measureName"] = $measure;

  $r["quantity_value"] = (float)($r["quantity"] ?? 0);
  $r["quantity"] = format_qty_for_ui($r["quantity"] ?? 0, $measure);

  unset($r["measure_name"]);

  // images/thumb
  $imgs = decode_photo_to_images($r["photo"] ?? "");
  $first = $imgs[0] ?? "";

  $r["images"] = $first ? [$first] : [];
  $r["thumb"]  = $first ?: "/img/no-photo.png";

  unset($r["photo"]);
}
unset($r);


  echo json_encode($rows, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(["error" => true, "message" => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}