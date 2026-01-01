<?php

date_default_timezone_set("Europe/Moscow");

// Чтобы header() внутри подключаемых файлов не ругался из-за раннего echo
ob_start();

$DOCROOT = realpath(__DIR__ . "/../.."); // /var/www/.../xozmag.ru
if (!$DOCROOT) {
  echo "!!!! ERROR: DOCROOT not found\n";
  exit(1);
}
$_SERVER["DOCUMENT_ROOT"] = $DOCROOT;

// ====== GLOBAL LOCK ======
$lockFile = sys_get_temp_dir() . "/xozmag_cron_10min.lock";
$lockFp = fopen($lockFile, "c+");
if (!$lockFp || !flock($lockFp, LOCK_EX | LOCK_NB)) {
  echo "[" . date("Y-m-d H:i:s") . "] Already running (global lock)\n";
  exit;
}

function run_php_include(string $file, array $get = []): string {
  if (!is_file($file)) {
    throw new Exception("File not found: " . $file);
  }

  $oldGet = $_GET;
  $_GET = $get;

  ob_start();
  try {
    include $file;
  } finally {
    $out = ob_get_clean();
    $_GET = $oldGet;
  }
  return (string)$out;
}

// вытащить JSON даже если перед ним были warnings/лишний текст
function extract_json(string $s): ?array {
  $s = trim($s);
  if ($s === "") return null;

  // пробуем взять JSON с последней "{"
  $pos = strrpos($s, "{");
  if ($pos !== false) {
    $j = json_decode(substr($s, $pos), true);
    if (is_array($j)) return $j;
  }

  $j = json_decode($s, true);
  return is_array($j) ? $j : null;
}

try {
  echo "==== " . date("Y-m-d H:i:s") . " START ====\n";

  // 1) CONVERT IMAGES (init + poll status until done)
  echo "-> convert_images (init)\n";
  $initOut = run_php_include($GLOBALS["DOCROOT"] . "/api/admin/functions/convert_images_step.php", [
    "init" => "1",
    "dry_run" => "0",
  ]);

  $init = extract_json($initOut);
  if (!$init) throw new Exception("convert init: not JSON: " . substr($initOut, 0, 300));
  if (!empty($init["error"])) throw new Exception("convert init error: " . $init["error"]);

  if (!empty($init["done"])) {
    echo "convert: nothing to do\n";
  } else {
    $token = (string)($init["token"] ?? "");
    if ($token === "") throw new Exception("convert init: token empty");

    echo "convert: token=$token total=" . ($init["total"] ?? "?") . "\n";

    $maxSeconds = 20 * 60;
    $t0 = time();

    while (true) {
      if (time() - $t0 > $maxSeconds) {
        throw new Exception("convert: timeout > {$maxSeconds}s");
      }

      usleep(700000);

      $stOut = run_php_include($GLOBALS["DOCROOT"] . "/api/admin/functions/convert_images_step.php", [
        "status" => "1",
        "token" => $token,
      ]);

      $st = extract_json($stOut);
      if (!$st) throw new Exception("convert status: not JSON: " . substr($stOut, 0, 300));
      if (!empty($st["error"])) throw new Exception("convert status error: " . $st["error"]);

      $idx = (int)($st["index"] ?? 0);
      $done = !empty($st["done"]);

      if ($idx % 10 === 0) {
        echo "convert: index=$idx done=" . ($done ? "1" : "0") . "\n";
      }

      if ($done) {
        echo "convert: DONE\n";
        break;
      }
    }
  }

  // 2) SYNC EVOTOR
  echo "-> sync_evotor\n";
  $syncOut = run_php_include($GLOBALS["DOCROOT"] . "/api/admin/functions/sync_evotor.php", [
    "cron" => "1",
    "nocache" => "1",
  ]);
  echo trim($syncOut) . "\n";

  // 3) GENERATE YML
  echo "-> generate_yml\n";
  $ymlOut = run_php_include($GLOBALS["DOCROOT"] . "/api/admin/functions/generate_yml.php", [
    "cron" => "1",
  ]);
  echo trim($ymlOut) . "\n";

  echo "==== " . date("Y-m-d H:i:s") . " DONE ====\n\n";

} catch (Throwable $e) {
  echo "!!!! ERROR: " . $e->getMessage() . "\n";
  echo "==== " . date("Y-m-d H:i:s") . " FAIL ====\n\n";
} finally {
  if ($lockFp) {
    flock($lockFp, LOCK_UN);
    fclose($lockFp);
  }
}
