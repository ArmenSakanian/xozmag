<?php
$folder = $_SERVER["DOCUMENT_ROOT"] . "/photo_product_vitrina/";
$files = scandir($folder);

echo "<pre>";

foreach ($files as $file) {

    $path = $folder . $file;

    if (!is_file($path)) continue;

    // Получить MIME
    $info = @getimagesize($path);
    if (!$info) {
        echo "❌ BAD IMAGE: $file\n";
        continue;
    }

    $mime = $info["mime"];
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION)); // фактическое расширение
    $name = pathinfo($file, PATHINFO_FILENAME);             // имя без расширения

    // --- CASE 1: Файл уже WEBP, но расширение не .webp ---
    if ($mime === "image/webp") {

        if ($ext !== "webp") {
            $newPath = $folder . $name . ".webp";

            if (!file_exists($newPath)) {
                rename($path, $newPath);
                echo "✔ FIXED EXT: $file → $name.webp\n";
            } else {
                echo "⚠ SKIP: $name.webp already exists\n";
            }
        } else {
            echo "✔ WEBP OK: $file\n";
        }

        continue;
    }

    // --- CASE 2: JPEG FILES ---
    if ($mime === "image/jpeg") {

        // неправильное расширение → исправить
        if ($ext !== "jpg" && $ext !== "jpeg") {
            $corrected = $folder . $name . ".jpg";
            rename($path, $corrected);
            $path = $corrected;
            echo "✔ FIXED EXT: $file → $name.jpg\n";
        }

        // загружаем JPEG
        $img = @imagecreatefromjpeg($path);
        if (!$img) {
            echo "❌ ERROR: Can't open JPEG: $file\n";
            continue;
        }

        // сохраняем в webp
        imagewebp($img, $folder . $name . ".webp", 75);
        imagedestroy($img);

        unlink($path);
        echo "✔ CONVERTED JPG → WEBP: $file\n";
        continue;
    }

    // --- CASE 3: PNG FILES ---
    if ($mime === "image/png") {

        // исправить расширение если неправильно
        if ($ext !== "png") {
            $corrected = $folder . $name . ".png";
            rename($path, $corrected);
            $path = $corrected;
            echo "✔ FIXED EXT: $file → $name.png\n";
        }

        $img = @imagecreatefrompng($path);
        if (!$img) {
            echo "❌ ERROR: Can't open PNG: $file\n";
            continue;
        }

        // подготовка PNG с альфой
        imagepalettetotruecolor($img);
        imagealphablending($img, true);
        imagesavealpha($img, true);

        imagewebp($img, $folder . $name . ".webp", 75);
        imagedestroy($img);

        unlink($path);
        echo "✔ CONVERTED PNG → WEBP: $file\n";
        continue;
    }

    echo "⚠ Unsupported MIME ($mime): $file\n";
}

echo "</pre>";
