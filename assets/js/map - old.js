"use strict";

(function(){

    function initMap(){

        var obj=document.getElementById("svgMap");

        if(!obj) return;


        function bind(){

            var svg=obj.contentDocument;


            if(!svg){

                setTimeout(bind,200);
                return;

            }


            /*
                Cari wilayah SVG
                Prioritas:
                1. class district
                2. path dengan id
                3. group dengan id
            */

            var districts =
    svg.querySelectorAll(
        "path.district"
    );


            console.log(
                "District ditemukan :",
                districts.length
            );


            districts.forEach(function(item){


                /*
                    Hindari mengambil group
                    kosong atau elemen bukan wilayah
                */

                if(!item.id)
                    return;



                item.style.cursor="pointer";

                item.style.transition=
                "fill .25s ease";



                item.addEventListener(
                "mouseenter",
                function(){


                    item.dataset.fill =
                    item.getAttribute("fill")
                    ||
                    "#7fb3d5";


                    item.setAttribute(
                        "fill",
                        "#00d4ff"
                    );


                });



                item.addEventListener(
                "mouseleave",
                function(){


                    item.setAttribute(
                        "fill",
                        item.dataset.fill
                        ||
                        "#7fb3d5"
                    );


                });



                item.addEventListener(
                "click",
                function(){


                    document.dispatchEvent(

                        new CustomEvent(
                        "district-click",
                        {

                            detail:{

                                id:item.id,

                               nama:
                                item.dataset.name
                                ||
                                item.getAttribute("data-name")
                                ||
                                item.id

                            }

                        })

                    );


                    console.log(
                        "Klik Kecamatan :",
                        item.id
                    );


                });


            });


            console.log(
                "Map binding selesai."
            );


        }



        if(obj.contentDocument){

            bind();

        }else{

            obj.addEventListener(
                "load",
                bind
            );

        }


    }



    if(document.readyState==="loading"){


        document.addEventListener(
            "DOMContentLoaded",
            initMap
        );


    }else{


        initMap();


    }


})();