<?php
/**
 * ==========================================================
 * PORTAL DKK
 * Map Service v1.0
 * ==========================================================
 */

class MapService
{

    /**
     * lokasi SVG interactive
     */
    private static function svgFile()
    {
        return ROOT_PATH . "/assets/svg/sukoharjo_interactive.svg";
    }

    /**
     * ======================================================
     * Ambil semua kecamatan dari SVG
     * ======================================================
     */

    public static function all()
    {

        $file=self::svgFile();

        if(!file_exists($file)){
            return [];
        }

        libxml_use_internal_errors(true);

        $dom=new DOMDocument();

        $dom->load($file);

        $paths=$dom->getElementsByTagName("path");

        $rows=[];

        foreach($paths as $path){

            if($path->getAttribute("class")!="district"){
                continue;
            }

            $rows[]=[

                "id"=>$path->getAttribute("id"),

                "nama"=>$path->getAttribute("data-name"),

                "warna"=>"#2196F3",

                "status"=>"normal"

            ];

        }

        return $rows;

    }

    /**
     * ======================================================
     * Cari Kecamatan
     * ======================================================
     */

    public static function find($id)
    {

        foreach(self::all() as $row){

            if($row["id"]==$id){

                return $row;

            }

        }

        return null;

    }

    /**
     * ======================================================
     * Apakah ada?
     * ======================================================
     */

    public static function exists($id)
    {

        return self::find($id)!==null;

    }

    /**
     * ======================================================
     * Total Kecamatan
     * ======================================================
     */

    public static function districtCount()
    {

        return count(self::all());

    }

    /**
     * ======================================================
     * Warna Status
     * ======================================================
     */

    public static function colors()
    {

        return [

            "normal"=>"#2196F3",

            "baik"=>"#4CAF50",

            "warning"=>"#FFC107",

            "bahaya"=>"#F44336",

            "nodata"=>"#9E9E9E"

        ];

    }

    /**
     * ======================================================
     * Update warna sementara
     * (v1 belum menyimpan database)
     * ======================================================
     */

    public static function updateStatus(&$district,$status)
    {

        $colors=self::colors();

        if(isset($colors[$status])){

            $district["status"]=$status;

            $district["warna"]=$colors[$status];

        }

        return $district;

    }

}