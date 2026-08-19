"use strict";

const netCanvas=document.getElementById("networkCanvas");
const nctx=netCanvas.getContext("2d");

let particles=[];

const TOTAL=60;

resize();

window.addEventListener("resize",resize);

function resize(){

    netCanvas.width=window.innerWidth;

    netCanvas.height=window.innerHeight;

}

class Particle{

    constructor(){

        this.reset();

    }

    reset(){

        this.x=Math.random()*netCanvas.width;

        this.y=Math.random()*netCanvas.height;

        this.vx=(Math.random()-.5)*0.4;

        this.vy=(Math.random()-.5)*0.4;

        this.size=2+Math.random()*2;

    }

    update(){

        this.x+=this.vx;

        this.y+=this.vy;

        if(this.x<0||this.x>netCanvas.width) this.vx*=-1;

        if(this.y<0||this.y>netCanvas.height) this.vy*=-1;

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

    nctx.clearRect(0,0,netCanvas.width,netCanvas.height);

    particles.forEach(function(p){

        p.update();

        p.draw();

    });

    connect();

    requestAnimationFrame(animate);

}

animate();