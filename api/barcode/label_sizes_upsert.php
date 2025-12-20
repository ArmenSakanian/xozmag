<?php
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

$raw = file_get_contents("php://input");
$data = json_decode($raw, true);
if (!is_array($data)) {
    echo json_encode(["status" => "error", "msg" => "bad json"]);
    exit;
}

$id = isset($data["id"]) ? (int)$data["id"] : 0;

$value = trim((string)($data["value"] ?? ""));
$text  = trim((string)($data["text"] ?? ""));

$width  = (float)($data["width_mm"] ?? 0);
$height = (float)($data["height_mm"] ?? 0);

$orientation = strtoupper(trim((string)($data["orientation"] ?? "L")));
if ($orientation !== "P") $orientation = "L";

if ($width <= 0 || $height <= 0) {
    echo json_encode(["status" => "error", "msg" => "width/height required"]);
    exit;
}

if ($value === "") {
    $value = (int)$width . "x" . (int)$height;
}

if (!preg_match('/^\d{2,3}\s*[xх]\s*\d{2,3}$/u', $value)) {
    echo json_encode(["status" => "error", "msg" => "value must be like 42x25"]);
    exit;
}

// нормализуем value -> 42x25
$value = preg_replace('/\s+/u', '', $value);
$value = str_ireplace('х', 'x', $value);

if ($text === "") {
    $text = str_replace("x", " × ", $value) . " мм";
}

try {
    if ($id > 0) {
        $stmt = $pdo->prepare("
            UPDATE barcode_label_sizes
            SET value = ?, text = ?, width_mm = ?, height_mm = ?, orientation = ?
            WHERE id = ?
        ");
        $stmt->execute([$value, $text, $width, $height, $orientation, $id]);
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO barcode_label_sizes (value, text, width_mm, height_mm, orientation)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$value, $text, $width, $height, $orientation]);
        $id = (int)$pdo->lastInsertId();
    }
} catch (PDOException $e) {
    if (!empty($e->errorInfo[1]) && (int)$e->errorInfo[1] === 1062) {
        echo json_encode(["status" => "error", "msg" => "Такой размер уже существует"]);
        exit;
    }
    echo json_encode(["status" => "error", "msg" => "db error"]);
    exit;
}

echo json_encode(["status" => "success", "id" => $id]);
