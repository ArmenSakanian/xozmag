<?php
require_once $_SERVER['DOCUMENT_ROOT'] . "/api/auth/require_admin.php";
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . "/../db.php";

function ensureLabelSizesTable(PDO $pdo): void {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS barcode_label_sizes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            value VARCHAR(32) NOT NULL UNIQUE,
            text VARCHAR(64) NOT NULL,
            width_mm DECIMAL(6,2) NOT NULL,
            height_mm DECIMAL(6,2) NOT NULL,
            orientation CHAR(1) NOT NULL DEFAULT 'L'
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $pdo->exec("
        INSERT IGNORE INTO barcode_label_sizes (value, text, width_mm, height_mm, orientation) VALUES
        ('42x25', '42 × 25 мм', 42, 25, 'L'),
        ('30x20', '30 × 20 мм', 30, 20, 'L')
    ");
}

ensureLabelSizesTable($pdo);

$stmt = $pdo->query("SELECT id, value, text, width_mm, height_mm, orientation FROM barcode_label_sizes ORDER BY width_mm DESC, height_mm DESC");
echo json_encode([
    "status" => "success",
    "items"  => $stmt->fetchAll(PDO::FETCH_ASSOC)
]);
