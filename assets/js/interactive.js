"use strict";

/* ==========================================================
   PORTAL DKK v4
   Interactive GIS
========================================================== */


window.InteractiveMap = (function(){


    var api = {};



    var districtData = {};


    /* ======================================================
   LOAD DATA KECAMATAN
====================================================== */


api.loadDistrict = function(){


    fetch("/portal_dkk/api/kecamatan.php")

    .then(function(response){

        return response.json();

    })

    .then(function(result){


        if(!result.status){

            console.warn(
                "Data kecamatan gagal"
            );

            return;

        }



        result.data.forEach(function(row){



            var key =
                row.nama_kecamatan
                .toLowerCase()
                .replace(/\s+/g,"");



            districtData[key]={


                nama:
                    row.nama_kecamatan,


                penduduk:
                    Number(
                    row.jumlah_penduduk
                    )
                    .toLocaleString("id-ID"),

                 kepadatan:
                        Number(row.kepadatan)
                        .toLocaleString("id-ID"),       


                desa:
                    row.jumlah_desa,


                puskesmas:
                    row.jumlah_puskesmas,


                pustu:
                    row.jumlah_pustu,


                posyandu:
                    row.jumlah_posyandu,


                rs:
                    row.jumlah_rs,


                luas:
                    row.luas_wilayah


                    


            };



        });



        console.log(
            "GIS Database Loaded",
            districtData
        );


    })


    .catch(function(error){

        console.error(
            "API GIS Error",
            error
        );

    });


};

    api.selectDistrict=function(id){


        console.log(
            "Pilih Kecamatan:",
            id
        );


        var data =
            districtData[id];


        if(!data){

            console.warn(
                "Data kecamatan belum ada",
                id
            );

            return;

        }


        updatePanel(data);


    };

   function updatePanel(data){


    /*
       HEADER
    */


    setText(
        "namaKecamatan",
        data.nama
    );


    /*
       HIGHLIGHT STAT
    */


    setText(
        "districtPopulation",
        data.penduduk
    );



    /*
       MINI STAT
    */


    setText(
        "miniPuskesmas",
        data.puskesmas
    );


    setText(
        "miniDesa",
        data.desa
    );


    setText(
        "miniPosyandu",
        data.posyandu
    );


    setText(
        "miniKepadatan",
        data.kepadatan
    );



    /*
       DETAIL TABLE
    */


    setText(
        "luasWilayah",
        data.luas + " km²"
    );


    setText(
        "jumlahDesa",
        data.desa
    );


    setText(
        "jumlahPenduduk",
        data.penduduk
    );


    setText(
        "jumlahPuskesmas",
        data.puskesmas
    );


    setText(
        "jumlahPustu",
        data.pustu
    );


    setText(
        "jumlahPosyandu",
        data.posyandu
    );


    setText(
        "jumlahRS",
        data.rs
    );


}


    function setText(id,value){


        var el =
            document.getElementById(id);


        if(el){

            el.innerHTML=value;

        }

    }


    /* ======================================================
   TOOLTIP
====================================================== */


api.showTooltip = function(id,event){


    var data =
        districtData[id];


    if(!data){

        return;

    }


    var tooltip =
        document.getElementById("gisTooltip");


    if(!tooltip){

        return;

    }


    tooltip.innerHTML = `

        <div class="tooltip-title">

            ${data.nama}

        </div>


        <div>

            Penduduk :
            ${data.penduduk}

        </div>


        <div>

            Desa :
            ${data.desa}

        </div>


        <div>

            Puskesmas :
            ${data.puskesmas}

        </div>

    `;



    tooltip.style.display="block";


    tooltip.style.left =
        event.clientX + 15 + "px";


    tooltip.style.top =
        event.clientY + 15 + "px";


};



api.hideTooltip=function(){


    var tooltip =
        document.getElementById("gisTooltip");


    if(tooltip){

        tooltip.style.display="none";

    }


};

api.init=function(){

    api.loadDistrict();

};

    return api;


})();