<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header("Content-Type: application/json; charset=utf-8");
require_once "../config/database.php";

// Ambil dari tbl_penyakit_items (tabel dinamis untuk 10 penyakit populer)
$sql = "SELECT id, nama_item, nilai FROM tbl_penyakit_items WHERE aktif='Y' ORDER BY urutan"; //LIMIT DASHBOARD 10 PENYAKIT POPULER
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
            ['nama' => 'ISPA', 'nilai' => 1540],
            ['nama' => 'Hipertensi', 'nilai' => 1230],
            ['nama' => 'COVID-19', 'nilai' => 1100],
            ['nama' => 'Diare', 'nilai' => 890],
            ['nama' => 'Gastritis', 'nilai' => 760],
            ['nama' => 'TBC', 'nilai' => 640],
            ['nama' => 'Diabetes', 'nilai' => 510],
            ['nama' => 'Asma', 'nilai' => 430],
            ['nama' => 'Pneumonia', 'nilai' => 380],
            ['nama' => 'Demam Berdarah', 'nilai' => 320]
        ]
    ]);
}
?>