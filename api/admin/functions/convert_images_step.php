<?php
header("Content-Type: application/json; charset=utf-8");

function json_out($arr, $code = 200) {
  http_response_code($code);
  echo json_encode($arr, JSON_UNESCAPED_UNICODE);
  exit;
}

$folder = rtrim($_SERVER["DOCUMENT_ROOT"], "/") . "/photo_product_vitrina/";
if (!is_dir($folder)) {
  json_out(["error" => "Folder not found: /photo_product_vitrina/"], 500);
}

function safe_basename($name) {
  $name = basename($name);
  // на всякий случай уберём нулевые байты
  return str_replace("\0", "", $name);
}

function detect_image_mime($path) {
  $info = @getimagesize($path);
  if ($info && !empty($info["mime"])) return $info["mime"];

  // fallback: finfo
  if (function_exists("finfo_open")) {
    $f = @finfo_open(FILEINFO_MIME_TYPE);
    if ($f) {
      $m = @finfo_file($f, $path);
      @finfo_close($f);
      if ($m) return $m;
    }
  }
  return null;
}

function build_queue($folder) {
  $all = @scandir($folder);
  if (!is_array($all)) return [];

  $queue = [];

  foreach ($all as $f) {
    if ($f === "." || $f === "..") continue;

    $full = $folder . $f;
    if (!is_file($full)) continue;

    $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));

    // Берём только то, что реально может быть исходником:
    // - jpg/jpeg/png
    // - + любые файлы, у которых расширение jpg/jpeg/png/webp (на всякий), но "настоящий" MIME выясним на шаге
    // (Мы НЕ берём *.webp как исходник, если у него уже ext=webp — оно и так готово.)
    if (in_array($ext, ["jpg", "jpeg", "png"])) {
      $queue[] = $f;
      continue;
    }

    // Иногда бывает webp с неверным расширением (например .jpg), мы это поймаем выше.
    // А вот если залили webp с расширением .webp — не трогаем.
    if ($ext === "webp") {
      // готовые webp пропускаем (не добавляем в очередь)
      continue;
    }
  }

  sort($queue, SORT_NATURAL | SORT_FLAG_CASE);
  return array_values($queue);
}

function job_path($folder, $token) {
  // Храним job рядом с фотками (сканер по расширениям его не тронет)
  return $folder . ".convert_job_" . $token . ".json";
}

/**
 * ===== INIT JOB =====
 * GET ?init=1&dry_run=0
 */
$init = isset($_GET["init"]) ? intval($_GET["init"]) : 0;
$dryRun = isset($_GET["dry_run"]) ? (intval($_GET["dry_run"]) === 1) : false;

if ($init === 1) {
  $queue = build_queue($folder);
  $total = count($queue);

  $token = bin2hex(random_bytes(16));
  $job = [
    "token" => $token,
    "created_at" => date("c"),
    "folder" => $folder,
    "dry_run" => $dryRun,
    "total" => $total,
    "files" => $queue,
  ];

  $jp = job_path($folder, $token);
  if (@file_put_contents($jp, json_encode($job, JSON_UNESCAPED_UNICODE)) === false) {
    json_out(["error" => "Cannot write job file. Check permissions."], 500);
  }

  // если нечего делать — сразу done
  if ($total === 0) {
    @unlink($jp);
    json_out([
      "done" => true,
      "token" => $token,
      "total" => 0,
      "index" => 0,
      "results" => [],
    ]);
  }

  json_out([
    "done" => false,
    "token" => $token,
    "total" => $total,
    "index" => 0,
  ]);
}

/**
 * ===== STEP =====
 * GET ?token=...&index=0&batch=10
 */
$token = isset($_GET["token"]) ? preg_replace("/[^a-f0-9]/", "", $_GET["token"]) : "";
if (!$token) {
  json_out(["error" => "Missing token. Call ?init=1 first."], 400);
}

$jp = job_path($folder, $token);
if (!is_file($jp)) {
  json_out(["error" => "Job not found or expired. Call ?init=1 again."], 400);
}

$jobRaw = @file_get_contents($jp);
$job = $jobRaw ? json_decode($jobRaw, true) : null;
if (!is_array($job) || empty($job["files"]) || !isset($job["total"])) {
  @unlink($jp);
  json_out(["error" => "Job file corrupted. Call ?init=1 again."], 500);
}

$files = $job["files"];
$total = intval($job["total"]);
$dryRun = !empty($job["dry_run"]);

$index = isset($_GET["index"]) ? intval($_GET["index"]) : 0;
if ($index < 0) $index = 0;

$batch = isset($_GET["batch"]) ? intval($_GET["batch"]) : 1;
if ($batch < 1) $batch = 1;
if ($batch > 30) $batch = 30; // разумный лимит

if ($index >= $total) {
  @unlink($jp);
  json_out([
    "done" => true,
    "token" => $token,
    "total" => $total,
    "index" => $total,
    "results" => [],
  ]);
}

$results = [];

$end = min($index + $batch, $total);
for ($i = $index; $i < $end; $i++) {
  $file = safe_basename($files[$i]);
  $path = $folder . $file;

  if (!is_file($path)) {
    $results[] = [
      "status" => "missing",
      "file" => $file,
    ];
    continue;
  }

  $mime = detect_image_mime($path);
  if (!$mime) {
    $results[] = [
      "status" => "bad",
      "file" => $file,
    ];
    continue;
  }

  $ext  = strtolower(pathinfo($file, PATHINFO_EXTENSION));
  $name = pathinfo($file, PATHINFO_FILENAME);
  $target = $folder . $name . ".webp";

  // ===== WEBP (в т.ч. webp с неверным расширением) =====
  if ($mime === "image/webp") {
    if ($ext === "webp") {
      // в очередь такие не должны попадать, но на всякий:
      $results[] = [
        "status" => "skip",
        "file" => $file,
      ];
      continue;
    }

    $targetExists = is_file($target);

    if ($dryRun) {
      $results[] = [
        "status" => $targetExists ? "would_rename_overwrite" : "would_rename",
        "file" => $file,
      ];
      continue;
    }

    // безопаснее: сначала переименуем во временный, затем заменим target
    $tmp = $folder . $name . ".webp.__tmp__" . $token;

    // move/rename исходник -> tmp
    if (!@rename($path, $tmp)) {
      $results[] = [
        "status" => "error",
        "file" => $file,
        "error" => "rename_failed",
      ];
      continue;
    }

    if ($targetExists) @unlink($target);

    if (!@rename($tmp, $target)) {
      // попытка отката
      @rename($tmp, $path);
      $results[] = [
        "status" => "error",
        "file" => $file,
        "error" => "final_rename_failed",
      ];
      continue;
    }

    $results[] = [
      "status" => $targetExists ? "renamed_overwrite" : "renamed",
      "file" => $file,
    ];
    continue;
  }

  // ===== JPEG =====
  if ($mime === "image/jpeg") {
    $targetExists = is_file($target);

    if ($dryRun) {
      $results[] = [
        "status" => $targetExists ? "would_convert_overwrite" : "would_convert",
        "file" => $file,
      ];
      continue;
    }

    $img = @imagecreatefromjpeg($path);
    if (!$img) {
      $results[] = [
        "status" => "error",
        "file" => $file,
        "error" => "imagecreatefromjpeg_failed",
      ];
      continue;
    }

    $tmp = $folder . $name . ".webp.__tmp__" . $token;
    $ok = @imagewebp($img, $tmp, 75);
    @imagedestroy($img);

    if (!$ok || !is_file($tmp)) {
      @unlink($tmp);
      $results[] = [
        "status" => "error",
        "file" => $file,
        "error" => "imagewebp_failed",
      ];
      continue;
    }

    // только после успешного создания tmp — заменяем старый webp
    if ($targetExists) @unlink($target);

    if (!@rename($tmp, $target)) {
      @unlink($tmp);
      $results[] = [
        "status" => "error",
        "file" => $file,
        "error" => "final_rename_failed",
      ];
      continue;
    }

    @unlink($path);

    $results[] = [
      "status" => $targetExists ? "converted_overwrite" : "converted_new",
      "file" => $file,
    ];
    continue;
  }

  // ===== PNG =====
  if ($mime === "image/png") {
    $targetExists = is_file($target);

    if ($dryRun) {
      $results[] = [
        "status" => $targetExists ? "would_convert_overwrite" : "would_convert",
        "file" => $file,
      ];
      continue;
    }

    $img = @imagecreatefrompng($path);
    if (!$img) {
      $results[] = [
        "status" => "error",
        "file" => $file,
        "error" => "imagecreatefrompng_failed",
      ];
      continue;
    }

    if (function_exists("imagepalettetotruecolor")) {
      @imagepalettetotruecolor($img);
    }
    @imagealphablending($img, true);
    @imagesavealpha($img, true);

    $tmp = $folder . $name . ".webp.__tmp__" . $token;
    $ok = @imagewebp($img, $tmp, 75);
    @imagedestroy($img);

    if (!$ok || !is_file($tmp)) {
      @unlink($tmp);
      $results[] = [
        "status" => "error",
        "file" => $file,
        "error" => "imagewebp_failed",
      ];
      continue;
    }

    if ($targetExists) @unlink($target);

    if (!@rename($tmp, $target)) {
      @unlink($tmp);
      $results[] = [
        "status" => "error",
        "file" => $file,
        "error" => "final_rename_failed",
      ];
      continue;
    }

    @unlink($path);

    $results[] = [
      "status" => $targetExists ? "converted_overwrite" : "converted_new",
      "file" => $file,
    ];
    continue;
  }

  // ===== UNSUPPORTED =====
  $results[] = [
    "status" => "unsupported",
    "file" => $file,
    "mime" => $mime,
  ];
}

$newIndex = $end;
$done = ($newIndex >= $total);
if ($done) {
  @unlink($jp);
}

json_out([
  "done" => $done,
  "token" => $token,
  "total" => $total,
  "index" => $newIndex,
  "results" => $results,
  "dry_run" => $dryRun,
]);
