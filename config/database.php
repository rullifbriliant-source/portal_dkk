<?php

define('DB_HOST', 'localhost');
define('DB_NAME', 'portal_dkk');
define('DB_USER', 'root');
define('DB_PASS', '');

$config = mysqli_connect(
    DB_HOST,
    DB_USER,
    DB_PASS,
    DB_NAME
);

if (!$config) {
    die("Database Error : " . mysqli_connect_error());
}

mysqli_set_charset($config, 'utf8mb4');
date_default_timezone_set('Asia/Jakarta');