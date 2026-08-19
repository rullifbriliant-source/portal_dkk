"use strict";

/* ==========================================================
   PORTAL DKK v4
   utils.js
   Utility Library
========================================================== */

window.Utils = (function () {

    var exports = {};

    /* ======================================================
       CONFIG
    ====================================================== */

    exports.config = {

        api: "api/",

        refresh: 60000,

        weatherRefresh: 1800000,

        animation: 300

    };


    /* ======================================================
       DOM
    ====================================================== */

    exports.$ = function (selector) {

        return document.querySelector(selector);

    };

    exports.$$ = function (selector) {

        return document.querySelectorAll(selector);

    };


    exports.id = function (id) {

        return document.getElementById(id);

    };


    /* ======================================================
       FORMAT ANGKA
    ====================================================== */

    exports.number = function (value) {

        value = parseFloat(value || 0);

        return value.toLocaleString("id-ID");

    };


    /* ======================================================
       FORMAT PERSEN
    ====================================================== */

    exports.percent = function (value) {

        return parseFloat(value).toFixed(1) + "%";

    };


    /* ======================================================
       FORMAT LUAS
    ====================================================== */

    exports.area = function (value) {

        return exports.number(value) + " km²";

    };


    /* ======================================================
       FORMAT JAM
    ====================================================== */

    exports.time = function (date) {

        return date.toLocaleTimeString("id-ID", {

            hour: "2-digit",

            minute: "2-digit",

            second: "2-digit"

        });

    };


    /* ======================================================
       FORMAT TANGGAL
    ====================================================== */

    exports.date = function (date) {

        return date.toLocaleDateString("id-ID", {

            weekday: "long",

            day: "2-digit",

            month: "long",

            year: "numeric"

        });

    };


    /* ======================================================
       FORMAT TANGGAL MYSQL
    ====================================================== */

    exports.mysqlDate = function (str) {

        if (!str) return "-";

        var d = new Date(str);

        return exports.date(d);

    };


    /* ======================================================
       LOADER
    ====================================================== */

    exports.showLoader = function (id) {

        var el = exports.id(id);

        if (el) {

            el.classList.remove("hidden");

        }

    };


    exports.hideLoader = function (id) {

        var el = exports.id(id);

        if (el) {

            el.classList.add("hidden");

        }

    };


    /* ======================================================
       AJAX
    ====================================================== */

    exports.getJSON = function (url, callback) {

        fetch(url)

            .then(function (r) {

                return r.json();

            })

            .then(function (json) {

                callback(json);

            })

            .catch(function (e) {

                console.error(e);

            });

    };


    /* ======================================================
       POST JSON
    ====================================================== */

    exports.postJSON = function (url, data, callback) {

        fetch(url, {

            method: "POST",

            headers: {

                "Content-Type": "application/json"

            },

            body: JSON.stringify(data)

        })

        .then(function (r) {

            return r.json();

        })

        .then(function (json) {

            callback(json);

        });

    };


    /* ======================================================
       DEBOUNCE
    ====================================================== */

    exports.debounce = function (fn, delay) {

        var timer;

        return function () {

            var args = arguments;

            clearTimeout(timer);

            timer = setTimeout(function () {

                fn.apply(null, args);

            }, delay);

        };

    };


    /* ======================================================
       THROTTLE
    ====================================================== */

    exports.throttle = function (fn, limit) {

        var wait = false;

        return function () {

            if (!wait) {

                fn.apply(null, arguments);

                wait = true;

                setTimeout(function () {

                    wait = false;

                }, limit);

            }

        };

    };


    /* ======================================================
       ANIMATE NUMBER
    ====================================================== */

    exports.counter = function (id, end) {

        var el = exports.id(id);

        if (!el) return;

        var start = 0;

        var step = Math.ceil(end / 60);

        function run() {

            start += step;

            if (start >= end) {

                start = end;

            }

            el.textContent = exports.number(start);

            if (start < end) {

                requestAnimationFrame(run);

            }

        }

        run();

    };


    /* ======================================================
       COLOR
    ====================================================== */

    exports.statusColor = function (status) {

        switch (status) {

            case "Baik":

                return "#00d27a";

            case "Sedang":

                return "#ffb300";

            case "Buruk":

                return "#ff4d6d";

            default:

                return "#00d4ff";

        }

    };


    /* ======================================================
       FULLSCREEN
    ====================================================== */

    exports.fullscreen = function () {

        if (!document.fullscreenElement) {

            document.documentElement.requestFullscreen();

        } else {

            document.exitFullscreen();

        }

    };


    /* ======================================================
       FPS
    ====================================================== */

    exports.startFPS = function () {

        var fps = exports.id("fpsInfo");

        if (!fps) return;

        var last = performance.now();

        var frames = 0;

        function loop(now) {

            frames++;

            if (now >= last + 1000) {

                fps.textContent = frames + " FPS";

                frames = 0;

                last = now;

            }

            requestAnimationFrame(loop);

        }

        requestAnimationFrame(loop);

    };


    /* ======================================================
       INIT
    ====================================================== */

    exports.init = function () {

        console.log("Utils Loaded");

        exports.startFPS();

    };


    return exports;

})();