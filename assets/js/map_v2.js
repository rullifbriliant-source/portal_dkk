"use strict";

/* ==========================================================
   MAP ENGINE V2
========================================================== */

const MapEngine = (()=>{

    let svg=null;

    let activeDistrict=null;

    const container=document.getElementById("svgContainer");

    async function load(){

        try{

            const response=await fetch("assets/svg/sukoharjo_interactive.svg");

            const text=await response.text();

            container.innerHTML=text;

            svg=container.querySelector("svg");

            if(!svg){

                throw "SVG gagal dimuat";

            }

            prepare();

        }catch(err){

            container.innerHTML=

                "<h3 style='color:white'>Peta gagal dimuat</h3>";

            console.error(err);

        }

    }

        function prepare(){

        svg.removeAttribute("width");

        svg.removeAttribute("height");

        svg.setAttribute("preserveAspectRatio","xMidYMid meet");

        const wilayah=

            svg.querySelectorAll("path");

        wilayah.forEach((item,index)=>{

            item.classList.add("district");

            item.dataset.id=index+1;

            item.style.cursor="pointer";

        });

        bind();

    }

        function bind(){

        const daerah=

            svg.querySelectorAll(".district");

        daerah.forEach(item=>{

            item.addEventListener("mouseenter",hover);

            item.addEventListener("mouseleave",leave);

            item.addEventListener("click",click);

        });

    }

    function hover(e){

        e.target.style.fill="#00e5ff";

        e.target.style.filter="drop-shadow(0 0 8px cyan)";

    }

    function leave(e){

        if(activeDistrict===e.target){

            return;

        }

        e.target.style.fill="";

        e.target.style.filter="";

    }

        function click(e){

        if(activeDistrict){

            activeDistrict.style.fill="";

            activeDistrict.style.filter="";

        }

        activeDistrict=e.target;

        activeDistrict.style.fill="#00ff90";

        activeDistrict.style.filter="drop-shadow(0 0 15px lime)";

        const nama=

            activeDistrict.getAttribute("title") ||

            activeDistrict.id ||

            "Wilayah";

        updateInfo(nama);

    }

        function updateInfo(nama){

        const el=document.getElementById("namaKecamatan");

        if(el){

            el.textContent=nama;

        }

    }

        let scale=1;

    function initZoom(){

        container.addEventListener("wheel",function(e){

            e.preventDefault();

            if(e.deltaY<0){

                scale+=0.1;

            }else{

                scale-=0.1;

            }

            scale=Math.max(1,Math.min(scale,4));

            svg.style.transform="scale("+scale+")";

        });

    }

        function init(){

        load();

        initZoom();

    }

    return{

        init

    };

})();

document.addEventListener("DOMContentLoaded",function(){

    MapEngine.init();

});
