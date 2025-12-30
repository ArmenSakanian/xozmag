<?php

$DOCROOT = realpath(__DIR__ . "/../.."); // => /home/.../www/xozmag.ru
$_SERVER["DOCUMENT_ROOT"] = $DOCROOT;

date_default_timezone_set("Europe/Moscow");

// ====== GLOBAL LOCK ======
$lockFile = sys_get_temp_dir() . "/xozmag_cron_10min.lock";
$lockFp = fopen($lockFile, "c+");
if (!$lockFp || !flock($lockFp, LOCK_EX | LOCK_NB)) {
  echo "[" . date("Y-m-d H:i:s") . "] Already running (global lock)\n";
  exit;
}

function run_php_include(string $file, array $get = []): string {
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

function j($s) {
  $d = json_decode($s, true);
  return is_array($d) ? $d : null;
}

try {
  echo "==== " . date("Y-m-d H:i:s") . " START ====\n";

  // 1) SYNC EVOTOR
  echo "-> sync_evotor\n";
  $syncOut = run_php_include($GLOBALS["DOCROOT"] . "/api/admin/functions/sync_evotor.php", [
    "cron" => "1",
    "nocache" => "1",
  ]);
  echo $syncOut . "\n";

  // 2) CONVERT IMAGES (init + poll status until done)
  echo "-> convert_images (init)\n";
  $initOut = run_php_include($GLOBALS["DOCROOT"] . "/api/admin/functions/convert_images_step.php", [
    "init" => "1",
    "dry_run" => "0",
  ]);
  $init = j($initOut);
  if (!$init) {
    throw new Exception("convert init: not JSON: " . substr($initOut, 0, 300));
  }
  if (!empty($init["error"])) throw new Exception("convert init error: " . $init["error"]);

  if (!empty($init["done"])) {
    echo "convert: nothing to do\n";
  } else {
    $token = (string)($init["token"] ?? "");
    if ($token === "") throw new Exception("convert init: token empty");

    echo "convert: token=$token total=" . ($init["total"] ?? "?") . "\n";

    $maxSeconds = 20 * 60; // максимум 20 минут на конвертацию (настрой)
    $t0 = time();

    while (true) {
      if (time() - $t0 > $maxSeconds) {
        throw new Exception("convert: timeout > {$maxSeconds}s");
      }

      usleep(700000); // 0.7s

      $stOut = run_php_include($GLOBALS["DOCROOT"] . "/api/admin/functions/convert_images_step.php", [
        "status" => "1",
        "token" => $token,
      ]);
      $st = j($stOut);
      if (!$st) throw new Exception("convert status: not JSON: " . substr($stOut, 0, 300));
      if (!empty($st["error"])) throw new Exception("convert status error: " . $st["error"]);

      $idx = (int)($st["index"] ?? 0);
      $done = !empty($st["done"]);

      // лог раз в ~10 шагов
      if ($idx % 10 === 0) {
        echo "convert: index=$idx done=" . ($done ? "1" : "0") . "\n";
      }

      if ($done) {
        echo "convert: DONE\n";
        break;
      }
    }
  }

  // 3) GENERATE YML
  echo "-> generate_yml\n";
  $ymlOut = run_php_include($GLOBALS["DOCROOT"] . "/api/admin/functions/generate_yml.php", [
    "cron" => "1",
  ]);
  echo $ymlOut . "\n";

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