<?php
require_once __DIR__ . "/db.php";

header("Content-Type: text/csv; charset=utf-8");
header("Content-Disposition: attachment; filename=export_barcodes.csv");

// Чтобы русские символы нормально открывались в Excel
echo "\xEF\xBB\xBF"; // UTF-8 BOM

$idsRaw = $_GET["ids"] ?? "[]";
$ids = json_decode($idsRaw, true);

if (!$ids || !is_array($ids)) {
    die("BAD IDS");
}

if (count($ids) === 0) {
    die("NO DATA");
}

$in = implode(",", array_map("intval", $ids));
$stmt = $pdo->query("SELECT * FROM barcodes WHERE id IN ($in) ORDER BY id DESC");
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ======== ЗАГОЛОВКИ ========
$headers = ["Штрих-код", "Название", "Цена", "Артикул", "Контрагент", "Остаток"];
echo implode(";", $headers) . "\n";

// ======== ДАННЫЕ ========
foreach ($data as $item) {
    $row = [
        $item["barcode"],
        $item["product_name"],
        $item["price"],
        $item["sku"],
        $item["contractor"],
        $item["stock"],
    ];

    echo implode(";", array_map(fn($v) => str_replace(";", ",", $v), $row)) . "\n";
}

exit;
