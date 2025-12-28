<?php
require_once $_SERVER['DOCUMENT_ROOT'] . "/api/auth/require_admin.php";
require_once __DIR__ . "/../db.php";
require_once __DIR__ . "/../libs/tcpdf/tcpdf.php";

header("Content-Type: application/pdf; charset=UTF-8");

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

function getLabelSize(PDO $pdo, string $value): array {
    ensureLabelSizesTable($pdo);

    $value = trim($value);
    if ($value === "") $value = "42x25";

    $stmt = $pdo->prepare("SELECT value, text, width_mm, height_mm, orientation FROM barcode_label_sizes WHERE value = ? LIMIT 1");
    $stmt->execute([$value]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        return [
            "value" => (string)$row["value"],
            "text"  => (string)$row["text"],
            "w"     => (float)$row["width_mm"],
            "h"     => (float)$row["height_mm"],
            "o"     => strtoupper((string)$row["orientation"] ?: "L"),
        ];
    }

    if (preg_match('/^\s*(\d{2,3})\s*[xх]\s*(\d{2,3})\s*$/u', $value, $m)) {
        return [
            "value" => $m[1]."x".$m[2],
            "text"  => $m[1]." × ".$m[2]." мм",
            "w"     => (float)$m[1],
            "h"     => (float)$m[2],
            "o"     => "L",
        ];
    }

    return ["value"=>"42x25","text"=>"42 × 25 мм","w"=>42.0,"h"=>25.0,"o"=>"L"];
}

function clamp(float $v, float $min, float $max): float {
    return max($min, min($max, $v));
}

function estimateModules(string $type, string $code): int {
    if ($type === "EAN13") return 95;
    $len = mb_strlen($code, "UTF-8");
    return max(80, 11 * $len + 35);
}

function fitOrWrap($pdf, string $text, float $widthMm, float $maxFont): array {
    $text = trim($text);
    if ($text === "") return ["lines"=>0];

    $font = $maxFont;
    while ($font > 5) {
        $pdf->SetFont('dejavusans', '', $font, '', true);
        if ($pdf->GetStringWidth($text) <= $widthMm) {
            return ["lines" => 1, "font" => $font, "l1" => $text, "l2" => ""];
        }
        $font -= 0.4;
    }

    $words = preg_split('/\s+/u', $text);
    $l1 = ""; $l2 = "";

    foreach ($words as $w) {
        $try = $l1 === "" ? $w : ($l1 . " " . $w);
        if ($pdf->GetStringWidth($try) <= $widthMm) {
            $l1 = $try;
        } else {
            $l2 = $l2 === "" ? $w : ($l2 . " " . $w);
        }
    }

    $pdf->SetFont('dejavusans', '', 6, '', true);
    return ["lines" => 2, "font" => 6, "l1" => $l1, "l2" => $l2];
}

/* === DATA === */
$dataRaw = $_GET["data"] ?? "";
$data = json_decode($dataRaw, true);
if (!is_array($data)) {
    // иногда бывает двойная кодировка
    $data = json_decode(urldecode($dataRaw), true);
}
if (!is_array($data)) die("BAD DATA");

/* === PDF === */
$pdf = new TCPDF();
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetAutoPageBreak(false);

foreach ($data as $id => $opts) {

    $id = (int)$id;
    if ($id <= 0) continue;

    $sizeVal   = (string)($opts["size"] ?? "42x25");
    $withName  = !empty($opts["withName"]);
    $withPrice = !empty($opts["withPrice"]);

    $stmt = $pdo->prepare("SELECT * FROM barcodes WHERE id = ?");
    $stmt->execute([$id]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$item) continue;

    /* === тип штрихкода === */
    $raw = trim((string)$item["barcode"]);
    $digits = preg_replace('/\D+/', '', $raw);

    if (strlen($digits) === 13) {
        $barcodeType = "EAN13";
        $code = $digits;
    } else {
        $barcodeType = "C128B";
        $code = preg_replace('/\s+/u', '', $raw);
        if ($code === "") $code = $digits;
        if ($code === "") continue;
    }

    /* === размер === */
    $cfg = getLabelSize($pdo, $sizeVal);
    $w = $cfg["w"];
    $h = $cfg["h"];
    $o = ($cfg["o"] === "P") ? "P" : "L";

    $pdf->AddPage($o, [$w, $h]);
    $pdf->SetMargins(0, 0, 0);

    $margin = 1.2;
    $maxW = $w - 2 * $margin;
    $y = $margin;

    /* === название === */
    if ($withName && !empty($item["product_name"])) {
        $maxFont = ($h <= 20) ? 7.2 : 8.8;
        $fit = fitOrWrap($pdf, (string)$item["product_name"], $maxW, $maxFont);

        if (($fit["lines"] ?? 0) === 1) {
            $pdf->SetFont('dejavusans', '', $fit["font"], '', true);
            $pdf->SetXY($margin, $y);
            $pdf->Cell($maxW, ($h <= 20 ? 3.2 : 4.0), $fit["l1"], 0, 0, "C");
            $y += ($h <= 20 ? 3.8 : 4.6);
        } elseif (($fit["lines"] ?? 0) === 2) {
            $pdf->SetFont('dejavusans', '', $fit["font"], '', true);
            $lineH = ($h <= 20) ? 3.0 : 3.6;
            $pdf->SetXY($margin, $y);
            $pdf->Cell($maxW, $lineH, $fit["l1"], 0, 0, "C");
            $pdf->SetXY($margin, $y + $lineH);
            $pdf->Cell($maxW, $lineH, $fit["l2"], 0, 0, "C");
            $y += (2 * $lineH + 0.6);
        }
    }

    /* === цена резерв === */
    $priceH = 0.0;
    $priceText = "";
    if ($withPrice && !empty($item["price"])) {
        $priceText = (string)$item["price"] . " руб";
        $priceH = ($h <= 20) ? 3.4 : 4.8;
    }

    /* === штрихкод === */
    $availableH = $h - $y - $priceH - $margin;
    $barcodeH = max(($h <= 20 ? 8.6 : 10.0), $availableH);

    $modules = estimateModules($barcodeType === "EAN13" ? "EAN13" : "C128", $code);
    $moduleW = clamp($maxW / $modules, 0.18, ($w <= 30 ? 0.30 : 0.40));

    $fontSize = ($h <= 20) ? 7 : 8;

    $style = [
        'border' => false,
        'padding' => 0,
        'fgcolor' => [0,0,0],
        'bgcolor' => false,
        'text' => true,
        'font' => 'dejavusans',
        'fontsize' => $fontSize,
        'stretchtext' => false,
    ];

    $pdf->write1DBarcode(
        $code,
        $barcodeType,
        $margin,
        $y,
        $maxW,
        $barcodeH,
        $moduleW,
        $style,
        'C'
    );

    /* === цена === */
    if ($priceH > 0) {
        $pdf->SetFont('dejavusans', '', ($h <= 20 ? 6.8 : 7.8), '', true);
        $pdf->SetXY($margin, $h - $margin - $priceH);
        $pdf->Cell($maxW, $priceH, $priceText, 0, 0, "C");
    }
}

$pdf->Output("labels.pdf", "I");
