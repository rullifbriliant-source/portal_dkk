<?php

$file = __DIR__ . "/assets/svg/sukoharjo_interactive.svg";

echo "<pre>";

echo "File : " . $file . PHP_EOL;

echo "Ada  : " . (file_exists($file) ? "YA" : "TIDAK") . PHP_EOL;

if (file_exists($file)) {
    echo "Ukuran : " . filesize($file) . " byte" . PHP_EOL;
}