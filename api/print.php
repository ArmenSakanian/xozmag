<?php
require_once __DIR__ . "/db.php";
require_once __DIR__ . "/libs/tcpdf/tcpdf.php";

$id = $_GET['id'] ?? 0;

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

$pdf = new TCPDF($orientation, "mm", [$width, $height], true, "UTF-8", false);
$pdf->SetMargins(0, 0, 0);
$pdf->SetAutoPageBreak(false);
$pdf->AddPage();

$style = [
    'border' => false,
    'padding' => 0,
    'fgcolor' => [0,0,0],
    'bgcolor' => false,

    'text' => true,
    'font' => 'helvetica',
    'fontsize' => 8,

    'stretch' => false,
    'stretchtext' => 0,

    'label' => $code,   // вручную выводим надпись под штрихкодом
    'fontstretch' => 100,
    'drawbars' => true,   // bars = вертикальные линии, но НЕ горизонтальная!
    'background' => false
];




// Параметры штрихкода
$left = 1;
$top = 3;
$w = $width - 2;    // 1мм поля слева и справа
$h = $height - 8;   // под текст

if ($size === "58x40") {
    $top = 4;
    $h = 28;
}

$pdf->write1DBarcode(
    $code,
    'C128B',    
    $left,
    $top,
    $w,
    $h,
    0.35,
    $style,
    'N'
);



$pdf->Output("barcode-$code.pdf", "I");
