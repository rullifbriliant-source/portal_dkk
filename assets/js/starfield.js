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

        // Ukuran viewport terakhir dalam CSS px (koordinat gambar selalu CSS px).
        var viewW = 0;
        var viewH = 0;

        function viewport() {
            return {
                w: window.innerWidth || document.documentElement.clientWidth || 0,
                h: window.innerHeight || document.documentElement.clientHeight || 0
            };
        }

        // Menangani resize canvas dan remapping posisi bintang terhadap viewport
        function resize() {
            var vp = viewport();
            if (vp.w <= 0 || vp.h <= 0) return;

            var dpr = window.devicePixelRatio || 1;
            if (!(dpr > 0)) dpr = 1;

            // Rasio perubahan viewport (mis. setelah browser zoom in/out).
            var scaleX = viewW > 0 ? vp.w / viewW : 1;
            var scaleY = viewH > 0 ? vp.h / viewH : 1;

            viewW = vp.w;
            viewH = vp.h;

            // Backing store mengikuti DPR agar tajam di semua level zoom.
            // (Set canvas.width me-reset transform, jadi setTransform sesudahnya.)
            canvas.width = Math.round(vp.w * dpr);
            canvas.height = Math.round(vp.h * dpr);
            ctx.setTransform(dpr, 0, 0, dpr, 0, 0);

            // Petakan ulang posisi bintang agar tetap tersebar memenuhi
            // seluruh viewport baru (jumlah/kecepatan/bentuk tidak diubah).
            for (var i = 0; i < stars.length; i++) {
                var s = stars[i];
                s.x *= scaleX;
                s.y *= scaleY;
                if (s.x < 0 || s.x > viewW) s.x = Math.random() * viewW;
                if (s.y < 0 || s.y > viewH) s.y = Math.random() * viewH;
            }
        }

        function makeStar() {
            return {
                x: Math.random() * viewW,
                y: Math.random() * viewH,
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
            ctx.clearRect(0, 0, viewW, viewH);

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
                if (s.y > viewH + 5) {
                    s.y = -5;
                    s.x = Math.random() * viewW;
                }
            }

            requestAnimationFrame(draw);
        }

        // Tangani semua perubahan viewport: resize window, pinch-zoom
        // (visualViewport), dan rotasi layar. Debounce via rAF agar
        // resize beruntun saat zoom tidak memicu layout berulang.
        var resizeQueued = false;
        function scheduleResize() {
            if (resizeQueued) return;
            resizeQueued = true;
            requestAnimationFrame(function () {
                resizeQueued = false;
                resize();
            });
        }

        window.addEventListener("resize", scheduleResize);
        window.addEventListener("orientationchange", scheduleResize);
        if (window.visualViewport) {
            window.visualViewport.addEventListener("resize", scheduleResize);
        }

        setup();
        draw();
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", init);
    } else {
        init();
    }
})();