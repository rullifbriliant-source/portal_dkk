<?php

$host = "localhost";
$username = "root";
$password = "";
$database = "portal_dkk";

// Membuat koneksi
$config = mysqli_connect(
    $host,
    $username,
    $password,
    $database
);

// Cek koneksi
if (!$config) {
    die(
        "Koneksi database gagal: " .
        mysqli_connect_error()
    );
}

// Set charset
mysqli_set_charset($config, "utf8mb4");