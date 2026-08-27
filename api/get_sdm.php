<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header("Content-Type: application/json; charset=utf-8");
require_once "../config/database.php";

// ============================================================
// PARAMETER KECAMATAN (opsional)
// Kalau ada -> ambil SDMK khusus kecamatan itu (tbl_sdm_kecamatan)
// Kalau kosong -> ambil TOTAL SDM se-kabupaten (tbl_sdm_items)
// ============================================================
$kecamatan = isset($_GET['kecamatan']) ? trim($_GET['kecamatan']) : '';

$items = [];

if ($kecamatan !== '') {

    // Cari id_kecamatan dari nama (case-insensitive, sama seperti api/kecamatan.php)
    $kecEsc = mysqli_real_escape_string($config, $kecamatan);
    $kecQuery = mysqli_query($config, "
        SELECT id_kecamatan FROM tbl_kecamatan
        WHERE LOWER(nama_kecamatan) = LOWER('$kecEsc') AND aktif = 'Y'
        LIMIT 1
    ");
    $kecRow = $kecQuery ? mysqli_fetch_assoc($kecQuery) : null;

    if ($kecRow) {
        $id_kecamatan = (int)$kecRow['id_kecamatan'];

        // Ambil semua item SDM, join jumlah khusus kecamatan ini (default 0 jika belum diisi)
        $sql = "
            SELECT si.id, si.nama_item,
                   COALESCE(sk.jumlah, 0) AS nilai
            FROM tbl_sdm_items si
            LEFT JOIN tbl_sdm_kecamatan sk
                ON sk.id_item = si.id AND sk.id_kecamatan = $id_kecamatan
            WHERE si.aktif = 'Y'
            ORDER BY si.urutan
        ";
        $query = mysqli_query($config, $sql);
        while ($row = mysqli_fetch_assoc($query)) {
            $items[] = [
                'id' => (int)$row['id'],
                'nama' => $row['nama_item'],
                'nilai' => (int)$row['nilai']
            ];
        }
    }
    // Kalau kecamatan tidak ditemukan, $items tetap kosong -> fallback default di bawah

} else {

    // TOTAL SE-KABUPATEN (perilaku lama, tidak berubah)
    $sql = "SELECT id, nama_item, nilai FROM tbl_sdm_items WHERE aktif='Y' ORDER BY urutan";
    $query = mysqli_query($config, $sql);
    while ($row = mysqli_fetch_assoc($query)) {
        $items[] = [
            'id' => (int)$row['id'],
            'nama' => $row['nama_item'],
            'nilai' => (int)$row['nilai']
        ];
    }
}

if (count($items) > 0) {
    echo json_encode([
        "status" => true,
        "kecamatan" => $kecamatan !== '' ? $kecamatan : null,
        "data" => $items
    ]);
} else {
    // Default jika belum ada data sama sekali
    echo json_encode([
        "status" => true,
        "kecamatan" => $kecamatan !== '' ? $kecamatan : null,
        "data" => [
            ['nama' => 'Dokter', 'nilai' => 0],
            ['nama' => 'Perawat', 'nilai' => 0],
            ['nama' => 'Bidan', 'nilai' => 0],
            ['nama' => 'Nakes Lainnya', 'nilai' => 0]
        ]
    ]);
}
?>