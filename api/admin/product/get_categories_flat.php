<?php
header("Content-Type: application/json; charset=utf-8");
require_once __DIR__ . "/../../db.php";

$baseDir = "/photo_categories_vitrina/";

function getOrigin(): string {
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    $scheme = $https ? "https" : "http";
    $host = $_SERVER['HTTP_HOST'] ?? "";
    return $host ? ($scheme . "://" . $host) : "";
}

try {
    $origin = getOrigin();

    // пробуем с photo_categories
    try {
        $sql = "
SELECT
    id,
    name,
    slug,
    parent_id,
    level,
    code,
    CONCAT(code, ' — ', name) AS full_name,
    photo_categories
FROM categories
ORDER BY code ASC

        ";

        $stmt = $pdo->query($sql);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // добавляем url (относительный) + url_abs (абсолютный)
        foreach ($rows as &$r) {
            $file = trim((string)($r["photo_categories"] ?? ""));
            if ($file !== "") {
                $r["photo_url"] = $baseDir . $file;              // /photo_categories_vitrina/xxx.webp
                $r["photo_url_abs"] = $origin . $r["photo_url"]; // https://site.ru/photo_categories_vitrina/xxx.webp
            } else {
                $r["photo_url"] = null;
                $r["photo_url_abs"] = null;
            }
        }
        unset($r);

    } catch (PDOException $e) {
        // если колонки нет — не ломаем сайт
        if (strpos($e->getMessage(), "Unknown column") !== false || $e->getCode() === "42S22") {
            $sql = "
SELECT
    id,
    name,
    slug,
    parent_id,
    level,
    code,
    CONCAT(code, ' — ', name) AS full_name
FROM categories
ORDER BY code ASC

            ";

            $stmt = $pdo->query($sql);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($rows as &$r) {
                $r["photo_categories"] = null;
                $r["photo_url"] = null;
                $r["photo_url_abs"] = null;
            }
            unset($r);
        } else {
            throw $e;
        }
    }

    echo json_encode($rows, JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode([
        "error" => true,
        "message" => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
