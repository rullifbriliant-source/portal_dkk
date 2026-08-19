<?php
header("Content-Type: application/json; charset=utf-8");
date_default_timezone_set("Asia/Jakarta");

$id = isset($_GET["id"])
    ? strtolower(trim($_GET["id"]))
    : "";

$data = [

    "baki" => [
        "nama" => "Kecamatan Baki",
        "penduduk" => 65231,
        "kk" => 18520,
        "puskesmas" => 1,
        "pustu" => 2,
        "posyandu" => 58,
        "desa" => 14
    ],

    "bendosari" => [
        "nama" => "Kecamatan Bendosari",
        "penduduk" => 54321,
        "kk" => 16002,
        "puskesmas" => 1,
        "pustu" => 3,
        "posyandu" => 47,
        "desa" => 13
    ],

    "bulu" => [
        "nama" => "Kecamatan Bulu",
        "penduduk" => 39215,
        "kk" => 12100,
        "puskesmas" => 1,
        "pustu" => 2,
        "posyandu" => 39,
        "desa" => 12
    ],

    "gatak" => [
        "nama" => "Kecamatan Gatak",
        "penduduk" => 48995,
        "kk" => 14233,
        "puskesmas" => 1,
        "pustu" => 2,
        "posyandu" => 44,
        "desa" => 14
    ],

    "grogol" => [
        "nama" => "Kecamatan Grogol",
        "penduduk" => 124650,
        "kk" => 35210,
        "puskesmas" => 2,
        "pustu" => 5,
        "posyandu" => 81,
        "desa" => 14
    ],

    "kartasura" => [
        "nama" => "Kecamatan Kartasura",
        "penduduk" => 108542,
        "kk" => 30982,
        "puskesmas" => 2,
        "pustu" => 4,
        "posyandu" => 74,
        "desa" => 12
    ],

    "mojolaban" => [
        "nama" => "Kecamatan Mojolaban",
        "penduduk" => 81234,
        "kk" => 23541,
        "puskesmas" => 2,
        "pustu" => 3,
        "posyandu" => 66,
        "desa" => 15
    ],

    "nguter" => [
        "nama" => "Kecamatan Nguter",
        "penduduk" => 58324,
        "kk" => 17410,
        "puskesmas" => 1,
        "pustu" => 2,
        "posyandu" => 48,
        "desa" => 16
    ],

    "polokarto" => [
        "nama" => "Kecamatan Polokarto",
        "penduduk" => 69114,
        "kk" => 19652,
        "puskesmas" => 2,
        "pustu" => 3,
        "posyandu" => 55,
        "desa" => 17
    ],

    "sukoharjo" => [
        "nama" => "Kecamatan Sukoharjo",
        "penduduk" => 98762,
        "kk" => 28611,
        "puskesmas" => 2,
        "pustu" => 4,
        "posyandu" => 72,
        "desa" => 14
    ],

    "tawangsari" => [
        "nama" => "Kecamatan Tawangsari",
        "penduduk" => 50412,
        "kk" => 14882,
        "puskesmas" => 1,
        "pustu" => 2,
        "posyandu" => 45,
        "desa" => 12
    ],

    "weru" => [
        "nama" => "Kecamatan Weru",
        "penduduk" => 42356,
        "kk" => 12652,
        "puskesmas" => 1,
        "pustu" => 2,
        "posyandu" => 40,
        "desa" => 13
    ]

];

if (!isset($data[$id])) {

    echo json_encode([
        "status" => false,
        "message" => "Kecamatan tidak ditemukan"
    ]);

    exit;
}

echo json_encode(array_merge(
    ["status" => true],
    $data[$id]
));