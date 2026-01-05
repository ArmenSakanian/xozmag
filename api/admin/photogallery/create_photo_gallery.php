<?php
header("Content-Type: application/json; charset=utf-8");

require_once $_SERVER['DOCUMENT_ROOT'] . "/api/auth/require_admin.php";
require_once __DIR__ . "/../../db.php";

function fail($code, $http = 400, $extra = []) {
  http_response_code($http);
  echo json_encode(array_merge(["ok" => false, "error" => $code], $extra), JSON_UNESCAPED_UNICODE);
  exit;
}

function normalize_uploads($files) {
  $out = [];
  if (!isset($files["name"])) return $out;

  if (is_array($files["name"])) {
    $n = count($files["name"]);
    for ($i = 0; $i < $n; $i++) {
      $out[] = [
        "name" => $files["name"][$i] ?? "",
        "type" => $files["type"][$i] ?? "",
        "tmp_name" => $files["tmp_name"][$i] ?? "",
        "error" => $files["error"][$i] ?? 0,
        "size" => $files["size"][$i] ?? 0,
      ];
    }
  } else {
    $out[] = $files;
  }
  return $out;
}

$maxBytes = 8 * 1024 * 1024; // 8MB
$allowed = [
  "image/jpeg" => "jpg",
  "image/png"  => "png",
  "image/webp" => "webp",
];

// ✅ папка в корне сайта
$destDir = rtrim($_SERVER["DOCUMENT_ROOT"], "/") . "/slider_photo/";
if (!is_dir($destDir)) {
  if (!mkdir($destDir, 0775, true)) {
    fail("DIR_CREATE_FAILED", 500);
  }
}

$uploads = [];
if (isset($_FILES["files"])) $uploads = normalize_uploads($_FILES["files"]);
else if (isset($_FILES["file"])) $uploads = normalize_uploads($_FILES["file"]);

if (!$uploads || count($uploads) === 0) fail("NO_FILE");

$finfo = new finfo(FILEINFO_MIME_TYPE);

$duplicates = [];
$seenHashes = [];
$valid = []; // сюда попадут только файлы, которые реально загружаем

// ✅ 1) валидация + отбрасываем дубли
foreach ($uploads as $idx => $file) {
  if (!isset($file["tmp_name"]) || !is_uploaded_file($file["tmp_name"])) {
    fail("BAD_UPLOAD", 400, ["index" => $idx]);
  }
  if (!empty($file["error"])) {
    fail("UPLOAD_ERROR", 400, ["index" => $idx]);
  }
  if ((int)$file["size"] > $maxBytes) {
    fail("FILE_TOO_LARGE", 400, ["index" => $idx]);
  }

  $mime = $finfo->file($file["tmp_name"]);
  if (!isset($allowed[$mime])) {
    fail("BAD_TYPE", 400, ["index" => $idx, "mime" => $mime]);
  }

  $hash = hash_file("sha256", $file["tmp_name"]);

  // дубликат внутри одной пачки
  if (isset($seenHashes[$hash])) {
    $duplicates[] = ["hash" => $hash, "reason" => "BATCH_DUPLICATE"];
    continue;
  }
  $seenHashes[$hash] = true;

  // дубликат в БД
  $chk = $pdo->prepare("SELECT id, url FROM photo_gallery WHERE file_hash = :h LIMIT 1");
  $chk->execute([":h" => $hash]);
  $exists = $chk->fetch(PDO::FETCH_ASSOC);

  if ($exists) {
    $duplicates[] = [
      "hash" => $hash,
      "reason" => "DB_DUPLICATE",
      "existing_id" => (int)$exists["id"],
      "existing_url" => (string)$exists["url"],
    ];
    continue;
  }

  $valid[] = [
    "file" => $file,
    "hash" => $hash,
    "ext"  => $allowed[$mime],
  ];
}

// ✅ если всё было дублями — просто вернём duplicates
if (count($valid) === 0) {
  echo json_encode([
    "ok" => true,
    "items" => [],
    "duplicates" => $duplicates
  ], JSON_UNESCAPED_UNICODE);
  exit;
}

// ✅ 2) загрузка + insert
try {
  $pdo->beginTransaction();

  $maxStmt = $pdo->query("SELECT COALESCE(MAX(sort_order), 0) AS m FROM photo_gallery");
  $max = (int)($maxStmt->fetch(PDO::FETCH_ASSOC)["m"] ?? 0);

  $ins = $pdo->prepare("INSERT INTO photo_gallery (url, file_hash, sort_order) VALUES (:url, :hash, :sort)");

  $items = [];
  $movedPaths = [];

  foreach ($valid as $v) {
    $file = $v["file"];
    $hash = $v["hash"];
    $ext  = $v["ext"];

    $rand = bin2hex(random_bytes(6));
    $filename = "gallery_" . date("Ymd_His") . "_" . $rand . "." . $ext;

    $destPath = $destDir . $filename;
    $publicUrl = "/slider_photo/" . $filename;

    if (!move_uploaded_file($file["tmp_name"], $destPath)) {
      foreach ($movedPaths as $p) @unlink($p);
      $pdo->rollBack();
      fail("MOVE_FAILED", 500);
    }

    $movedPaths[] = $destPath;
    $max++;

    $ins->execute([
      ":url" => $publicUrl,
      ":hash" => $hash,
      ":sort" => $max
    ]);

    $id = (int)$pdo->lastInsertId();

    $items[] = [
      "id" => $id,
      "url" => $publicUrl,
      "sort_order" => $max,
      "created_at" => date("Y-m-d H:i:s")
    ];
  }

  $pdo->commit();

  echo json_encode([
    "ok" => true,
    "items" => $items,
    "duplicates" => $duplicates
  ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  http_response_code(500);
  echo json_encode(["ok" => false, "error" => "DB_ERROR"], JSON_UNESCAPED_UNICODE);
}
