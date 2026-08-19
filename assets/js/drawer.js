const drawer = document.getElementById("appDrawer");

document.getElementById("apps").onclick = function(){

    drawer.classList.add("show");

};

document.getElementById("closeDrawer").onclick = function(){

    drawer.classList.remove("show");

};

const search = document.getElementById("searchApp");

search.onkeyup = function(){

    let keyword = this.value.toLowerCase();

    document.querySelectorAll(".drawer-card").forEach(function(card){

        card.style.display =
            card.innerText.toLowerCase().includes(keyword)
            ? "flex"
            : "none";

    });

};