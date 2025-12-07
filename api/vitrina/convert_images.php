<?php
$folder = $_SERVER["DOCUMENT_ROOT"] . "/photo_product_vitrina/";


$allowed = ["jpg", "jpeg", "png"];
$files = scandir($folder);

foreach ($files as $file) {
    $path = $folder . $file;

    // пропускаем папки
    if (!is_file($path)) continue;

    $info = pathinfo($path);
    $ext = strtolower($info["extension"]);
    $basename = $info["filename"];

    // уже webp — пропускаем
    if ($ext === "webp") continue;

    // если не jpg / png → пропустить
    if (!in_array($ext, $allowed)) continue;

    // загружаем изображение
    if ($ext === "jpg" || $ext === "jpeg") {
        $img = imagecreatefromjpeg($path);
    } elseif ($ext === "png") {
        $img = imagecreatefrompng($path);
        imagepalettetotruecolor($img);
        imagealphablending($img, true);
        imagesavealpha($img, true);
    }

    if (!$img) continue;

    // путь для webp
    $newPath = $folder . $basename . ".webp";

    // качество 70–80% — идеальный размер
    imagewebp($img, $newPath, 75);
    imagedestroy($img);

    // удаляем старый файл
    unlink($path);
}

echo "OK: conversion completed";
