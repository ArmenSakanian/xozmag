<?php
// api/auth/_init.php

declare(strict_types=1);

// Всегда JSON
header("Content-Type: application/json; charset=utf-8");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

function json_response(array $data, int $code = 200): void {
  http_response_code($code);
  echo json_encode($data, JSON_UNESCAPED_UNICODE);
  exit;
}

function is_https(): bool {
  if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') return true;
  if (!empty($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443) return true;
  return false;
}

function start_secure_session(): void {
  if (session_status() === PHP_SESSION_ACTIVE) return;

  ini_set('session.use_strict_mode', '1');
  ini_set('session.use_only_cookies', '1');
  ini_set('session.cookie_httponly', '1');

  // SameSite + Secure
  $cookieParams = session_get_cookie_params();
  $secure = is_https();

  // PHP 7.3+ (везде почти так)
  session_set_cookie_params([
    'lifetime' => 0,
    'path' => $cookieParams['path'] ?? '/',
    'domain' => $cookieParams['domain'] ?? '',
    'secure' => $secure,
    'httponly' => true,
    'samesite' => 'Lax',
  ]);

  // чтобы было понятно в devtools
  session_name('xozmag_sess');

  session_start();
}

function client_ip(): string {
  // Если у тебя Cloudflare/прокси - позже можно расширить.
  return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

/**
 * Очень простой анти-брутфорс по IP (файл в sys temp).
 * - 10 ошибок за 10 минут => блок на 10 минут
 */
function login_rate_limit_check_or_fail(string $ip): void {
  $file = sys_get_temp_dir() . '/xozmag_login_' . sha1($ip) . '.json';
  if (!is_file($file)) return;

  $raw = @file_get_contents($file);
  $data = $raw ? json_decode($raw, true) : null;
  if (!is_array($data)) return;

  $now = time();
  $lockedUntil = (int)($data['locked_until'] ?? 0);
  if ($lockedUntil > $now) {
    json_response([
      'status' => 'error',
      'message' => 'Слишком много попыток. Попробуйте позже.'
    ], 429);
  }
}

function login_rate_limit_fail(string $ip): void {
  $file = sys_get_temp_dir() . '/xozmag_login_' . sha1($ip) . '.json';
  $now = time();

  $data = [
    'count' => 0,
    'first' => $now,
    'locked_until' => 0,
  ];

  if (is_file($file)) {
    $raw = @file_get_contents($file);
    $old = $raw ? json_decode($raw, true) : null;
    if (is_array($old)) $data = array_merge($data, $old);
  }

  $first = (int)($data['first'] ?? $now);
  $count = (int)($data['count'] ?? 0);

  // если прошло больше 10 минут - сброс окна
  if (($now - $first) > 600) {
    $first = $now;
    $count = 0;
  }

  $count++;

  $lockedUntil = (int)($data['locked_until'] ?? 0);
  // 10 ошибок => блок
  if ($count >= 10) {
    $lockedUntil = $now + 600; // 10 минут
  }

  $new = [
    'count' => $count,
    'first' => $first,
    'locked_until' => $lockedUntil,
  ];

  @file_put_contents($file, json_encode($new));
}

function login_rate_limit_success(string $ip): void {
  $file = sys_get_temp_dir() . '/xozmag_login_' . sha1($ip) . '.json';
  if (is_file($file)) @unlink($file);
}

function auth_user(): ?array {
  start_secure_session();
  if (empty($_SESSION['auth']) || !is_array($_SESSION['auth'])) return null;

  // минимальная проверка
  $u = $_SESSION['auth'];
  if (empty($u['id']) || empty($u['username'])) return null;
  return $u;
}

function require_login(): array {
  $u = auth_user();
  if (!$u) {
    json_response(['status' => 'error', 'message' => 'Не авторизован'], 401);
  }
  return $u;
}

function require_admin(): array {
  $u = require_login();
  if (($u['role'] ?? '') !== 'admin') {
    json_response(['status' => 'error', 'message' => 'Нет доступа'], 403);
  }
  return $u;
}

function do_logout(): void {
  start_secure_session();

  $_SESSION = [];

  if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
      $params["path"] ?? '/',
      $params["domain"] ?? '',
      (bool)($params["secure"] ?? false),
      true
    );
  }

  session_destroy();
}
