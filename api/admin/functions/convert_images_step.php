<?php
require_once $_SERVER['DOCUMENT_ROOT'] . "/api/auth/require_admin.php";
header("Content-Type: application/json; charset=utf-8");

// ✅ чтобы не останавливалось при закрытии вкладки
ignore_user_abort(true);
@set_time_limit(0);
@ini_set("max_execution_time", "0");

function json_out($arr, $code = 200) {
  http_response_code($code);
  echo json_encode($arr, JSON_UNESCAPED_UNICODE);
  exit;
}

$folder = rtrim($_SERVER["DOCUMENT_ROOT"], "/") . "/photo_product_vitrina/";
if (!is_dir($folder)) {
  json_out(["error" => "Folder not found: /photo_product_vitrina/"], 500);
}

// ===== SETTINGS =====
const LOCK_STALE_SEC = 900;      // 15 мин: если нет heartbeat — считаем lock "мертвым"
const JOB_TTL_SEC    = 86400;    // 24 часа: чистка старых job
const TMP_TTL_SEC    = 3600;     // 1 час: чистка старых __tmp__
const MAX_LOG_LINES  = 600;      // сколько строк логов храним

function safe_basename($name) {
  $name = basename($name);
  return str_replace("\0", "", $name);
}

function detect_image_mime($path) {
  $info = @getimagesize($path);
  if ($info && !empty($info["mime"])) return $info["mime"];

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
    if (in_array($ext, ["jpg", "jpeg", "png"])) {
      $queue[] = $f;
    }
    // .webp как исходник не берём
  }

  sort($queue, SORT_NATURAL | SORT_FLAG_CASE);
  return array_values($queue);
}

function lock_path($folder) {
  return $folder . ".convert_lock.json";
}
function job_path($folder, $token) {
  return $folder . ".convert_job_" . $token . ".json";
}

function read_json_file($path) {
  if (!is_file($path)) return null;
  $raw = @file_get_contents($path);
  if (!$raw) return null;
  $data = json_decode($raw, true);
  return is_array($data) ? $data : null;
}

function write_json_atomic($path, $data) {
  $tmp = $path . ".tmp_" . bin2hex(random_bytes(4));
  $json = json_encode($data, JSON_UNESCAPED_UNICODE);
  if (@file_put_contents($tmp, $json) === false) return false;
  return @rename($tmp, $path);
}

function now_iso() { return date("c"); }

function cleanup_stale($folder) {
  $lp = lock_path($folder);
  $lock = read_json_file($lp);
  if ($lock && !empty($lock["updated_at"])) {
    $ts = strtotime($lock["updated_at"]);
    if ($ts && (time() - $ts) > LOCK_STALE_SEC) {
      @unlink($lp);
    }
  }

  // чистим старые job-файлы
  $all = @scandir($folder);
  if (!is_array($all)) return;

  foreach ($all as $f) {
    if ($f === "." || $f === "..") continue;
    $full = $folder . $f;
    if (!is_file($full)) continue;

    // tmp
    if (strpos($f, ".webp.__tmp__") !== false) {
      $mt = @filemtime($full);
      if ($mt && (time() - $mt) > TMP_TTL_SEC) @unlink($full);
      continue;
    }

    // jobs
    if (strpos($f, ".convert_job_") === 0 && str_ends_with($f, ".json")) {
      $mt = @filemtime($full);
      if ($mt && (time() - $mt) > JOB_TTL_SEC) @unlink($full);
      continue;
    }
  }
}

function acquire_lock($folder, $token, $dryRun) {
  $lp = lock_path($folder);
  $lock = read_json_file($lp);

  if ($lock && !empty($lock["updated_at"])) {
    $ts = strtotime($lock["updated_at"]);
    if ($ts && (time() - $ts) <= LOCK_STALE_SEC) {
      return [false, $lock]; // уже запущено
    }
    // stale lock — удалим и перехватим
    @unlink($lp);
  }

  $newLock = [
    "token" => $token,
    "dry_run" => $dryRun ? 1 : 0,
    "started_at" => now_iso(),
    "updated_at" => now_iso(),
  ];

  if (!write_json_atomic($lp, $newLock)) {
    return [false, ["error" => "Cannot write lock file"]];
  }

  return [true, $newLock];
}

function heartbeat_lock($folder, $token) {
  $lp = lock_path($folder);
  $lock = read_json_file($lp);
  if (!$lock) return;

  if (!empty($lock["token"]) && $lock["token"] !== $token) return;

  $lock["updated_at"] = now_iso();
  write_json_atomic($lp, $lock);
}

function release_lock($folder, $token) {
  $lp = lock_path($folder);
  $lock = read_json_file($lp);
  if (!$lock) { @unlink($lp); return; }
  if (!empty($lock["token"]) && $lock["token"] !== $token) return;
  @unlink($lp);
}

function push_log(&$job, $line) {
  if (!isset($job["logs"]) || !is_array($job["logs"])) $job["logs"] = [];
  array_unshift($job["logs"], $line);
  if (count($job["logs"]) > MAX_LOG_LINES) {
    $job["logs"] = array_slice($job["logs"], 0, MAX_LOG_LINES);
  }
}

function send_and_detach(array $payload) {
  $json = json_encode($payload, JSON_UNESCAPED_UNICODE);

  // если есть буферы — закрыть
  while (ob_get_level() > 0) { @ob_end_clean(); }

  header("Content-Type: application/json; charset=utf-8");
  header("Connection: close");
  header("Content-Length: " . strlen($json));

  echo $json;
  @flush();

  // ✅ для php-fpm это идеально: клиент получит ответ, а скрипт продолжит
  if (function_exists("fastcgi_finish_request")) {
    @fastcgi_finish_request();
  }
}

// ===== STATUS endpoint =====
// GET ?status=1[&token=...]
if (isset($_GET["status"]) && intval($_GET["status"]) === 1) {
  cleanup_stale($folder);

  $token = isset($_GET["token"]) ? preg_replace("/[^a-f0-9]/", "", $_GET["token"]) : "";
  if (!$token) {
    // если токен не дали — попробуем взять из lock
    $lock = read_json_file(lock_path($folder));
    if (!empty($lock["token"])) $token = $lock["token"];
  }
  if (!$token) json_out(["error" => "No running job"], 404);

  $jp = job_path($folder, $token);
  $job = read_json_file($jp);
  if (!$job) json_out(["error" => "Job not found"], 404);

  json_out([
    "token" => $token,
    "status" => $job["status"] ?? "unknown",
    "done" => !empty($job["done"]),
    "dry_run" => !empty($job["dry_run"]),
    "total" => intval($job["total"] ?? 0),
    "index" => intval($job["index"] ?? 0),
    "started_at" => $job["started_at"] ?? "",
    "finished_at" => $job["finished_at"] ?? "",
    "logs" => is_array($job["logs"] ?? null) ? $job["logs"] : [],
    "counters" => is_array($job["counters"] ?? null) ? $job["counters"] : [],
  ]);
}

// ===== INIT (start background job) =====
// GET ?init=1&dry_run=0
$init = isset($_GET["init"]) ? intval($_GET["init"]) : 0;
$dryRun = isset($_GET["dry_run"]) ? (intval($_GET["dry_run"]) === 1) : false;

if ($init !== 1) {
  json_out(["error" => "Bad request. Use ?init=1 or ?status=1"], 400);
}

cleanup_stale($folder);

$token = bin2hex(random_bytes(16));

// если уже запущено — не стартуем второй раз
[$okLock, $lockInfo] = acquire_lock($folder, $token, $dryRun);
if (!$okLock) {
  if (!empty($lockInfo["token"])) {
    $t = $lockInfo["token"];
    $jp = job_path($folder, $t);
    $job = read_json_file($jp);

    json_out([
      "already_running" => true,
      "token" => $t,
      "total" => intval($job["total"] ?? 0),
      "index" => intval($job["index"] ?? 0),
      "dry_run" => !empty($job["dry_run"]),
    ]);
  }
  json_out(["error" => "Lock error"], 500);
}

// snapshot очереди (✅ новые файлы во время конвертации НЕ попадут)
$queue = build_queue($folder);
$total = count($queue);

if ($total === 0) {
  // нечего делать — снимем lock сразу
  release_lock($folder, $token);
  json_out([
    "done" => true,
    "token" => "",
    "total" => 0,
    "index" => 0,
    "already_running" => false,
  ]);
}

$job = [
  "token" => $token,
  "folder" => $folder,
  "dry_run" => $dryRun ? 1 : 0,
  "status" => "run",
  "done" => false,
  "created_at" => now_iso(),
  "started_at" => now_iso(),
  "finished_at" => "",
  "total" => $total,
  "index" => 0,
  "files" => $queue,
  "logs" => [],
  "counters" => [
    "converted" => 0,
    "renamed" => 0,
    "missing" => 0,
    "bad" => 0,
    "unsupported" => 0,
    "errors" => 0,
    "skip" => 0,
  ],
];

push_log($job, "→ init: total={$total}, dry_run=" . ($dryRun ? "1" : "0"));
$jp = job_path($folder, $token);
if (!write_json_atomic($jp, $job)) {
  release_lock($folder, $token);
  json_out(["error" => "Cannot write job file. Check permissions."], 500);
}

// ✅ отдать ответ браузеру и продолжить в фоне
send_and_detach([
  "done" => false,
  "already_running" => false,
  "token" => $token,
  "total" => $total,
  "index" => 0,
  "dry_run" => $dryRun ? 1 : 0,
]);

// ===== BACKGROUND WORK (runs even if tab closed) =====
function process_job($folder, $token) {
  $jp = job_path($folder, $token);
  $job = read_json_file($jp);
  if (!$job) { release_lock($folder, $token); return; }

  $files = is_array($job["files"] ?? null) ? $job["files"] : [];
  $total = intval($job["total"] ?? 0);
  $dryRun = !empty($job["dry_run"]);

  for ($i = intval($job["index"] ?? 0); $i < $total; $i++) {
    heartbeat_lock($folder, $token);

    $file = safe_basename($files[$i] ?? "");
    $path = $folder . $file;

    $status = "unknown";
    $err = "";

    if (!$file || !is_file($path)) {
      $status = "missing";
      $job["counters"]["missing"]++;
      push_log($job, "MISSING | {$file}");
      $job["index"] = $i + 1;
      write_json_atomic($jp, $job);
      continue;
    }

    $mime = detect_image_mime($path);
    if (!$mime) {
      $status = "bad";
      $job["counters"]["bad"]++;
      push_log($job, "BAD     | {$file}");
      $job["index"] = $i + 1;
      write_json_atomic($jp, $job);
      continue;
    }

    $ext  = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    $name = pathinfo($file, PATHINFO_FILENAME);
    $target = $folder . $name . ".webp";
    $targetExists = is_file($target);

    // WEBP с неверным расширением -> переименовать
    if ($mime === "image/webp") {
      if ($ext === "webp") {
        $status = "skip";
        $job["counters"]["skip"]++;
        push_log($job, "SKIP    | {$file}");
      } else {
        if ($dryRun) {
          $status = $targetExists ? "would_rename_overwrite" : "would_rename";
          push_log($job, strtoupper($status) . " | {$file}");
        } else {
          $tmp = $folder . $name . ".webp.__tmp__" . $token;
          if (!@rename($path, $tmp)) {
            $status = "error";
            $err = "rename_failed";
          } else {
            if ($targetExists) @unlink($target);
            if (!@rename($tmp, $target)) {
              @rename($tmp, $path);
              $status = "error";
              $err = "final_rename_failed";
            } else {
              $status = $targetExists ? "renamed_overwrite" : "renamed";
              $job["counters"]["renamed"]++;
            }
          }

          if ($status === "error") {
            $job["counters"]["errors"]++;
            push_log($job, "ERROR   | {$file} | {$err}");
          } else {
            push_log($job, strtoupper($status) . " | {$file}");
          }
        }

        $job["index"] = $i + 1;
        write_json_atomic($jp, $job);
        continue;
      }

      $job["index"] = $i + 1;
      write_json_atomic($jp, $job);
      continue;
    }

    // JPEG
    if ($mime === "image/jpeg") {
      if ($dryRun) {
        $status = $targetExists ? "would_convert_overwrite" : "would_convert";
        push_log($job, strtoupper($status) . " | {$file}");
      } else {
        $img = @imagecreatefromjpeg($path);
        if (!$img) {
          $status = "error"; $err = "imagecreatefromjpeg_failed";
        } else {
          $tmp = $folder . $name . ".webp.__tmp__" . $token;
          $ok = @imagewebp($img, $tmp, 75);
          @imagedestroy($img);

          if (!$ok || !is_file($tmp)) {
            @unlink($tmp);
            $status = "error"; $err = "imagewebp_failed";
          } else {
            if ($targetExists) @unlink($target);
            if (!@rename($tmp, $target)) {
              @unlink($tmp);
              $status = "error"; $err = "final_rename_failed";
            } else {
              @unlink($path);
              $status = $targetExists ? "converted_overwrite" : "converted_new";
              $job["counters"]["converted"]++;
            }
          }
        }

        if ($status === "error") {
          $job["counters"]["errors"]++;
          push_log($job, "ERROR   | {$file} | {$err}");
        } else {
          push_log($job, strtoupper($status) . " | {$file}");
        }
      }

      $job["index"] = $i + 1;
      write_json_atomic($jp, $job);
      continue;
    }

    // PNG
    if ($mime === "image/png") {
      if ($dryRun) {
        $status = $targetExists ? "would_convert_overwrite" : "would_convert";
        push_log($job, strtoupper($status) . " | {$file}");
      } else {
        $img = @imagecreatefrompng($path);
        if (!$img) {
          $status = "error"; $err = "imagecreatefrompng_failed";
        } else {
          if (function_exists("imagepalettetotruecolor")) @imagepalettetotruecolor($img);
          @imagealphablending($img, true);
          @imagesavealpha($img, true);

          $tmp = $folder . $name . ".webp.__tmp__" . $token;
          $ok = @imagewebp($img, $tmp, 75);
          @imagedestroy($img);

          if (!$ok || !is_file($tmp)) {
            @unlink($tmp);
            $status = "error"; $err = "imagewebp_failed";
          } else {
            if ($targetExists) @unlink($target);
            if (!@rename($tmp, $target)) {
              @unlink($tmp);
              $status = "error"; $err = "final_rename_failed";
            } else {
              @unlink($path);
              $status = $targetExists ? "converted_overwrite" : "converted_new";
              $job["counters"]["converted"]++;
            }
          }
        }

        if ($status === "error") {
          $job["counters"]["errors"]++;
          push_log($job, "ERROR   | {$file} | {$err}");
        } else {
          push_log($job, strtoupper($status) . " | {$file}");
        }
      }

      $job["index"] = $i + 1;
      write_json_atomic($jp, $job);
      continue;
    }

    // UNSUPPORTED
    $job["counters"]["unsupported"]++;
    push_log($job, "UNSUP   | {$file} | {$mime}");
    $job["index"] = $i + 1;
    write_json_atomic($jp, $job);
  }

  $job["done"] = true;
  $job["status"] = "done";
  $job["finished_at"] = now_iso();
  push_log($job, $dryRun ? "✔ DRY-RUN COMPLETE" : "✔ ALL FILES PROCESSED");
  write_json_atomic($jp, $job);

  release_lock($folder, $token);
}

process_job($folder, $token);
exit;
