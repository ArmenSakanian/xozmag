<?php
require_once $_SERVER['DOCUMENT_ROOT'] . "/api/auth/require_admin.php";
header("Content-Type: application/json; charset=utf-8");

$CONFIG = sys_get_temp_dir() . "/xozmag_autorun_config.json";
$STATE  = sys_get_temp_dir() . "/xozmag_autorun_state.json";
$LOCK   = sys_get_temp_dir() . "/xozmag_cron_10min.lock";

$cfg = ["enabled" => false, "interval_minutes" => 10];
if (is_file($CONFIG)) {
  $tmp = json_decode((string)file_get_contents($CONFIG), true);
  if (is_array($tmp)) $cfg = array_merge($cfg, $tmp);
}

$state = ["last_run" => 0, "last_ok" => null, "last_code" => null, "last_msg" => ""];
if (is_file($STATE)) {
  $tmp = json_decode((string)file_get_contents($STATE), true);
  if (is_array($tmp)) $state = array_merge($state, $tmp);
}

$intervalMin = (int)($cfg["interval_minutes"] ?? 10);
if ($intervalMin < 1) $intervalMin = 1;
if ($intervalMin > 1440) $intervalMin = 1440;

$last = (int)($state["last_run"] ?? 0);
$next = $last > 0 ? ($last + $intervalMin * 60) : time();

$running = false;
$fp = fopen($LOCK, "c+");
if ($fp) {
  $running = !flock($fp, LOCK_EX | LOCK_NB);
  if (!$running) flock($fp, LOCK_UN);
  fclose($fp);
}

echo json_encode([
  "ok" => true,
  "enabled" => !empty($cfg["enabled"]),
  "interval_minutes" => $intervalMin,
  "running" => $running,
  "last_run_ts" => $last,
  "next_run_ts" => $next,
  "last_ok" => $state["last_ok"],
  "last_code" => $state["last_code"],
  "last_msg" => $state["last_msg"],
], JSON_UNESCAPED_UNICODE);
