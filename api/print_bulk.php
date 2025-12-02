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

/* === Функция уменьшения текста с переносом === */
function fitOrWrap($pdf, $text, $widthMm, $maxFont = 9) {

    $font = $maxFont;
    while ($font > 5) {
        $pdf->SetFont('dejavusans','',$font,'',true);
        if ($pdf->GetStringWidth($text) <= $widthMm)
            return ["lines"=>1,"font"=>$font];
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
            return ["lines"=>2,"font"=>$font,"l1"=>$l1,"l2"=>$l2];

        $font -= 0.4;
    }

    return ["lines"=>2,"font"=>5,"l1"=>$l1,"l2"=>$l2];
}


/* ======================================================
      ОСНОВНОЙ ЦИКЛ — ГЕНЕРАЦИЯ МНОГИХ ЭТИКЕТОК
====================================================== */
foreach ($data as $id => $opts) {

    $size      = $opts["size"] ?? "30x20";
    $withName  = !empty($opts["withName"]);
    $withPrice = !empty($opts["withPrice"]);

    /* === Получаем товар === */
    $stmt = $pdo->prepare("SELECT * FROM barcodes WHERE id = ?");
    $stmt->execute([$id]);
    $item = $stmt->fetch();
    if (!$item) continue;

    $code = str_replace("-", "", $item["barcode"]);


    /* === Размер страницы === */
    if ($size === "30x20") {
        $w = 30;
        $h = 20;
        $o = "L";
    }
    elseif ($size === "42x25") {
        $w = 42;
        $h = 25;
        $o = "L";
    }
    else {
        $w = 30;
        $h = 20;
        $o = "L";
    }

    $pdf->AddPage($o, [$w,$h]);
    $pdf->SetMargins(1,1,1);


    /* =====================================================
         НАЗВАНИЕ
    ======================================================= */

    $barcodeTop = 2;

    if ($withName && !empty($item["product_name"])) {

        $maxW = $w - 2;
        $maxFont = ($size === "30x20") ? 8 : 9;

        $fit = fitOrWrap($pdf, $item["product_name"], $maxW, $maxFont);
        $pdf->SetFont('dejavusans','',$fit["font"],'',true);

        if ($fit["lines"] == 1) {

            if ($size === "30x20") {
                $pdf->SetXY(1, 1);
                $pdf->Cell($maxW, 3.2, $item["product_name"], 0, 0, "C");
                $barcodeTop = 5;

            } else { // 42x25
                $pdf->SetXY(1, 1);
                $pdf->Cell($maxW, 4, $item["product_name"], 0, 0, "C");
                $barcodeTop = 6;
            }

        } else {

            if ($size === "30x20") {
                $pdf->SetXY(1, 1);
                $pdf->Cell($maxW, 2.8, $fit["l1"], 0, 0, "C");

                $pdf->SetXY(1, 3.8);
                $pdf->Cell($maxW, 2.8, $fit["l2"], 0, 0, "C");

                $barcodeTop = 8;

            } else { // 42x25
                $pdf->SetXY(1, 1);
                $pdf->Cell($maxW, 3.5, $fit["l1"], 0, 0, "C");

                $pdf->SetXY(1, 4.2);
                $pdf->Cell($maxW, 3.5, $fit["l2"], 0, 0, "C");

                $barcodeTop = 9;
            }
        }
    }


    /* =====================================================
            ШТРИХКОД
    ======================================================= */
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
        'border'=>false,
        'padding'=>0,
        'fgcolor'=>[0,0,0],
        'bgcolor'=>false,
        'text'=>true,
        'font'=>'dejavusans',
        'fontsize'=>7,
        'label'=>$code,
        'drawbars'=>true
    ];

    $pdf->write1DBarcode(
        $code,
        'C128B',
        1,
        $barcodeTop,
        $w - 2,
        $barcodeHeight,
        $barcodeModule,
        $style,
        'N'
    );


    /* =====================================================
            ЦЕНА
    ======================================================= */
    if ($withPrice && !empty($item["price"])) {

        if ($size === "30x20") {
            $pdf->SetFont('dejavusans','',7,'',true);
            $pdf->SetXY(1, $h - 4);
            $pdf->Cell($w - 2, 3, $item["price"] . " руб", 0, 0, "C");

        } else { // 42×25
            $pdf->SetFont('dejavusans','',8,'',true);
            $pdf->SetXY(1, $h - 5);
            $pdf->Cell($w - 2, 4, $item["price"] . " руб", 0, 0, "C");
        }
    }
}

$pdf->Output("labels.pdf", "I");
