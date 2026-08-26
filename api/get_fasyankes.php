<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header("Content-Type: application/json; charset=utf-8");
require_once "../config/database.php";

// Ambil dari tbl_fasyankes_items (dinamis)
$sql = "SELECT id, nama_item, nilai FROM tbl_fasyankes_items WHERE aktif='Y' ORDER BY urutan";
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
    // Jika belum ada data, beri default
    echo json_encode([
        "status" => true,
        "data" => [
            ['nama' => 'Puskesmas', 'nilai' => 12],
            ['nama' => 'Pustu', 'nilai' => 24],
            ['nama' => 'Klinik', 'nilai' => 18],
            ['nama' => 'Rumah Sakit', 'nilai' => 8]
        ]
    ]);
}
?>