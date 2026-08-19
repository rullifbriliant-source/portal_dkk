<?php
/**
 * ==========================================================
 * PORTAL DKK
 * MAP API v1.0
 * ==========================================================
 */

ini_set('display_errors',1);
error_reporting(E_ALL);

header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");

define("ROOT_PATH", dirname(__DIR__));

require_once ROOT_PATH."/app/Services/MapService.php";

try{

   $id = isset($_GET['id']) ? trim($_GET['id']) : '';

if($id !== ''){

    $row = MapService::find($id);

    if(!$row){

        http_response_code(404);

        echo json_encode([
            "status"=>false,
            "message"=>"Kecamatan tidak ditemukan"
        ]);

        exit;
    }

    echo json_encode([
        "status"=>true,
        "district"=>$row
    ], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);

    exit;
}

$rows = MapService::all();

echo json_encode([
    "status"=>true,
    "generated"=>date("Y-m-d H:i:s"),
    "total"=>count($rows),
    "districts"=>$rows
], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);

}catch(Throwable $e){

    http_response_code(500);

    echo json_encode([

        "status"=>false,

        "message"=>$e->getMessage()

    ]);

}