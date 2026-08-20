<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Content-Type: application/json; charset=utf-8");

require_once "../config/database.php";

$id = isset($_GET['id']) ? $_GET['id'] : null;

if ($id) {
    $id = mysqli_real_escape_string($config, $id);
    
    // Cari berdasarkan nama_kecamatan (karena ID di SVG pakai nama)
    $sql = "SELECT 
                id_kecamatan,
                kode_kecamatan,
                nama_kecamatan,
                jumlah_penduduk,
                jumlah_desa,
                jumlah_puskesmas,
                jumlah_pustu,
                jumlah_posyandu,
                jumlah_rs,
                luas_wilayah,
                kepadatan
            FROM tbl_kecamatan 
            WHERE LOWER(nama_kecamatan) = LOWER('$id') 
            AND aktif='Y'
            LIMIT 1";
    
    $query = mysqli_query($config, $sql);
    
    if (!$query) {
        echo json_encode([
            "status" => false,
            "message" => "Query error: " . mysqli_error($config)
        ]);
        exit;
    }
    
    if ($row = mysqli_fetch_assoc($query)) {
        echo json_encode([
            "status" => true,
            "id_kecamatan" => (int)$row['id_kecamatan'],
            "kode_kecamatan" => $row['kode_kecamatan'],
            "nama" => $row['nama_kecamatan'],
            "penduduk" => (int)$row['jumlah_penduduk'],
            "desa" => (int)$row['jumlah_desa'],
            "puskesmas" => (int)$row['jumlah_puskesmas'],
            "pustu" => (int)$row['jumlah_pustu'],
            "posyandu" => (int)$row['jumlah_posyandu'],
            "rs" => (int)$row['jumlah_rs'],
            "luas" => (float)$row['luas_wilayah'],
            "kepadatan" => (int)$row['kepadatan']
        ]);
    } else {
        // Coba cari berdasarkan id_kecamatan
        $sql2 = "SELECT 
                    id_kecamatan,
                    kode_kecamatan,
                    nama_kecamatan,
                    jumlah_penduduk,
                    jumlah_desa,
                    jumlah_puskesmas,
                    jumlah_pustu,
                    jumlah_posyandu,
                    jumlah_rs,
                    luas_wilayah,
                    kepadatan
                FROM tbl_kecamatan 
                WHERE id_kecamatan = '$id' 
                AND aktif='Y'
                LIMIT 1";
        
        $query2 = mysqli_query($config, $sql2);
        if ($row2 = mysqli_fetch_assoc($query2)) {
            echo json_encode([
                "status" => true,
                "id_kecamatan" => (int)$row2['id_kecamatan'],
                "kode_kecamatan" => $row2['kode_kecamatan'],
                "nama" => $row2['nama_kecamatan'],
                "penduduk" => (int)$row2['jumlah_penduduk'],
                "desa" => (int)$row2['jumlah_desa'],
                "puskesmas" => (int)$row2['jumlah_puskesmas'],
                "pustu" => (int)$row2['jumlah_pustu'],
                "posyandu" => (int)$row2['jumlah_posyandu'],
                "rs" => (int)$row2['jumlah_rs'],
                "luas" => (float)$row2['luas_wilayah'],
                "kepadatan" => (int)$row2['kepadatan']
            ]);
        } else {
            echo json_encode([
                "status" => false,
                "message" => "Kecamatan '$id' tidak ditemukan"
            ]);
        }
    }
} else {
    // Ambil semua data
    $sql = "SELECT 
                id_kecamatan,
                kode_kecamatan,
                nama_kecamatan,
                jumlah_penduduk,
                jumlah_desa,
                jumlah_puskesmas,
                jumlah_pustu,
                jumlah_posyandu,
                jumlah_rs,
                luas_wilayah,
                kepadatan
            FROM tbl_kecamatan 
            WHERE aktif='Y' 
            ORDER BY nama_kecamatan";
    
    $query = mysqli_query($config, $sql);
    $data = [];
    while ($row = mysqli_fetch_assoc($query)) {
        $data[] = $row;
    }
    
    echo json_encode([
        "status" => true,
        "total" => count($data),
        "data" => $data
    ]);
}
?>