<?php

header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . "/../config/database.php";

// Dukungan filter kecamatan: id numerik, nama, atau prefix "kec_"
$kecamatanParam = $_GET['kecamatan'] ?? $_GET['id'] ?? null;

$whereClauses = ["f.aktif = 'Y'"];

if ($kecamatanParam) {
    if (strpos($kecamatanParam, 'kec_') === 0) {
        $kecamatanParam = substr($kecamatanParam, 4);
    }

    if (is_numeric($kecamatanParam)) {
        $whereClauses[] = "f.id_kecamatan = " . (int)$kecamatanParam;
    } else {
        $escKec = mysqli_real_escape_string($config, $kecamatanParam);
        $whereClauses[] = "(LOWER(f.kecamatan) = LOWER('$escKec') OR LOWER(k.nama_kecamatan) = LOWER('$escKec'))";
    }
}

$whereSql = implode(' AND ', $whereClauses);

$sql = "SELECT
            f.id_faskes,
            f.kode_faskes,
            f.nama_faskes,
            f.jenis,
            f.id_kecamatan,
            f.kecamatan AS kecamatan_slug,
            k.nama_kecamatan,
            f.alamat,
            f.telepon,
            f.email,
            f.foto,
            f.aktif,
            (SELECT b.total FROM tbl_faskes_bed b
             WHERE b.id_faskes = f.id_faskes AND b.kategori = 'Umum' AND b.aktif = 'Y'
             LIMIT 1) AS jumlah_kasur
        FROM tbl_faskes f
        LEFT JOIN tbl_kecamatan k ON f.id_kecamatan = k.id_kecamatan
        WHERE $whereSql
        ORDER BY f.jenis ASC, f.nama_faskes ASC";

$query = mysqli_query($config, $sql);

if (!$query) {
    echo json_encode([
        "status" => false,
        "message" => mysqli_error($config)
    ]);
    exit;
}

$data = [];

while ($row = mysqli_fetch_assoc($query)) {
    // null bila belum ada data (jangan tampilkan 0 palsu)
    $row['jumlah_kasur'] = $row['jumlah_kasur'] === null ? null : (int)$row['jumlah_kasur'];
    $data[] = $row;
}

echo json_encode([
    "status" => true,
    "data" => $data,
    "total" => count($data),
    "filter_kecamatan" => $kecamatanParam
]);