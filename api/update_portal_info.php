<?php
header("Content-Type: application/json; charset=utf-8");
require_once "../config/database.php";

$input = json_decode(file_get_contents("php://input"), true);
$deskripsi = mysqli_real_escape_string($config, $input['deskripsi'] ?? '');

$sql = "UPDATE tbl_portal_info SET deskripsi = '$deskripsi' WHERE id = 1";
mysqli_query($config, $sql);

echo json_encode(["status" => true, "message" => "Berhasil diupdate"]);
?>