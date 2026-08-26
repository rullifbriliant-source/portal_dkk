"use strict";

/* ==========================================================
   PORTAL TERPADU
   DINAS KESEHATAN KABUPATEN SUKOHARJO
   app_v2.js
   Version 3.2 - Fixed Interactive Map + Disease Orbit
   Version 3.2.1 - FIX: pin/tooltip klik peta sekarang mengikuti
                   posisi cursor dengan benar (koordinat SVG
                   di dalam <object> dikonversi ke koordinat
                   halaman utama)
========================================================== */

/* ==========================================================
   CORE CONFIG
========================================================== */

const Portal = {
    version: "3.2.1",
    build: "2026.08",
    debug: true,
    online: navigator.onLine,
    started: false
};

/* ==========================================================
   LOGGER
========================================================== */

const Log = {
    info: function() {
        if (!Portal.debug) return;
        console.log.apply(console, arguments);
    },
    warn: function() {
        if (!Portal.debug) return;
        console.warn.apply(console, arguments);
    },
    error: function() {
        console.error.apply(console, arguments);
    }
};

/* ==========================================================
   DOM HELPER
========================================================== */

const DOM = {
    id: function(id) {
        return document.getElementById(id);
    },
    one: function(selector) {
        return document.querySelector(selector);
    },
    all: function(selector) {
        return document.querySelectorAll(selector);
    }
};

/* ==========================================================
   CACHE ENGINE
========================================================== */

const Cache = {
    store: {},
    set: function(key, value) {
        this.store[key] = {
            value: value,
            time: Date.now()
        };
    },
    get: function(key, maxAge) {
        if (!this.store[key]) {
            return null;
        }
        if (Date.now() - this.store[key].time > maxAge) {
            return null;
        }
        return this.store[key].value;
    },
    remove: function(key) {
        delete this.store[key];
    },
    clear: function() {
        this.store = {};
    }
};

/* ==========================================================
   UTIL
========================================================== */

const Util = {
    pad: function(v) {
        return v < 10 ? "0" + v : String(v);
    },
    number: function(v) {
        return Number(v || 0).toLocaleString("id-ID");
    },
    random: function(min, max) {
        return Math.floor(Math.random() * (max - min + 1)) + min;
    },
    show: function(el) {
        if (el) {
            el.style.display = "";
        }
    },
    hide: function(el) {
        if (el) {
            el.style.display = "none";
        }
    },
    fadeOut: function(el, time) {
        if (!el) return;
        el.style.transition = "opacity " + time + "ms";
        el.style.opacity = "0";
    },
    fadeIn: function(el, time) {
        if (!el) return;
        el.style.display = "";
        el.style.transition = "opacity " + time + "ms";
        el.style.opacity = "1";
    }
};

/* ==========================================================
   LOADER
========================================================== */

const Loader = {
    init: function() {
        window.addEventListener("load", function() {
            Loader.hide();
        });
    },
    hide: function() {
        const box = DOM.id("loadingScreen");
        if (!box) {
            document.body.classList.add("portal-ready");
            return;
        }
        setTimeout(function() {
            box.style.transition = "opacity .7s ease";
            box.style.opacity = "0";
            box.style.pointerEvents = "none";
            setTimeout(function() {
                box.style.display = "none";
                document.body.classList.add("portal-ready");
            }, 700);
        }, 900);
    }
};

/* ==========================================================
   CONNECTION
========================================================== */

const Connection = {
    init: function() {
        window.addEventListener("online", function() {
            Portal.online = true;
            Log.info("Portal Online");
        });
        window.addEventListener("offline", function() {
            Portal.online = false;
            Log.warn("Portal Offline");
        });
    }
};

/* ==========================================================
   ERROR HANDLER
========================================================== */

const ErrorHandler = {
    init: function() {
        window.onerror = function(msg, url, line, col, err) {
            Log.error("ERROR :", msg, "Line :", line);
            return false;
        };
        window.addEventListener("unhandledrejection", function(e) {
            Log.error("Promise Error :", e.reason);
        });
    }
};

/* ==========================================================
   CORE
========================================================== */

const Core = {
    init: function() {
        if (Portal.started) {
            return;
        }
        Portal.started = true;
        Loader.init();
        Connection.init();
        ErrorHandler.init();
        Log.info("%cPORTAL TERPADU DKK SUKOHARJO", "color:#00d4ff;font-size:16px;font-weight:bold");
        Log.info("Version", Portal.version);
    }
};

/* ==========================================================
   BAGIAN 2 - HEADER ENGINE
========================================================== */

const Clock = {
    timer: null,
    init: function() {
        this.update();
        this.timer = setInterval(function() {
            Clock.update();
        }, 1000);
    },
    update: function() {
        const el = DOM.id("clock");
        if (!el) return;
        const now = new Date();
        el.textContent = Util.pad(now.getHours()) + ":" + Util.pad(now.getMinutes()) + ":" + Util.pad(now.getSeconds());
    }
};

const Tanggal = {
    update: function() {
        const el = DOM.id("tanggalIndonesia") || DOM.id("tanggal");
        if (!el) return;
        const now = new Date();
        el.textContent = now.toLocaleDateString("id-ID", {
            weekday: "long",
            day: "numeric",
            month: "long",
            year: "numeric"
        });
    },
    init: function() {
        this.update();
    }
};

const Greeting = {
    init: function() {
        const el = DOM.id("greeting");
        if (!el) return;
        const h = new Date().getHours();
        let text = "";
        if (h < 11) {
            text = "Selamat Pagi";
        } else if (h < 15) {
            text = "Selamat Siang";
        } else if (h < 18) {
            text = "Selamat Sore";
        } else {
            text = "Selamat Malam";
        }
        el.textContent = text;
    }
};

const Weather = {
    latitude: -7.683,
    longitude: 110.833,
    interval: 600000,
    init: function() {
        this.load();
        setInterval(function() {
            Weather.load();
        }, this.interval);
    },
    load: function() {
        const el = DOM.id("weather");
        if (!el) return;
        fetch("https://api.open-meteo.com/v1/forecast?latitude=" + this.latitude + "&longitude=" + this.longitude + "&current_weather=true", {
            cache: "no-store"
        })
        .then(function(res) {
            return res.json();
        })
        .then(function(json) {
            if (!json.current_weather) {
                el.innerHTML = "-";
                return;
            }
            el.innerHTML = Math.round(json.current_weather.temperature) + "&deg;C";
        })
        .catch(function() {
            el.innerHTML = "-";
        });
    }
};

const Header = {
    init: function() {
        Clock.init();
        Tanggal.init();
        Greeting.init();
        Weather.init();
    }
};

;

/* ==========================================================
   BAGIAN 4 - ORBIT ENGINE
========================================================== */

const Orbit = {
    menu: null,
    items: [],
radius: 260,
centerX: 270,
centerY: 270,
    angle: 0,
    speed: 0.004,
    running: true,
    raf: null,
    init: function() {
        this.menu = DOM.id("orbitMenu");
        if (!this.menu) {
            Log.warn("orbitMenu tidak ditemukan");
            return;
        }
        this.items = this.menu.querySelectorAll(".orbit-item");
        if (this.items.length === 0) {
            Log.warn("orbit-item tidak ditemukan");
            return;
        }
        this.bind();
        this.animate();
    },
    bind: function() {
        this.menu.addEventListener("mouseenter", function() {
            Orbit.running = false;
        });
        this.menu.addEventListener("mouseleave", function() {
            Orbit.running = true;
        });
        this.items.forEach(function(item) {
            item.style.position = "absolute";
            item.style.transition = "transform .25s ease";
            item.addEventListener("mouseenter", function() {
                item.style.transform = "scale(1.15)";
            });
            item.addEventListener("mouseleave", function() {
                item.style.transform = "scale(1)";
            });
        });
    },
    animate: function() {
        if (Orbit.running) {
            Orbit.angle += Orbit.speed;
        }
        const total = Orbit.items.length;
        Orbit.items.forEach(function(item, index) {
            const a = Orbit.angle + (Math.PI * 2 / total) * index;
            const x = Orbit.centerX + Math.cos(a) * Orbit.radius;
            const y = Orbit.centerY + Math.sin(a) * Orbit.radius;
            item.style.left = (x - 45) + "px";
            item.style.top = (y - 45) + "px";
        });
        Orbit.raf = requestAnimationFrame(Orbit.animate);
    },
    stop: function() {
        cancelAnimationFrame(this.raf);
    },
    start: function() {
        cancelAnimationFrame(this.raf);
        this.animate();
    },
    setRadius: function(radius) {
        this.radius = radius;
    },
    setSpeed: function(speed) {
        this.speed = speed;
    }
};

const OrbitMenu = {
    init: function() {
        DOM.all("#orbitMenu .orbit-item").forEach(function(item) {
            item.addEventListener("click", function(e) {
                e.preventDefault();
                const nama = item.textContent.trim();
                Log.info("Menu : " + nama);
            });
        });
    }
};

/* ==========================================================
   BAGIAN 4B - DISEASE ORBIT (MENU DATA KESEHATAN)
========================================================== */

const DiseaseOrbit = {
    init: function() {
        DOM.all(".disease-orbit-item").forEach(function(item) {
            item.addEventListener("click", function(e) {
                e.preventDefault();
                var type = item.getAttribute("data-disease");
                Log.info("Orbit kesehatan diklik:", type);

                var district = MapEngine.current; // kecamatan aktif saat ini (bisa null)
                DiseaseData.load(district, type);
            });
        });
    }
};

const DiseaseData = {
    load: function(kecamatanId, category) {
        var url = "api/get_penyakit.php?kecamatan=" +
            encodeURIComponent(kecamatanId || "") +
            "&kategori=" + encodeURIComponent(category || "") +
            "&ts=" + Date.now();

        fetch(url, { cache: "no-store" })
            .then(function(res) {
                if (!res.ok) {
                    throw new Error("HTTP " + res.status);
                }
                return res.json();
            })
            .then(function(json) {
                if (!json.status) {
                    Log.warn("Data penyakit tidak ditemukan:", json.message || "");
                    return;
                }
                DiseaseData.render(json);
            })
            .catch(function(err) {
                Log.error("Gagal load data penyakit:", err);
            });
    },

    render: function(json) {
        Log.info("Data penyakit diterima:", json);

        if (json.kategori) {
            var key = "count" + json.kategori.charAt(0).toUpperCase() + json.kategori.slice(1);
            var el = DOM.id(key);
            if (el) {
                el.textContent = Util.number(json.total);
            }
        }

        // Hook tambahan: jika ada panel detail penyakit di halaman,
        // render daftar json.data (nama_penyakit, total_kasus) di sini.
    }
};

/* ==========================================================
   BAGIAN 5 - INTERACTIVE MAP ENGINE (FIXED)
========================================================== */

const MapEngine = {

    current: null,
    tooltip: null,
    bound: false,

    init: function() {

        this.createTooltip();

        this.bindSVG();

        document.addEventListener(
            "district-click",
            this.onDistrictClick.bind(this)
        );

        Log.info("Map Engine Ready");
    },


    /* ==========================================================
       TOOLTIP
    ========================================================== */

    createTooltip: function() {

        let tip = DOM.id("mapTooltip");

        if (!tip) {

            tip = document.createElement("div");

            tip.id = "mapTooltip";

            tip.style.position = "fixed";
            tip.style.zIndex = "99999";
            tip.style.pointerEvents = "none";

            tip.style.padding = "8px 14px";

            tip.style.background =
                "rgba(0,0,0,.82)";

            tip.style.color = "#fff";

            tip.style.borderRadius = "8px";

            tip.style.border =
                "1px solid rgba(0,212,255,.3)";

            tip.style.fontSize = "13px";

            tip.style.opacity = "0";

            tip.style.transition =
                "opacity .2s";

            document.body.appendChild(tip);
        }

        this.tooltip = tip;
    },


    /* ==========================================================
       BIND SVG
    ========================================================== */

    bindSVG: function() {

        const obj =
            DOM.id("svgInteractive") ||
            DOM.id("svgMap");

        if (!obj) {

            Log.warn(
                "Tidak ada object SVG ditemukan"
            );

            return;
        }

        const self = this;


        /* ======================================================
           HELPER: KONVERSI KOORDINAT DI DALAM <object>
           MENJADI KOORDINAT HALAMAN UTAMA (VIEWPORT).

           PENTING:
           e.clientX / e.clientY dari event di dalam SVG
           yang dimuat lewat <object> itu RELATIF terhadap
           document SVG itu sendiri (viewport-nya sendiri),
           BUKAN terhadap window/document utama.

           Makanya harus ditambah posisi <object> di halaman
           utama (getBoundingClientRect) supaya pin/tooltip
           muncul PERSIS di titik cursor, bukan di posisi lain
           (misalnya nyangkut di kiri map).
        ====================================================== */

        const toPageCoords = function(evt) {

            const objRect =
                obj.getBoundingClientRect();

            return {
                x: objRect.left + evt.clientX,
                y: objRect.top + evt.clientY
            };
        };


        const attachEvents = function(svg) {

            if (!svg) return;

            if (svg.__mapEngineBound) {
                return;
            }


            const districts =
                svg.querySelectorAll(".district");


            if (!districts ||
                districts.length === 0) {

                Log.warn(
                    "Tidak ada .district di SVG"
                );

                return;
            }


            Log.info(
                "Menemukan " +
                districts.length +
                " district"
            );


            /* ==================================================
               CLICK
            ================================================== */

            svg.addEventListener(
                "click",
                function(e) {

                    let target = e.target;

                    /*
                     * Cari parent .district
                     * sampai ketemu
                     */

                    while (
                        target &&
                        target !== svg &&
                        !target.classList.contains("district")
                    ) {

                        target =
                            target.parentElement;
                    }


                    if (
                        !target ||
                        !target.classList ||
                        !target.classList.contains("district")
                    ) {
                        return;
                    }


                    const id =
                        target.id ||
                        target.getAttribute("id");


                    const nama =
                        target.getAttribute("data-name") ||
                        id;


                    Log.info(
                        "District diklik:",
                        id,
                        "Nama:",
                        nama
                    );


                    /*
                     * Konversi posisi klik (di dalam SVG)
                     * ke posisi halaman utama, supaya pin
                     * muncul tepat di titik yang diklik.
                     */

                    const pagePos =
                        toPageCoords(e);


                    document.dispatchEvent(
                        new CustomEvent(
                            "district-click",
                            {
                                detail: {
                                    id: id,
                                    nama: nama,
                                    x: pagePos.x,
                                    y: pagePos.y
                                }
                            }
                        )
                    );
                }
            );


            /* ==================================================
               MOUSEMOVE (supaya tooltip tetap mengikuti
               cursor SELAMA di dalam area peta, karena
               mousemove di document utama tidak bisa
               "menembus" ke dalam <object>)
            ================================================== */

            svg.addEventListener(
                "mousemove",
                function(e) {

                    const pagePos =
                        toPageCoords(e);

                    MapEngine.moveTooltip(
                        pagePos.x,
                        pagePos.y
                    );
                }
            );


            /* ==================================================
               HOVER
            ================================================== */

            svg.addEventListener(
                "mouseover",
                function(e) {

                    let target = e.target;

                    while (
                        target &&
                        target !== svg &&
                        !target.classList.contains("district")
                    ) {

                        target =
                            target.parentElement;
                    }


                    if (
                        !target ||
                        !target.classList ||
                        !target.classList.contains("district")
                    ) {
                        return;
                    }


                    /*
                     * Kalau sedang aktif,
                     * JANGAN ubah style.
                     */

                    if (
                        target.classList.contains(
                            "district-active"
                        )
                    ) {
                        return;
                    }


                    target.style.opacity =
                        "0.85";


                    target.style.filter =
                        "brightness(1.25) " +
                        "drop-shadow(0 0 6px rgba(0,212,255,.5))";
                }
            );


            /* ==================================================
               MOUSE OUT
            ================================================== */

            svg.addEventListener(
                "mouseout",
                function(e) {

                    let target = e.target;

                    while (
                        target &&
                        target !== svg &&
                        !target.classList.contains("district")
                    ) {

                        target =
                            target.parentElement;
                    }


                    if (
                        !target ||
                        !target.classList ||
                        !target.classList.contains("district")
                    ) {
                        return;
                    }


                    /*
                     * PENTING:
                     *
                     * Kalau wilayah sedang aktif,
                     * JANGAN reset style.
                     */

                    if (
                        target.classList.contains(
                            "district-active"
                        )
                    ) {
                        return;
                    }


                    target.style.opacity =
                        "1";

                    target.style.filter =
                        "";
                }
            );


            /* ==================================================
               DEFAULT STYLE
            ================================================== */

            Array.prototype.forEach.call(
                districts,
                function(item) {

                    item.style.cursor =
                        "pointer";

                    item.style.transition =
                        "all 0.25s ease";

                    item.style.opacity =
                        "1";

                    item.style.fill =
                        "rgba(111,184,214,0.28)";

                    item.style.stroke =
                        "#00ff88";

                    item.style.strokeWidth =
                        "2";
                }
            );


            /* ==================================================
               HIDE BACKGROUND
            ================================================== */

            const pageLayer =
                svg.getElementById("Page");

            if (pageLayer) {

                pageLayer.style.display =
                    "none";
            }


            const bgLayer =
                svg.getElementById(
                    "Map 1: Background"
                );

            if (bgLayer) {

                bgLayer.style.display =
                    "none";
            }


            svg.__mapEngineBound = true;

            self.bound = true;


            Log.info(
                "SVG events terpasang untuk " +
                districts.length +
                " district"
            );
        };


        /* ======================================================
           SVG SUDAH LOAD
        ====================================================== */

        if (
            obj.contentDocument &&
            obj.contentDocument.documentElement
        ) {

            attachEvents(
                obj.contentDocument
            );

        } else {

            obj.addEventListener(
                "load",
                function() {

                    setTimeout(
                        function() {

                            if (
                                obj.contentDocument
                            ) {

                                attachEvents(
                                    obj.contentDocument
                                );
                            }

                        },
                        100
                    );
                }
            );
        }


        /* ======================================================
           RETRY
        ====================================================== */

        let retry = 0;

        const interval =
            setInterval(
                function() {

                    retry++;


                    if (
                        self.bound ||
                        retry > 20
                    ) {

                        clearInterval(
                            interval
                        );

                        return;
                    }


                    const obj2 =
                        DOM.id(
                            "svgInteractive"
                        ) ||
                        DOM.id(
                            "svgMap"
                        );


                    if (
                        obj2 &&
                        obj2.contentDocument &&
                        obj2.contentDocument.documentElement
                    ) {

                        const svg =
                            obj2.contentDocument;


                        if (
                            svg.querySelectorAll(
                                ".district"
                            ).length > 0
                        ) {

                            attachEvents(svg);

                            clearInterval(
                                interval
                            );
                        }
                    }

                },
                300
            );
    },


    /* ==========================================================
       DISTRICT CLICK
    ========================================================== */

    onDistrictClick: function(e) {

        const data = e.detail;


        this.current =
            data.id;


        Log.info(
            "Klik Kecamatan:",
            data.nama,
            "ID:",
            data.id
        );


        /*
         * Highlight permanen
         */

        this.highlight(
            data.id
        );


        /*
         * Tooltip - langsung dipasang di titik klik
         * (data.x / data.y sudah dikonversi ke koordinat
         * halaman utama di bindSVG)
         */

        this.showTooltip(
            data.nama
        );

        if (
            typeof data.x === "number" &&
            typeof data.y === "number"
        ) {

            this.moveTooltip(
                data.x,
                data.y
            );
        }


        /*
         * Load informasi wilayah
         */

        if (
            typeof Dashboard !== "undefined" &&
            Dashboard.loadDistrict
        ) {

            Dashboard.loadDistrict(
                data.id
            );
        }
    },


    /* ==========================================================
       HIGHLIGHT DISTRICT
       VERSI KUAT UNTUK SVG
    ========================================================== */

    highlight: function(id) {

        const obj =
            DOM.id("svgInteractive") ||
            DOM.id("svgMap");


        if (
            !obj ||
            !obj.contentDocument
        ) {

            Log.warn(
                "SVG tidak tersedia untuk highlight"
            );

            return;
        }


        const svg =
            obj.contentDocument;


        /* ======================================================
           RESET SEMUA DISTRICT
        ====================================================== */

        svg.querySelectorAll(
            ".district"
        ).forEach(
            function(item) {

                item.classList.remove(
                    "district-active"
                );


                item.removeAttribute(
                    "data-selected"
                );


                item.style.opacity =
                    "1";


                item.style.filter =
                    "";


                item.style.fill =
                    "rgba(111,184,214,0.28)";


                item.style.stroke =
                    "#00ff88";


                item.style.strokeWidth =
                    "2";


                /*
                 * Reset juga semua anak SVG
                 */

                item.querySelectorAll(
                    "*"
                ).forEach(
                    function(child) {

                        child.style.opacity =
                            "";

                        child.style.filter =
                            "";

                        child.style.stroke =
                            "";

                        child.style.strokeWidth =
                            "";
                    }
                );
            }
        );


        /* ======================================================
           CARI DISTRICT YANG DIPILIH
        ====================================================== */

        const aktif =
            svg.getElementById(id);


        if (!aktif) {

            Log.warn(
                "District tidak ditemukan:",
                id
            );


            const allIds =
                Array.prototype.map.call(
                    svg.querySelectorAll(
                        ".district"
                    ),
                    function(d) {
                        return d.id;
                    }
                );


            Log.info(
                "District tersedia:",
                allIds
            );


            return;
        }


        /* ======================================================
           AKTIFKAN DISTRICT
        ====================================================== */

        aktif.classList.add(
            "district-active"
        );


        aktif.setAttribute(
            "data-selected",
            "true"
        );


        aktif.style.opacity =
            "1";


        aktif.style.fill =
            "#00d4ff";


        aktif.style.stroke =
            "#ffd700";


        aktif.style.strokeWidth =
            "5";


        aktif.style.filter =
            "brightness(1.6) " +
            "drop-shadow(0 0 8px #00d4ff) " +
            "drop-shadow(0 0 18px #00d4ff) " +
            "drop-shadow(0 0 30px #00d4ff)";


        aktif.style.cursor =
            "pointer";


        /* ======================================================
           PAKSA SEMUA PATH / SHAPE DI DALAM DISTRICT IKUT
           HIGHLIGHT
        ====================================================== */

        aktif.querySelectorAll(
            "path, polygon, polyline, rect, circle, ellipse"
        ).forEach(
            function(child) {

                child.style.opacity =
                    "1";


                child.style.fill =
                    "#00d4ff";


                child.style.stroke =
                    "#ffd700";


                child.style.strokeWidth =
                    "5";


                child.style.filter =
                    "brightness(1.6) " +
                    "drop-shadow(0 0 8px #00d4ff) " +
                    "drop-shadow(0 0 18px #00d4ff)";


                child.style.pointerEvents =
                    "auto";
            }
        );


        Log.info(
            "================================"
        );

        Log.info(
            "HIGHLIGHT AKTIF:",
            id
        );

        Log.info(
            "================================"
        );
    },


    /* ==========================================================
       TOOLTIP
    ========================================================== */

    showTooltip: function(text) {

        if (!this.tooltip) {
            return;
        }


        this.tooltip.innerHTML =
            "📍 " + text;


        this.tooltip.style.opacity =
            "1";


        clearTimeout(
            this.tooltip.timer
        );


        this.tooltip.timer =
            setTimeout(
                function() {

                    if (
                        MapEngine.tooltip
                    ) {

                        MapEngine.tooltip.style.opacity =
                            "0";
                    }

                },
                2000
            );
    },


    moveTooltip: function(x, y) {

        if (!this.tooltip) {
            return;
        }


        this.tooltip.style.left =
            (x + 20) + "px";


        this.tooltip.style.top =
            (y + 20) + "px";
    }
};

/* ==========================================================
   TOOLTIP FOLLOW MOUSE
   (untuk area DI LUAR <object> peta - misal saat hover di
   panel kiri/kanan. Untuk area DI DALAM peta, sudah ditangani
   listener mousemove terpisah di dalam bindSVG di atas,
   karena document di dalam <object> terpisah dari document
   utama ini.)
========================================================== */

document.addEventListener("mousemove", function(e) {
    MapEngine.moveTooltip(e.clientX, e.clientY);
});

/* ==========================================================
   BAGIAN 6 - API ENGINE
========================================================== */

const PortalAPI = {
    base: "",
    interval: 300000,

    init: function() {
        this.loadAll();
        setInterval(function() {
            PortalAPI.loadAll();
        }, this.interval);
    },

    loadAll: function() {
        this.loadRunning();
        this.loadAgenda();
        this.loadDashboard();
    },

    fetchJSON: function(url) {
        return fetch(url, { cache: "no-store" })
            .then(function(res) {
                if (!res.ok) {
                    throw new Error("HTTP " + res.status);
                }
                return res.json();
            });
    },

    loadRunning: function() {
        var bar = DOM.id("runningText");
        if (!bar) return;
        this.fetchJSON(this.base + "get_running.php?ts=" + Date.now())
            .then(function(json) {
                if (!json.status) {
                    return;
                }
                bar.innerHTML = json.text || "";
            })
            .catch(function() {
                Log.warn("Running text offline");
            });
    },

    loadAgenda: function() {
        var box = DOM.id("agendaHariIni");
        if (!box) return;
        this.fetchJSON(this.base + "get_jadwal.php?ts=" + Date.now())
            .then(function(json) {
                if (!json.status || json.total === 0) {
                    box.innerHTML = "<div class='agenda-empty'>Tidak ada agenda hari ini</div>";
                    return;
                }
                var html = "";
                json.agenda.forEach(function(item) {
                    html += "<div class='agenda-item'>" +
                        "<div class='agenda-jam'>" + item.mulai + "</div>" +
                        "<div class='agenda-info'>" +
                        "<b>" + item.topik + "</b><br>" +
                        "<small>" + item.ruangan + "</small>" +
                        "</div>" +
                        "</div>";
                });
                box.innerHTML = html;
            })
            .catch(function() {
                box.innerHTML = "<div class='agenda-error'>Agenda gagal dimuat</div>";
            });
    },

    loadDashboard: function() {
        var cache = Cache.get("dashboard", 60000);
        if (cache) {
            this.renderDashboard(cache);
            return;
        }
        this.fetchJSON(this.base + "get_dashboard.php?ts=" + Date.now())
            .then(function(json) {
                Cache.set("dashboard", json);
                PortalAPI.renderDashboard(json);
            })
            .catch(function() {
                Log.warn("Dashboard offline");
            });
    },

    

    renderDashboard: function(data) {
        if (!data.status) {
            return;
        }
        Counter.start("statPenduduk", data.penduduk);
        Counter.start("statPuskesmas", data.puskesmas);
        Counter.start("statPustu", data.pustu);
        Counter.start("statProgram", data.program);
    },

    /* ==========================================================
       DATA KECAMATAN
    ========================================================== */

    loadDistrict: function(id) {
        // Hapus prefix "kec_" jika ada
        var cleanId = id;
        if (id && id.startsWith("kec_")) {
            cleanId = id.substring(4);
        }

        return this.fetchJSON(
            "api/kecamatan.php?id=" +
            encodeURIComponent(cleanId) +
            "&ts=" +
            Date.now()
        );
    }
};

/* ==========================================================
   COUNTER
========================================================== */

const Counter = {
    start: function(id, target) {
        var el = DOM.id(id);
        if (!el) {
            return;
        }
        target = parseInt(target) || 0;
        var value = 0;
        var step = Math.max(1, Math.ceil(target / 80));
        var timer = setInterval(function() {
            value += step;
            if (value >= target) {
                value = target;
                clearInterval(timer);
            }
            el.textContent = Util.number(value);
        }, 18);
    }
};

/* ==========================================================
   BAGIAN 7 - DASHBOARD ENGINE (FIXED)
========================================================== */

const Dashboard = {
    currentDistrict: null,
    loading: false,
    lastData: {},

    init: function() {
        Log.info("Dashboard Engine Ready");
        this.loadDefault();
    },

    loadDefault: function() {
        this.setText("namaKecamatan", "Kabupaten Sukoharjo");
        this.setText("jumlahPenduduk", "-");
        this.setText("jumlahKK", "-");
        this.setText("jumlahPuskesmas", "-");
        this.setText("jumlahPustu", "-");
        this.setText("jumlahPosyandu", "-");
        this.setText("jumlahDesa", "-");
    },

    /* ==========================================================
       DASHBOARD LOAD DISTRICT (FIXED)
    ========================================================== */

    loadDistrict: function(id) {
        if (!id) {
            Log.warn("loadDistrict: id kosong");
            return;
        }

        // Hapus prefix "kec_" jika ada
        var cleanId = id;
        if (id && id.startsWith("kec_")) {
            cleanId = id.substring(4);
        }

        // Mapping ID SVG ke nama kecamatan di database
        var nameMapping = {
            'mojolaban': 'Mojolaban',
            'baki': 'Baki',
            'gatak': 'Gatak',
            'bendosari': 'Bendosari',
            'polokarto': 'Polokarto',
            'grogol': 'Grogol',
            'kartasura': 'Kartasura',
            'sukoharjo': 'Sukoharjo',
            'tawangsari': 'Tawangsari',
            'bulu': 'Bulu',
            'weru': 'Weru',
            'nguter': 'Nguter'
        };

        // Gunakan mapping jika ada
        if (nameMapping[cleanId]) {
            cleanId = nameMapping[cleanId];
        }

        this.currentDistrict = cleanId;
        this.showLoading();

        var url = "api/kecamatan.php?id=" + encodeURIComponent(cleanId) + "&ts=" + Date.now();

        Log.info("Load district:", cleanId, "URL:", url);

        fetch(url, { cache: "no-store" })
            .then(function(res) {
                if (!res.ok) {
                    throw new Error("HTTP " + res.status);
                }
                return res.json();
            })
            .then(function(json) {
                Log.info("Data district response:", json);
                Dashboard.render(json);
            })
            .catch(function(err) {
                Log.error("Error load district:", err);
                Dashboard.showOffline();
            });
    },

    /* ==========================================================
       DASHBOARD RENDER (FIXED)
    ========================================================== */

    render: function(json) {
        this.hideLoading();

        if (!json) {
            this.showOffline();
            return;
        }

        if (json.status === false) {
            if (json.data && json.data.length > 0) {
                var found = json.data.find(function(item) {
                    return item.nama_kecamatan &&
                        item.nama_kecamatan.toLowerCase() === Dashboard.currentDistrict.toLowerCase();
                });
                if (found) {
                    this.renderData(found);
                    return;
                }
            }
            this.showOffline();
            return;
        }

        this.renderData(json);
    },

    renderData: function(data) {
        var d = data.data && data.data.length > 0 ? data.data[0] : data;

        Log.info("Render data:", d);

        this.setText("namaKecamatan", d.nama || d.nama_kecamatan || "-");
        this.setNumber("jumlahPenduduk", d.penduduk || d.jumlah_penduduk || 0);
        this.setNumber("jumlahKK", d.kk || d.jumlah_kk || 0);
        this.setNumber("jumlahPuskesmas", d.puskesmas || d.jumlah_puskesmas || 0);
        this.setNumber("jumlahPustu", d.pustu || d.jumlah_pustu || 0);
        this.setNumber("jumlahPosyandu", d.posyandu || d.jumlah_posyandu || 0);
        this.setNumber("jumlahDesa", d.desa || d.jumlah_desa || 0);

        this.lastData = d;
    },

    showLoading: function() {
        this.loading = true;
        this.setText("namaKecamatan", "Memuat...");
    },

    hideLoading: function() {
        this.loading = false;
    },

    showOffline: function() {
        this.hideLoading();
        if (this.lastData && this.lastData.nama) {
            this.renderData(this.lastData);
            return;
        }
        this.setText("namaKecamatan", "Data tidak ditemukan");
        this.setText("jumlahPenduduk", "-");
        this.setText("jumlahKK", "-");
        this.setText("jumlahPuskesmas", "-");
        this.setText("jumlahPustu", "-");
        this.setText("jumlahPosyandu", "-");
        this.setText("jumlahDesa", "-");
    },

    setText: function(id, value) {
        var el = DOM.id(id);
        if (el) {
            el.textContent = value;
        }
    },

    setNumber: function(id, value) {
        Counter.start(id, value);
    }
};

/* ==========================================================
   BAGIAN 8 - STARTUP ENGINE
========================================================== */

const Startup = {
    initialized: false,

    init: function() {
        if (this.initialized) {
            return;
        }
        this.initialized = true;

        Log.info("Memulai Portal...");

        Core.init();
        Header.init();
        Visual.init();
        Orbit.init();
        OrbitMenu.init();
        DiseaseOrbit.init();
        MapEngine.init();
        Dashboard.init();
        PortalAPI.init();

        Log.info("%cPORTAL TERPADU DKK SUKOHARJO", "color:#00d4ff;font-size:16px;font-weight:bold");
        Log.info("Version : " + Portal.version);
        Log.info("Startup selesai.");
    }
};

/* ==========================================================
   DOM READY
========================================================== */

document.addEventListener("DOMContentLoaded", function() {
    Startup.init();
});

/* ==========================================================
   WINDOW LOAD
========================================================== */

window.addEventListener("load", function() {
    document.body.classList.add("portal-ready");
});

/* ==========================================================
   END OF FILE
========================================================== */

Log.info("app_v2.js loaded successfully.");