<?php
// api/auth/login.php

declare(strict_types=1);

require_once __DIR__ . "/_init.php";
require_once __DIR__ . "/../db.php";

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
  json_response(['status' => 'error', 'message' => 'Method not allowed'], 405);
}

$username = trim((string)($_POST["username"] ?? ""));
$password = (string)($_POST["password"] ?? "");

if ($username === '' || $password === '') {
  json_response(["status" => "error", "message" => "Введите логин и пароль"], 400);
}

$ip = client_ip();
login_rate_limit_check_or_fail($ip);

// Важно: не палим "неверный логин" / "неверный пароль" отдельно
$fail = function() use ($ip) {
  login_rate_limit_fail($ip);
  json_response(["status" => "error", "message" => "Неверный логин или пароль"], 401);
};

try {
  $stmt = $pdo->prepare("SELECT id, username, password, role FROM users WHERE username = ? LIMIT 1");
  $stmt->execute([$username]);
  $user = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$user) $fail();

  if (!password_verify($password, (string)$user["password"])) $fail();

  // Успешно
  login_rate_limit_success($ip);

  start_secure_session();
  session_regenerate_id(true);

  $_SESSION['auth'] = [
    'id' => (int)$user['id'],
    'username' => (string)$user['username'],
    'role' => (string)($user['role'] ?? 'user'), // добавь role в users
    'login_at' => time(),
  ];

  json_response([
    "status" => "success",
    "user" => [
      "id" => (int)$user['id'],
      "username" => (string)$user['username'],
      "role" => (string)($user['role'] ?? 'user'),
    ]
  ]);
} catch (Throwable $e) {
  // На проде лучше логировать, а клиенту — общая ошибка
  json_response(["status" => "error", "message" => "Ошибка сервера"], 500);
}
