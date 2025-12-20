<?php
require_once __DIR__ . "/../db.php";
require_once __DIR__ . "/../libs/tcpdf/tcpdf.php";

header("Content-Type: application/pdf");

$dataRaw = $_GET["data"] ?? "";
$data = json_decode($dataRaw, true);

if (!$data) die("BAD DATA");

$pdf = new TCPDF();
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetAutoPageBreak(false);

/* === Функция уменьшения текста с переносом === */
function fitOrWrap($pdf, $text, $widthMm, $maxFont = 10) {

    $font = $maxFont;
    while ($font > 5) {
        $pdf->SetFont('dejavusans','',$font,'',true);
        if ($pdf->GetStringWidth($text) <= $widthMm)
            return ["lines"=>1,"font"=>$font];
        $font -= 0.5;
    }

    $words = explode(" ", $text);
    $l1=""; $l2="";

    foreach ($words as $w) {
        if ($pdf->GetStringWidth($l1." ".$w) <= $widthMm)
            $l1 .= ($l1?" ":"") . $w;
        else
            $l2 .= ($l2?" ":"") . $w;
    }

    $font=$maxFont;
    while ($font>5) {
        $pdf->SetFont('dejavusans','',$font,'',true);
        if (
            $pdf->GetStringWidth($l1) <= $widthMm &&
            $pdf->GetStringWidth($l2) <= $widthMm
        ) {
            return ["lines"=>2,"font"=>$font,"l1"=>$l1,"l2"=>$l2];
        }
        $font -= 0.5;
    }

    return ["lines"=>2,"font"=>5,"l1"=>$l1,"l2"=>$l2];
}


/* ======================================================
      ОСНОВНОЙ ЦИКЛ — ГЕНЕРАЦИЯ МНОГИХ ЭТИКЕТОК
====================================================== */
foreach ($data as $id => $opts) {

    $size      = $opts["size"] ?? "40x30";
    $withName  = !empty($opts["withName"]);
    $withPrice = !empty($opts["withPrice"]);

    /* === Получаем товар === */
    $stmt = $pdo->prepare("SELECT * FROM barcodes WHERE id = ?");
    $stmt->execute([$id]);
    $item = $stmt->fetch();
    if (!$item) continue;

    $code = str_replace("-", "", $item["barcode"]);


    /* === Размер страницы === */
    if ($size === "58x40") {
        $w=58; $h=40; $o="L";
    }
    elseif ($size === "42x25") {
        $w=42; $h=25; $o="L";
    }
    else {
        $w=40; $h=30; $o="L";
    }

    $pdf->AddPage($o, [$w,$h]);
    $pdf->SetMargins(1,1,1);


    /* =====================================================
         БЛОК НАЗВАНИЯ (отдельно)
    ======================================================= */
    $barcodeTop = 2;

    if ($withName && !empty($item["product_name"])) {

        $maxW = $w - 2;
        $maxFont = ($size === "42x25") ? 8 : 10;

        $fit = fitOrWrap($pdf, $item["product_name"], $maxW, $maxFont);
        $pdf->SetFont('dejavusans','',$fit["font"],'',true);

        if ($fit["lines"] == 1) {

            // 1 строка
            if ($size === "42x25") {
                $pdf->SetXY(1,1);
                $pdf->Cell($maxW,3.2,$item["product_name"],0,0,"C");
                $barcodeTop = 6;
            } else {
                $pdf->SetXY(1,1);
                $pdf->Cell($maxW,4,$item["product_name"],0,0,"C");
                $barcodeTop = 7;
            }

        } else {

            // 2 строки
            if ($size === "42x25") {
                $pdf->SetXY(1,1);
                $pdf->Cell($maxW,3,$fit["l1"],0,0,"C");

                $pdf->SetXY(1,4);
                $pdf->Cell($maxW,3,$fit["l2"],0,0,"C");

                $barcodeTop = 9;

            } else {
                $pdf->SetXY(1,1);
                $pdf->Cell($maxW,4,$fit["l1"],0,0,"C");

                $pdf->SetXY(1,5);
                $pdf->Cell($maxW,4,$fit["l2"],0,0,"C");

                $barcodeTop = 10;
            }
        }
    }


    /* =====================================================
            ШТРИХКОД
    ======================================================= */
    $barcodeHeight = ($size==="42x25") ? 10 : 14;
    $barcodeModule = ($size==="42x25") ? 0.30 : 0.35;

    $style = [
        'border'=>false,
        'padding'=>0,
        'fgcolor'=>[0,0,0],
        'bgcolor'=>false,
        'text'=>true,
        'font'=>'dejavusans',
        'fontsize'=>8,
        'label'=>$code,
        'drawbars'=>true
    ];

    $pdf->write1DBarcode(
        $code,
        'C128B',
        1,
        $barcodeTop,
        $w-2,
        $barcodeHeight,
        $barcodeModule,
        $style,
        'N'
    );

    /* =====================================================
            ЦЕНА
    ======================================================= */
    if ($withPrice && !empty($item["price"])) {

        if ($size === "42x25") {
            $pdf->SetFont('dejavusans','',7.5,'',true);
            $pdf->SetXY(1, $h-4);
            $pdf->Cell($w-2,3.5,$item["price"]." руб",0,0,"C");
        } else {
            $pdf->SetFont('dejavusans','',9,'',true);
            $pdf->SetXY(1, $h-6);
            $pdf->Cell($w-2,5,$item["price"]." руб",0,0,"C");
        }
    }
}

$pdf->Output("labels.pdf","I");