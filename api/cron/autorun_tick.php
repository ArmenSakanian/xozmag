<?php
// /api/cron/autorun_tick.php
date_default_timezone_set("Europe/Moscow");

$DOCROOT = realpath(__DIR__ . "/../..");
if (!$DOCROOT) exit(1);
$_SERVER["DOCUMENT_ROOT"] = $DOCROOT;

$CONFIG = sys_get_temp_dir() . "/xozmag_autorun_config.json";
$STATE  = sys_get_temp_dir() . "/xozmag_autorun_state.json";
$LOG    = sys_get_temp_dir() . "/xozmag_cron_10min.log";

$PHP = "/usr/bin/php";
$JOB = $DOCROOT . "/api/cron/run_10min.php";

// defaults
$cfg = [
  "enabled" => false,
  "interval_minutes" => 10,
];

if (is_file($CONFIG)) {
  $tmp = json_decode((string)file_get_contents($CONFIG), true);
  if (is_array($tmp)) $cfg = array_merge($cfg, $tmp);
}

if (empty($cfg["enabled"])) exit(0);

$intervalMin = (int)($cfg["interval_minutes"] ?? 10);
if ($intervalMin < 1) $intervalMin = 1;
if ($intervalMin > 1440) $intervalMin = 1440;

$state = [
  "last_run" => 0,
  "last_ok"  => null,
  "last_code"=> null,
  "last_msg" => "",
];
if (is_file($STATE)) {
  $tmp = json_decode((string)file_get_contents($STATE), true);
  if (is_array($tmp)) $state = array_merge($state, $tmp);
}

$now = time();
$last = (int)($state["last_run"] ?? 0);

// еще рано запускать
if ($last > 0 && ($now - $last) < ($intervalMin * 60)) exit(0);

// запускаем джобу (она сама имеет глобальный lock — наложений не будет)
$descriptors = [
  1 => ["pipe", "w"],
  2 => ["pipe", "w"],
];

$proc = proc_open([$PHP, $JOB], $descriptors, $pipes);
if (!is_resource($proc)) {
  $state["last_run"] = $now;
  $state["last_ok"] = false;
  $state["last_code"] = 1;
  $state["last_msg"] = "proc_open failed";
  file_put_contents($STATE, json_encode($state, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), LOCK_EX);
  exit(1);
}

$out = stream_get_contents($pipes[1]); fclose($pipes[1]);
$err = stream_get_contents($pipes[2]); fclose($pipes[2]);
$code = proc_close($proc);

file_put_contents($LOG, (string)$out . ($err ? ("\n" . $err) : "") . "\n", FILE_APPEND);

$state["last_run"] = $now;
$state["last_ok"]  = ($code === 0);
$state["last_code"]= $code;
$state["last_msg"] = $code === 0 ? "ok" : ("exit=" . $code);

file_put_contents($STATE, json_encode($state, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), LOCK_EX);
