"use strict";

window.MarkerLayer=(function(){

    var api={};

    var layer=null;

    var markers=[];

   

api.refresh=function(){

    markers.forEach(function(m){

        api.positionMarker(

            m.element,

            m.data

        );

    });

};

    api.init=function(){

        layer=document.getElementById("markerLayer");

        api.load();

    };


    api.load=function(){

        fetch("api/faskes.php")

        .then(r=>r.json())

        .then(function(res){

            if(!res.status) return;

            res.data.forEach(api.draw);

        });

    };


    api.draw=function(item){

        const marker=document.createElement("div");

        marker.className="gis-marker";

        api.positionMarker=function(marker,item){

    const vb=MapManager.getViewBox();

    const tf=MapManager.getTransform();

    const layer=document.getElementById("markerLayer");

    const w=layer.clientWidth;

    const h=layer.clientHeight;

    const sx=w/vb.width;

    const sy=h/vb.height;

    const x=item.x_svg*sx;
    const y=item.y_svg*sy;

    marker.style.left=(x*tf.scale+tf.translateX)+"px";

    marker.style.top=(y*tf.scale+tf.translateY)+"px";

};

        marker.innerHTML="🏥";

        marker.title=item.nama_faskes;

        layer.appendChild(marker);

        api.positionMarker(marker,item);

    };


    return api;

})();