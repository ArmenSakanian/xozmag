<?php
header('Content-Type: application/json');
require_once "db.php";

$search = $_GET['search'] ?? '';

if ($search !== '') {

    $stmt = $pdo->prepare("
        SELECT * FROM barcodes
        WHERE LOWER(barcode)      LIKE LOWER(:s1)
           OR LOWER(product_name) LIKE LOWER(:s2)
           OR LOWER(sku)          LIKE LOWER(:s3)
           OR LOWER(contractor)   LIKE LOWER(:s4)
        ORDER BY id DESC
    ");

    $pattern = "%$search%";

    $stmt->execute([
        ":s1" => $pattern,
        ":s2" => $pattern,
        ":s3" => $pattern,
        ":s4" => $pattern
    ]);

} else {

    $stmt = $pdo->query("
        SELECT * FROM barcodes
        ORDER BY id DESC
    ");

}

echo json_encode($stmt->fetchAll());