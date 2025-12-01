<?php
header("Content-Type: application/json");
require_once __DIR__ . "/db.php";

$username = $_POST["username"] ?? "";
$password = $_POST["password"] ?? "";

if (!$username || !$password) {
    echo json_encode(["status" => "error", "message" => "Введите логин и пароль"]);
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
$stmt->execute([$username]);
$user = $stmt->fetch();

if (!$user) {
    echo json_encode(["status" => "error", "message" => "Неверный логин"]);
    exit;
}

if (!password_verify($password, $user["password"])) {
    echo json_encode(["status" => "error", "message" => "Неверный пароль"]);
    exit;
}

$token = bin2hex(random_bytes(32));

echo json_encode([
    "status" => "success",
    "token" => $token
]);
