<?php
require_once $_SERVER['DOCUMENT_ROOT'] . "/api/auth/require_admin.php";
header("Content-Type: application/json; charset=utf-8");

$LOG = sys_get_temp_dir() . "/xozmag_cron_10min.log";
$lines = (int)($_GET["lines"] ?? 200);
if ($lines < 20) $lines = 20;
if ($lines > 800) $lines = 800;

function tail_lines(string $file, int $lines): array {
  if (!is_file($file)) return [];
  $fp = fopen($file, "rb");
  if (!$fp) return [];

  $pos = -1;
  $buf = "";
  $lineCount = 0;

  fseek($fp, 0, SEEK_END);
  $size = ftell($fp);

  while ($size + $pos >= 0) {
    fseek($fp, $pos, SEEK_END);
    $ch = fgetc($fp);
    if ($ch === "\n") {
      $lineCount++;
      if ($lineCount > $lines) break;
    }
    $buf = $ch . $buf;
    $pos--;
    if (-$pos > 1024 * 1024 * 2) break; // защита: не больше 2MB
  }
  fclose($fp);

  $arr = preg_split("/\r?\n/", trim($buf));
  $arr = array_values(array_filter($arr, fn($x) => $x !== ""));
  if (count($arr) > $lines) $arr = array_slice($arr, -$lines);
  return $arr;
}

echo json_encode([
  "ok" => true,
  "lines" => tail_lines($LOG, $lines),
], JSON_UNESCAPED_UNICODE);
