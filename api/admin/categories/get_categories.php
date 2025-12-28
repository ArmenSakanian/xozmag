<?php
require_once $_SERVER['DOCUMENT_ROOT'] . "/api/auth/require_admin.php";
header("Content-Type: application/json; charset=utf-8");
require_once __DIR__ . "/../../db.php";

/*
  Отдаём все категории
  Сортировка важна: сначала по level, потом по parent_id, потом по sort
*/

$stmt = $pdo->query("
    SELECT
        id,
        parent_id,
        name,
        sort,
        level,
        code
    FROM categories
    ORDER BY
        level ASC,
        parent_id ASC,
        sort ASC
");

$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($rows, JSON_UNESCAPED_UNICODE);
