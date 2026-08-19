<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once dirname(__DIR__) . '/app/core/loader.php';

header('Content-Type: application/json');

echo json_encode(
    LauncherService::grouped(),
    JSON_PRETTY_PRINT
);