<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . "/../../db.php";
require_once $_SERVER['DOCUMENT_ROOT'] . "/vendor/autoload.php";

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

/* =====================================================
   Проверка файла
===================================================== */

if (!isset($_FILES["file"]) || $_FILES["file"]["error"] !== UPLOAD_ERR_OK) {
    echo json_encode(["success" => false, "error" => "Файл не загружен"], JSON_UNESCAPED_UNICODE);
    exit;
}

$tmpPath = $_FILES["file"]["tmp_name"];

/* =====================================================
   Чтение Excel файла
===================================================== */

try {
    $spreadsheet = IOFactory::load($tmpPath);
    $sheet = $spreadsheet->getActiveSheet();
} catch (Exception $e) {
    echo json_encode(["success" => false, "error" => "Ошибка чтения Excel: " . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    exit;
}

$highestRow    = $sheet->getHighestRow();
$highestColumn = $sheet->getHighestColumn();
$highestColIdx = Coordinate::columnIndexFromString($highestColumn);

/* =====================================================
   НОРМАЛИЗАЦИЯ ЗАГОЛОВКОВ
===================================================== */

function norm($t) {
    return mb_strtolower(trim(preg_replace('~\s+~u', ' ', $t)));
}

$MAIN_SKIP = ["название", "наименование", "name"];
$CATEGORY_HEADERS = ["категория", "группа"];

/* =====================================================
   Разбор заголовков
===================================================== */

$columns = [];

for ($col = 1; $col <= $highestColIdx; $col++) {

    $letter = Coordinate::stringFromColumnIndex($col);
    $raw = trim((string)$sheet->getCell($letter."1")->getValue());
    $norm = norm($raw);

    if ($raw === "") continue;

    if (in_array($norm, $MAIN_SKIP)) {
        // пропускаем название товара
        continue;
    }

    if (in_array($norm, $CATEGORY_HEADERS)) {
        $columns[$col] = ["type" => "category"];
        continue;
    }

    if ($norm === "штрихкод" || $norm === "штрих-код" || $norm === "barcode") {
        $columns[$col] = ["type" => "barcode"];
        continue;
    }

    // остальные — характеристики
    $columns[$col] = [
        "type" => "attr",
        "name" => $raw,
        "attr_id" => getOrCreateAttributeId($pdo, $raw)
    ];
}

if (empty($columns)) {
    echo json_encode(["success" => false, "error" => "Не найдено ни одного корректного столбца"], JSON_UNESCAPED_UNICODE);
    exit;
}

/* =====================================================
   Подготовка отчёта
===================================================== */

$report = new Spreadsheet();
$sheetR = $report->getActiveSheet();
$sheetR->setTitle("Импорт отчёт");
$sheetR->fromArray(["Barcode", "Название товара", "Статус", "Комментарий"], NULL, 'A1');

$reportRow = 2;

/* =====================================================
   Основной цикл
===================================================== */

for ($row = 2; $row <= $highestRow; $row++) {

    $barcode = null;
    $categoryPath = null;
    $attrs = [];

    // читаем строку
    foreach ($columns as $col => $meta) {
        $letter = Coordinate::stringFromColumnIndex($col);
        $value = trim((string)$sheet->getCell($letter.$row)->getValue());

        if ($meta["type"] === "barcode") {
            $barcode = $value;
        }
        elseif ($meta["type"] === "category") {
            $categoryPath = $value;
        }
        elseif ($meta["type"] === "attr") {
            if ($value !== "") {
                $attrs[] = [
                    "attr_id" => $meta["attr_id"],
                    "value"   => $value,
                ];
            }
        }
    }

    if (!$barcode) continue; // пустая строка

    /* =====================================================
       Находим товар по barcode
    ====================================================== */

    $stmt = $pdo->prepare("SELECT id, name FROM products WHERE barcode = :bc LIMIT 1");
    $stmt->execute([":bc" => $barcode]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {

        // === Товар отсутствует → в отчёт "NOT FOUND"
        addReport($sheetR, $reportRow, $barcode, "", "NOT FOUND", "Товар с таким штрихкодом отсутствует");
        continue;
    }

    $productId = (int)$product["id"];
    $productName = $product["name"] ?? "";

    /* =====================================================
       Категория
    ====================================================== */

    if ($categoryPath) {
        $categoryId = getOrCreateCategoryPath($pdo, $categoryPath);

        if ($categoryId) {
            $pdo->prepare("UPDATE products SET category_id = :cid WHERE id = :id")
                ->execute([":cid" => $categoryId, ":id" => $productId]);
        }
    }

    /* =====================================================
       Характеристики
    ====================================================== */

    foreach ($attrs as $a) {

        // есть ли характеристика у товара?
        $stmt = $pdo->prepare("
            SELECT id, value 
            FROM product_attribute_values 
            WHERE product_id = :pid AND attribute_id = :aid
            LIMIT 1
        ");
        $stmt->execute([
            ":pid" => $productId,
            ":aid" => $a["attr_id"]
        ]);

        $rowAttr = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$rowAttr) {
            // создаём
            $pdo->prepare("
                INSERT INTO product_attribute_values (product_id, attribute_id, value)
                VALUES (:pid, :aid, :val)
            ")->execute([
                ":pid" => $productId,
                ":aid" => $a["attr_id"],
                ":val" => $a["value"]
            ]);

            continue;
        }

        // если такое же — пропускаем
        if ($rowAttr["value"] === $a["value"]) {
            continue;
        }

        // обновляем
        $pdo->prepare("
            UPDATE product_attribute_values 
            SET value = :v 
            WHERE id = :id
        ")->execute([
            ":v"  => $a["value"],
            ":id" => $rowAttr["id"]
        ]);
    }

    // === В отчёт — FOUND + UPDATE
    addReport($sheetR, $reportRow, $barcode, $productName, "UPDATED", "Характеристики обновлены");
}

/* =====================================================
   Сохраняем отчёт
===================================================== */

$savePath = $_SERVER["DOCUMENT_ROOT"] . "/api/admin/product/import_report.xlsx";
$writer = new Xlsx($report);
$writer->save($savePath);

echo json_encode([
    "success" => true,
    "report"  => "/api/admin/product/import_report.xlsx"
], JSON_UNESCAPED_UNICODE);


/* =====================================================
   ФУНКЦИИ
===================================================== */

function addReport($sheetR, &$row, $barcode, $name, $status, $comment) {
    $sheetR->setCellValue("A".$row, $barcode);
    $sheetR->setCellValue("B".$row, $name);
    $sheetR->setCellValue("C".$row, $status);
    $sheetR->setCellValue("D".$row, $comment);
    $row++;
}

function getOrCreateAttributeId(PDO $pdo, string $name): int {
    $stmt = $pdo->prepare("SELECT id FROM product_attributes WHERE name = :n LIMIT 1");
    $stmt->execute([":n" => $name]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) return (int)$row["id"];

    $pdo->prepare("INSERT INTO product_attributes (name) VALUES (:n)")
        ->execute([":n" => $name]);

    return (int)$pdo->lastInsertId();
}

function getOrCreateCategoryPath(PDO $pdo, string $path): int {

    $parts = array_filter(array_map('trim', explode("/", $path)));
    if (!$parts) return 0;

    $parent = null;
    $parentLevelCode = "";
    $parentLevel = 0;

    foreach ($parts as $name) {

        // ищем существующую категорию
        if ($parent === null) {
            $stmt = $pdo->prepare("SELECT * FROM categories WHERE name = :n AND parent_id IS NULL LIMIT 1");
            $stmt->execute([":n" => $name]);
        } else {
            $stmt = $pdo->prepare("SELECT * FROM categories WHERE name = :n AND parent_id = :p LIMIT 1");
            $stmt->execute([":n" => $name, ":p" => $parent]);
        }

        $cat = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($cat) {
            // категория существует — обновляем данные для следующего уровня
            $parent = (int)$cat["id"];
            $parentLevel = (int)$cat["level"];
            $parentLevelCode = (string)$cat["level_code"];
            continue;
        }

        // === СОЗДАЁМ НОВУЮ КАТЕГОРИЮ ===

        // уровень = уровень родителя + 1
        $level = $parentLevel + 1;

        // level_code = уровень родителя + "/" + ID (временно пусто, обновим после INSERT)
        $level_code = ""; // пока пусто, обновим после вставки

        $stmt = $pdo->prepare("
            INSERT INTO categories (name, parent_id, level, level_code, position, path)
            VALUES (:n, :p, :l, :lc, 0, '')
        ");
        $stmt->execute([
            ":n"  => $name,
            ":p"  => $parent,
            ":l"  => $level,
            ":lc" => $level_code
        ]);

        $newId = (int)$pdo->lastInsertId();

        // формируем корректный level_code
        if ($parent === null) {
            $newLevelCode = "." . $newId;
        } else {
            $newLevelCode = $parentLevelCode . "." . $newId;
        }

        $pdo->prepare("UPDATE categories SET level_code = :lc WHERE id = :id")
            ->execute([
                ":lc" => $newLevelCode,
                ":id" => $newId
            ]);

        // готовим данные для следующего уровня
        $parent = $newId;
        $parentLevelCode = $newLevelCode;
        $parentLevel = $level;
    }

    return $parent ?? 0;
}


?>
