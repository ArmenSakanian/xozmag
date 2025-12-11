<?php
header("Content-Type: application/json; charset=utf-8");
require_once __DIR__ . "/../../db.php";

$stmt = $pdo->query("
    SELECT id, name, parent_id, level, level_code
    FROM categories
    ORDER BY level_code ASC
");

echo json_encode($stmt->fetchAll(), JSON_UNESCAPED_UNICODE);
