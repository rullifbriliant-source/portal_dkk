<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header("Content-Type: application/json; charset=utf-8");
require_once __DIR__ . "/../config/database.php";

/*
|--------------------------------------------------------------------------
| REKAP SARANA PELAYANAN KESEHATAN 2021–2025 (READ-ONLY)
|--------------------------------------------------------------------------
| Sumber: tbl_faskes_rekap (agregat KABUPATEN, bukan per kecamatan).
| Hanya SELECT aktif='Y'. Tidak ada INSERT/UPDATE/DELETE.
| Tabel kosong (atau migration belum jalan) -> status true, data [].
| Response: { status, data: [{tahun, items: [{jenis_sarana, jumlah}]}], total }
|--------------------------------------------------------------------------
*/

$check = @mysqli_query($config, "SHOW TABLES LIKE 'tbl_faskes_rekap'");
if (!$check || mysqli_num_rows($check) === 0) {
    echo json_encode([
        "status" => true,
        "data" => [],
        "total" => 0,
        "note" => "migration belum dijalankan"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$query = mysqli_query(
    $config,
    "SELECT tahun, jenis_sarana, jumlah
     FROM tbl_faskes_rekap
     WHERE aktif = 'Y'
     ORDER BY tahun ASC, jenis_sarana ASC"
);

if (!$query) {
    echo json_encode([
        "status" => false,
        "message" => "Gagal membaca data rekap"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$byYear = [];
$total = 0;
while ($row = mysqli_fetch_assoc($query)) {
    $t = (int)$row['tahun'];
    if (!isset($byYear[$t])) {
        $byYear[$t] = ["tahun" => $t, "items" => []];
    }
    $byYear[$t]["items"][] = [
        "jenis_sarana" => $row['jenis_sarana'],
        "jumlah" => (int)$row['jumlah']
    ];
    $total++;
}

echo json_encode([
    "status" => true,
    "data" => array_values($byYear),
    "total" => $total
], JSON_UNESCAPED_UNICODE);
