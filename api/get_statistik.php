<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header("Content-Type: application/json; charset=utf-8");
require_once "../config/database.php";

// Ambil total statistik dari semua kecamatan
$sql = "SELECT 
    SUM(jumlah_penduduk) as total_penduduk,
    SUM(jumlah_kk) as total_kk,
    SUM(jumlah_puskesmas) as total_puskesmas,
    SUM(jumlah_pustu) as total_pustu,
    SUM(jumlah_posyandu) as total_posyandu,
    SUM(jumlah_desa) as total_desa,
    COUNT(*) as total_kecamatan
FROM tbl_kecamatan 
WHERE aktif='Y'";

$query = mysqli_query($config, $sql);
$totals = mysqli_fetch_assoc($query);

// Ambil data tenaga kesehatan (contoh, sesuaikan dengan tabel Anda)
$sql_nakes = "SELECT 
    COUNT(*) as total_nakes
FROM tbl_pegawai 
WHERE aktif='Y' AND kategori='nakes'";
$query_nakes = mysqli_query($config, $sql_nakes);
$nakes = mysqli_fetch_assoc($query_nakes);

// Ambil data cakupan imunisasi (contoh)
$sql_imunisasi = "SELECT 
    AVG(cakupan) as rata_cakupan
FROM tbl_imunisasi 
WHERE tahun=YEAR(CURRENT_DATE)";
$query_imunisasi = mysqli_query($config, $sql_imunisasi);
$imunisasi = mysqli_fetch_assoc($query_imunisasi);

// Ambil data stunting
$sql_stunting = "SELECT 
    SUM(jumlah_kasus) as total_stunting,
    SUM(total_balita) as total_balita
FROM tbl_stunting 
WHERE tahun=YEAR(CURRENT_DATE)";
$query_stunting = mysqli_query($config, $sql_stunting);
$stunting = mysqli_fetch_assoc($query_stunting);

// Hitung persentase stunting
$persen_stunting = 0;
if ($stunting['total_balita'] > 0) {
    $persen_stunting = round(($stunting['total_stunting'] / $stunting['total_balita']) * 100, 2);
}

// Ambil data penyakit terbanyak
$sql_penyakit = "SELECT 
    nama_penyakit,
    jumlah_kasus
FROM tbl_penyakit 
WHERE tahun=YEAR(CURRENT_DATE)
ORDER BY jumlah_kasus DESC 
LIMIT 5";
$query_penyakit = mysqli_query($config, $sql_penyakit);
$penyakit = [];
while ($row = mysqli_fetch_assoc($query_penyakit)) {
    $penyakit[] = $row;
}

// Ambil agenda hari ini
$sql_agenda = "SELECT 
    judul,
    waktu_mulai,
    lokasi
FROM tbl_agenda 
WHERE tanggal=CURDATE() 
ORDER BY waktu_mulai ASC 
LIMIT 3";
$query_agenda = mysqli_query($config, $sql_agenda);
$agenda = [];
while ($row = mysqli_fetch_assoc($query_agenda)) {
    $agenda[] = $row;
}

echo json_encode([
    "status" => true,
    "data" => [
        "total_penduduk" => (int)$totals['total_penduduk'],
        "total_kk" => (int)$totals['total_kk'],
        "total_puskesmas" => (int)$totals['total_puskesmas'],
        "total_pustu" => (int)$totals['total_pustu'],
        "total_posyandu" => (int)$totals['total_posyandu'],
        "total_desa" => (int)$totals['total_desa'],
        "total_kecamatan" => (int)$totals['total_kecamatan'],
        "total_nakes" => (int)$nakes['total_nakes'],
        "rata_cakupan_imunisasi" => (float)$imunisasi['rata_cakupan'],
        "total_stunting" => (int)$stunting['total_stunting'],
        "persen_stunting" => $persen_stunting,
        "penyakit_terbanyak" => $penyakit,
        "agenda_hari_ini" => $agenda
    ]
]);
?>