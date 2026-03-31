<?php

declare(strict_types=1);

function pf_normalize_q(string $s): string
{
    $s = mb_strtolower((string)$s, 'UTF-8');
    $s = str_replace('ё', 'е', $s);
    $s = preg_replace('~[^\p{L}\p{N}]+~u', ' ', $s);
    $s = preg_replace('~\s+~u', ' ', $s);
    return trim((string)$s);
}

function pf_split_tokens(string $norm): array
{
    $norm = trim($norm);
    if ($norm === '') {
        return [];
    }

    $parts = preg_split('~\s+~u', $norm) ?: [];
    $out = [];
    foreach ($parts as $part) {
        $part = trim((string)$part);
        if ($part !== '') {
            $out[] = $part;
        }
    }
    return $out;
}

function pf_stopwords(): array
{
    return [
        'для', 'под', 'над', 'при', 'без', 'или', 'либо', 'как', 'что', 'это', 'эти', 'этот', 'эта', 'того', 'такой',
        'товар', 'товара', 'товары', 'к', 'и', 'в', 'во', 'на', 'по', 'из', 'с', 'со', 'у', 'о', 'об', 'от', 'до',
    ];
}

function pf_query_tokens(string $query): array
{
    $norm = pf_normalize_q($query);
    $tokens = pf_split_tokens($norm);
    if (empty($tokens)) {
        return [];
    }

    $stop = array_flip(pf_stopwords());
    $out = [];
    foreach ($tokens as $token) {
        if (isset($stop[$token])) {
            continue;
        }
        if (mb_strlen($token, 'UTF-8') < 2) {
            continue;
        }
        $out[] = $token;
    }

    if (empty($out)) {
        return $tokens;
    }

    return array_values(array_unique($out));
}

function pf_is_code(string $s): bool
{
    return preg_match('~^[0-9]+(\.[0-9]+)*$~', $s) === 1;
}

function pf_has_column(PDO $pdo, string $table, string $col): bool
{
    static $cache = [];
    $key = $table . '.' . $col;
    if (array_key_exists($key, $cache)) {
        return (bool)$cache[$key];
    }

    $st = $pdo->prepare(
        'SELECT 1
         FROM INFORMATION_SCHEMA.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME = ?
           AND COLUMN_NAME = ?
         LIMIT 1'
    );
    $st->execute([$table, $col]);
    $cache[$key] = (bool)$st->fetchColumn();
    return (bool)$cache[$key];
}

function pf_safe_rel_from_url_or_path(string $path): string
{
    $path = trim($path);
    if ($path === '') {
        return '';
    }

    $parsed = parse_url($path);
    $path = trim((string)($parsed['path'] ?? $path));
    if ($path === '') {
        return '';
    }

    if ($path[0] !== '/') {
        $path = '/' . ltrim($path, '/');
    }

    return $path;
}

function pf_decode_photo_to_images(string $photo): array
{
    $photo = trim($photo);
    if ($photo === '' || $photo === '[]') {
        return [];
    }

    $decoded = json_decode($photo, true);
    if (is_array($decoded)) {
        $out = [];
        foreach ($decoded as $item) {
            $rel = pf_safe_rel_from_url_or_path((string)$item);
            if ($rel !== '' && !in_array($rel, $out, true)) {
                $out[] = $rel;
            }
        }
        return $out;
    }

    $one = pf_safe_rel_from_url_or_path($photo);
    return $one === '' ? [] : [$one];
}

function pf_format_qty_for_ui(mixed $qty, ?string $measureName): string
{
    $measure = mb_strtolower(trim((string)$measureName), 'UTF-8');
    $num = (float)str_replace(',', '.', (string)$qty);

    if ($measure === 'шт' || $measure === 'pcs' || $measure === 'pc') {
        return (string)intval(round($num, 0));
    }

    $s = number_format($num, 3, '.', '');
    $s = rtrim(rtrim($s, '0'), '.');
    if ($s === '' || $s === '-0') {
        $s = '0';
    }
    return $s;
}

function pf_select_sql(): string
{
    return 'SELECT p.id, p.name, p.slug, p.price, p.brand, p.barcode, p.article, p.photo, p.quantity, p.measure_name FROM products p';
}

function pf_resolve_category_filter(PDO $pdo, string $catRaw): array
{
    $catRaw = trim($catRaw);
    if ($catRaw === '') {
        return ['', []];
    }

    $catCode = '';
    if (pf_is_code($catRaw)) {
        $catCode = $catRaw;
    } else {
        $st = $pdo->prepare('SELECT code FROM categories WHERE slug = ? LIMIT 1');
        $st->execute([$catRaw]);
        $catCode = (string)($st->fetch(PDO::FETCH_ASSOC)['code'] ?? '');
    }

    if ($catCode === '') {
        return ['', []];
    }

    $st = $pdo->prepare('SELECT id FROM categories WHERE code = ? OR code LIKE CONCAT(?, ".%")');
    $st->execute([$catCode, $catCode]);
    $catIds = array_map(static fn(array $row): int => (int)$row['id'], $st->fetchAll(PDO::FETCH_ASSOC) ?: []);
    if (empty($catIds)) {
        return ['', []];
    }

    $in = implode(',', array_fill(0, count($catIds), '?'));
    return [" AND p.category_id IN ($in) ", $catIds];
}

function pf_name_and_clause(array $tokens, string $nameExpr, array &$params): string
{
    $and = [];
    foreach ($tokens as $token) {
        if (mb_strlen($token, 'UTF-8') < 2) {
            continue;
        }
        $and[] = "$nameExpr LIKE ?";
        $params[] = '%' . $token . '%';
    }
    return empty($and) ? '' : '(' . implode(' AND ', $and) . ')';
}

function pf_mb_chars(string $s): array
{
    if ($s === '') {
        return [];
    }
    return preg_split('//u', $s, -1, PREG_SPLIT_NO_EMPTY) ?: [];
}

function pf_mb_levenshtein(string $a, string $b): int
{
    if ($a === $b) {
        return 0;
    }

    $A = pf_mb_chars($a);
    $B = pf_mb_chars($b);
    $n = count($A);
    $m = count($B);
    if ($n === 0) {
        return $m;
    }
    if ($m === 0) {
        return $n;
    }

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

function pf_common_prefix_len(string $a, string $b): int
{
    $A = pf_mb_chars($a);
    $B = pf_mb_chars($b);
    $len = min(count($A), count($B));
    $n = 0;
    for ($i = 0; $i < $len; $i++) {
        if ($A[$i] !== $B[$i]) {
            break;
        }
        $n++;
    }
    return $n;
}

function pf_token_match_score(string $queryToken, string $nameToken): int
{
    if ($queryToken === $nameToken) {
        return 0;
    }

    if (mb_strpos($nameToken, $queryToken, 0, 'UTF-8') !== false || mb_strpos($queryToken, $nameToken, 0, 'UTF-8') !== false) {
        return 0;
    }

    $prefix = pf_common_prefix_len($queryToken, $nameToken);
    $minLen = min(mb_strlen($queryToken, 'UTF-8'), mb_strlen($nameToken, 'UTF-8'));
    if ($prefix >= 4 || ($minLen <= 5 && $prefix >= 3)) {
        return 1;
    }

    $dist = pf_mb_levenshtein($queryToken, $nameToken);
    if ($dist <= 1) {
        return 1;
    }
    if ($dist === 2 && $minLen >= 6) {
        return 2;
    }

    return 99;
}

function pf_fuzzy_candidates(PDO $pdo, string $whereCat, array $paramsCat, int $limit): array
{
    $sql = pf_select_sql() . " WHERE 1=1 $whereCat ORDER BY p.id DESC LIMIT 5000";
    $st = $pdo->prepare($sql);
    $st->execute($paramsCat);
    return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function pf_fuzzy_search_by_name(PDO $pdo, string $query, int $limit, string $whereCat = '', array $paramsCat = []): array
{
    $tokens = pf_query_tokens($query);
    if (empty($tokens)) {
        return [];
    }

    $candidates = pf_fuzzy_candidates($pdo, $whereCat, $paramsCat, $limit);
    $scored = [];

    foreach ($candidates as $row) {
        $nameNorm = pf_normalize_q((string)($row['name'] ?? ''));
        $nameTokens = pf_split_tokens($nameNorm);
        if (empty($nameTokens)) {
            continue;
        }

        $matched = 0;
        $strong = 0;
        $score = 0;
        $bad = 0;

        foreach ($tokens as $qt) {
            $best = 99;
            foreach ($nameTokens as $nt) {
                $tokenScore = pf_token_match_score($qt, $nt);
                if ($tokenScore < $best) {
                    $best = $tokenScore;
                }
                if ($best === 0) {
                    break;
                }
            }

            if ($best <= 2) {
                $matched++;
                if ($best <= 1) {
                    $strong++;
                }
                $score += $best;
            } else {
                $bad++;
                $score += 20;
            }
        }

        $need = count($tokens) === 1 ? 1 : max(1, count($tokens) - 1);
        if ($matched < $need) {
            continue;
        }
        if ($strong < 1) {
            continue;
        }
        if (count($tokens) === 1 && $bad > 0) {
            continue;
        }
        if (count($tokens) >= 2 && $bad > 1) {
            continue;
        }

        $score += (int)(mb_strlen($nameNorm, 'UTF-8') / 80);
        $row['_score'] = $score;
        $scored[] = $row;
    }

    usort($scored, static function (array $a, array $b): int {
        $sa = (int)($a['_score'] ?? 999999);
        $sb = (int)($b['_score'] ?? 999999);
        if ($sa === $sb) {
            return ((int)($b['id'] ?? 0)) <=> ((int)($a['id'] ?? 0));
        }
        return $sa <=> $sb;
    });

    $out = [];
    foreach ($scored as $row) {
        unset($row['_score']);
        $out[] = $row;
        if (count($out) >= $limit) {
            break;
        }
    }

    return $out;
}

function pf_search_products_general(PDO $pdo, string $qRaw, int $limit = 12, string $catRaw = ''): array
{
    $qRaw = trim($qRaw);
    if ($qRaw === '') {
        return [];
    }

    $limit = max(1, min(30, $limit));
    $qNorm = pf_normalize_q($qRaw);
    $qTokens = pf_query_tokens($qRaw);

    $isDigitsInput = preg_match('~^\d{5,}$~', $qRaw) === 1;
    $codeCompact = preg_replace('~\s+~u', '', $qRaw);
    $isCodeLike = preg_match('~^[\p{L}\p{N}\.\-/_]{2,}$~u', $codeCompact) === 1;

    if (!$isDigitsInput && !$isCodeLike && mb_strlen($qNorm, 'UTF-8') < 2) {
        return [];
    }

    [$whereCat, $paramsCat] = pf_resolve_category_filter($pdo, $catRaw);

    $hasNameNorm = pf_has_column($pdo, 'products', 'name_norm');
    $hasArticleNorm = pf_has_column($pdo, 'products', 'article_norm');
    $nameExpr = $hasNameNorm ? "COALESCE(NULLIF(p.name_norm,''), p.name)" : 'p.name';
    $articleExpr = $hasArticleNorm ? "COALESCE(NULLIF(p.article_norm,''), p.article)" : 'p.article';

    $rows = [];
    $select = pf_select_sql();

    if ($isDigitsInput) {
        $prefix = $qRaw . '%';
        $contains = '%' . $qRaw . '%';
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
        $st->execute(array_merge([
            $qRaw, $prefix,
            $qRaw, $prefix,
            $contains,
            $contains,
            $qRaw,
            $qRaw,
            $prefix,
            $prefix,
        ], $paramsCat));
        $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } else {
        $or = [];
        $params = [];

        $nameClause = pf_name_and_clause($qTokens, $nameExpr, $params);
        if ($nameClause !== '') {
            $or[] = $nameClause;
        }

        $digitsOnly = preg_replace('~\D+~', '', $qRaw);
        $or[] = 'p.barcode LIKE ?';
        $params[] = '%' . ($digitsOnly !== '' && strlen($digitsOnly) >= 3 ? $digitsOnly : $qRaw) . '%';

        $or[] = 'p.article LIKE ?';
        $params[] = '%' . $qRaw . '%';

        if ($hasArticleNorm && $qNorm !== '') {
            $or[] = "$articleExpr LIKE ?";
            $params[] = '%' . $qNorm . '%';
        }

        if (empty($or)) {
            return [];
        }

        $prefix = $qRaw . '%';
        $sql = "
            $select
            WHERE (" . implode(' OR ', $or) . ")
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
        $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    if (empty($rows) && !$isDigitsInput && mb_strlen($qNorm, 'UTF-8') >= 3) {
        $rows = pf_fuzzy_search_by_name($pdo, $qRaw, $limit, $whereCat, $paramsCat);
    }

    return $rows;
}

function pf_search_products_by_name(PDO $pdo, string $query, int $limit = 10): array
{
    $query = trim($query);
    if ($query === '') {
        return [];
    }

    $limit = max(1, min(10, $limit));
    $qNorm = pf_normalize_q($query);
    if ($qNorm === '' || mb_strlen($qNorm, 'UTF-8') < 2) {
        return [];
    }

    $qTokens = pf_query_tokens($query);
    if (empty($qTokens)) {
        return [];
    }

    $hasNameNorm = pf_has_column($pdo, 'products', 'name_norm');
    $nameExpr = $hasNameNorm ? "COALESCE(NULLIF(p.name_norm,''), p.name)" : 'p.name';

    $params = [];
    $nameClause = pf_name_and_clause($qTokens, $nameExpr, $params);
    $rows = [];

    if ($nameClause !== '') {
        $sql = "
            " . pf_select_sql() . "
            WHERE $nameClause
            ORDER BY CHAR_LENGTH($nameExpr) ASC, p.id DESC
            LIMIT $limit
        ";
        $st = $pdo->prepare($sql);
        $st->execute($params);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    if (empty($rows) && mb_strlen($qNorm, 'UTF-8') >= 3) {
        $rows = pf_fuzzy_search_by_name($pdo, $query, $limit);
    }

    return $rows;
}

function pf_find_product_by_barcode(PDO $pdo, string $barcode): ?array
{
    $raw = trim($barcode);
    if ($raw === '') {
        return null;
    }

    $digits = preg_replace('~\D+~', '', $raw);
    $sql = pf_select_sql() . ' WHERE p.barcode = ?';
    $params = [$raw];
    if ($digits !== '' && $digits !== $raw) {
        $sql .= ' OR p.barcode = ?';
        $params[] = $digits;
    }
    $sql .= ' ORDER BY (p.barcode = ?) DESC, p.id DESC LIMIT 1';
    $params[] = $raw;

    $st = $pdo->prepare($sql);
    $st->execute($params);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function pf_find_products_by_article(PDO $pdo, string $article, int $limit = 10): array
{
    $article = trim($article);
    if ($article === '') {
        return [];
    }

    $limit = max(1, min(10, $limit));
    $variants = array_values(array_unique(array_filter([
        $article,
        pf_normalize_q($article),
    ], static fn(string $v): bool => trim($v) !== '')));

    $hasArticleNorm = pf_has_column($pdo, 'products', 'article_norm');
    $articleExpr = $hasArticleNorm ? "COALESCE(NULLIF(p.article_norm,''), p.article)" : 'p.article';

    $seen = [];
    $out = [];

    foreach ($variants as $variant) {
        $prefix = $variant . '%';
        $contains = '%' . $variant . '%';
        $sql = "
            " . pf_select_sql() . "
            WHERE p.article = ?
               OR $articleExpr = ?
               OR p.article LIKE ?
               OR $articleExpr LIKE ?
            ORDER BY
                (p.article = ?) DESC,
                ($articleExpr = ?) DESC,
                (p.article LIKE ?) DESC,
                ($articleExpr LIKE ?) DESC,
                CHAR_LENGTH(p.article) ASC,
                p.id DESC
            LIMIT $limit
        ";

        $st = $pdo->prepare($sql);
        $st->execute([$variant, $variant, $prefix, $contains, $variant, $variant, $prefix, $contains]);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

        foreach ($rows as $row) {
            $id = (int)($row['id'] ?? 0);
            if ($id <= 0 || isset($seen[$id])) {
                continue;
            }
            $seen[$id] = true;
            $out[] = $row;
            if (count($out) >= $limit) {
                break 2;
            }
        }
    }

    return $out;
}

function pf_fetch_product_by_id(PDO $pdo, int $id): ?array
{
    $st = $pdo->prepare(pf_select_sql() . ' WHERE p.id = ? LIMIT 1');
    $st->execute([$id]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function pf_map_rows_for_ui(array $rows): array
{
    foreach ($rows as &$row) {
        $measure = (string)($row['measure_name'] ?? '');
        $row['measureName'] = $measure;
        $row['quantity_value'] = (float)($row['quantity'] ?? 0);
        $row['quantity'] = pf_format_qty_for_ui($row['quantity'] ?? 0, $measure);
        unset($row['measure_name']);

        $images = pf_decode_photo_to_images((string)($row['photo'] ?? ''));
        $first = $images[0] ?? '';
        $row['images'] = $first !== '' ? [$first] : [];
        $row['thumb'] = $first !== '' ? $first : '/img/no-photo.png';
        unset($row['photo']);
    }
    unset($row);

    return $rows;
}
