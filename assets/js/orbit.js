document.addEventListener("DOMContentLoaded", function(){

    const orbit = document.querySelector(".orbit-scene");

    if(!orbit) return;

    const items = document.querySelectorAll(".orbit-item");

    const radius = 210;

    let angle = 0;

    function animate(){

        const total = items.length;

        items.forEach(function(item,index){

            const a = angle + (360/total)*index;

            const rad = a*Math.PI/180;

            const x = Math.cos(rad)*radius;

            const y = Math.sin(rad)*radius;

            item.style.left = (orbit.clientWidth/2 + x - 41)+"px";

            item.style.top = (orbit.clientHeight/2 + y - 41)+"px";

        });

        angle += 0.08;

        requestAnimationFrame(animate);

    }

    animate();

});