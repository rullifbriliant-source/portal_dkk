<?php
/**
 * ==========================================================
 * PORTAL DKK — Database Configuration
 * Terpusat, membaca kredensial dari environment variable
 * ==========================================================
 * Sumber env (prioritas):
 *   1. System env (getenv / $_ENV / $_SERVER) — untuk hosting/VPS
 *   2. File .env di project root — untuk development lokal
 * Jika tidak ada .env, fallback ke default lokal (localhost/root).
 */

// ------------------------------------------------------------------
// Load .env manual (tanpa composer) — no-op jika file tidak ada
// ------------------------------------------------------------------
if (!function_exists('portal_load_env')) {
    function portal_load_env(string $path): void
    {
        if (!is_file($path) || !is_readable($path)) {
            return;
        }
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return;
        }
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#' || $line[0] === ';') {
                continue;
            }
            // skip lines without =
            if (strpos($line, '=') === false) {
                continue;
            }
            // Handle "export KEY=val" prefix
            if (strncasecmp($line, 'export ', 7) === 0) {
                $line = trim(substr($line, 7));
            }
            [$key, $val] = explode('=', $line, 2);
            $key = trim($key);
            $val = trim($val);
            // Remove surrounding quotes (single/double)
            if (strlen($val) >= 2 && (
                ($val[0] === '"' && $val[strlen($val)-1] === '"') ||
                ($val[0] === "'" && $val[strlen($val)-1] === "'")
            )) {
                $val = substr($val, 1, -1);
            }
            // Jangan override jika sudah ada di system env
            if (getenv($key) !== false) {
                continue;
            }
            // Set ke env runtime
            putenv($key . '=' . $val);
            $_ENV[$key] = $val;
            $_SERVER[$key] = $val;
        }
    }
}

// Cari file .env di beberapa lokasi kandidat
$__envCandidates = [
    defined('ROOT_PATH') ? rtrim(ROOT_PATH, '/\\') . DIRECTORY_SEPARATOR . '.env' : null,
    __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '.env',
    dirname(__DIR__) . DIRECTORY_SEPARATOR . '.env',
];
foreach ($__envCandidates as $__candidate) {
    if ($__candidate && is_file($__candidate)) {
        portal_load_env($__candidate);
        break;
    }
}
unset($__envCandidates, $__candidate);

// ------------------------------------------------------------------
// Helper env()
// ------------------------------------------------------------------
if (!function_exists('portal_env')) {
    function portal_env(string $key, $default = null)
    {
        $val = getenv($key);
        if ($val !== false && $val !== '') {
            return $val;
        }
        if (isset($_ENV[$key]) && $_ENV[$key] !== '') {
            return $_ENV[$key];
        }
        if (isset($_SERVER[$key]) && $_SERVER[$key] !== '') {
            return $_SERVER[$key];
        }
        return $default;
    }
}

// ------------------------------------------------------------------
// Ambil kredensial (dengan default untuk development lokal)
// ------------------------------------------------------------------
$__dbHost = portal_env('DB_HOST', 'localhost');
$__dbName = portal_env('DB_NAME', 'portal_dkk');
$__dbUser = portal_env('DB_USER', 'root');
$__dbPass = portal_env('DB_PASS', '');
$__dbPort = portal_env('DB_PORT', '3306');

// Normalisasi port
$__dbPort = is_numeric($__dbPort) ? (int)$__dbPort : 3306;
if ($__dbPort <= 0) {
    $__dbPort = 3306;
}

// Define konstanta sekali saja (hindari redeclare jika di-include 2x)
if (!defined('DB_HOST')) define('DB_HOST', $__dbHost);
if (!defined('DB_NAME')) define('DB_NAME', $__dbName);
if (!defined('DB_USER')) define('DB_USER', $__dbUser);
if (!defined('DB_PASS')) define('DB_PASS', $__dbPass);
if (!defined('DB_PORT')) define('DB_PORT', $__dbPort);

unset($__dbHost, $__dbName, $__dbUser, $__dbPass, $__dbPort);

// ------------------------------------------------------------------
// Buat koneksi global $config (konvensi existing di codebase)
// ------------------------------------------------------------------
if (!isset($config) || !$config instanceof mysqli) {
    $config = mysqli_connect(
        DB_HOST,
        DB_USER,
        DB_PASS,
        DB_NAME,
        (int)DB_PORT
    );

    if (!$config) {
        // Tampilkan pesan yang aman tapi informatif (jangan bocorkan pass)
        die("Database Error : " . mysqli_connect_error());
    }

    mysqli_set_charset($config, 'utf8mb4');
}

date_default_timezone_set('Asia/Jakarta');
