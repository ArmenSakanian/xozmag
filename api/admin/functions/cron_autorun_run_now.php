<?php
require_once $_SERVER['DOCUMENT_ROOT'] . "/api/auth/require_admin.php";
header("Content-Type: application/json; charset=utf-8");

$DOCROOT = realpath($_SERVER["DOCUMENT_ROOT"]);
$PHP = "/usr/bin/php";
$JOB = $DOCROOT . "/api/cron/run_10min.php";

$LOCK = sys_get_temp_dir() . "/xozmag_cron_10min.lock";
$fp = fopen($LOCK, "c+");
if ($fp && !flock($fp, LOCK_EX | LOCK_NB)) {
  echo json_encode(["ok" => false, "error" => "already_running"], JSON_UNESCAPED_UNICODE);
  exit;
}
if ($fp) { flock($fp, LOCK_UN); fclose($fp); }

$descriptors = [
  1 => ["pipe", "w"],
  2 => ["pipe", "w"],
];
$proc = proc_open([$PHP, $JOB], $descriptors, $pipes);
if (!is_resource($proc)) {
  echo json_encode(["ok" => false, "error" => "proc_open_failed"], JSON_UNESCAPED_UNICODE);
  exit;
}

$out = stream_get_contents($pipes[1]); fclose($pipes[1]);
$err = stream_get_contents($pipes[2]); fclose($pipes[2]);
$code = proc_close($proc);

echo json_encode([
  "ok" => $code === 0,
  "code" => $code,
  "out" => $out,
  "err" => $err,
], JSON_UNESCAPED_UNICODE);
