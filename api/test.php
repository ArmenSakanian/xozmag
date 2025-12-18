<?php
header("Content-Type: application/json; charset=utf-8");

// ===== CACHE =====
$cacheFile = __DIR__ . "/evotor_groups_contragents_cache.json";
$cacheTtl  = 300; // 5 min
$noCache   = isset($_GET["nocache"]) && $_GET["nocache"] == "1";

if (!$noCache && file_exists($cacheFile) && (time() - filemtime($cacheFile) < $cacheTtl)) {
    header("X-From-Cache: 1");
    readfile($cacheFile);
    exit;
}
header("X-From-Cache: 0");

// ===== EVOTOR =====
$token   = "59a62817-90d7-4ee2-8a35-92d0de7ac91f";
$storeId = "20230324-1379-4034-80CD-1581DAED4A6E";
$url = "https://api.evotor.ru/api/v1/inventories/stores/$storeId/products";

// ===== CURL =====
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $token",
    "Accept: application/json"
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);

$response = curl_exec($ch);
$curlErr  = curl_error($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($curlErr) {
    echo json_encode(["error" => "curl error", "details" => $curlErr], JSON_UNESCAPED_UNICODE);
    exit;
}
if ($httpCode < 200 || $httpCode >= 300) {
    echo json_encode(["error" => "evotor http error", "httpCode" => $httpCode, "body" => $response], JSON_UNESCAPED_UNICODE);
    exit;
}

$data = json_decode($response, true);
if (!is_array($data)) {
    echo json_encode(["error" => "invalid json from evotor"], JSON_UNESCAPED_UNICODE);
    exit;
}

// ===== BUILD categories + contragents =====
$categories = [];     // uuid => {uuid,name,parentUuid}
$contrMap   = [];     // normalized => original

foreach ($data as $item) {
    // groups -> categories
    if (!empty($item["group"])) {
        $uuid = $item["uuid"] ?? null;
        if ($uuid) {
            $categories[$uuid] = [
                "uuid"       => $uuid,
                "name"       => $item["name"] ?? "",
                "parentUuid" => $item["parentUuid"] ?? null,
            ];
        }
        continue;
    }

    // products -> contragents (supplier)
    $c = "";

    // 1) чаще всего у тебя это поле "code"
    if (!empty($item["code"]) && is_string($item["code"])) {
        $c = trim($item["code"]);
    }

    // 2) если когда-нибудь начнёт приходить counterparty.name
    if ($c === "" && !empty($item["counterparty"]) && is_array($item["counterparty"])) {
        if (!empty($item["counterparty"]["name"]) && is_string($item["counterparty"]["name"])) {
            $c = trim($item["counterparty"]["name"]);
        }
    }

    if ($c !== "") {
        // нормализуем ключ, чтобы убрать дубли из-за пробелов/регистра
        $key = mb_strtolower(preg_replace('/\s+/u', ' ', $c), "UTF-8");
        $contrMap[$key] = $c;
    }
}

// сортировка
$categoriesList = array_values($categories);
usort($categoriesList, fn($a,$b) => strnatcasecmp($a["name"], $b["name"]));

$contragents = array_values($contrMap);
usort($contragents, fn($a,$b) => strnatcasecmp($a, $b));

$out = json_encode([
    "categories"  => $categoriesList,
    "contragents" => $contragents,
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

// cache + output
file_put_contents($cacheFile, $out);
echo $out;
