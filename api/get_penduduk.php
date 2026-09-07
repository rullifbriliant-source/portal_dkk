<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . '/../config/database.php';

/*
|--------------------------------------------------------------------------
| DATA PENDUDUK PER KECAMATAN (read-only, sumber: tbl_kecamatan)
| Catatan: database tidak memiliki rincian laki-laki/perempuan,
| sehingga endpoint ini hanya menyediakan total per kecamatan.
|--------------------------------------------------------------------------
*/

$data = [];
$q = mysqli_query($config, "SELECT nama_kecamatan, jumlah_penduduk FROM tbl_kecamatan WHERE aktif='Y' ORDER BY nama_kecamatan");

if (!$q) {
    echo json_encode([
        "status" => false,
        "message" => mysqli_error($config)
    ]);
    exit;
}

while ($r = mysqli_fetch_assoc($q)) {
    $data[] = [
        "kecamatan" => $r["nama_kecamatan"],
        "penduduk" => (int)$r["jumlah_penduduk"]
    ];
}

$total = 0;
foreach ($data as $d) {
    $total += $d["penduduk"];
}

echo json_encode([
    "status" => true,
    "total" => $total,
    "data" => $data,
    "laki_laki" => null,
    "perempuan" => null,
    "source" => "tbl_kecamatan"
], JSON_UNESCAPED_UNICODE);
