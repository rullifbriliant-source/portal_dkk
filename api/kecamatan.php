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

// Bersihkan prefix kec_ jika ada
if (strpos($id, 'kec_') === 0) {
    $id = substr($id, 4);
}

$id = mysqli_real_escape_string($config, $id);

/*
|--------------------------------------------------------------------------
| AMBIL DATA DASAR KECAMATAN
|--------------------------------------------------------------------------
*/

$sql = "SELECT
            id_kecamatan,
            kode_kecamatan,
            nama_kecamatan,
            jumlah_penduduk,
            jumlah_kk,
            jumlah_desa,
            jumlah_posyandu,
            luas_wilayah,
            kepadatan
        FROM tbl_kecamatan
        WHERE (LOWER(nama_kecamatan) = LOWER('$id') OR id_kecamatan = '$id')
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

$idKec = (int)$row['id_kecamatan'];
$namaKec = mysqli_real_escape_string($config, $row['nama_kecamatan']);

/*
|--------------------------------------------------------------------------
| HITUNG JUMLAH FASILITAS KESEHATAN SECARA DINAMIS DARI tbl_faskes
|--------------------------------------------------------------------------
*/
$sqlFaskes = "SELECT 
    SUM(CASE WHEN jenis = 'Puskesmas' THEN 1 ELSE 0 END) AS count_puskesmas,
    SUM(CASE WHEN jenis = 'Pustu' THEN 1 ELSE 0 END) AS count_pustu,
    SUM(CASE WHEN jenis = 'Klinik' THEN 1 ELSE 0 END) AS count_klinik,
    SUM(CASE WHEN jenis = 'Rumah Sakit' THEN 1 ELSE 0 END) AS count_rs
FROM tbl_faskes 
WHERE (id_kecamatan = $idKec OR LOWER(kecamatan) = LOWER('$namaKec')) 
AND aktif = 'Y'";

$queryFaskes = mysqli_query($config, $sqlFaskes);
$faskesCount = mysqli_fetch_assoc($queryFaskes);

$puskesmas = (int)($faskesCount['count_puskesmas'] ?? 0);
$pustu = (int)($faskesCount['count_pustu'] ?? 0);
$klinik = (int)($faskesCount['count_klinik'] ?? 0);
$rumah_sakit = (int)($faskesCount['count_rs'] ?? 0);

// ============================================================
// RESPONSE
// ============================================================

echo json_encode([
    'status' => true,

    'id_kecamatan' => $idKec,
    'kode_kecamatan' => $row['kode_kecamatan'],
    'nama' => $row['nama_kecamatan'],

    // DATA DASAR
    'penduduk' => (int) $row['jumlah_penduduk'],
    'kk' => (int) $row['jumlah_kk'],
    'desa' => (int) $row['jumlah_desa'],

    // FASYANKES (DIHITUNG DARI DATA RIIL tbl_faskes)
    'puskesmas' => $puskesmas,
    'pustu' => $pustu,
    'klinik' => $klinik,
    'rumah_sakit' => $rumah_sakit,

    // DATA TAMBAHAN
    'posyandu' => (int) $row['jumlah_posyandu'],
    'rs' => $rumah_sakit,
    'luas' => (float) $row['luas_wilayah'],
    'kepadatan' => (int) $row['kepadatan']
]);