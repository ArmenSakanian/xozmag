<?php
header("Content-Type: application/json; charset=utf-8");
require_once __DIR__ . "/../../db.php";

function normalize_q($s) {
  $s = mb_strtolower((string)$s, "UTF-8");
  $s = str_replace("ё", "е", $s);
  $s = preg_replace('~[^\p{L}\p{N}]+~u', ' ', $s);
  $s = preg_replace('~\s+~u', ' ', $s);
  return trim($s);
}

function findProductImages($barcode) {
  if (!$barcode) return [];

  $folder  = $_SERVER["DOCUMENT_ROOT"] . "/photo_product_vitrina/";
  $urlBase = "/photo_product_vitrina/";

  if (!is_dir($folder)) return [];

  $mask  = $folder . $barcode . "*.webp";
  $files = glob($mask);

  if (!$files) return [];

  $images = [];
  foreach ($files as $path) {
    $images[] = $urlBase . basename($path);
  }

  sort($images);
  return $images;
}

$qRaw = isset($_GET["q"]) ? trim((string)$_GET["q"]) : "";
if ($qRaw === "") { echo json_encode([]); exit; }

$limit = isset($_GET["limit"]) ? (int)$_GET["limit"] : 12;
if ($limit < 1) $limit = 12;
if ($limit > 30) $limit = 30;

$q = normalize_q($qRaw);

// короткие запросы (кроме цифр)
$isDigits = preg_match('~^\d{5,}$~', $q) === 1;
if (!$isDigits && mb_strlen($q, "UTF-8") < 2) {
  echo json_encode([]);
  exit;
}

try {
  // цифры (штрихкод) — приоритет exact/prefix
  if ($isDigits) {
    $sql = "
      SELECT id, name, price, brand, barcode
      FROM products
      WHERE barcode = ?
         OR barcode LIKE ?
         OR name   LIKE ?
         OR brand  LIKE ?
      ORDER BY
        (barcode = ?) DESC,
        (barcode LIKE ?) DESC,
        id DESC
      LIMIT $limit
    ";
    $st = $pdo->prepare($sql);
    $st->execute([$q, $q."%", "%$q%", "%$q%", $q, $q."%"]);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);
  } else {
    // текст
    $like = "%$q%";
    $sql = "
      SELECT id, name, price, brand, barcode
      FROM products
      WHERE name LIKE ?
         OR brand LIKE ?
         OR barcode LIKE ?
      ORDER BY id DESC
      LIMIT $limit
    ";
    $st = $pdo->prepare($sql);
    $st->execute([$like, $like, $like]);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);
  }

  // ✅ добавляем миниатюры
  foreach ($rows as &$r) {
    $imgs = findProductImages($r["barcode"] ?? "");
    $r["images"] = $imgs;
    $r["thumb"]  = $imgs[0] ?? "";
  }
  unset($r);

  echo json_encode($rows, JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(["error" => true, "message" => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
