<?php
require_once $_SERVER['DOCUMENT_ROOT'] . "/api/auth/require_admin.php";
header("Content-Type: application/json; charset=utf-8");

$CONFIG = sys_get_temp_dir() . "/xozmag_autorun_config.json";

$raw = (string)file_get_contents("php://input");
$data = json_decode($raw, true);
if (!is_array($data)) $data = [];

$enabled = !empty($data["enabled"]);
$intervalMin = (int)($data["interval_minutes"] ?? 10);
if ($intervalMin < 1) $intervalMin = 1;
if ($intervalMin > 1440) $intervalMin = 1440;

$cfg = [
  "enabled" => $enabled,
  "interval_minutes" => $intervalMin,
];

file_put_contents($CONFIG, json_encode($cfg, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), LOCK_EX);

echo json_encode(["ok" => true, "config" => $cfg], JSON_UNESCAPED_UNICODE);
