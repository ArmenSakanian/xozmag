<?php
header('Content-Type: application/json');
require_once "db.php";

$search = $_GET['search'] ?? '';

if ($search !== '') {

    $stmt = $pdo->prepare("
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

    $stmt->execute([
        ":s" => $pattern
    ]);

} else {

    $stmt = $pdo->query("
        SELECT * FROM barcodes
        ORDER BY id DESC
    ");

}

echo json_encode($stmt->fetchAll());
