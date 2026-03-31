<?php
header("Content-Type: application/json; charset=utf-8");
require_once __DIR__ . "/../../db.php";

/* ===== helpers ===== */
function normalize_q($s) {
  $s = mb_strtolower((string)$s, "UTF-8");
  $s = str_replace("ё", "е", $s);
  $s = preg_replace('~[^\p{L}\p{N}]+~u', ' ', $s);
  $s = preg_replace('~\s+~u', ' ', $s);
  return trim($s);
}

function safe_rel_from_url_or_path($p) {
  $p = trim((string)$p);
  if ($p === "") return "";
  $u = parse_url($p);
  $p = $u["path"] ?? $p;
  $p = trim((string)$p);
  if ($p === "") return "";
  if ($p[0] !== "/") $p = "/" . ltrim($p, "/");
  return $p;
}

function decode_photo_to_images($photo) {
  $photo = trim((string)$photo);
  if ($photo === "" || $photo === "[]") return [];
  $decoded = json_decode($photo, true);
  if (is_array($decoded)) {
    $out = [];
    foreach ($decoded as $p) {
      $p = safe_rel_from_url_or_path($p);
      if ($p !== "") $out[] = $p;
    }
    return array_values(array_unique($out));
  }
  $one = safe_rel_from_url_or_path($photo);
  return $one ? [$one] : [];
}

function is_code($s) {
  return preg_match('~^[0-9]+(\.[0-9]+)*$~', (string)$s) === 1;
}

function format_qty_for_ui($qty, $measureName): string {
  $m = mb_strtolower(trim((string)$measureName), "UTF-8");
  $num = (float)str_replace(",", ".", (string)$qty);

  if ($m === "шт" || $m === "pcs" || $m === "pc") {
    return (string)intval(round($num, 0));
  }

  $s = number_format($num, 3, ".", "");
  $s = rtrim(rtrim($s, "0"), ".");
  if ($s === "" || $s === "-0") $s = "0";
  return $s;
}

/* columns detector (чтобы не ломалось, если колонок ещё нет) */
function has_column(PDO $pdo, string $table, string $col): bool {
  static $cache = [];
  $key = $table . "." . $col;
  if (array_key_exists($key, $cache)) return (bool)$cache[$key];

  $st = $pdo->prepare("
    SELECT 1
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = ?
      AND COLUMN_NAME = ?
    LIMIT 1
  ");
  $st->execute([$table, $col]);
  $cache[$key] = (bool)$st->fetchColumn();
  return (bool)$cache[$key];
}

/* ===== UTF-8 levenshtein (для “опечаток”) ===== */
function mb_chars(string $s): array {
  if ($s === "") return [];
  return preg_split('//u', $s, -1, PREG_SPLIT_NO_EMPTY) ?: [];
}
function mb_levenshtein(string $a, string $b): int {
  if ($a === $b) return 0;
  $A = mb_chars($a);
  $B = mb_chars($b);
  $n = count($A);
  $m = count($B);
  if ($n === 0) return $m;
  if ($m === 0) return $n;

  $prev = range(0, $m);
  for ($i = 1; $i <= $n; $i++) {
    $cur = [];
    $cur[0] = $i;
    $ai = $A[$i - 1];
    for ($j = 1; $j <= $m; $j++) {
      $cost = ($ai === $B[$j - 1]) ? 0 : 1;
      $cur[$j] = min(
        $prev[$j] + 1,
        $cur[$j - 1] + 1,
        $prev[$j - 1] + $cost
      );
    }
    $prev = $cur;
  }
  return (int)$prev[$m];
}

function split_tokens(string $norm): array {
  $norm = trim($norm);
  if ($norm === "") return [];
  $parts = preg_split('~\s+~u', $norm) ?: [];
  $out = [];
  foreach ($parts as $p) {
    $p = trim($p);
    if ($p === "") continue;
    $out[] = $p;
  }
  return $out;
}

/* ===== input ===== */
$qRaw = isset($_GET["q"]) ? trim((string)$_GET["q"]) : "";
if ($qRaw === "") { echo json_encode([]); exit; }

$limit = isset($_GET["limit"]) ? (int)$_GET["limit"] : 12;
if ($limit < 1) $limit = 12;
if ($limit > 30) $limit = 30;

$catRaw = isset($_GET["cat"]) ? trim((string)$_GET["cat"]) : "";

$qNorm = normalize_q($qRaw);
$qTokens = split_tokens($qNorm);

/* режимы запроса */
$isDigitsInput = preg_match('~^\d{5,}$~', $qRaw) === 1; // чисто цифры (штрихкод обычно)
$codeCompact = preg_replace('~\s+~u', '', $qRaw);
$isCodeLike = preg_match('~^[\p{L}\p{N}\.\-\/_]{2,}$~u', $codeCompact) === 1;

/* короткие запросы (кроме кодовых) */
if (!$isDigitsInput && !$isCodeLike && mb_strlen($qNorm, "UTF-8") < 2) {
  echo json_encode([]);
  exit;
}

try {
  /* ===== cat filter (optional) ===== */
  $catIds = [];
  if ($catRaw !== "") {
    $catCode = "";
    if (is_code($catRaw)) $catCode = $catRaw;
    else {
      $st = $pdo->prepare("SELECT code FROM categories WHERE slug = ? LIMIT 1");
      $st->execute([$catRaw]);
      $catCode = (string)($st->fetch(PDO::FETCH_ASSOC)["code"] ?? "");
    }

    if ($catCode !== "") {
      $st = $pdo->prepare("SELECT id FROM categories WHERE code = ? OR code LIKE CONCAT(?, '.%')");
      $st->execute([$catCode, $catCode]);
      $catIds = array_map(fn($x)=> (int)$x["id"], $st->fetchAll(PDO::FETCH_ASSOC));
    }
  }

  $whereCat = "";
  $paramsCat = [];
  if (!empty($catIds)) {
    $in = implode(",", array_fill(0, count($catIds), "?"));
    $whereCat = " AND p.category_id IN ($in) ";
    $paramsCat = $catIds;
  }

  /* ===== choose normalized columns if exist ===== */
  $hasNameNorm = has_column($pdo, "products", "name_norm");
  $hasArticleNorm = has_column($pdo, "products", "article_norm");

  // если колонки есть, но пустые — не ломаемся: падаем на обычные поля
  $nameExpr    = $hasNameNorm ? "COALESCE(NULLIF(p.name_norm,''), p.name)" : "p.name";
  $articleExpr = $hasArticleNorm ? "COALESCE(NULLIF(p.article_norm,''), p.article)" : "p.article";

  $select = "
    SELECT
      p.id, p.name, p.slug, p.price, p.brand,
      p.barcode, p.article,
      p.photo, p.quantity, p.measure_name
    FROM products p
  ";

  $rows = [];

  /* ===== MAIN SEARCH ===== */
  if ($isDigitsInput) {
    // цифры -> приоритет штрихкода/артикула, но название тоже смотрим
    $sql = "
      $select
      WHERE (
        p.barcode = ?
        OR p.barcode LIKE ?
        OR p.article = ?
        OR p.article LIKE ?
        OR $articleExpr LIKE ?
        OR $nameExpr LIKE ?
      )
      $whereCat
      ORDER BY
        (p.barcode = ?) DESC,
        (p.article = ?) DESC,
        (p.barcode LIKE ?) DESC,
        (p.article LIKE ?) DESC,
        p.id DESC
      LIMIT $limit
    ";
    $st = $pdo->prepare($sql);

    $prefix = $qRaw . "%";
    $contains = "%" . $qRaw . "%";

    $st->execute(array_merge(
      [
        $qRaw, $prefix,
        $qRaw, $prefix,
        $contains,
        $contains,
        $qRaw,
        $qRaw,
        $prefix,
        $prefix
      ],
      $paramsCat
    ));
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);

  } else {
    // текст/код -> имя (по словам) + barcode/article (contains), brand НЕ ищем
    $or = [];
    $params = [];

    // поиск по названию: все слова должны встретиться
    if (!empty($qTokens)) {
      $and = [];
      foreach ($qTokens as $w) {
        // пропускаем совсем короткие мусорные токены
        if (mb_strlen($w, "UTF-8") < 2) continue;
        $and[] = "$nameExpr LIKE ?";
        $params[] = "%" . $w . "%";
      }
      if (!empty($and)) $or[] = "(" . implode(" AND ", $and) . ")";
    }

    // barcode: если в запросе есть цифры — ищем по цифрам
    $digitsOnly = preg_replace('~\D+~', '', $qRaw);
    if ($digitsOnly !== "" && strlen($digitsOnly) >= 3) {
      $or[] = "p.barcode LIKE ?";
      $params[] = "%" . $digitsOnly . "%";
    } else {
      $or[] = "p.barcode LIKE ?";
      $params[] = "%" . $qRaw . "%";
    }

    // article: по сырому (как ввёл) — чтобы работали точки/тире/слэш
    $or[] = "p.article LIKE ?";
    $params[] = "%" . $qRaw . "%";

    // article_norm: если есть — можно искать и по нормализованному вводу
    if ($hasArticleNorm && $qNorm !== "") {
      $or[] = "$articleExpr LIKE ?";
      $params[] = "%" . $qNorm . "%";
    }

    $where = implode(" OR ", $or);
    if ($where === "") {
      echo json_encode([]);
      exit;
    }

    // ранжирование: сначала точные/префиксы barcode+article, потом короче названия, потом свежие id
    $prefix = $qRaw . "%";
    $sql = "
      $select
      WHERE ( $where )
      $whereCat
      ORDER BY
        (p.barcode = ?) DESC,
        (p.article = ?) DESC,
        (p.barcode LIKE ?) DESC,
        (p.article LIKE ?) DESC,
        CHAR_LENGTH($nameExpr) ASC,
        p.id DESC
      LIMIT $limit
    ";
    $st = $pdo->prepare($sql);
    $st->execute(array_merge($params, [$qRaw, $qRaw, $prefix, $prefix], $paramsCat));
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);
  }

  /* ===== FUZZY FALLBACK (опечатки) =====
     Включаем ТОЛЬКО если:
     - основной поиск ничего не нашёл
     - это не чисто-цифровой запрос
     - длина нормализованного ввода >= 3
  */
  if (empty($rows) && !$isDigitsInput && mb_strlen($qNorm, "UTF-8") >= 3) {
    $qTok = array_values(array_filter($qTokens, function($x){
      return mb_strlen($x, "UTF-8") >= 2;
    }));

    if (!empty($qTok)) {
      // берём все товары (3k — ок), считаем “похожесть” по словам
      $sqlAll = "
        $select
        WHERE 1=1
        $whereCat
        ORDER BY p.id DESC
        LIMIT 5000
      ";
      $stAll = $pdo->prepare($sqlAll);
      $stAll->execute($paramsCat);
      $cand = $stAll->fetchAll(PDO::FETCH_ASSOC);

      $scored = [];
      foreach ($cand as $r) {
        $nameN = normalize_q($r["name"] ?? "");
        $nameTok = split_tokens($nameN);

        // если нет токенов — пропуск
        if (empty($nameTok)) continue;

        $score = 0;
        $bad = 0;

        foreach ($qTok as $qt) {
          $best = 999;

          // быстрые попадания
          foreach ($nameTok as $nt) {
            if ($nt === $qt) { $best = 0; break; }
            if (mb_strpos($nt, $qt, 0, "UTF-8") !== false) { $best = 0; break; }
          }
          if ($best === 0) { $score += 0; continue; }

          // считаем расстояние
          foreach ($nameTok as $nt) {
            $d = mb_levenshtein($qt, $nt);
            if ($d < $best) $best = $d;
            if ($best <= 1) break;
          }

          // штрафуем сильно, если слово совсем “не похоже”
          if ($best >= 4) $bad++;
          $score += min(8, $best);
        }

        // если много “плохих” слов — вверх не пускаем
        $score += $bad * 2;

        // чем короче название — тем чуть лучше
        $score += (int)(mb_strlen($nameN, "UTF-8") / 60);

        $r["_score"] = $score;
        $scored[] = $r;
      }

      usort($scored, function($a, $b){
        $sa = $a["_score"] ?? 999999;
        $sb = $b["_score"] ?? 999999;
        if ($sa === $sb) return ((int)($b["id"] ?? 0)) <=> ((int)($a["id"] ?? 0));
        return $sa <=> $sb;
      });

      // берём лучших, но не слишком “далёких”
      $bestRows = [];
      foreach ($scored as $r) {
        if (count($bestRows) >= $limit) break;
        if (($r["_score"] ?? 999) > 10) continue; // порог (можно потом подкрутить)
        unset($r["_score"]);
        $bestRows[] = $r;
      }

      if (!empty($bestRows)) $rows = $bestRows;
    }
  }

  /* ===== output mapping (НЕ ломаем старый формат) ===== */
  foreach ($rows as &$r) {
    $measure = (string)($r["measure_name"] ?? "");
    $r["measureName"] = $measure;

    $r["quantity_value"] = (float)($r["quantity"] ?? 0);
    $r["quantity"] = format_qty_for_ui($r["quantity"] ?? 0, $measure);

    unset($r["measure_name"]);

    $imgs = decode_photo_to_images($r["photo"] ?? "");
    $first = $imgs[0] ?? "";

    $r["images"] = $first ? [$first] : [];
    $r["thumb"]  = $first ?: "/img/no-photo.png";

    unset($r["photo"]);
    // brand оставляем в выдаче (UI может показывать), но в поиске он больше не участвует
    // article тоже оставляем — может пригодиться дальше (UI не сломает)
  }
  unset($r);

  echo json_encode($rows, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(["error" => true, "message" => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
