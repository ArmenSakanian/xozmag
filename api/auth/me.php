<?php
// api/auth/me.php

declare(strict_types=1);

require_once __DIR__ . "/_init.php";

$u = auth_user();
if (!$u) {
  json_response(["status" => "guest"]);
}

json_response([
  "status" => "success",
  "user" => [
    "id" => (int)$u['id'],
    "username" => (string)$u['username'],
    "role" => (string)($u['role'] ?? 'user'),
  ]
]);
