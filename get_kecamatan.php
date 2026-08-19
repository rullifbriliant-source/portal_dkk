<?php
header("Content-Type: application/json; charset=utf-8");
date_default_timezone_set("Asia/Jakarta");

require_once "config/database.php";

$id = isset($_GET['id']) ? trim($_GET['id']) : "";

if ($id == "") {
    echo json_encode([
        "status" => false,
        "message" => "Parameter kecamatan kosong."
    ]);
    exit;
}

/* ==========================================================
   DATA KECAMATAN
========================================================== */

$sql = "
SELECT
    nama_kecamatan,
    jumlah_penduduk,
    jumlah_kk,
    jumlah_desa,
    jumlah_puskesmas,
    jumlah_posyandu
FROM tbl_kecamatan
WHERE kode_kecamatan=?
LIMIT 1
";

$stmt = mysqli_prepare($config, $sql);

if (!$stmt) {

    echo json_encode([
        "status"=>false,
        "message"=>"Query gagal."
    ]);
    exit;
}

mysqli_stmt_bind_param($stmt,"s",$id);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

if(mysqli_num_rows($result)==0){

    echo json_encode([
        "status"=>false,
        "message"=>"Data tidak ditemukan."
    ]);

    exit;
}

$row = mysqli_fetch_assoc($result);

echo json_encode([

    "status"=>true,

    "id"=>$id,

    "nama"=>$row["nama_kecamatan"],

    "penduduk"=>(int)$row["jumlah_penduduk"],

    "kk"=>(int)$row["jumlah_kk"],

    "desa"=>(int)$row["jumlah_desa"],

    "puskesmas"=>(int)$row["jumlah_puskesmas"],

    "posyandu"=>(int)$row["jumlah_posyandu"]

]);