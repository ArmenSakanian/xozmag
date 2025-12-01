<?php
require_once "db.php";

$username = "admin_xozmag";
$password = password_hash("xozmag7725/1", PASSWORD_DEFAULT);

$stmt = $pdo->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
$stmt->execute([$username, $password]);

echo "OK";
