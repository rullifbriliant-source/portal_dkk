<?php



error_reporting(E_ALL);
ini_set('display_errors',1);

header("Content-Type: application/json; charset=utf-8");


require_once "../config/database.php";

$sql="

SELECT

*,

CASE

WHEN luas_wilayah > 0

THEN ROUND(
jumlah_penduduk / luas_wilayah
)

ELSE 0

END AS kepadatan


FROM tbl_kecamatan


WHERE aktif='Y'


ORDER BY nama_kecamatan

";

$query=mysqli_query(
    $config,
    $sql
);



$data=[];


while($row=mysqli_fetch_assoc($query)){


    $data[]=$row;


}



echo json_encode([

    "status"=>true,

    "total"=>count($data),

    "data"=>$data

]);