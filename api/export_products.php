<?php
require_once $_SERVER["DOCUMENT_ROOT"] . "/vendor/autoload.php";
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// === правильный путь к API ===
$domain = $_SERVER['HTTP_HOST'];
$url = "https://{$domain}/api/vitrina/evotor_catalog.php";

// === получаем JSON ===
$json = file_get_contents($url);
$data = json_decode($json, true);
$products = $data["products"] ?? [];

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Заголовки
$headers = [
    "A1" => "Название",
    "B1" => "Артикул",
    "C1" => "Штрихкод",
    "D1" => "Цена",
    "E1" => "Фото (URL)",
    "F1" => "Тип товара",
    "G1" => "Бренд"
];

foreach ($headers as $cell => $text) {
    $sheet->setCellValue($cell, $text);
}

$row = 2;
foreach ($products as $p) {

    $sheet->setCellValue("A{$row}", $p["name"]);
    $sheet->setCellValue("B{$row}", $p["article"]);
    $sheet->setCellValue("C{$row}", $p["barcode"]);
    $sheet->setCellValue("D{$row}", $p["price"]);
    $sheet->setCellValue("E{$row}", $p["images"][0] ?? "");
    $sheet->setCellValue("F{$row}", $p["typeName"]);
    $sheet->setCellValue("G{$row}", $p["brandName"]);

    $row++;
}

$filename = "products_export_" . date("Y-m-d_H-i") . ".xlsx";

header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Cache-Control: max-age=0");

$writer = new Xlsx($spreadsheet);
$writer->save("php://output");
exit;
