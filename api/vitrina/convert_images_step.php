<?php
header("Content-Type: application/json; charset=utf-8");

$folder = $_SERVER["DOCUMENT_ROOT"] . "/photo_product_vitrina/";
$files  = array_values(array_filter(scandir($folder), function ($f) use ($folder) {
    return is_file($folder . $f);
}));

$total = count($files);
$index = intval($_GET["index"] ?? 0);

if ($index >= $total) {
    echo json_encode([
        "done" => true,
        "total" => $total
    ]);
    exit;
}

$file = $files[$index];
$path = $folder . $file;

$info = @getimagesize($path);
if (!$info) {
    echo json_encode([
        "index" => $index,
        "total" => $total,
        "status" => "bad",
        "file" => $file
    ]);
    exit;
}

$mime = $info["mime"];
$ext  = strtolower(pathinfo($file, PATHINFO_EXTENSION));
$name = pathinfo($file, PATHINFO_FILENAME);

/* ===== WEBP ===== */
if ($mime === "image/webp") {
    if ($ext !== "webp") {
        rename($path, $folder . $name . ".webp");
    }

    echo json_encode([
        "index" => $index + 1,
        "total" => $total,
        "status" => "skip",
        "file" => $file
    ]);
    exit;
}

/* ===== JPEG ===== */
if ($mime === "image/jpeg") {
    $img = @imagecreatefromjpeg($path);
    if ($img) {
        imagewebp($img, $folder . $name . ".webp", 75);
        imagedestroy($img);
        unlink($path);
    }

    echo json_encode([
        "index" => $index + 1,
        "total" => $total,
        "status" => "converted",
        "file" => $file
    ]);
    exit;
}

/* ===== PNG ===== */
if ($mime === "image/png") {
    $img = @imagecreatefrompng($path);
    if ($img) {
        imagepalettetotruecolor($img);
        imagealphablending($img, true);
        imagesavealpha($img, true);
        imagewebp($img, $folder . $name . ".webp", 75);
        imagedestroy($img);
        unlink($path);
    }

    echo json_encode([
        "index" => $index + 1,
        "total" => $total,
        "status" => "converted",
        "file" => $file
    ]);
    exit;
}

echo json_encode([
    "index" => $index + 1,
    "total" => $total,
    "status" => "unsupported",
    "file" => $file
]);
