/* ==========================================================
   PORTAL TERPADU
   DINAS KESEHATAN KABUPATEN SUKOHARJO
   app.js
========================================================== */

"use strict";

/* ==========================================================
   DOM READY
========================================================== */

document.addEventListener("DOMContentLoaded", function () {

    initClock();

    initGreeting();

    initLogo();

    initParallax();

    initCards();

    initMouseGlow();

    initTanggal();

    console.log("Portal Dinkes Sukoharjo Ready");

});


/* ==========================================================
   JAM DIGITAL
========================================================== */

function initClock() {

    const clock = document.getElementById("clock");

    if (!clock) return;

    function updateClock() {

        const now = new Date();

        const jam = String(now.getHours()).padStart(2, "0");

        const menit = String(now.getMinutes()).padStart(2, "0");

        const detik = String(now.getSeconds()).padStart(2, "0");

        clock.innerHTML = jam + ":" + menit + ":" + detik;

    }

    updateClock();

    setInterval(updateClock, 1000);

    const headerClock = document.getElementById("clockHeader");

if (headerClock) {

    headerClock.innerHTML = jam + ":" + menit + ":" + detik + " WIB";

}

}


/* ==========================================================
   SAPAAN
========================================================== */

function initGreeting() {

    const jam = new Date().getHours();

    let salam = "";

    if (jam < 11)

        salam = "Selamat Pagi";

    else if (jam < 15)

        salam = "Selamat Siang";

    else if (jam < 18)

        salam = "Selamat Sore";

    else

        salam = "Selamat Malam";

    console.log(salam);

}


/* ==========================================================
   LOGO LOGIN
========================================================== */

function initLogo(){

    const logo=document.getElementById("btnLogin");

    if(!logo) return;

    logo.addEventListener("click",function(e){

        e.preventDefault();

        document.body.style.opacity="0";

        setTimeout(function(){

            window.location.href="login/";

        },600);

    });

}


/* ==========================================================
   PARALLAX MOUSE
========================================================== */

function initParallax() {

    const map = document.getElementById("map-container");

    if (!map) return;

    document.addEventListener("mousemove", function (e) {

        const x = (window.innerWidth / 2 - e.clientX) / 70;

        const y = (window.innerHeight / 2 - e.clientY) / 70;

        map.style.transform =

            "rotateX(" + y + "deg) rotateY(" + (-x) + "deg)";

    });

}


/* ==========================================================
   GLASS CARD
========================================================== */

function initCards() {

    const cards = document.querySelectorAll(".glass-card");

    cards.forEach(function (card) {

        card.addEventListener("mouseenter", function () {

            card.style.transform = "translateY(-8px)";

        });

        card.addEventListener("mouseleave", function () {

            card.style.transform = "translateY(0px)";

        });

    });

}


/* ==========================================================
   RIPPLE EFFECT
========================================================== */

function ripple(el) {

    el.animate(

        [

            {

                transform: "scale(1)",

                opacity: 1

            },

            {

                transform: "scale(1.08)",

                opacity: .9

            },

            {

                transform: "scale(1)",

                opacity: 1

            }

        ],

        {

            duration: 400

        }

    );

}


/* ==========================================================
   LOADER (UNTUK TAHAP BERIKUTNYA)
========================================================== */

function hideLoader() {

    const loader = document.querySelector(".loading-screen");

    if (!loader) return;

    loader.classList.add("loading-hide");

}


/* ==========================================================
   RESIZE WINDOW
========================================================== */

window.addEventListener("resize", function () {

    console.log(

        "Viewport :",

        window.innerWidth,

        "x",

        window.innerHeight

    );

});


/* ==========================================================
   KEYBOARD SHORTCUT
========================================================== */

document.addEventListener("keydown", function (e) {

    // F11

    if (e.key === "F11") {

        console.log("Fullscreen");

    }

});


/* ==========================================================
   ANIMATION LOOP
========================================================== */

function animate() {

    requestAnimationFrame(animate);

}

/* ==========================================================
   CAMERA FLY INTRO
========================================================== */

window.addEventListener("load", function () {

    if (typeof gsap === "undefined") return;

    gsap.set("#topHeader", {
        y: -80,
        opacity: 0
    });

    gsap.set("#leftPanel", {
        x: -120,
        opacity: 0
    });

    gsap.set("#rightPanel", {
        x: 120,
        opacity: 0
    });

    gsap.set("#footerBar", {
        y: 80,
        opacity: 0
    });

    gsap.set(".map-scene", {
        scale: 0.65,
        opacity: 0
    });

    gsap.set("#btnLogin", {
        scale: 0,
        rotation: -180,
        opacity: 0
    });

    const tl = gsap.timeline();

    tl.to(".overlay-dark", {
        opacity: 0.55,
        duration: 1
    })

    .to("#topHeader", {
        y: 0,
        opacity: 1,
        duration: .8
    })

    .to(".map-scene", {
        scale: 1,
        opacity: 1,
        duration: 1.2,
        ease: "power3.out"
    }, "-=.2")

    .to("#btnLogin", {
        scale: 1,
        rotation: 0,
        opacity: 1,
        duration: .9,
        ease: "back.out(1.8)"
    }, "-=.5")

    .to("#leftPanel", {
        x: 0,
        opacity: 1,
        duration: .8
    }, "-=.4")

    .to("#rightPanel", {
        x: 0,
        opacity: 1,
        duration: .8
    }, "-=.7")

    .to("#footerBar", {
        y: 0,
        opacity: 1,
        duration: .8
    }, "-=.5");

});

animate();

/* ==========================================================
LOADING
========================================================== */

window.addEventListener("load", function(){

    const loading=document.getElementById("loadingScreen");

    const text=document.getElementById("loadingText");

    const pesan=[

        "Memuat Sistem...",

        "Menghubungkan Modul...",

        "Menyiapkan Portal...",

        "Selesai"

    ];

    let i=0;

    let interval=setInterval(function(){

        if(i<pesan.length){

            text.innerHTML=pesan[i];

            i++;

        }

    },700);

    setTimeout(function(){

        clearInterval(interval);

        loading.style.transition="1s";

        loading.style.opacity="0";

        setTimeout(function(){

            loading.remove();

        },1000);

    },3000);

});

/* ==========================================================
   MOUSE GLOW
========================================================== */

function initMouseGlow(){

    const glow=document.getElementById("mouseGlow");

    if(!glow) return;

    let mouseX=window.innerWidth/2;
    let mouseY=window.innerHeight/2;

    let posX=mouseX;
    let posY=mouseY;

    document.addEventListener("mousemove",function(e){

        mouseX=e.clientX;
        mouseY=e.clientY;

    });

    function animateGlow(){

        posX+=(mouseX-posX)*0.12;
        posY+=(mouseY-posY)*0.12;

        glow.style.left=posX+"px";
        glow.style.top=posY+"px";

        requestAnimationFrame(animateGlow);

    }

    animateGlow();

}

/* ==========================================================
TANGGAL INDONESIA
========================================================== */

function initTanggal(){

    const hari=[

        "Minggu",

        "Senin",

        "Selasa",

        "Rabu",

        "Kamis",

        "Jumat",

        "Sabtu"

    ];

    const bulan=[

        "Januari",

        "Februari",

        "Maret",

        "April",

        "Mei",

        "Juni",

        "Juli",

        "Agustus",

        "September",

        "Oktober",

        "November",

        "Desember"

    ];

    const now=new Date();

    const text=

        hari[now.getDay()] +

        ", " +

        now.getDate() +

        " " +

        bulan[now.getMonth()] +

        " " +

        now.getFullYear();

    document.getElementById("tanggalIndonesia").innerHTML=text;

}