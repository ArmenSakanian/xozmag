<?php
header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . "/../../db.php";

try {
  $stmt = $pdo->query("SELECT id, url, sort_order, created_at FROM photo_gallery ORDER BY sort_order ASC, id ASC");
  $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

  echo json_encode([
    "ok" => true,
    "items" => $items
  ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode([
    "ok" => false,
    "error" => "DB_ERROR"
  ], JSON_UNESCAPED_UNICODE);
}
