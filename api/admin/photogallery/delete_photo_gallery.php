<?php
header("Content-Type: application/json; charset=utf-8");

require_once $_SERVER['DOCUMENT_ROOT'] . "/api/auth/require_admin.php";
require_once __DIR__ . "/../../db.php";

function fail($code, $http = 400) {
  http_response_code($http);
  echo json_encode(["ok" => false, "error" => $code], JSON_UNESCAPED_UNICODE);
  exit;
}

// ids[] из form-data
$ids = [];
if (isset($_POST["ids"]) && is_array($_POST["ids"])) {
  $ids = array_map("intval", $_POST["ids"]);
}
// ids[] как ids[]
if (isset($_POST["ids[]"]) && is_array($_POST["ids[]"])) {
  $ids = array_map("intval", $_POST["ids[]"]);
}
// одиночный id
if (!$ids && isset($_POST["id"])) {
  $ids = [(int)$_POST["id"]];
}

// json body
if (!$ids) {
  $raw = file_get_contents("php://input");
  if ($raw) {
    $j = json_decode($raw, true);
    if (is_array($j)) {
      if (isset($j["ids"]) && is_array($j["ids"])) $ids = array_map("intval", $j["ids"]);
      else if (isset($j["id"])) $ids = [(int)$j["id"]];
    }
  }
}

$ids = array_values(array_filter(array_unique($ids), fn($x) => $x > 0));
if (!$ids) fail("NO_ID");

try {
  // достаем url
  $in = implode(",", array_fill(0, count($ids), "?"));
  $sel = $pdo->prepare("SELECT id, url FROM photo_gallery WHERE id IN ($in)");
  $sel->execute($ids);
  $rows = $sel->fetchAll(PDO::FETCH_ASSOC);

  if (!$rows) fail("NOT_FOUND", 404);

  // удаляем из бд
  $del = $pdo->prepare("DELETE FROM photo_gallery WHERE id IN ($in)");
  $del->execute($ids);

  // удаляем файлы
  $docRoot = rtrim($_SERVER["DOCUMENT_ROOT"], "/");
  $allowedDir = realpath($docRoot . "/slider_photo");

  $deletedFiles = 0;

  foreach ($rows as $row) {
    $url = (string)$row["url"];
    $filePath = $docRoot . $url;

    $realFile = realpath($filePath);
    if ($allowedDir && $realFile && str_starts_with($realFile, $allowedDir)) {
      if (@unlink($realFile)) $deletedFiles++;
    }
  }

  echo json_encode([
    "ok" => true,
    "deleted" => count($rows),
    "deleted_files" => $deletedFiles
  ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(["ok" => false, "error" => "DB_ERROR"], JSON_UNESCAPED_UNICODE);
}
