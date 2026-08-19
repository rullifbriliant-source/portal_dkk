<?php
header("Content-Type: application/json; charset=utf-8");
date_default_timezone_set("Asia/Jakarta");

require_once "config/database.php";

/*
|--------------------------------------------------------------------------
| Fungsi menghitung jumlah record
|--------------------------------------------------------------------------
*/

function total($conn, $table, $where = "")
{
    $sql = "SELECT COUNT(*) AS jml FROM $table";

    if ($where != "") {
        $sql .= " WHERE " . $where;
    }

    $q = mysqli_query($conn, $sql);

    if (!$q) {
        return 0;
    }

    $r = mysqli_fetch_assoc($q);

    return (int)$r["jml"];
}

/*
|--------------------------------------------------------------------------
| Statistik
|--------------------------------------------------------------------------
*/

$data = array(

    // Penduduk
    "penduduk" => total($config, "tbl_penduduk"),

    // Puskesmas
    "puskesmas" => total($config, "tbl_puskesmas"),

    // Pustu
    "pustu" => total($config, "tbl_pustu"),

    // Posyandu
    "posyandu" => total($config, "tbl_posyandu"),

    // Pegawai
    "pegawai" => total($config, "tbl_pegawai"),

    // Program
    "program" => total($config, "tbl_program")

);

echo json_encode(array(

    "status" => true,

    "server_time" => date("Y-m-d H:i:s"),

    "data" => $data

));