<?php
require_once __DIR__ . "/db.php";
require_once __DIR__ . "/libs/tcpdf/tcpdf.php";

$id = intval($_GET['id'] ?? 0);
$withInfo = intval($_GET['withInfo'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM barcodes WHERE id = ?");
$stmt->execute([$id]);
$item = $stmt->fetch();

if (!$item) {
    die("Not found");
}

$code = str_replace("-", "", $item["barcode"]);

// === РАЗМЕРЫ ===
$size = $_GET["size"] ?? "40x30";

switch ($size) {
    case "58x40":
        $width = 58; 
        $height = 40; 
        $orientation = "L";
        break;

    default:
        $width = 40; 
        $height = 30; 
        $orientation = "L";
}

// ==== TCPDF SETUP ====
$pdf = new TCPDF($orientation, "mm", [$width, $height], true, "UTF-8", false);
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetMargins(1, 1, 1);
$pdf->SetAutoPageBreak(false);
$pdf->AddPage();
$pdf->SetFont('dejavusans', '', 9, '', true);

/* === Функция подбора шрифта + перенос === */
function fitOrWrap($pdf, $text, $widthMm, $maxFont = 10) {

    // сначала пробуем в 1 строку
    $font = $maxFont;
    while ($font > 5) {
        $pdf->SetFont('dejavusans', '', $font, '', true);
        if ($pdf->GetStringWidth($text) <= $widthMm) {
            return ["lines" => 1, "font" => $font];
        }
        $font -= 0.5;
    }

    // делим на 2 строки
    $words = explode(" ", $text);
    $line1 = "";
    $line2 = "";

    foreach ($words as $w) {
        if ($pdf->GetStringWidth($line1 . " " . $w) <= $widthMm) {
            $line1 .= ($line1 ? " " : "") . $w;
        } else {
            $line2 .= ($line2 ? " " : "") . $w;
        }
    }

    // подгоняем шрифт для двух строк
    $font = $maxFont;
    while ($font > 5) {
        $pdf->SetFont('dejavusans', '', $font, '', true);

        if (
            $pdf->GetStringWidth($line1) <= $widthMm &&
            $pdf->GetStringWidth($line2) <= $widthMm
        ) {
            return [
                "lines" => 2,
                "font"  => $font,
                "l1"    => $line1,
                "l2"    => $line2
            ];
        }

        $font -= 0.5;
    }

    // fallback
    return [
        "lines" => 2,
        "font"  => 5,
        "l1"    => $line1,
        "l2"    => $line2
    ];
}

/* ============== НАЗВАНИЕ ============== */
if ($withInfo && !empty($item["product_name"])) {

    $maxWidth = $width - 2;
    $fit = fitOrWrap($pdf, $item["product_name"], $maxWidth);

    $pdf->SetFont('dejavusans', '', $fit["font"], '', true);

    if ($fit["lines"] == 1) {
        $pdf->SetXY(1, 1);
        $pdf->Cell($maxWidth, 4.5, $item["product_name"], 0, 0, "C");
        $barcodeTop = 7;
    } else {
        $pdf->SetXY(1, 1);
        $pdf->Cell($maxWidth, 4.0, $fit["l1"], 0, 0, "C");

        $pdf->SetXY(1, 5);
        $pdf->Cell($maxWidth, 4.0, $fit["l2"], 0, 0, "C");

        $barcodeTop = 10;
    }

} else {
    $barcodeTop = 2;
}

/* ============== ШТРИХКОД ============== */
$style = [
    'border'    => false,
    'padding'   => 0,
    'fgcolor'   => [0,0,0],
    'bgcolor'   => false,
    'text'      => true,
    'font'      => 'dejavusans',
    'fontsize'  => 8,
    'label'     => $code,
    'drawbars'  => true,
];

$pdf->write1DBarcode(
    $code,
    'C128B',
    1,
    $barcodeTop,
    $width - 2,
    14,
    0.35,
    $style,
    'N'
);

/* ============== ЦЕНА ============== */
if ($withInfo && !empty($item["price"])) {
    $pdf->SetFont('dejavusans', '', 9, '', true);
    $pdf->SetXY(1, $height - 6);
    $pdf->Cell($width - 2, 5, $item["price"] . " руб", 0, 0, "C");
}

$pdf->Output("barcode-$code.pdf", "I");