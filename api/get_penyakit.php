<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header("Content-Type: application/json; charset=utf-8");

require_once "../config/database.php";

$kecamatan = isset($_GET['kecamatan']) ? mysqli_real_escape_string($config, $_GET['kecamatan']) : null;
$kategori  = isset($_GET['kategori'])  ? mysqli_real_escape_string($config, $_GET['kategori'])  : null;

if (!$kecamatan) {
    echo json_encode(["status" => false, "message" => "Parameter kecamatan wajib diisi"]);
    exit;
}

// Bersihkan prefix "kec_" kalau ada, samakan dengan format api/kecamatan.php
$kecamatan = preg_replace('/^kec_/', '', $kecamatan);

$sql = "SELECT 
            p.nama_penyakit,
            p.kategori,
            SUM(k.jumlah_kasus) AS total_kasus
        FROM tbl_kasus_penyakit k
        JOIN tbl_kecamatan c ON c.id_kecamatan = k.id_kecamatan
        JOIN tbl_penyakit p ON p.id_penyakit = k.id_penyakit
        WHERE LOWER(c.nama_kecamatan) = LOWER('$kecamatan')
        AND c.aktif = 'Y'";

if ($kategori) {
    $sql .= " AND p.kategori = '$kategori'";
}

$sql .= " GROUP BY p.id_penyakit ORDER BY total_kasus DESC";

$query = mysqli_query($config, $sql);

if (!$query) {
    echo json_encode(["status" => false, "message" => "Query error: " . mysqli_error($config)]);
    exit;
}

$data = [];
$grandTotal = 0;
while ($row = mysqli_fetch_assoc($query)) {
    $row['total_kasus'] = (int)$row['total_kasus'];
    $grandTotal += $row['total_kasus'];
    $data[] = $row;
}

echo json_encode([
    "status" => true,
    "kecamatan" => $kecamatan,
    "kategori" => $kategori,
    "total" => $grandTotal,
    "data" => $data
]);