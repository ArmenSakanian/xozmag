<?php
require_once __DIR__ . "/db.php";
require_once __DIR__ . "/libs/tcpdf/tcpdf.php";

/* === ПАРАМЕТРЫ === */
$id        = intval($_GET['id'] ?? 0);
$withName  = intval($_GET['withName'] ?? 0);
$withPrice = intval($_GET['withPrice'] ?? 0);
$size      = $_GET['size'] ?? "40x30";

/* === ПОЛУЧЕНИЕ ТОВАРА === */
$stmt = $pdo->prepare("SELECT * FROM barcodes WHERE id = ?");
$stmt->execute([$id]);
$item = $stmt->fetch();

if (!$item) die("Not found");

$code = str_replace("-", "", $item["barcode"]);

/* === РАЗМЕРЫ === */
switch ($size) {

  case "58x40":
    $width = 58;
    $height = 40;
    $orientation = "L";
    break;

  case "42x25":
    $width = 42;
    $height = 25;
    $orientation = "L";
    break;

  default:
    $width = 40;
    $height = 30;
    $orientation = "L";
}

/* === TCPDF === */
$pdf = new TCPDF($orientation, "mm", [$width, $height], true, "UTF-8", false);
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetMargins(1, 1, 1);
$pdf->SetAutoPageBreak(false);
$pdf->AddPage();

/* === функция уменьшения текста === */
function fitOrWrap($pdf, $text, $widthMm, $maxFont = 10) {

    $font = $maxFont;
    while ($font > 5) {
        $pdf->SetFont('dejavusans', '', $font, '', true);
        if ($pdf->GetStringWidth($text) <= $widthMm)
            return ["lines" => 1, "font" => $font];
        $font -= 0.5;
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

    $font = $maxFont;
    while ($font > 5) {
        $pdf->SetFont('dejavusans', '', $font, '', true);
        if (
            $pdf->GetStringWidth($l1) <= $widthMm &&
            $pdf->GetStringWidth($l2) <= $widthMm
        ) {
            return ["lines" => 2, "font" => $font, "l1" => $l1, "l2" => $l2];
        }
        $font -= 0.5;
    }

    return ["lines" => 2, "font" => 5, "l1" => $l1, "l2" => $l2];
}

/* === НАЗВАНИЕ === */
$barcodeTop = 2;

if ($withName && !empty($item["product_name"])) {

    $maxWidth = $width - 2;
    $maxFont = ($size === "42x25") ? 8 : 10;

    $fit = fitOrWrap($pdf, $item["product_name"], $maxWidth, $maxFont);
    $pdf->SetFont('dejavusans', '', $fit["font"], '', true);

    if ($fit["lines"] == 1) {
        $pdf->SetXY(1, 1);
        $pdf->Cell($maxWidth, 4, $item["product_name"], 0, 0, "C");
        $barcodeTop = ($size === "42x25") ? 6 : 7;
    } else {
        $pdf->SetXY(1, 1);
        $pdf->Cell($maxWidth, 3.5, $fit["l1"], 0, 0, "C");

        $pdf->SetXY(1, 4.5);
        $pdf->Cell($maxWidth, 3.5, $fit["l2"], 0, 0, "C");

        $barcodeTop = ($size === "42x25") ? 9 : 10;
    }
}

/* === ШТРИХКОД === */
$barcodeHeight = ($size === "42x25") ? 10 : 14;
$barcodeModule = ($size === "42x25") ? 0.30 : 0.35;

$style = [
    'border' => false,
    'padding' => 0,
    'fgcolor' => [0,0,0],
    'bgcolor' => false,
    'text' => true,
    'font' => 'dejavusans',
    'fontsize' => 8,
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

    if ($size === "42x25") {
        $pdf->SetFont('dejavusans', '', 7.5, '', true);
        $pdf->SetXY(1, $height - 4);
        $pdf->Cell($width - 2, 3.5, $item["price"] . " руб", 0, 0, "C");
    }

    else {
        $pdf->SetFont('dejavusans', '', 9, '', true);
        $pdf->SetXY(1, $height - 6);
        $pdf->Cell($width - 2, 5, $item["price"] . " руб", 0, 0, "C");
    }
}

$pdf->Output("barcode-$code.pdf", "I");