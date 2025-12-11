<?php
echo "DOC_ROOT = " . $_SERVER['DOCUMENT_ROOT'] . "<br>";

$path = $_SERVER['DOCUMENT_ROOT'] . "/vendor/autoload.php";

echo "CHECK = " . $path . "<br>";

if (file_exists($path)) {
    echo "FOUND!";
} else {
    echo "NOT FOUND!";
}
