<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header("Content-Type: application/json; charset=utf-8");
require_once "../config/database.php";

$kecamatan = isset($_GET['kecamatan']) ? mysqli_real_escape_string($config, $_GET['kecamatan']) : '';

if ($kecamatan) {
    // AMBIL DAFTAR 10 NAMA PENYAKIT MASTER DARI TOTAL KABUPATEN (YANG AKTIF SAJA)
    $sqlMaster = "SELECT nama_item FROM tbl_penyakit_items WHERE aktif='Y' ORDER BY urutan LIMIT 10";
    $queryMaster = mysqli_query($config, $sqlMaster);
    
    // AMBIL NILAI YANG SUDAH DIINPUT PER KECAMATAN
    $sqlNilai = "SELECT nama_item, nilai FROM tbl_penyakit_kecamatan WHERE aktif='Y' AND LOWER(kode_kecamatan) = LOWER('$kecamatan')";
    $queryNilai = mysqli_query($config, $sqlNilai);
    
    $dataNilai = [];
    while ($row = mysqli_fetch_assoc($queryNilai)) {
        $dataNilai[$row['nama_item']] = (int)$row['nilai'];
    }
    
    $items = [];
    $no = 1;
    while ($row = mysqli_fetch_assoc($queryMaster)) {
        $nama = $row['nama_item'];
        $items[] = [
            'id' => $no++, 
            'nama' => $nama,
            'nilai' => isset($dataNilai[$nama]) ? $dataNilai[$nama] : 0
        ];
    }
    
} else {
    $sql = "SELECT id, nama_item, nilai FROM tbl_penyakit_items WHERE aktif='Y' ORDER BY urutan LIMIT 10";
    $query = mysqli_query($config, $sql);
    $items = [];
    while ($row = mysqli_fetch_assoc($query)) {
        $items[] = [
            'id' => (int)$row['id'],
            'nama' => $row['nama_item'],
            'nilai' => (int)$row['nilai']
        ];
    }
}

echo json_encode([
    "status" => true,
    "data" => $items
]);
?>