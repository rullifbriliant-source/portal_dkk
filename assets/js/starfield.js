"use strict";

(function(){

const canvas = document.getElementById("starCanvas");

if(!canvas){
    console.warn("starCanvas tidak ditemukan.");
    return;
}

const ctx = canvas.getContext("2d");

let stars=[];

const totalStars=180;

resize();

window.addEventListener("resize",resize);

function resize(){

    canvas.width=window.innerWidth;

    canvas.height=window.innerHeight;

}

class Star{

    constructor(){

        this.reset();

    }

    reset(){

        this.x=Math.random()*canvas.width;

        this.y=Math.random()*canvas.height;

        this.radius=Math.random()*2.2;

        this.speed=0.08+Math.random()*0.35;

        this.alpha=.3+Math.random()*.7;

        this.twinkle=Math.random()*0.03;

    }

    update(){

        this.y+=this.speed;

        this.alpha+=this.twinkle;

        if(this.alpha>1 || this.alpha<0.2){

            this.twinkle*=-1;

        }

        if(this.y>canvas.height){

            this.y=-5;

            this.x=Math.random()*canvas.width;

        }

    }

    draw(){

        ctx.beginPath();

        ctx.fillStyle="rgba(255,255,255,"+this.alpha+")";

        ctx.arc(this.x,this.y,this.radius,0,Math.PI*2);

        ctx.fill();

    }

}

for(let i=0;i<totalStars;i++){

    stars.push(new Star());

}

function animate(){

    ctx.clearRect(0,0,canvas.width,canvas.height);

    for(let s of stars){

        s.update();

        s.draw();

    }

    requestAnimationFrame(animate);

}

animate();

})();