<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';

$id = $_GET['id'] ?? null;

if (!$id) {
    echo json_encode([
        'status' => false,
        'message' => 'ID kecamatan tidak diberikan'
    ]);
    exit;
}

$id = mysqli_real_escape_string($config, $id);

/*
|--------------------------------------------------------------------------
| AMBIL DATA KECAMATAN (TANPA jumlah_kk)
|--------------------------------------------------------------------------
*/

$sql = "SELECT
            id_kecamatan,
            kode_kecamatan,
            nama_kecamatan,
            jumlah_penduduk,
            jumlah_kk,
            jumlah_desa,
            jumlah_puskesmas,
            jumlah_pustu,
            jumlah_posyandu,
            jumlah_klinik,
            jumlah_rumah_sakit,
            jumlah_rs,
            luas_wilayah,
            kepadatan
        FROM tbl_kecamatan
        WHERE LOWER(nama_kecamatan) = LOWER('$id')
        AND aktif = 'Y'
        LIMIT 1";

$query = mysqli_query($config, $sql);

if (!$query) {
    echo json_encode([
        'status' => false,
        'message' => mysqli_error($config)
    ]);
    exit;
}

$row = mysqli_fetch_assoc($query);

if (!$row) {
    echo json_encode([
        'status' => false,
        'message' => "Kecamatan '$id' tidak ditemukan"
    ]);
    exit;
}

// ============================================================
// RESPONSE
// ============================================================

echo json_encode([
    'status' => true,

    'id_kecamatan' => (int) $row['id_kecamatan'],
    'kode_kecamatan' => $row['kode_kecamatan'],
    'nama' => $row['nama_kecamatan'],

    // DATA DASAR
    'penduduk' => (int) $row['jumlah_penduduk'],
    'kk' => (int) $row['jumlah_kk'],
    'desa' => (int) $row['jumlah_desa'],

    // FASYANKES
    'puskesmas' => (int) $row['jumlah_puskesmas'],
    'pustu' => (int) $row['jumlah_pustu'],
    'klinik' => (int) $row['jumlah_klinik'],
    'rumah_sakit' => (int) $row['jumlah_rumah_sakit'],

    // DATA TAMBAHAN
    'posyandu' => (int) $row['jumlah_posyandu'],
    'rs' => (int) $row['jumlah_rs'],
    'luas' => (float) $row['luas_wilayah'],
    'kepadatan' => (int) $row['kepadatan']
]);