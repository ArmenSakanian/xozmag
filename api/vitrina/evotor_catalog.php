<?php
// api/vitrina/evotor_catalog.php
require_once $_SERVER['DOCUMENT_ROOT'] . "/api/auth/require_admin.php";

header("Content-Type: application/json; charset=utf-8");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

require_once __DIR__ . "/evotor_catalog_core.php";

$nocache = isset($_GET["nocache"]) && $_GET["nocache"] === "1";
$debug   = isset($_GET["debug"])   && $_GET["debug"] === "1";

$result = evotor_catalog_build([
  "nocache" => $nocache,
  "debug" => $debug,
  "cache_ttl" => 1,
]);

header("X-From-Cache: " . (!empty($result["from_cache"]) ? "1" : "0"));
header("X-Duplicates-Found: " . (int)($result["duplicates_found"] ?? 0));

if (empty($result["ok"])) {
  http_response_code(502);
  echo json_encode([
    "error" => $result["error"] ?? "evotor_failed",
    "http" => $result["http"] ?? 0,
    "details" => $result["details"] ?? ""
  ], JSON_UNESCAPED_UNICODE);
  exit;
}

echo $result["json"];
