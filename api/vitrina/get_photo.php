<?php
header("Content-Type: application/json; charset=utf-8");

$barcode = $_GET["barcode"] ?? "";
$barcode = trim($barcode);

$saveDir  = $_SERVER['DOCUMENT_ROOT'] . "/photo_product_vitrina/";
$savePath = $saveDir . $barcode . ".jpg";
$urlBase  = "https://" . $_SERVER['SERVER_NAME'] . "/photo_product_vitrina/";

if (!$barcode) {
    echo json_encode(["photo_url" => $urlBase . "no_photo.png"]);
    exit;
}

if (file_exists($savePath)) {
    echo json_encode(["photo_url" => $urlBase . "$barcode.jpg"]);
    exit;
}

// === GOOGLE SEARCH ===
$googleUrl = "https://www.google.com/search?tbm=isch&q=" . urlencode($barcode);

$context = stream_context_create([
    "http" => [
        "header" => "User-Agent: Mozilla/5.0\r\n"
    ]
]);

$html = @file_get_contents($googleUrl, false, $context);

if (!$html) {
    echo json_encode(["photo_url" => $urlBase . "no_photo.png"]);
    exit;
}

// === Парсим картинку ===
preg_match_all('/<img[^>]+src="([^"]+)"/i', $html, $matches);

$imgs = $matches[1] ?? [];

if (!$imgs) {
    echo json_encode(["photo_url" => $urlBase . "no_photo.png"]);
    exit;
}

$photoUrl = "";

foreach ($imgs as $img) {
    if (strpos($img, "data:image") !== false) continue;
    if (strlen($img) < 10) continue;

    if (strpos($img, "//") === 0) {
        $img = "https:" . $img;
    }

    $photoUrl = $img;
    break;
}

if (!$photoUrl) {
    echo json_encode(["photo_url" => $urlBase . "no_photo.png"]);
    exit;
}

// === Скачиваем ===
$imageData = @file_get_contents($photoUrl);

if (!$imageData) {
    echo json_encode(["photo_url" => $urlBase . "no_photo.png"]);
    exit;
}

if (!file_exists($saveDir)) {
    mkdir($saveDir, 0777, true);
}

$tmpPath = $saveDir . "_tmp_" . $barcode;
file_put_contents($tmpPath, $imageData);

$info = @getimagesize($tmpPath);

if (!$info) {
    unlink($tmpPath);
    echo json_encode(["photo_url" => $urlBase . "no_photo.png"]);
    exit;
}

$mime = $info["mime"];

switch ($mime) {
    case "image/jpeg":
        $src = imagecreatefromjpeg($tmpPath);
        break;
    case "image/png":
        $src = imagecreatefrompng($tmpPath);
        break;
    case "image/webp":
        $src = imagecreatefromwebp($tmpPath);
        break;
    default:
        unlink($tmpPath);
        echo json_encode(["photo_url" => $urlBase . "no_photo.png"]);
        exit;
}

// === Уменьшаем до 800px ===
$w = imagesx($src);
$h = imagesy($src);

$target = 800;
$ratio = min($target / $w, $target / $h);

$newW = (int)($w * $ratio);
$newH = (int)($h * $ratio);

$dst = imagecreatetruecolor($newW, $newH);
$white = imagecolorallocate($dst, 255, 255, 255);
imagefill($dst, 0, 0, $white);

imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $w, $h);

// === Сохраняем <1MB ===
$quality = 90;

do {
    imagejpeg($dst, $savePath, $quality);
    $size = filesize($savePath);
    $quality -= 5;
} while ($size > 1024 * 1024 && $quality > 40);

unlink($tmpPath);

echo json_encode([
    "photo_url" => $urlBase . "$barcode.jpg"
], JSON_UNESCAPED_UNICODE);

exit;
