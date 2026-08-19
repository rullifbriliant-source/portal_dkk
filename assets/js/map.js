"use strict";

/* ==========================================================
   PORTAL DKK v4
   map.js
   Map Manager
========================================================== */

window.MapManager = (function () {

    var exports = {};

    var svgObject = null;
    var svgDocument = null;
    var svgRoot = null;

    var activeElement = null;

    var scale = 1;
    var translateX = 0;
    var translateY = 0;

    var isDragging = false;
    var startX = 0;
    var startY = 0;

    /* ======================================================
       INIT
    ====================================================== */

    exports.init = function () {

        svgObject = Utils.id("svgMap");

        if (!svgObject) {

            console.warn("svgMap tidak ditemukan.");

            return;

        }

        svgObject.addEventListener("load", exports.onLoad);

    };


    /* ======================================================
       SVG READY
    ====================================================== */

    exports.onLoad = function () {

        svgDocument = svgObject.contentDocument;

        if (!svgDocument) {

            console.error("SVG gagal dimuat.");

            return;

        }

        svgRoot = svgDocument.querySelector("svg");

        if (!svgRoot) {

            console.error("Root SVG tidak ditemukan.");

            return;

        }

        exports.prepare();

        svgRoot.style.cursor = "default";

setTimeout(function(){

    exports.fitMap();

},500);


console.log("Main Map Ready");

exports.prepare();


var btnIn = Utils.id("btnZoomIn");

if(btnIn){

    btnIn.onclick = exports.zoomIn;

}



var btnOut = Utils.id("btnZoomOut");

if(btnOut){

    btnOut.onclick = exports.zoomOut;

}



var btnReset = Utils.id("btnResetMap");

if(btnReset){

    btnReset.onclick = exports.reset;

}


console.log("Main Map Ready");

    };


    /* ======================================================
       PREPARE SVG
    ====================================================== */

    exports.prepare = function () {


    var list = svgRoot.querySelectorAll(
    "#mojolaban,"
    +"#baki,"
    +"#gatak,"
    +"#bendosari,"
    +"#polokarto,"
    +"#grogol,"
    +"#kartasura,"
    +"#sukoharjo,"
    +"#tawangsari,"
    +"#bulu,"
    +"#weru,"
    +"#nguter"
);


    list.forEach(function (el) {


        el.style.cursor = "pointer";


        el.addEventListener(
            "mouseenter",
            exports.mouseEnter
        );


        el.addEventListener(
            "mouseleave",
            exports.mouseLeave
        );


        el.addEventListener(
            "click",
            exports.click
        );

        el.addEventListener(
    "mousemove",
    exports.mouseMove
);


el.addEventListener(
    "mouseleave",
    exports.mouseLeaveMap
);

    });


    svgRoot.addEventListener(
        "wheel",
        exports.zoom,
        {
            passive:false
        }
    );


    /*
svgRoot.addEventListener(
    "mousedown",
    exports.dragStart
);


window.addEventListener(
    "mousemove",
    exports.dragMove
);


window.addEventListener(
    "mouseup",
    exports.dragEnd
);
*/


};


    /* ======================================================
       HOVER
    ====================================================== */

    exports.mouseEnter=function(){


    if(this !== activeElement){


        this.style.opacity="0.75";


        this.style.filter =
        "drop-shadow(0 0 8px rgba(0,212,255,.8))";


    }


};

    exports.mouseLeave=function(){


    if(this !== activeElement){


        this.style.opacity="1";


        this.style.filter="";


    }


};
        /* ======================================================
   TOOLTIP MOVE
====================================================== */


exports.mouseMove=function(e){


    if(
        window.InteractiveMap &&
        typeof InteractiveMap.showTooltip==="function"
    ){

        InteractiveMap.showTooltip(
            this.id,
            e
        );

    }


};



exports.mouseLeaveMap=function(){


    if(
        window.InteractiveMap &&
        typeof InteractiveMap.hideTooltip==="function"
    ){

        InteractiveMap.hideTooltip();

    }


    };


    /* ======================================================
       CLICK
    ====================================================== */

   exports.click = function () {


    var id = this.id;


    var districts = [

        "mojolaban",
        "baki",
        "gatak",
        "bendosari",
        "polokarto",
        "grogol",
        "kartasura",
        "sukoharjo",
        "tawangsari",
        "bulu",
        "weru",
        "nguter"

    ];



    // Abaikan layer SVG bukan kecamatan

    if(
        !districts.includes(id)
    ){

        console.log(
            "Layer dilewati:",
            id
        );

        return;

    }



    exports.highlight(this);



    if(
        window.InteractiveMap &&
        typeof InteractiveMap.selectDistrict === "function"
    ){

        InteractiveMap.selectDistrict(id);

    }


};


   /* ======================================================
   ACTIVE DISTRICT
====================================================== */


exports.highlight = function(el){


    if(activeElement){


        activeElement.classList.remove(
            "active-district"
        );


        activeElement.style.filter="";


    }



    activeElement = el;



    activeElement.classList.add(
        "active-district"
    );



    activeElement.style.filter =
        "drop-shadow(0 0 12px #00d4ff)";


};

    /* ======================================================
       ZOOM
    ====================================================== */

    exports.zoom = function (e) {

        e.preventDefault();

        if (e.deltaY < 0) {

            scale += 0.1;

        } else {

            scale -= 0.1;

        }

        if (scale < 0.5) scale = 0.5;

        if (scale > 8) scale = 8;

        exports.applyTransform();

    };


    /* ======================================================
       DRAG
    ====================================================== */

    exports.dragStart = function (e) {


    if(e.button !== 0){

        return;

    }


    isDragging = true;


    startX = e.clientX;

    startY = e.clientY;


    svgRoot.style.cursor = "grabbing";


};

    exports.dragMove = function(e){


    if(!isDragging){

        return;

    }



    translateX +=
        e.clientX - startX;



    translateY +=
        e.clientY - startY;



    startX = e.clientX;

    startY = e.clientY;



    exports.applyTransform();


};


    exports.dragEnd = function(){


    isDragging = false;


    if(svgRoot){

        svgRoot.style.cursor="grab";

    }


};


    /* ======================================================
       APPLY
    ====================================================== */

    exports.applyTransform = function () {

        svgRoot.style.transform =

            "translate(" +

            translateX +

            "px," +

            translateY +

            "px) scale(" +

            scale +

            ")";

            if(window.MarkerLayer){

    MarkerLayer.refresh();

}

    };


    /* ======================================================
       BUTTON
    ====================================================== */

    exports.zoomIn = function () {

        scale += 0.2;

        exports.applyTransform();

    };


    exports.zoomOut = function () {

        scale -= 0.2;

        if (scale < 0.5) scale = 0.5;

        exports.applyTransform();

    };


    exports.reset = function () {

        scale = 1;

        translateX = 0;

        translateY = 0;

        exports.applyTransform();

    };


    /* ======================================================
       PUBLIC
    ====================================================== */

    exports.getSVG = function () {

        return svgDocument;

    };

    exports.getRoot = function () {

        return svgRoot;

    };

    exports.getSelected = function () {

        return activeElement;

    };

 /* ======================================================
   FIT MAP PROFESSIONAL
====================================================== */

exports.fitMap = function(){


    if(!svgRoot){

        console.warn("SVG belum siap");
        return;

    }



    var bbox = svgRoot.getBBox();



    if(!bbox.width || !bbox.height){

        console.warn(
            "BBox SVG kosong"
        );

        return;

    }



    var container =
        svgObject.parentElement;



    var width =
        container.clientWidth;



    var height =
        container.clientHeight;



    var padding = 0.85;



    scale =
        Math.min(

            width / bbox.width,

            height / bbox.height

        )
        *
        padding;



    translateX =
        (
            width
            -
            bbox.width * scale

        ) / 2
        -
        bbox.x * scale;



    translateY =
        (
            height
            -
            bbox.height * scale

        ) / 2
        -
        bbox.y * scale;



    exports.applyTransform();



    console.log(
        "FIT MAP OK",
        {
            bbox,
            scale,
            translateX,
            translateY
        }
    );


};
    
    exports.getRoot = function(){

        return svgRoot;

    };


    exports.getSelected = function(){

        return activeElement;

    };


    exports.fitMap = function(){

        // kode fitMap di sini

    };

    exports.getTransform = function () {

    return {

        scale: scale,

        translateX: translateX,

        translateY: translateY

    };

};

exports.getViewBox = function(){

    return {

        width:3507,

        height:2480

    };

};


    return exports;


})();