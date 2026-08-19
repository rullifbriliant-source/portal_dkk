<?php

header("Content-Type: application/json; charset=utf-8");

require_once "../config/database.php";

$sql="

SELECT *

FROM tbl_faskes

WHERE aktif='Y'

ORDER BY nama_faskes

";

$query=mysqli_query($config,$sql);

$data=[];

while($row=mysqli_fetch_assoc($query)){

    $data[]=$row;

}

echo json_encode([
    "status"=>true,
    "data"=>$data
]);