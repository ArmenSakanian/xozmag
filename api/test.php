<?php
echo "OK 1<br>";

require_once "db.php";

echo "OK 2<br>";

$barcode = "TEST-123456789";

$stmt = $pdo->prepare("SELECT id FROM barcodes WHERE barcode = ?");
$stmt->execute([$barcode]);

echo "OK 3<br>";

var_dump($stmt->fetch());