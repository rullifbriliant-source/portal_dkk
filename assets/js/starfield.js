"use strict";

/* ==========================================================
   STARFIELD — efek bintang berkelip & jatuh perlahan
   untuk background. Mandiri, tidak butuh app_v2.js.
========================================================== */

(function () {
    function init() {
        var canvas = document.getElementById("starCanvas");
        if (!canvas) return;

        var ctx = canvas.getContext("2d");
        var stars = [];
        var STAR_COUNT = 140;

        function resize() {
            canvas.width = window.innerWidth;
            canvas.height = window.innerHeight;
        }

        function makeStar() {
            return {
                x: Math.random() * canvas.width,
                y: Math.random() * canvas.height,
                radius: Math.random() * 1.6 + 0.4,
                speedY: Math.random() * 0.4 + 0.15,   // kecepatan jatuh ke bawah
                twinkleSpeed: Math.random() * 0.02 + 0.005,
                twinklePhase: Math.random() * Math.PI * 2,
                baseAlpha: Math.random() * 0.5 + 0.4
            };
        }

        function setup() {
            resize();
            stars = [];
            for (var i = 0; i < STAR_COUNT; i++) {
                stars.push(makeStar());
            }
        }

        function draw() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);

            for (var i = 0; i < stars.length; i++) {
                var s = stars[i];

                s.twinklePhase += s.twinkleSpeed;
                var alpha = s.baseAlpha + Math.sin(s.twinklePhase) * 0.3;
                if (alpha < 0.1) alpha = 0.1;
                if (alpha > 1) alpha = 1;

                ctx.beginPath();
                ctx.arc(s.x, s.y, s.radius, 0, Math.PI * 2);
                ctx.fillStyle = "rgba(0, 212, 255, " + alpha + ")";
                ctx.shadowColor = "rgba(0, 212, 255, 0.8)";
                ctx.shadowBlur = 4;
                ctx.fill();

                // Jatuh perlahan ke bawah
                s.y += s.speedY;

                // Kalau sudah keluar layar bawah, munculkan lagi dari atas
                if (s.y > canvas.height + 5) {
                    s.y = -5;
                    s.x = Math.random() * canvas.width;
                }
            }

            requestAnimationFrame(draw);
        }

        window.addEventListener("resize", function () {
            resize();
        });

        setup();
        draw();
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", init);
    } else {
        init();
    }
})();