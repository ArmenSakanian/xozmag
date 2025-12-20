<?php
header("Content-Type: application/json; charset=utf-8");
require_once __DIR__ . "/../../db.php";

function jexit($arr, $code = 200) {
    http_response_code($code);
    echo json_encode($arr, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

function norm_header($s) {
    $s = mb_strtolower(trim((string)$s), "UTF-8");
    $s = preg_replace('/\s+/u', ' ', $s);
    $s = preg_replace('/[^\p{L}\p{N}\s\-_]+/u', '', $s);
    return $s;
}

/**
 * НОРМАЛИЗАЦИЯ ШТРИХКОДОВ В ОДНОЙ ЯЧЕЙКЕ:
 * - принимает: "4607...,4607..." или "4607...; 4607..." или с пробелами
 * - приводит к каноническому виду: "4607...,4607..." (без пробелов)
 * - проверяет, что каждый штрихкод состоит только из цифр и не пустой
 * - удаляет дубликаты, сортирует (чтобы "a,b" и "b,a" считались одинаковыми)
 */
function norm_barcode_list($s) {
    $s = trim((string)$s);
    if ($s === "") return "";

    // убрать пробелы/неразрывные пробелы
    $s = str_replace(["\u{00A0}", " "], "", $s);

    // разрешим ; как разделитель, нормализуем в ,
    $s = str_replace(";", ",", $s);

    // если кто-то вставил несколько запятых подряд
    $s = preg_replace('/,+/u', ',', $s);
    $s = trim($s, ",");

    if ($s === "") return "";

    $parts = explode(",", $s);
    $clean = [];

    foreach ($parts as $p) {
        $p = trim($p);
        if ($p === "") continue;

        // оставим только цифры (если внезапно были символы)
        $p2 = preg_replace('/[^\d]+/u', '', $p);
        if ($p2 === "") return ""; // если часть невалидная

        $clean[] = $p2;
    }

    if (empty($clean)) return "";

    // уник + сорт для стабильного ключа
    $clean = array_values(array_unique($clean));
    sort($clean, SORT_STRING);

    return implode(",", $clean);
}

function to_int($v) {
    if ($v === null) return null;
    $s = trim((string)$v);
    if ($s === "") return null;
    $s = str_replace(",", ".", $s);
    if (!preg_match('/-?\d+(\.\d+)?/u', $s, $m)) return null;
    $i = (int)floor((float)$m[0]);
    if ($i < 0) $i = 0;
    return $i;
}

function parse_csv_rows($filepath) {
    $rows = [];
    $fh = fopen($filepath, "r");
    if (!$fh) return $rows;

    $first = fgets($fh);
    if ($first === false) { fclose($fh); return $rows; }
    $delim = (substr_count($first, ";") >= substr_count($first, ",")) ? ";" : ",";
    rewind($fh);

    while (($data = fgetcsv($fh, 0, $delim)) !== false) {
        if (!empty($data[0])) $data[0] = preg_replace('/^\xEF\xBB\xBF/', '', $data[0]);
        $rows[] = $data;
        if (count($rows) > 50000) break;
    }
    fclose($fh);
    return $rows;
}

function col_letter_to_index($letters) {
    $letters = strtoupper($letters);
    $n = 0;
    for ($i = 0; $i < strlen($letters); $i++) {
        $c = ord($letters[$i]) - 64;
        if ($c < 1 || $c > 26) continue;
        $n = $n * 26 + $c;
    }
    return $n;
}

function xlsx_read_first_sheet_rows($filepath) {
    $zip = new ZipArchive();
    if ($zip->open($filepath) !== true) return [];

    $sharedStrings = [];
    $ss = $zip->getFromName("xl/sharedStrings.xml");
    if ($ss !== false) {
        $xml = @simplexml_load_string($ss);
        if ($xml) {
            foreach ($xml->si as $si) {
                if (isset($si->t)) {
                    $sharedStrings[] = (string)$si->t;
                } else {
                    $acc = "";
                    if (isset($si->r)) foreach ($si->r as $r) $acc .= (string)$r->t;
                    $sharedStrings[] = $acc;
                }
            }
        }
    }

    $sheetPath = "xl/worksheets/sheet1.xml";
    $workbook = $zip->getFromName("xl/workbook.xml");
    $rels = $zip->getFromName("xl/_rels/workbook.xml.rels");

    if ($workbook !== false && $rels !== false) {
        $wb = @simplexml_load_string($workbook);
        $rl = @simplexml_load_string($rels);
        if ($wb && $rl) {
            $relationships = [];
            foreach ($rl->Relationship as $rel) {
                $relationships[(string)$rel["Id"]] = (string)$rel["Target"];
            }
            $ns = $wb->getNamespaces(true);
            if (isset($wb->sheets->sheet[0])) {
                $sheet = $wb->sheets->sheet[0];
                $rid = (string)$sheet->attributes($ns['r'])["id"];
                if ($rid && isset($relationships[$rid])) {
                    $sheetPath = "xl/" . ltrim($relationships[$rid], "/");
                }
            }
        }
    }

    $sheetXml = $zip->getFromName($sheetPath);
    if ($sheetXml === false) { $zip->close(); return []; }

    $sx = @simplexml_load_string($sheetXml);
    
    if (!$sx) { $zip->close(); return []; }

    $sx->registerXPathNamespace("x", "http://schemas.openxmlformats.org/spreadsheetml/2006/main");
    $rowNodes = $sx->xpath("//x:sheetData/x:row");
    if (!$rowNodes) { $zip->close(); return []; }

    $rows = [];
    foreach ($rowNodes as $row) {
        $r = [];
$ns = "http://schemas.openxmlformats.org/spreadsheetml/2006/main";
$cells = $row->children($ns)->c;
        if ($cells) {
            foreach ($cells as $c) {
                $ref = (string)$c["r"];
                preg_match('/^([A-Z]+)\d+$/', strtoupper($ref), $m);
                $colLetters = $m[1] ?? "";
                $colIndex = $colLetters ? col_letter_to_index($colLetters) : null;
                if (!$colIndex) continue;

                $type = (string)$c["t"];
                $vNode = $c->v;
                $value = "";

                if ($type === "s") {
                    $idx = (int)$vNode;
                    $value = $sharedStrings[$idx] ?? "";
                } elseif ($type === "inlineStr") {
    $ns = "http://schemas.openxmlformats.org/spreadsheetml/2006/main";
    $value = "";
    $cns = $c->children($ns);
    if (isset($cns->is)) {
        $isns = $cns->is->children($ns);
        if (isset($isns->t)) $value = (string)$isns->t;
    }
}
 else {
                    $value = isset($vNode) ? (string)$vNode : "";
                }

                $r[$colIndex] = $value;
            }
        }

        if (!empty($r)) {
            $maxCol = max(array_keys($r));
            $line = [];
            for ($i = 1; $i <= $maxCol; $i++) $line[] = $r[$i] ?? "";
            $rows[] = $line;
        } else {
            $rows[] = [];
        }

        if (count($rows) > 50000) break;
    }

    $zip->close();
    return $rows;
}

function read_rows_from_file($filepath, $ext) {
    if ($ext === "csv") return parse_csv_rows($filepath);
    if ($ext === "xlsx") return xlsx_read_first_sheet_rows($filepath);
    return [];
}

// ----------------- main -----------------
if ($_SERVER["REQUEST_METHOD"] !== "POST") jexit(["error" => "Only POST"], 405);
if (!isset($_FILES["file"]) || !is_uploaded_file($_FILES["file"]["tmp_name"])) jexit(["error" => "Файл не загружен (file)"], 400);

$dryRun = isset($_POST["dry_run"]) && $_POST["dry_run"] == "1";

$maxSize = 15 * 1024 * 1024;
if (!empty($_FILES["file"]["size"]) && $_FILES["file"]["size"] > $maxSize) jexit(["error" => "Файл слишком большой. Макс 15MB."], 400);

$name = $_FILES["file"]["name"] ?? "upload";
$tmp  = $_FILES["file"]["tmp_name"];
$ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
if (!in_array($ext, ["xlsx", "csv"], true)) jexit(["error" => "Поддерживаются только .xlsx или .csv"], 400);

$rows = read_rows_from_file($tmp, $ext);
if (!$rows || count($rows) < 1) jexit(["error" => "Не удалось прочитать файл или он пустой"], 400);

$header = $rows[0] ?? [];
if (!$header || count($header) < 2) jexit(["error" => "В первой строке должны быть заголовки колонок"], 400);

$hmap = [];
foreach ($header as $i => $val) $hmap[$i] = norm_header($val);

$barcodeCol = null;
$minCol = null;

foreach ($hmap as $i => $h) {
    if ($barcodeCol === null && ($h === "штрихкод" || $h === "barcode" || str_contains($h, "штрих") || str_contains($h, "barcod"))) {
        $barcodeCol = $i;
    }
    if ($minCol === null && (
        $h === "минимальный остаток" ||
        (str_contains($h, "миним") && str_contains($h, "остат")) ||
        $h === "min_stock" ||
        str_contains($h, "min stock")
    )) {
        $minCol = $i;
    }
}

if ($barcodeCol === null || $minCol === null) {
    jexit([
        "error" => "Не нашёл колонки. Нужны заголовки: 'Штрихкод' и 'Минимальный остаток'.",
        "detected_headers" => $header
    ], 400);
}

$items = []; // barcode_string => min_stock
$invalid = [];
$parsed = 0;

for ($r = 1; $r < count($rows); $r++) {
    $line = $rows[$r];
    if (!is_array($line)) continue;

    $rawBarcode = $line[$barcodeCol] ?? "";
    $rawMin = $line[$minCol] ?? "";

    $barcodeStr = norm_barcode_list($rawBarcode); // <-- ВАЖНО: список в одной строке
    $minStock = to_int($rawMin);

    if ($barcodeStr === "" && ($rawMin === "" || $rawMin === null)) continue;

    if ($barcodeStr === "") {
        if (count($invalid) < 50) $invalid[] = ["row" => $r + 1, "error" => "Пустой/неверный штрихкод(ы)"];
        continue;
    }
    if ($minStock === null) {
        if (count($invalid) < 50) $invalid[] = ["row" => $r + 1, "barcode" => $barcodeStr, "error" => "Пустой/неверный минимальный остаток"];
        continue;
    }

    $items[$barcodeStr] = $minStock;
    $parsed++;
}

if (empty($items)) {
    jexit(["error" => "Не найдено ни одной корректной строки", "invalid_preview" => $invalid], 400);
}

// существующие
$keys = array_keys($items);
$existing = [];

$chunkSize = 800;
for ($i = 0; $i < count($keys); $i += $chunkSize) {
    $chunk = array_slice($keys, $i, $chunkSize);
    $in = implode(",", array_fill(0, count($chunk), "?"));
    $st = $pdo->prepare("SELECT barcode, min_stock FROM product_min_stock WHERE barcode IN ($in)");
    $st->execute($chunk);
    while ($row = $st->fetch(PDO::FETCH_ASSOC)) {
        $existing[(string)$row["barcode"]] = (int)$row["min_stock"];
    }
}

$inserted = 0;
$updated = 0;
$unchanged = 0;
$preview = [];
$previewLimit = 25;

try {
    if (!$dryRun) $pdo->beginTransaction();

    $stIns = $pdo->prepare("INSERT INTO product_min_stock (barcode, min_stock) VALUES (?, ?)");
    $stUpd = $pdo->prepare("UPDATE product_min_stock SET min_stock = ? WHERE barcode = ?");

    foreach ($items as $barcodeStr => $minStock) {
        $old = $existing[$barcodeStr] ?? null;

        if ($old === null) {
            if (!$dryRun) $stIns->execute([$barcodeStr, $minStock]);
            $inserted++;
            $action = "insert";
        } else {
            if ((int)$old !== (int)$minStock) {
                if (!$dryRun) $stUpd->execute([$minStock, $barcodeStr]);
                $updated++;
                $action = "update";
            } else {
                $unchanged++;
                $action = "skip";
            }
        }

        if (count($preview) < $previewLimit) {
            $preview[] = ["barcode" => $barcodeStr, "min_stock" => $minStock, "action" => $action];
        }
    }

    if (!$dryRun) $pdo->commit();
} catch (Throwable $e) {
    if (!$dryRun && $pdo->inTransaction()) $pdo->rollBack();
    jexit(["error" => "DB error", "details" => $e->getMessage()], 500);
}

jexit([
    "success" => true,
    "dry_run" => (bool)$dryRun,
    "file" => $name,
    "ext" => $ext,
    "rows_total_in_file" => count($rows),
    "rows_parsed" => $parsed,
    "unique_barcodes" => count($items),
    "inserted" => $inserted,
    "updated" => $updated,
    "unchanged" => $unchanged,
    "invalid_preview" => $invalid,
    "preview" => $preview
]);
