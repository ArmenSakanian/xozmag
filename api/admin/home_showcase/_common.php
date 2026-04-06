<?php
require_once __DIR__ . "/../../db.php";

function hs_json_fail(string $code, int $http = 400, array $extra = []): void {
    http_response_code($http);
    echo json_encode(array_merge(["ok" => false, "error" => $code], $extra), JSON_UNESCAPED_UNICODE);
    exit;
}

function hs_doc_root(): string {
    $root = rtrim((string)($_SERVER["DOCUMENT_ROOT"] ?? ""), "/");
    if ($root === "") hs_json_fail("DOCROOT_EMPTY", 500);
    return $root;
}

function hs_upload_dir(): string {
    return hs_doc_root() . "/home_showcase_cards/";
}

function hs_public_prefix(): string {
    return "/home_showcase_cards/";
}

function hs_column_exists(PDO $pdo, string $table, string $column): bool {
    $stmt = $pdo->prepare("SHOW COLUMNS FROM `{$table}` LIKE :column");
    $stmt->execute([":column" => $column]);
    return (bool)$stmt->fetch(PDO::FETCH_ASSOC);
}

function hs_ensure_schema(PDO $pdo): void {
    $pdo->exec("CREATE TABLE IF NOT EXISTS home_showcase_settings (
        id TINYINT UNSIGNED NOT NULL PRIMARY KEY,
        block_title VARCHAR(255) NOT NULL,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS home_showcase_items (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        description TEXT NULL,
        price VARCHAR(120) NOT NULL,
        image_url VARCHAR(255) NOT NULL,
        button_text VARCHAR(120) NOT NULL,
        button_url VARCHAR(500) NOT NULL,
        sort_order INT NOT NULL DEFAULT 0,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    if (!hs_column_exists($pdo, "home_showcase_items", "is_active")) {
        $pdo->exec("ALTER TABLE home_showcase_items ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1 AFTER button_url");
    }

    $pdo->exec("INSERT INTO home_showcase_settings (id, block_title)
        VALUES (1, 'Рекомендуем посмотреть')
        ON DUPLICATE KEY UPDATE id = id");
}

function hs_get_settings(PDO $pdo): array {
    hs_ensure_schema($pdo);
    $stmt = $pdo->query("SELECT id, block_title, updated_at FROM home_showcase_settings WHERE id = 1 LIMIT 1");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        return [
            "id" => 1,
            "block_title" => "Рекомендуем посмотреть",
            "updated_at" => null,
        ];
    }
    return $row;
}

function hs_get_items(PDO $pdo, bool $onlyActive = false): array {
    hs_ensure_schema($pdo);

    $sql = "SELECT id, title, description, price, image_url, button_text, button_url, is_active, sort_order, created_at, updated_at
        FROM home_showcase_items";
    if ($onlyActive) {
        $sql .= " WHERE is_active = 1";
    }
    $sql .= " ORDER BY sort_order ASC, id ASC";

    $stmt = $pdo->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function hs_normalize_text(?string $value, int $maxLen = 0, bool $required = true): string {
    $value = trim((string)$value);
    if ($required && $value === "") hs_json_fail("VALIDATION_ERROR", 422, ["field" => "text"]);
    if ($maxLen > 0 && mb_strlen($value, "UTF-8") > $maxLen) {
        hs_json_fail("VALIDATION_ERROR", 422, ["field" => "text_too_long"]);
    }
    return $value;
}

function hs_validate_item_payload(array $src): array {
    $title = trim((string)($src["title"] ?? ""));
    $description = trim((string)($src["description"] ?? ""));
    $price = trim((string)($src["price"] ?? ""));
    $buttonText = trim((string)($src["button_text"] ?? ""));
    $buttonUrl = trim((string)($src["button_url"] ?? ""));

    if ($title === "") hs_json_fail("VALIDATION_ERROR", 422, ["field" => "title"]);
    if ($buttonText === "") hs_json_fail("VALIDATION_ERROR", 422, ["field" => "button_text"]);
    if ($buttonUrl === "") hs_json_fail("VALIDATION_ERROR", 422, ["field" => "button_url"]);

    if (mb_strlen($title, "UTF-8") > 255) hs_json_fail("VALIDATION_ERROR", 422, ["field" => "title_too_long"]);
    if ($price !== "" && mb_strlen($price, "UTF-8") > 120) hs_json_fail("VALIDATION_ERROR", 422, ["field" => "price_too_long"]);
    if (mb_strlen($buttonText, "UTF-8") > 120) hs_json_fail("VALIDATION_ERROR", 422, ["field" => "button_text_too_long"]);
    if (mb_strlen($buttonUrl, "UTF-8") > 500) hs_json_fail("VALIDATION_ERROR", 422, ["field" => "button_url_too_long"]);

    return [
        "title" => $title,
        "description" => $description,
        "price" => $price,
        "button_text" => $buttonText,
        "button_url" => $buttonUrl,
    ];
}

function hs_ensure_upload_dir(): string {
    $dir = hs_upload_dir();
    if (!is_dir($dir) && !mkdir($dir, 0775, true)) {
        hs_json_fail("DIR_CREATE_FAILED", 500);
    }
    return $dir;
}

function hs_move_uploaded_image(string $fieldName = "image"): string {
    if (empty($_FILES[$fieldName]) || !is_array($_FILES[$fieldName])) {
        hs_json_fail("NO_FILE", 422);
    }

    $file = $_FILES[$fieldName];
    if (($file["error"] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        hs_json_fail("UPLOAD_ERROR", 400);
    }
    if (!is_uploaded_file($file["tmp_name"] ?? "")) {
        hs_json_fail("BAD_UPLOAD", 400);
    }
    if ((int)($file["size"] ?? 0) > 8 * 1024 * 1024) {
        hs_json_fail("FILE_TOO_LARGE", 422);
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = (string)$finfo->file($file["tmp_name"]);
    $allowed = [
        "image/jpeg" => "jpg",
        "image/png" => "png",
        "image/webp" => "webp",
    ];
    if (!isset($allowed[$mime])) {
        hs_json_fail("BAD_TYPE", 422, ["mime" => $mime]);
    }

    $dir = hs_ensure_upload_dir();
    $filename = "showcase_" . date("Ymd_His") . "_" . bin2hex(random_bytes(6)) . "." . $allowed[$mime];
    $dest = $dir . $filename;

    if (!move_uploaded_file($file["tmp_name"], $dest)) {
        hs_json_fail("MOVE_FAILED", 500);
    }

    return hs_public_prefix() . $filename;
}

function hs_delete_image_by_url(?string $url): void {
    $url = trim((string)$url);
    if ($url === "") return;

    $docRoot = hs_doc_root();
    $allowedDir = realpath(hs_upload_dir());
    if (!$allowedDir) return;

    $fullPath = $docRoot . $url;
    $realFile = realpath($fullPath);
    if ($realFile && str_starts_with($realFile, $allowedDir)) {
        @unlink($realFile);
    }
}
