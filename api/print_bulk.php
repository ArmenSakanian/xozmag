<?php
require_once __DIR__ . "/db.php";
require_once __DIR__ . "/libs/tcpdf/tcpdf.php";

header("Content-Type: application/pdf");

$dataRaw = $_GET["data"] ?? "";
$data = json_decode($dataRaw, true);

if (!$data) die("BAD DATA");

$pdf = new TCPDF();
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetAutoPageBreak(false);

/* === функция уменьшения + перенос === */
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

foreach ($data as $id => $opts) {

    $size = $opts["size"];
    $withInfo = !empty($opts["withInfo"]);

    $stmt = $pdo->prepare("SELECT * FROM barcodes WHERE id = ?");
    $stmt->execute([$id]);
    $item = $stmt->fetch();
    if (!$item) continue;

    $code = str_replace("-", "", $item["barcode"]);

    if ($size === "58x40") {
        $w = 58; $h = 40; $o = "L";
    } else {
        $w = 40; $h = 30; $o = "L";
    }

    $pdf->AddPage($o, [$w, $h]);
    $pdf->SetMargins(1, 1, 1);

    /* === НАЗВАНИЕ === */
    if ($withInfo && !empty($item["product_name"])) {

        $maxW = $w - 2;
        $fit = fitOrWrap($pdf, $item["product_name"], $maxW);

        $pdf->SetFont('dejavusans', '', $fit["font"], '', true);

        if ($fit["lines"] == 1) {
            $pdf->SetXY(1, 1);
            $pdf->Cell($maxW, 4.5, $item["product_name"], 0, 0, "C");
            $barcodeTop = 7;
        } else {
            $pdf->SetXY(1, 1);
            $pdf->Cell($maxW, 4.0, $fit["l1"], 0, 0, "C");

            $pdf->SetXY(1, 5);
            $pdf->Cell($maxW, 4.0, $fit["l2"], 0, 0, "C");

            $barcodeTop = 10;
        }

    } else {
        $barcodeTop = 2;
    }

    /* === ШТРИХКОД === */
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
        $w - 2,
        14,
        0.35,
        $style,
        'N'
    );

    /* === ЦЕНА === */
    if ($withInfo && !empty($item["price"])) {
        $pdf->SetFont('dejavusans', '', 9, '', true);
        $pdf->SetXY(1, $h - 6);
        $pdf->Cell($w - 2, 5, $item["price"] . " руб", 0, 0, "C");
    }
}

$pdf->Output("labels.pdf", "I");