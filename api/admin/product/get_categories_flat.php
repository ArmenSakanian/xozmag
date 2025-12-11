<?php
header("Content-Type: application/json; charset=utf-8");

// Подключение к базе
require_once __DIR__ . "/../../db.php";

try {
    // Загружаем все категории с нужными полями
    $sql = "
        SELECT 
            id,
            name,
            level_code,
            CONCAT(level_code, ' — ', name) AS full_name
        FROM categories
        ORDER BY level_code ASC
    ";

    $stmt = $pdo->query($sql);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($rows, JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {

    echo json_encode([
        "error" => true,
        "message" => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
