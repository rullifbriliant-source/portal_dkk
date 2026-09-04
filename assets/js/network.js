"use strict";

(function () {

if (!document.getElementById("networkCanvas")) return; // halaman tanpa layer network: keluar diam-diam, jangan crash

const netCanvas=document.getElementById("networkCanvas");
const nctx=netCanvas.getContext("2d");

let particles=[];

const TOTAL=60;

let viewW=0;
let viewH=0;

function viewport(){

    return {

        w: window.innerWidth || document.documentElement.clientWidth || 0,

        h: window.innerHeight || document.documentElement.clientHeight || 0

    };

}

function resize(){

    const vp=viewport();

    if (vp.w<=0||vp.h<=0) return;

    let dpr=window.devicePixelRatio || 1;

    if (!(dpr>0)) dpr=1;

    const scaleX=viewW>0 ? vp.w/viewW : 1;

    const scaleY=viewH>0 ? vp.h/viewH : 1;

    viewW=vp.w;

    viewH=vp.h;

    netCanvas.width=Math.round(vp.w*dpr);

    netCanvas.height=Math.round(vp.h*dpr);

    nctx.setTransform(dpr,0,0,dpr,0,0);

    particles.forEach(function(p){

        p.x*=scaleX;

        p.y*=scaleY;

        if(p.x<0||p.x>viewW||p.y<0||p.y>viewH) p.reset();

    });

}

resize();

var resizeQueued=false;

function scheduleResize(){

    if(resizeQueued) return;

    resizeQueued=true;

    requestAnimationFrame(function(){

        resizeQueued=false;

        resize();

    });

}

window.addEventListener("resize",scheduleResize);

window.addEventListener("orientationchange",scheduleResize);

if(window.visualViewport){

    window.visualViewport.addEventListener("resize",scheduleResize);

}

class Particle{

    constructor(){

        this.reset();

    }

    reset(){

        this.x=Math.random()*(viewW || window.innerWidth);

        this.y=Math.random()*(viewH || window.innerHeight);

        this.vx=(Math.random()-.5)*0.4;

        this.vy=(Math.random()-.5)*0.4;

        this.size=2+Math.random()*2;

    }

    update(){

        this.x+=this.vx;

        this.y+=this.vy;

        if(this.x<0||this.x>viewW) this.vx*=-1;

        if(this.y<0||this.y>viewH) this.vy*=-1;

    }

    draw(){

        nctx.beginPath();

        nctx.fillStyle="#00d4ff";

        nctx.arc(this.x,this.y,this.size,0,Math.PI*2);

        nctx.fill();

    }

}

for(let i=0;i<TOTAL;i++){

    particles.push(new Particle());

}

function connect(){

    for(let a=0;a<TOTAL;a++){

        for(let b=a+1;b<TOTAL;b++){

            let dx=particles[a].x-particles[b].x;

            let dy=particles[a].y-particles[b].y;

            let dist=Math.sqrt(dx*dx+dy*dy);

            if(dist<160){

                nctx.strokeStyle="rgba(0,212,255,"+(1-dist/160)*0.18+")";

                nctx.lineWidth=1;

                nctx.beginPath();

                nctx.moveTo(particles[a].x,particles[a].y);

                nctx.lineTo(particles[b].x,particles[b].y);

                nctx.stroke();

            }

        }

    }

}

function animate(){

    nctx.clearRect(0,0,viewW,viewH);

    particles.forEach(function(p){

        p.update();

        p.draw();

    });

    connect();

    requestAnimationFrame(animate);

}

animate();

})();