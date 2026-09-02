<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header("Content-Type: application/json; charset=utf-8");
require_once __DIR__ . "/../config/database.php";

$kecamatanParam = $_GET['kecamatan'] ?? $_GET['id'] ?? null;
$whereKecamatan = "";

if ($kecamatanParam) {
    if (strpos($kecamatanParam, 'kec_') === 0) {
        $kecamatanParam = substr($kecamatanParam, 4);
    }
    $escKec = mysqli_real_escape_string($config, $kecamatanParam);
    if (is_numeric($kecamatanParam)) {
        $whereKecamatan = " AND f.id_kecamatan = " . (int)$kecamatanParam;
    } else {
        $whereKecamatan = " AND (LOWER(f.kecamatan) = LOWER('$escKec') OR LOWER(k.nama_kecamatan) = LOWER('$escKec'))";
    }
}

// Hitung jumlah per jenis dari tbl_faskes secara dinamis
$sql = "SELECT 
    f.jenis,
    COUNT(*) as total
FROM tbl_faskes f
LEFT JOIN tbl_kecamatan k ON f.id_kecamatan = k.id_kecamatan
WHERE f.aktif = 'Y' $whereKecamatan
GROUP BY f.jenis";

$query = mysqli_query($config, $sql);
$counts = [
    'Puskesmas' => 0,
    'Pustu' => 0,
    'Klinik' => 0,
    'Rumah Sakit' => 0
];

while ($row = mysqli_fetch_assoc($query)) {
    $counts[$row['jenis']] = (int)$row['total'];
}

$items = [
    ['id' => 1, 'nama' => 'Puskesmas', 'nilai' => $counts['Puskesmas'] ?? 0],
    ['id' => 2, 'nama' => 'Pustu', 'nilai' => $counts['Pustu'] ?? 0],
    ['id' => 3, 'nama' => 'Klinik', 'nilai' => $counts['Klinik'] ?? 0],
    ['id' => 4, 'nama' => 'Rumah Sakit', 'nilai' => $counts['Rumah Sakit'] ?? 0]
];

// Tambahkan jenis lain jika ada (misal Poskesdes, Apotek, Laboratorium) yang nilainya > 0
$extraIndex = 5;
foreach ($counts as $jenisName => $val) {
    if (!in_array($jenisName, ['Puskesmas', 'Pustu', 'Klinik', 'Rumah Sakit']) && $val > 0) {
        $items[] = [
            'id' => $extraIndex++,
            'nama' => $jenisName,
            'nilai' => $val
        ];
    }
}

echo json_encode([
    "status" => true,
    "data" => $items,
    "total" => array_sum($counts),
    "filter_kecamatan" => $kecamatanParam
]);