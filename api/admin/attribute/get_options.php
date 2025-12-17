<?php
header("Content-Type: application/json; charset=utf-8");
require_once __DIR__ . "/../../db.php";

$attribute_id = intval($_GET["attribute_id"] ?? 0);

if ($attribute_id <= 0) {
    echo json_encode([]);
    exit;
}

/*
  Возвращает ВСЕ значения характеристики
  (ID — источник истины)
*/
$stmt = $pdo->prepare("
    SELECT
        id,
        value,
        meta_json
    FROM product_attribute_options
    WHERE attribute_id = ?
    ORDER BY LOWER(value), value
");
$stmt->execute([$attribute_id]);

$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($rows as &$r) {
    $meta = null;

    if (!empty($r["meta_json"])) {
        $tmp = json_decode($r["meta_json"], true);
        if (is_array($tmp)) $meta = $tmp;
    }

    unset($r["meta_json"]);
    $r["meta"] = $meta;
}
unset($r);

echo json_encode($rows, JSON_UNESCAPED_UNICODE);
