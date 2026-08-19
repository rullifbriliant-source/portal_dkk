/* ==========================================================
   PORTAL DKK
   PATH NUMBER ENGINE v1.0
==========================================================*/

(function(){

"use strict";

const svg=document.querySelector("#svgContainer svg");

if(!svg) return;

const viewport=document.getElementById("viewport") || svg;

let layer=document.getElementById("numberLayer");

if(!layer){

    layer=document.createElementNS(
        "http://www.w3.org/2000/svg",
        "g"
    );

    layer.setAttribute("id","numberLayer");

    svg.appendChild(layer);

}

const paths=viewport.querySelectorAll("path");

paths.forEach(function(path,index){

    const box=path.getBBox();

    const cx=box.x+(box.width/2);

    const cy=box.y+(box.height/2);

    const g=document.createElementNS(
        "http://www.w3.org/2000/svg",
        "g"
    );

    g.classList.add("path-label");

    const circle=document.createElementNS(
        "http://www.w3.org/2000/svg",
        "circle"
    );

    circle.setAttribute("cx",cx);
    circle.setAttribute("cy",cy);
    circle.setAttribute("r",12);

    circle.setAttribute("fill","#002b45");
    circle.setAttribute("stroke","#00E5FF");
    circle.setAttribute("stroke-width","1");

    const text=document.createElementNS(
        "http://www.w3.org/2000/svg",
        "text"
    );

    text.setAttribute("x",cx);
    text.setAttribute("y",cy+4);

    text.setAttribute("text-anchor","middle");

    text.setAttribute("font-size","10");

    text.setAttribute("fill","white");

    text.textContent=index+1;

    g.appendChild(circle);

    g.appendChild(text);

    g.style.cursor="pointer";

    g.onclick=function(){

        path.dispatchEvent(new MouseEvent("click"));

    };

    layer.appendChild(g);

});

})();