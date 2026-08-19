<?php
/**
 * ==========================================================
 * PORTAL DKK
 * MAP BUILDER
 * EXPORT ENGINE v1.0
 * ==========================================================
 */

header('Content-Type: application/json; charset=utf-8');

$svgFile = dirname(__DIR__,2).'/assets/svg/sukoharjo.svg';
$dataFile = __DIR__.'/data.json';
$outputFile = dirname(__DIR__,2).'/assets/svg/sukoharjo_interactive.svg';

if(!file_exists($svgFile)){
    echo json_encode([
        'status'=>false,
        'message'=>'File sukoharjo.svg tidak ditemukan.'
    ]);
    exit;
}

if(!file_exists($dataFile)){
    echo json_encode([
        'status'=>false,
        'message'=>'data.json belum ada.'
    ]);
    exit;
}

$data=json_decode(file_get_contents($dataFile),true);

if(!is_array($data)){
    echo json_encode([
        'status'=>false,
        'message'=>'data.json rusak.'
    ]);
    exit;
}

/* ==========================================================
   LOAD SVG
==========================================================*/

$dom=new DOMDocument();

$dom->preserveWhiteSpace=false;

$dom->formatOutput=true;

$dom->load($svgFile);

$paths=$dom->getElementsByTagName("path");

$used=[];

foreach($data as $item){

    if(isset($used[$item["id"]])){

        echo json_encode([

            "status"=>false,

            "message"=>"ID '".$item["id"]."' ganda."

        ]);

        exit;

    }

    $used[$item["id"]]=true;

}

/* ==========================================================
   MAPPING
==========================================================*/

foreach($data as $item){

    $index=(int)$item["path"]-1;

    if(!$paths->item($index)){
        continue;
    }

    $path=$paths->item($index);

    $path->setAttribute("id",$item["id"]);

    $path->setAttribute("class","district");

    $path->setAttribute("data-name",$item["nama"]);

}

/* ==========================================================
   SAVE
==========================================================*/

$result = $dom->save($outputFile);

if($result === false){

    echo json_encode([
        "status" => false,
        "message" => "Gagal menyimpan file SVG.",
        "output" => $outputFile
    ]);

    exit;
}

echo json_encode([
    "status" => true,
    "message" => "Interactive SVG berhasil dibuat.",
    "file" => "assets/svg/sukoharjo_interactive.svg",
    "output" => $outputFile,
    "total" => count($data)
]);