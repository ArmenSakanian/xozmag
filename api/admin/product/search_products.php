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

  // если это URL — берем только path
  if (preg_match('~^https?://~i', $p)) {
    $u = parse_url($p);
    $p = $u["path"] ?? "";
  } else {
    // если не URL — тоже вырежем ?query/#hash если вдруг есть
    $u = parse_url($p);
    $p = $u["path"] ?? $p;
  }

  $p = trim((string)$p);
  if ($p === "") return "";

  if ($p[0] !== "/") $p = "/" . ltrim($p, "/");
  return $p;
}

function decode_photo_to_images($photo) {
  $photo = trim((string)$photo);
  if ($photo === "" || $photo === "[]") return [];

  // 1) пробуем как JSON-массив
  $decoded = json_decode($photo, true);
  if (is_array($decoded)) {
    $out = [];
    foreach ($decoded as $p) {
      $p = safe_rel_from_url_or_path($p);
      if ($p !== "") $out[] = $p;
    }
    $out = array_values(array_unique($out));
    return $out;
  }

  // 2) если не JSON — считаем что там один путь строкой
  $one = safe_rel_from_url_or_path($photo);
  return $one ? [$one] : [];
}

/* ===== input ===== */
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
  if ($isDigits) {
    $sql = "
      SELECT id, name, price, brand, barcode, photo
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
    $like = "%$q%";
    $sql = "
      SELECT id, name, price, brand, barcode, photo
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

  foreach ($rows as &$r) {
    $imgs = decode_photo_to_images($r["photo"] ?? "");
    $first = $imgs[0] ?? "";

    // ✅ берём ТОЛЬКО первую фотку
    $r["images"] = $first ? [$first] : [];
    $r["thumb"]  = $first ?: "/img/no-photo.png";

    // чтобы не путаться — не отдаём сырой photo
    unset($r["photo"]);
  }
  unset($r);

  echo json_encode($rows, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(["error" => true, "message" => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
