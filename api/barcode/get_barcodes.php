<?php
require_once $_SERVER['DOCUMENT_ROOT'] . "/api/auth/require_admin.php";
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . "/../db.php";

$search = $_GET['search'] ?? '';
$search = trim($search);

if ($search !== '') {

    $stmt = $pdoget_barcodes.php->prepare("
        SELECT *
        FROM barcodes
        WHERE LOWER(barcode)      LIKE LOWER(:s)
           OR LOWER(product_name) LIKE LOWER(:s)
           OR LOWER(sku)          LIKE LOWER(:s)
           OR LOWER(contractor)   LIKE LOWER(:s)
           OR CAST(price AS CHAR) LIKE :s
           OR CAST(stock AS CHAR) LIKE :s
        ORDER BY id DESC
    ");

    $pattern = "%$search%";
    $stmt->execute([":s" => $pattern]);

} else {
    $stmt = $pdo->query("SELECT * FROM barcodes ORDER BY id DESC");
}

$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

/**
 * Авто-починка старых фото:
 * раньше create мог сохранить файл в /api/photo_product_barcode/,
 * но в базе стояло /photo_product_barcode/...
 * Тогда отдаём URL /api/photo_product_barcode/... чтобы фото открылось.
 */
foreach ($rows as &$r) {
    if (!empty($r["photo"])) {
        $p = $r["photo"];

        $rootPath = $_SERVER["DOCUMENT_ROOT"] . $p;
        if (!file_exists($rootPath)) {
            $altPath = $_SERVER["DOCUMENT_ROOT"] . "/api" . $p;
            if (file_exists($altPath)) {
                $r["photo"] = "/api" . $p;
            }
        }
    }
}
unset($r);

echo json_encode($rows);
