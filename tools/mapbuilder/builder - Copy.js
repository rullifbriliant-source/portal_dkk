/* ==========================================================
   PORTAL DKK
   MAP BUILDER v1.0
==========================================================*/

(function(){

"use strict";

/* ==========================================================
   ELEMENT
==========================================================*/

const svgContainer = document.getElementById("svgContainer");

const txtPath      = document.getElementById("pathIndex");
const txtSelected  = document.getElementById("selectedPath");
const txtTotal     = document.getElementById("totalPath");

const txtId        = document.getElementById("districtId");
const txtNama      = document.getElementById("districtName");

const districtList = document.getElementById("districtList");

const btnSave      = document.getElementById("btnSave");
const btnExport    = document.getElementById("btnExport");

/* ==========================================================
   DATA
==========================================================*/

let paths = [];
let selectedIndex = -1;
let selectedPath = null;

let districts = [];

/* ==========================================================
   INIT
==========================================================*/

window.addEventListener("load", init);

function init(){

    paths = svgContainer.querySelectorAll("path");

    txtTotal.innerHTML = paths.length;

    districtList.innerHTML = "";

    paths.forEach(function(path,index){

        path.dataset.index = index+1;

        path.dataset.id = "";

        path.dataset.nama = "";

        path.addEventListener("mouseenter",hoverPath);

        path.addEventListener("mouseleave",leavePath);

        path.addEventListener("click",selectPath);

    });

    btnSave.onclick=saveCurrent;

    btnExport.onclick=exportData;

    loadServer();

}

/* ==========================================================
   HOVER
==========================================================*/

function hoverPath(){

    if(this!==selectedPath){

        this.style.fill="#00BCD4";

    }

}

/* ==========================================================
   LEAVE
==========================================================*/

function leavePath(){

    if(this!==selectedPath){

        this.style.fill="";

    }

}

/* ==========================================================
   SELECT
==========================================================*/

function selectPath(){

    if(selectedPath){

        selectedPath.classList.remove("active");

    }

    selectedPath = this;

    selectedIndex = parseInt(this.dataset.index);

    this.classList.add("active");

    document
.querySelectorAll(".path-label circle")
.forEach(function(c){

    c.setAttribute("fill","#002b45");

});

const circle=document.querySelectorAll(".path-label circle")[selectedIndex-1];

if(circle){

    circle.setAttribute("fill","#00E5FF");

}

    txtPath.innerHTML = selectedIndex;

    txtSelected.innerHTML = selectedIndex;

    txtId.value = this.dataset.id;

    txtNama.value = this.dataset.nama;

}

/* ==========================================================
   SAVE
==========================================================*/

function saveCurrent(){

    if(selectedIndex<0){

        alert("Pilih wilayah terlebih dahulu.");

        return;

    }

    let id = txtId.value.trim().toLowerCase();

    let nama = txtNama.value.trim();

    if(id===""){

        alert("ID belum diisi.");

        return;

    }

    if(nama===""){

        alert("Nama belum diisi.");

        return;

    }

    selectedPath.dataset.id = id;

    selectedPath.dataset.nama = nama;

    let found = districts.findIndex(function(item){

        return item.path===selectedIndex;

    });

    let obj = {

        path:selectedIndex,

        id:id,

        nama:nama

    };

    if(found>=0){

        districts[found]=obj;

    }else{

        districts.push(obj);

    }

    renderList();

    /* ==========================================================
   SAVE SERVER
==========================================================*/

        function saveServer(){

        fetch("save.php",{

        method:"POST",

        headers:{

        "Content-Type":"application/json"

        },

        body:JSON.stringify(districts)

        })

        .then(function(res){

        return res.json();

        })

        .then(function(json){

        if(json.status){

            const status=document.getElementById("saveStatus");

if(status){

status.innerHTML="✔ Tersimpan : "+new Date().toLocaleTimeString();

}

        console.log(

        "Data berhasil disimpan"

        );

        }else{

        alert(json.message);

        }

        })

        .catch(function(err){

        console.error(err);

        alert("Gagal menghubungi server.");

        });

        }

    saveServer();

    alert("Berhasil disimpan.");

}

/* ==========================================================
   LOAD SERVER
==========================================================*/

function loadServer(){

fetch("data.json")

.then(function(res){

return res.json();

})

.then(function(json){

districts=json;

renderList();

})

.catch(function(){

districts=[];

});

}

/* ==========================================================
   LIST
==========================================================*/

function renderList(){

    districtList.innerHTML="";

    districts.sort(function(a,b){

        return a.path-b.path;

    });

    districts.forEach(function(item){

        let div=document.createElement("div");

        div.className="district-item";

        div.innerHTML=
        "<strong>"+item.nama+"</strong><br>"+
        "<small>Path : "+item.path+"</small>";

        div.onclick=function(){

            paths[item.path-1].dispatchEvent(
                new MouseEvent("click")
            );

        };

        districtList.appendChild(div);

    });

}

/* ==========================================================
   EXPORT JSON
==========================================================*/

function exportData(){

    let json = JSON.stringify(

        districts,

        null,

        4

    );

    let blob = new Blob(

        [json],

        {

            type:"application/json"

        }

    );

    let url = URL.createObjectURL(blob);

    let a=document.createElement("a");

    a.href=url;

    function exportData(){

fetch("export.php")

.then(function(res){

return res.json();

})

.then(function(json){

if(json.status){

alert(

"Berhasil membuat Interactive SVG"

);

window.open(

"../../assets/svg/sukoharjo_interactive.svg",

"_blank"

);

}else{

alert(json.message);

}

})

.catch(function(e){

console.error(e);

alert("Export gagal.");

});

}

    a.click();

    URL.revokeObjectURL(url);

}

/* ==========================================================
   SHORTCUT
==========================================================*/

document.addEventListener("keydown",function(e){

    if(e.ctrlKey && e.key==="s"){

        e.preventDefault();

        saveCurrent();

    }

});

})();

const btnReset=document.getElementById("btnReset");

if(btnReset){

    btnReset.onclick=function(){

        resetViewport();

    };

}