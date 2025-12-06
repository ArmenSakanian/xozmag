<?php
require_once __DIR__ . "/db.php";
require_once __DIR__ . "/libs/tcpdf/tcpdf.php";

/* === ПАРАМЕТРЫ === */
$id        = intval($_GET['id'] ?? 0);
$withName  = intval($_GET['withName'] ?? 0);
$withPrice = intval($_GET['withPrice'] ?? 0);
$size      = $_GET['size'] ?? "42x25"; // теперь по умолчанию 42x25

/* === ПОЛУЧЕНИЕ ТОВАРА === */
$stmt = $pdo->prepare("SELECT * FROM barcodes WHERE id = ?");
$stmt->execute([$id]);
$item = $stmt->fetch();

if (!$item) die("Not found");

$code = str_replace("-", "", $item["barcode"]);

/* === РАЗМЕРЫ (ТЕПЕРЬ ТОЛЬКО 2 ВАРИАНТА) === */
switch ($size) {

    case "30x20":
        $width = 30;
        $height = 20;
        $orientation = "L";
        break;

    default: // 42x25
        $width = 42;
        $height = 25;
        $orientation = "L";
}

/* === TCPDF === */
$pdf = new TCPDF($orientation, "mm", [$width, $height], true, "UTF-8", false);
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetMargins(1, 1, 1);
$pdf->SetAutoPageBreak(false);
$pdf->AddPage();

/* === ФУНКЦИЯ ПОДГОНА ТЕКСТА === */
function fitOrWrap($pdf, $text, $widthMm, $maxFont = 8) {

    $font = $maxFont;
    while ($font > 5) {
        $pdf->SetFont('dejavusans', '', $font, '', true);
        if ($pdf->GetStringWidth($text) <= $widthMm)
            return ["lines" => 1, "font" => $font];
        $font -= 0.4;
    }

    $words = explode(" ", $text);
    $l1 = ""; 
    $l2 = "";

    foreach ($words as $w) {
        if ($pdf->GetStringWidth($l1 . " " . $w) <= $widthMm) {
            $l1 .= ($l1 ? " " : "") . $w;
        } else {
            $l2 .= ($l2 ? " " : "") . $w;
        }
    }

    return [
        "lines" => 2,
        "font"  => 6,
        "l1"    => $l1,
        "l2"    => $l2
    ];
}

/* === НАЗВАНИЕ === */
$barcodeTop = 2;

if ($withName && !empty($item["product_name"])) {

    $maxWidth = $width - 2;
    $maxFont = ($size === "30x20") ? 7 : 8;

    $fit = fitOrWrap($pdf, $item["product_name"], $maxWidth, $maxFont);
    $pdf->SetFont('dejavusans', '', $fit["font"], '', true);

    if ($fit["lines"] == 1) {
        $pdf->SetXY(1, 1);
        $pdf->Cell($maxWidth, 3.5, $item["product_name"], 0, 0, "C");
        $barcodeTop = ($size === "30x20") ? 5 : 6;
    } else {
        $pdf->SetXY(1, 1);
        $pdf->Cell($maxWidth, 3.2, $fit["l1"], 0, 0, "C");

        $pdf->SetXY(1, 4);
        $pdf->Cell($maxWidth, 3.2, $fit["l2"], 0, 0, "C");

        $barcodeTop = ($size === "30x20") ? 7.5 : 9;
    }
}

/* === ШТРИХКОД === */
if ($size === "30x20") {
    $barcodeHeight = 9;
    $barcodeModule = 0.25;
} else { // 42x25
    $barcodeHeight = 10;
    $barcodeModule = 0.30;
}

$style = [
    'border' => false,
    'padding' => 0,
    'fgcolor' => [0,0,0],
    'bgcolor' => false,
    'text' => true,
    'font' => 'dejavusans',
    'fontsize' => 7,
    'label' => $code,
    'drawbars' => true,
];

$pdf->write1DBarcode(
    $code,
    'C128B',
    1,
    $barcodeTop,
    $width - 2,
    $barcodeHeight,
    $barcodeModule,
    $style,
    'N'
);

/* === ЦЕНА === */
if ($withPrice && !empty($item["price"])) {

    if ($size === "30x20") {
        $pdf->SetFont('dejavusans', '', 6.5, '', true);
        $pdf->SetXY(1, $height - 4);
        $pdf->Cell($width - 2, 3, $item["price"] . " руб", 0, 0, "C");
    }

    else { // 42x25
        $pdf->SetFont('dejavusans', '', 7.5, '', true);
        $pdf->SetXY(1, $height - 5);
        $pdf->Cell($width - 2, 4, $item["price"] . " руб", 0, 0, "C");
    }
}

$pdf->Output("barcode-$code.pdf", "I");