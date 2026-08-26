<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header("Content-Type: application/json; charset=utf-8");
require_once "../config/database.php";

// Ambil dari tbl_sdm_items
$sql = "SELECT id, nama_item, nilai FROM tbl_sdm_items WHERE aktif='Y' ORDER BY urutan";
$query = mysqli_query($config, $sql);

$items = [];
while ($row = mysqli_fetch_assoc($query)) {
    $items[] = [
        'id' => (int)$row['id'],
        'nama' => $row['nama_item'],
        'nilai' => (int)$row['nilai']
    ];
}

if (count($items) > 0) {
    echo json_encode([
        "status" => true,
        "data" => $items
    ]);
} else {
    // Default jika belum ada data
    echo json_encode([
        "status" => true,
        "data" => [
            ['nama' => 'Dokter', 'nilai' => 85],
            ['nama' => 'Perawat', 'nilai' => 320],
            ['nama' => 'Bidan', 'nilai' => 210],
            ['nama' => 'Nakes Lainnya', 'nilai' => 145]
        ]
    ]);
}
?>