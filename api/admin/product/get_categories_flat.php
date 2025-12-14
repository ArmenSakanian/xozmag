<?php
header("Content-Type: application/json; charset=utf-8");
require_once __DIR__ . "/../../db.php";

try {

    $sql = "
        SELECT
            id,
            name,
            parent_id,
            level,
            code,
            CONCAT(code, ' — ', name) AS full_name
        FROM categories
        ORDER BY code ASC
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
