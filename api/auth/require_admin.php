<?php
if (PHP_SAPI === 'cli') {
  return; // крон запускает php из консоли - разрешаем
}
require_once __DIR__ . "/_init.php";
require_admin();
