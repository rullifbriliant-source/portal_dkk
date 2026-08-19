"use strict";

/* ==========================================================
   PORTAL DKK
   RESPONSIVE ENGINE
   Version 1.0
========================================================== */

const Responsive = {

    screen : {},

    init(){

        this.cache();

        this.resize();

        window.addEventListener("resize",()=>{

            clearTimeout(this.timer);

            this.timer=setTimeout(()=>{

                this.resize();

            },150);

        });

    },

    cache(){

        this.body=document.body;

        this.root=document.documentElement;

        this.left=document.querySelector(".left-panel");

        this.center=document.querySelector(".center-panel");

        this.right=document.querySelector(".right-panel");

        this.map=document.getElementById("svgMap");

        this.map2=document.getElementById("svgInteractive");

    },

    resize(){

        this.screen.width=window.innerWidth;

        this.screen.height=window.innerHeight;

        this.screen.ratio=this.screen.width/this.screen.height;

        this.setScale();

        this.setPanels();

        this.setMaps();

        this.setFonts();

    },

    setScale(){

        let scale=1;

        if(this.screen.width>=3840){

            scale=1.35;

        }else
        if(this.screen.width>=2560){

            scale=1.18;

        }else
        if(this.screen.width>=1920){

            scale=1;

        }else
        if(this.screen.width>=1600){

            scale=.92;

        }else{

            scale=.85;

        }

        this.root.style.setProperty("--ui-scale",scale);

    },

    setPanels(){

        if(!this.left)return;

        if(this.screen.width>1800){

            this.left.style.flex="0 0 24%";
            this.center.style.flex="0 0 52%";
            this.right.style.flex="0 0 24%";

        }
        else if(this.screen.width>1400){

            this.left.style.flex="0 0 26%";
            this.center.style.flex="0 0 48%";
            this.right.style.flex="0 0 26%";

        }
        else{

            this.left.style.flex="0 0 28%";
            this.center.style.flex="0 0 44%";
            this.right.style.flex="0 0 28%";

        }

    },

    setMaps(){

        const maps=document.querySelectorAll(".map-left object,.map-right object");

        maps.forEach(map=>{

            map.style.width="100%";
            map.style.height="100%";

        });

    },

    setFonts(){

        let size=16;

        if(this.screen.width>=3840){

            size=28;

        }
        else if(this.screen.width>=2560){

            size=22;

        }
        else if(this.screen.width>=1920){

            size=18;

        }
        else if(this.screen.width>=1366){

            size=16;

        }
        else{

            size=14;

        }

        this.root.style.fontSize=size+"px";

    }

};

window.addEventListener("load",()=>{

    Responsive.init();

});

