<?php
// === Подключаем PhpSpreadsheet ===
require_once $_SERVER["DOCUMENT_ROOT"] . "/vendor/autoload.php";

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;


// ======================================================================
// 1) ПОЛУЧЕНИЕ JSON из evotor_catalog.php (правильный способ)
// ======================================================================
ob_start();
include __DIR__ . "/evotor_catalog.php";   // выполняем PHP и перехватываем вывод JSON
$json = ob_get_clean();

$data = json_decode($json, true);

if (!$data || !isset($data["products"])) {
    die("Ошибка: Неверный JSON от evotor_catalog.php");
}

$products = $data["products"];


// ======================================================================
// 2) ФУНКЦИЯ ИЗВЛЕЧЕНИЯ БРЕНДА
// ======================================================================
function extractBrand($name) {

    // Ищем ВСЕ скобки
    preg_match_all('/\((.*?)\)/u', $name, $m);

    if (empty($m[1])) {
        return "";
    }

    // Берём ПОСЛЕДНИЕ скобки
    $last = trim($m[1][count($m[1]) - 1]);

    // Если есть цифры → это размер, не бренд
    if (preg_match('/\d/', $last)) {
        return "";
    }

    // Список единиц измерения — если встречаются → это НЕ бренд
    $badUnits = ['см','mm','мм','л','ml','k','к','w','ват','вт','%', 'шт'];
    foreach ($badUnits as $unit) {
        if (mb_stripos($last, $unit) !== false) {
            return "";
        }
    }

    // Если нет букв → не бренд
    if (!preg_match('/[a-zA-Zа-яА-Я]/u', $last)) {
        return "";
    }

    // Форматирование: первая буква большая, остальные маленькие
    return mb_strtoupper(mb_substr($last, 0, 1)) . mb_strtolower(mb_substr($last, 1));
}


// ======================================================================
// 3) СОЗДАНИЕ EXCEL
// ======================================================================

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Заголовки
$sheet->setCellValue('A1', 'Штрихкод');
$sheet->setCellValue('B1', 'Название');
$sheet->setCellValue('C1', 'Бренд');

$sheet->getStyle("A1:C1")->getFont()->setBold(true);

$row = 2;

// Заполнение строк
foreach ($products as $p) {

    $barcode = $p["barcode"] ?? "";
    $name    = $p["name"] ?? "";
    $brand   = extractBrand($name);

    $sheet->setCellValue("A{$row}", $barcode);
    $sheet->setCellValue("B{$row}", $name);
    $sheet->setCellValue("C{$row}", $brand);

    $row++;
}


// ======================================================================
// 4) ОТДАЧА ФАЙЛА НА СКАЧИВАНИЕ
// ======================================================================

$filename = "evotor_brands_export_" . date("Y-m-d_H-i") . ".xlsx";

header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Cache-Control: max-age=0");

$writer = new Xlsx($spreadsheet);
$writer->save("php://output");
exit;
