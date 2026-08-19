"use strict";

/* ==========================================================
   PORTAL DKK v4
   Interactive GIS
========================================================== */


window.InteractiveMap = (function(){


    var api = {};



    var districtData = {


        mojolaban:{
            nama:"Mojolaban",
            penduduk:"85.420",
            desa:"15",
            puskesmas:"2",
            posyandu:"85"
        },


        baki:{
            nama:"Baki",
            penduduk:"82.110",
            desa:"14",
            puskesmas:"2",
            posyandu:"78"
        },


        gatak:{
            nama:"Gatak",
            penduduk:"54.230",
            desa:"14",
            puskesmas:"1",
            posyandu:"65"
        },


        bendosari:{
            nama:"Bendosari",
            penduduk:"70.500",
            desa:"14",
            puskesmas:"2",
            posyandu:"72"
        },


        polokarto:{
            nama:"Polokarto",
            penduduk:"78.900",
            desa:"17",
            puskesmas:"2",
            posyandu:"90"
        },


        grogol:{
            nama:"Grogol",
            penduduk:"125.400",
            desa:"14",
            puskesmas:"3",
            posyandu:"120"
        },


        kartasura:{
            nama:"Kartasura",
            penduduk:"132.450",
            desa:"12",
            puskesmas:"2",
            posyandu:"98"
        },


        sukoharjo:{
            nama:"Sukoharjo",
            penduduk:"95.200",
            desa:"14",
            puskesmas:"2",
            posyandu:"90"
        },


        tawangsari:{
            nama:"Tawangsari",
            penduduk:"55.800",
            desa:"12",
            puskesmas:"1",
            posyandu:"60"
        },


        bulu:{
            nama:"Bulu",
            penduduk:"52.600",
            desa:"12",
            puskesmas:"1",
            posyandu:"58"
        },


        weru:{
            nama:"Weru",
            penduduk:"66.300",
            desa:"13",
            puskesmas:"2",
            posyandu:"70"
        },


        nguter:{
            nama:"Nguter",
            penduduk:"68.700",
            desa:"16",
            puskesmas:"2",
            posyandu:"75"
        }


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


        setText(
            "namaKecamatan",
            data.nama
        );


        setText(
            "districtPopulation",
            data.penduduk
        );


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

    return api;


})();