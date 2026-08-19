<?php
header("Content-Type: application/json; charset=utf-8");

require_once "config/database.php";

date_default_timezone_set("Asia/Jakarta");


$tanggal=date("Y-m-d");


$sql="
SELECT

nomor_agenda,
tanggal,
waktu_mulai AS mulai,
waktu_selesai AS selesai,
topik,
uraian,
ruangan

FROM agenda

WHERE tanggal='$tanggal'

ORDER BY waktu_mulai ASC
";


$query=mysqli_query($koneksi,$sql);


$data=[];


if($query){

    while($row=mysqli_fetch_assoc($query)){

        $data[]=$row;

    }

}


echo json_encode([

    "status"=>true,

    "total"=>count($data),

    "agenda"=>$data

]);