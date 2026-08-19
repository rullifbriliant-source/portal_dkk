"use strict";

/* ==========================================================
   PORTAL DKK v4
   Bootstrap
========================================================== */


console.log("Portal DKK v4");


window.addEventListener("DOMContentLoaded", function(){


    console.log("Portal Ready");


    if(window.Utils){

        Utils.init();

    }


    if(window.Clock){

        Clock.init();

    }


    if(window.MapManager){

        MapManager.init();

        if(window.InteractiveMap){

    InteractiveMap.init();

}

    }


    if(window.Portal){

        Portal.init();

    }

    if(window.MarkerLayer){

    MarkerLayer.init();

}


});
