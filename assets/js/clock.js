"use strict";

/* ==========================================================
   PORTAL DKK v4
   clock.js
========================================================== */

window.Clock = (function () {

    var exports = {};

    var timer = null;

    var bulan = [
        "Januari","Februari","Maret","April","Mei","Juni",
        "Juli","Agustus","September","Oktober","November","Desember"
    ];

    var hari = [
        "Minggu","Senin","Selasa","Rabu",
        "Kamis","Jumat","Sabtu"
    ];

    /* ======================================================
       UPDATE JAM
    ====================================================== */

    exports.update = function () {

        var now = new Date();

        var hh = String(now.getHours()).padStart(2, "0");
        var mm = String(now.getMinutes()).padStart(2, "0");
        var ss = String(now.getSeconds()).padStart(2, "0");

        var jam = hh + ":" + mm + ":" + ss;

        var tanggal =
            hari[now.getDay()] + ", " +
            now.getDate() + " " +
            bulan[now.getMonth()] + " " +
            now.getFullYear();

        var elJam1 = Utils.id("digitalClock");
        if (elJam1) {
            elJam1.textContent = jam;
        }

        var elJam2 = Utils.id("clockTime");
        if (elJam2) {
            elJam2.textContent = jam;
        }

        var elTanggal1 = Utils.id("digitalDate");
        if (elTanggal1) {
            elTanggal1.textContent = tanggal;
        }

        var elTanggal2 = Utils.id("clockDate");
        if (elTanggal2) {
            elTanggal2.textContent = tanggal;
        }

    };


    /* ======================================================
       START
    ====================================================== */

    exports.start = function () {

        exports.update();

        timer = setInterval(function () {

            exports.update();

        }, 1000);

    };


    /* ======================================================
       STOP
    ====================================================== */

    exports.stop = function () {

        if (timer) {

            clearInterval(timer);

        }

    };


    /* ======================================================
       INIT
    ====================================================== */

    exports.init = function () {

        console.log("Clock Loaded");

        exports.start();

    };

    return exports;

})();