/* ==========================================================
   PORTAL DKK
   VIEWPORT ENGINE v1.0
==========================================================*/

(function(){

"use strict";

const container=document.getElementById("svgContainer");
const svg=container.querySelector("svg");

if(!svg) return;

/* ------------------------------------------
   Bungkus isi SVG ke dalam <g id="viewport">
-------------------------------------------*/

let viewport=document.getElementById("viewport");

if(!viewport){

    viewport=document.createElementNS(
        "http://www.w3.org/2000/svg",
        "g"
    );

    viewport.setAttribute("id","viewport");

    while(svg.firstChild){

        viewport.appendChild(svg.firstChild);

    }

    svg.appendChild(viewport);

}

/* ------------------------------------------
   Transform
-------------------------------------------*/

let scale=1;

let translateX=0;
let translateY=0;

let dragging=false;

let startX=0;
let startY=0;

/* ------------------------------------------
   Apply
-------------------------------------------*/

function render(){

    viewport.setAttribute(

        "transform",

        "translate("+translateX+","+translateY+") scale("+scale+")"

    );

}

/* ------------------------------------------
   Mouse Wheel Zoom
-------------------------------------------*/

container.addEventListener(

"wheel",

function(e){

    e.preventDefault();

    const rect=container.getBoundingClientRect();

    const mx=e.clientX-rect.left;

    const my=e.clientY-rect.top;

    const oldScale=scale;

    if(e.deltaY<0){

        scale*=1.10;

    }else{

        scale/=1.10;

    }

    scale=Math.max(.4,Math.min(scale,8));

    translateX=mx-(mx-translateX)*(scale/oldScale);

    translateY=my-(my-translateY)*(scale/oldScale);

    render();

},

{passive:false}

);

/* ------------------------------------------
   Drag
-------------------------------------------*/

container.addEventListener("mousedown",function(e){

    dragging=true;

    startX=e.clientX;

    startY=e.clientY;

});

window.addEventListener("mouseup",function(){

    dragging=false;

});

window.addEventListener("mousemove",function(e){

    if(!dragging) return;

    translateX+=e.clientX-startX;

    translateY+=e.clientY-startY;

    startX=e.clientX;

    startY=e.clientY;

    render();

});

/* ------------------------------------------
   Reset
-------------------------------------------*/

window.resetViewport=function(){

    scale=1;

    translateX=0;

    translateY=0;

    render();

};

render();

})();