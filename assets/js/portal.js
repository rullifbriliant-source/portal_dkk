let mouse={

x:window.innerWidth/2,

y:window.innerHeight/2

};

window.addEventListener("mousemove",function(e){

mouse.x=e.clientX;

mouse.y=e.clientY;

});

const canvas=document.getElementById("portalCanvas");

const ctx=canvas.getContext("2d");

function resize(){

canvas.width=window.innerWidth;

canvas.height=window.innerHeight;

}

resize();

window.addEventListener("resize",resize);

const stars=[];

for(let i=0;i<220;i++){

stars.push({

x:Math.random()*canvas.width,

y:Math.random()*canvas.height,

r:Math.random()*2,

v:.15+Math.random()*.5

});

}

const orbitItems=document.querySelectorAll(".orbit-item");

let orbitAngle=0;

function animate(){

ctx.clearRect(0,0,canvas.width,canvas.height);

const g=ctx.createRadialGradient(

mouse.x,

mouse.y,

0,

mouse.x,

mouse.y,

220

);

g.addColorStop(0,"rgba(61,213,255,.12)");

g.addColorStop(.4,"rgba(61,213,255,.05)");

g.addColorStop(1,"rgba(61,213,255,0)");

ctx.fillStyle=g;

ctx.fillRect(0,0,canvas.width,canvas.height);

ctx.fillStyle="rgba(0,120,255,.015)";

for(let i=0;i<5;i++){

ctx.beginPath();

ctx.arc(

canvas.width*.2+i*250,

canvas.height*.3,

220+i*20,

0,

Math.PI*2

);

ctx.fill();

}

ctx.fillStyle="white";

for(const s of stars){

ctx.beginPath();

ctx.arc(s.x,s.y,s.r,0,Math.PI*2);

ctx.fill();

s.y+=s.v;

if(s.y>canvas.height){

s.y=0;

s.x=Math.random()*canvas.width;

}

}

const radius=215;

orbitItems.forEach(function(item,index){

const a=(orbitAngle+(360/orbitItems.length)*index)*Math.PI/180;

const x=Math.cos(a)*radius;

const y=Math.sin(a)*radius;

item.style.left=(325+x-40)+"px";

item.style.top=(325+y-40)+"px";

item.style.background=item.dataset.color;

});

orbitAngle+=.05;

requestAnimationFrame(animate);

}

animate();

const info=document.getElementById("districtInfo");

const tooltip=document.getElementById("tooltip");

document

.querySelectorAll("#mapContainer path")

.forEach(function(path){

path.addEventListener("mousemove",function(e){

tooltip.style.opacity=1;

tooltip.innerHTML=this.id.toUpperCase();

tooltip.style.left=e.pageX+15+"px";

tooltip.style.top=e.pageY-15+"px";

});

path.addEventListener("mouseleave",function(){

tooltip.style.opacity=0;

});

path.addEventListener("click",function(){

info.innerHTML=`

<h2>${this.id.toUpperCase()}</h2>

<table>

<tr>

<td>Puskesmas</td>

<td>: 2</td>

</tr>

<tr>

<td>Desa</td>

<td>: 10</td>

</tr>

<tr>

<td>Kasus DBD</td>

<td>: 12</td>

</tr>

<tr>

<td>Stunting</td>

<td>: 18</td>

</tr>

</table>

`;

});

});