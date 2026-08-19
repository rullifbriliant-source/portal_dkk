/* ==========================================================
   PORTAL DKK
   MAP ENGINE v2
==========================================================*/

document.addEventListener("DOMContentLoaded", function () {

    const districts = document.querySelectorAll("#mapContainer .district");

    console.log("District ditemukan:", districts.length);

    if (districts.length === 0) {
        console.error("Tidak ada elemen .district pada SVG.");
        return;
    }

    fetch("api/map.php")
        .then(response => response.json())
        .then(json => {

            if (!json.status) return;

            json.districts.forEach(function(item){

                const el = document.getElementById(item.id);

                if(!el) return;

                el.style.fill = item.warna;
                el.dataset.nama = item.nama;
                el.dataset.status = item.status;

            });

            bindEvents();

        })
        .catch(console.error);

});

function hoverDistrict(e){

    this.style.filter="drop-shadow(0 0 12px cyan)";

    showInfo(this);

}

function leaveDistrict(){

    this.style.filter="";

}

function clickDistrict(){

    showInfo(this);

}

function showInfo(path){

    let panel=document.getElementById("districtContent");

    if(!panel) return;

    panel.innerHTML=`
        <h3>${path.dataset.nama}</h3>
        <p>Status : ${path.dataset.status}</p>
    `;
}