<?php
header("Content-Type: application/json; charset=utf-8");

require_once "config/database.php";


$sql="
SELECT isi_informasi AS text
FROM tbl_running
WHERE aktif='Y'
ORDER BY urutan ASC
LIMIT 1
";


$result=mysqli_query($koneksi,$sql);


if($result && mysqli_num_rows($result)>0){

    $data=mysqli_fetch_assoc($result);

    echo json_encode([
        "status"=>true,
        "text"=>$data['text']
    ]);

}else{

    echo json_encode([
        "status"=>false,
        "text"=>""
    ]);

}