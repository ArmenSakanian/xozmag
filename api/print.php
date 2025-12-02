<?php
require_once __DIR__ . "/db.php";
require_once __DIR__ . "/libs/tcpdf/tcpdf.php";

/* === ПАРАМЕТРЫ === */
$id        = intval($_GET['id'] ?? 0);
$withName  = intval($_GET['withName'] ?? 0);
$withPrice = intval($_GET['withPrice'] ?? 0);
$size      = $_GET['size'] ?? "30x20";

/* === ПОЛУЧЕНИЕ ТОВАРА === */
$stmt = $pdo->prepare("SELECT * FROM barcodes WHERE id = ?");
$stmt->execute([$id]);
$item = $stmt->fetch();

if (!$item) die("Not found");

$code = str_replace("-", "", $item["barcode"]);

/* === РАЗМЕРЫ === */
switch ($size) {

    case "30x20":
        $width = 30;
        $height = 20;
        $orientation = "L";
        break;

    case "42x25":
        $width = 42;
        $height = 25;
        $orientation = "L";
        break;

    default:
        // если передали неизвестный размер — считаем как 30×20
        $width = 30;
        $height = 20;
        $orientation = "L";
}

/* === TCPDF === */
$pdf = new TCPDF($orientation, "mm", [$width, $height], true, "UTF-8", false);
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetMargins(1, 1, 1);
$pdf->SetAutoPageBreak(false);
$pdf->AddPage();

/* === функция уменьшения текста с переносом === */
function fitOrWrap($pdf, $text, $widthMm, $maxFont = 9) {

    $font = $maxFont;
    while ($font > 5) {
        $pdf->SetFont('dejavusans','',$font,'',true);
        if ($pdf->GetStringWidth($text) <= $widthMm)
            return ["lines" => 1, "font" => $font];
        $font -= 0.4;
    }

    $words = explode(" ", $text);
    $l1 = "";
    $l2 = "";

    foreach ($words as $w) {
        if ($pdf->GetStringWidth($l1 . " " . $w) <= $widthMm)
            $l1 .= ($l1 ? " " : "") . $w;
        else
            $l2 .= ($l2 ? " " : "") . $w;
    }

    $font = $maxFont;
    while ($font > 5) {
        $pdf->SetFont('dejavusans','',$font,'',true);
        if (
            $pdf->GetStringWidth($l1) <= $widthMm &&
            $pdf->GetStringWidth($l2) <= $widthMm
        )
            return ["lines" => 2, "font" => $font, "l1" => $l1, "l2" => $l2];
        $font -= 0.4;
    }

    return ["lines" => 2, "font" => 5, "l1" => $l1, "l2" => $l2];
}


/* ==========================================================
                  БЛОК НАЗВАНИЯ
========================================================== */

$barcodeTop = 2;

if ($withName && !empty($item["product_name"])) {

    $maxWidth = $width - 2;
    $maxFont = ($size === "30x20") ? 8 : 9;

    $fit = fitOrWrap($pdf, $item["product_name"], $maxWidth, $maxFont);
    $pdf->SetFont('dejavusans','',$fit["font"],'',true);

    if ($fit["lines"] == 1) {

        if ($size === "30x20") {
            $pdf->SetXY(1, 1);
            $pdf->Cell($maxWidth, 3.2, $item["product_name"], 0, 0, "C");
            $barcodeTop = 5;
        } else { // 42x25
            $pdf->SetXY(1, 1);
            $pdf->Cell($maxWidth, 4, $item["product_name"], 0, 0, "C");
            $barcodeTop = 6;
        }

    } else {

        if ($size === "30x20") {
            $pdf->SetXY(1,1);
            $pdf->Cell($maxWidth,2.8,$fit["l1"],0,0,"C");

            $pdf->SetXY(1,3.8);
            $pdf->Cell($maxWidth,2.8,$fit["l2"],0,0,"C");

            $barcodeTop = 8;

        } else { // 42x25
            $pdf->SetXY(1,1);
            $pdf->Cell($maxWidth,3.5,$fit["l1"],0,0,"C");

            $pdf->SetXY(1,4.2);
            $pdf->Cell($maxWidth,3.5,$fit["l2"],0,0,"C");

            $barcodeTop = 9;
        }
    }
}


/* ==========================================================
                     ШТРИХКОД
========================================================== */

if ($size === "30x20") {
    $barcodeHeight = 8;
    $barcodeModule = 0.28;
}
elseif ($size === "42x25") {
    $barcodeHeight = 10;
    $barcodeModule = 0.30;
}
else {
    $barcodeHeight = 8;
    $barcodeModule = 0.28;
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


/* ==========================================================
                       ЦЕНА
========================================================== */

if ($withPrice && !empty($item["price"])) {

    if ($size === "30x20") {
        $pdf->SetFont('dejavusans','',7,'',true);
        $pdf->SetXY(1, $height - 4);
        $pdf->Cell($width - 2, 3, $item["price"] . " руб", 0, 0, "C");

    } else { // 42×25
        $pdf->SetFont('dejavusans','',8,'',true);
        $pdf->SetXY(1, $height - 5);
        $pdf->Cell($width - 2, 4, $item["price"] . " руб", 0, 0, "C");
    }
}

$pdf->Output("barcode-$code.pdf", "I");
