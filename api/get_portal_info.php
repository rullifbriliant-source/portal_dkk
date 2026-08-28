<?php
header("Content-Type: application/json; charset=utf-8");
require_once "../config/database.php";

$sql = "SELECT deskripsi FROM tbl_portal_info WHERE id = 1";
$query = mysqli_query($config, $sql);
$row = mysqli_fetch_assoc($query);

echo json_encode([
    "status" => true,
    "data" => [
        "deskripsi" => $row['deskripsi'] ?? ''
    ]
]);
?>