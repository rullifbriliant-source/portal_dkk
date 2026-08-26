<?php
session_start();

// Koneksi database
require_once __DIR__ . "/../config/database.php";

// Cek login
function isLoggedIn() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

// Redirect jika belum login
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit;
    }
}

// Ambil data user
function getAdminUser() {
    return $_SESSION['admin_username'] ?? 'Admin';
}
?>