<?php

header("Content-Type: application/json; charset=utf-8");

require_once "config/database.php";


$result=[];


/*
JUMLAH PENDUDUK
sesuaikan dengan tabel Anda
*/

$q=mysqli_query(
$koneksi,
"SELECT COUNT(*) jumlah FROM penduduk"
);


$result['penduduk']=0;


if($q){

    $result['penduduk']=
    mysqli_fetch_assoc($q)['jumlah'];

}


/*
PUSKESMAS
*/

$q=mysqli_query(
$koneksi,
"SELECT COUNT(*) jumlah FROM puskesmas"
);


$result['puskesmas']=0;


if($q){

    $result['puskesmas']=
    mysqli_fetch_assoc($q)['jumlah'];

}


/*
PUSTU
*/

$q=mysqli_query(
$koneksi,
"SELECT COUNT(*) jumlah FROM pustu"
);


$result['pustu']=0;


if($q){

    $result['pustu']=
    mysqli_fetch_assoc($q)['jumlah'];

}


/*
PROGRAM
*/

$q=mysqli_query(
$koneksi,
"SELECT COUNT(*) jumlah FROM program"
);


$result['program']=0;


if($q){

    $result['program']=
    mysqli_fetch_assoc($q)['jumlah'];

}



echo json_encode([

    "status"=>true,

    "penduduk"=>$result['penduduk'],

    "puskesmas"=>$result['puskesmas'],

    "pustu"=>$result['pustu'],

    "program"=>$result['program']

]);