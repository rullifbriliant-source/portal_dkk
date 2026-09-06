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
   Version 3.3.0 - FIX: panel SDM Kesehatan sekarang ikut
                   ter-update per kecamatan saat peta diklik
                   (sebelumnya cuma load total kabupaten sekali
                   di awal, tidak pernah refresh saat ganti
                   kecamatan)
========================================================== */

/* ==========================================================
   CORE CONFIG
========================================================== */

const Portal = {
    version: "3.3.0",
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
    pinned: false,
    bound: false,

    init: function() {

        this.createTooltip();

        this.bindSVG();

        document.addEventListener(
            "district-click",
            this.onDistrictClick.bind(this)
        );

        // Saat window di-resize, posisikan ulang tooltip yang terkunci
        // berdasarkan elemen kecamatan yang sedang dipilih.
        let resizeTimer = null;
        window.addEventListener("resize", function() {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(function() {
                if (MapEngine.current) {
                    MapEngine.pinTooltip(MapEngine.current);
                }
            }, 100);
        });

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
               MOUSEMOVE
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


        this.highlight(
            data.id
        );

        // Kunci tooltip pada kecamatan yang diklik selama 3 detik.
        // Timer sebelumnya dibersihkan agar reset bila klik kecamatan lain.
        this.pinned = true;

        clearTimeout(
            this.tooltip.timer
        );

        this.showTooltip(
            data.nama
        );

        this.pinTooltip(
            data.id
        );

        this.tooltip.timer =
            setTimeout(
                function() {

                    MapEngine.pinned = false;

                    if (
                        MapEngine.tooltip
                    ) {

                        MapEngine.tooltip.style.opacity =
                            "0";
                    }
                },
                3000
            );


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


        // Jika tooltip terkunci (lagi dipindahkan ke posisi kecamatan
        // yang diklik), timer auto-hide dijadwalkan oleh onDistrictClick.
        if (this.pinned) {
            return;
        }


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


    /* ==========================================================
       PIN TOOLTIP KE TENGAH ELEMEN KECAMATAN YANG DIPILIH
    ========================================================== */

    pinTooltip: function(id) {

        if (!this.tooltip) {
            return;
        }

        const obj =
            DOM.id("svgInteractive") ||
            DOM.id("svgMap");

        if (
            !obj ||
            !obj.contentDocument
        ) {
            return;
        }

        const el =
            obj.contentDocument.getElementById(id);

        if (!el) {
            return;
        }

        const rect =
            el.getBoundingClientRect();

        const objRect =
            obj.getBoundingClientRect();

        const x =
            objRect.left +
            rect.left +
            rect.width / 2;

        const y =
            objRect.top +
            rect.top +
            rect.height / 2;

        this.moveTooltip(x, y, true);
    },


    moveTooltip: function(x, y, force) {

        if (!this.tooltip) {
            return;
        }


        // Saat tooltip terkunci pada kecamatan yang diklik (3 detik),
        // hanya pemanggilan pinTooltip (force=true) yang boleh
        // mengubah posisi, bukan mousemove bebas.
        if (this.pinned && !force) {
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
        // this.loadFasyankes();  
        this.loadSdm(); // load total kabupaten dulu saat halaman pertama dibuka
        this.loadPenyakit();
        this.loadPortalInfo(); 
        
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

    loadFasyankes: function() {
        var cache = Cache.get("fasyankes", 120000);
        if (cache) {
            this.renderFasyankes(cache);
            return;
        }
        this.fetchJSON("api/get_fasyankes.php?ts=" + Date.now())
            .then(function(json) {
                if (json.status) {
                    Cache.set("fasyankes", json.data);
                    PortalAPI.renderFasyankes(json.data);
                }
            })
            .catch(function(err) {
                Log.warn("Gagal load data fasyankes:", err);
            });
    },    

    renderFasyankes: function(items) {
        var container = DOM.id("fasyankesContainer");
        if (!container) {
            items.forEach(function(item) {
                var el = null;
                var el2 = null;
                var nama = item.nama || item.nama_item || '';
                if (nama.toLowerCase() === 'puskesmas') {
                    el = DOM.id("statFasyankesPuskesmas");
                    el2 = DOM.id("statPuskesmas2");
                } else if (nama.toLowerCase() === 'pustu') {
                    el = DOM.id("statFasyankesPustu");
                    el2 = DOM.id("statPustu2");
                } else if (nama.toLowerCase() === 'klinik') {
                    el = DOM.id("statFasyankesKlinik");
                } else if (nama.toLowerCase() === 'rumah sakit') {
                    el = DOM.id("statFasyankesRS");
                }
                if (el) {
                    Counter.start(el.id, item.nilai || 0);
                }
                if (el2) {
                    Counter.start(el2.id, item.nilai || 0);
                }
            });
            return;
        }
        var html = '<table class="info-panel">';
        items.forEach(function(item) {
            var nama = item.nama || item.nama_item || '';
            var nilai = item.nilai || 0;
            html += '<tr><td>' + nama + '</td><td>' + Util.number(nilai) + '</td></tr>';
        });
        html += '</table>';
        container.innerHTML = html;
    },

loadPortalInfo: function() {
    this.fetchJSON("api/get_portal_info.php?ts=" + Date.now())
        .then(function(json) {
            if (json.status) {
                PortalAPI.renderPortalInfo(json.data);
            }
        })
        .catch(function(err) {
            Log.warn("Gagal load portal info:", err);
        });
},

renderPortalInfo: function(data) {
    var el = DOM.id("portalDeskripsi");
    if (el && data.deskripsi) {
        el.innerHTML = data.deskripsi;
    }
},
loadSdm: function(kecamatan) {
    var cacheKey = "sdm_" + (kecamatan || "total");
    var cache = Cache.get(cacheKey, 60000);
    if (cache) {
        this.renderSdm(cache);
        return;
    }

    var url = "api/get_sdm.php?ts=" + Date.now();
    if (kecamatan) {
        url += "&kecamatan=" + encodeURIComponent(kecamatan);
    }

    this.fetchJSON(url)
        .then(function(json) {
            if (json.status) {
                Cache.set(cacheKey, json.data);
                PortalAPI.renderSdm(json.data);
            }
        })
        .catch(function(err) {
            Log.warn("Gagal load data sdm:", err);
        });
},

renderSdm: function(items) {
    var container = DOM.id("sdmContainer");
    if (!container) return;
    
    var html = '<table class="info-panel">';
    items.forEach(function(item) {
        var nama = item.nama || item.nama_item || '';
        var nilai = item.nilai || 0;
        html += '<tr><td>' + nama + '</td><td>' + Util.number(nilai) + '</td></tr>';
    });
    html += '</table>';
    container.innerHTML = html;
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


       // ==========================================================
    // PENYAKIT POPULER (Bisa per Kecamatan)
    // ==========================================================

    loadPenyakit: function(kecamatan) {
        console.log("Penyakit dipanggil!", kecamatan);
        var self = this;
        
        var url = "api/get_penyakit_populer.php?ts=" + Date.now();
        if (kecamatan) {
            url += "&kecamatan=" + encodeURIComponent(kecamatan);
        }

        this.fetchJSON(url)
            .then(function(json) {
                console.log("API Response:", json);
                if (json.status) {
                    self.renderPenyakit(json.data);
                }
            })
            .catch(function(err) {
                console.error("Error:", err);
                Log.warn("Gagal load data penyakit populer:", err);
            });
    },

renderPenyakit: function(items) {
    console.log("renderPenyakit dipanggil, items:", items.length);
    var container = DOM.id("penyakitContainer");
    console.log("Container:", container);
    if (!container) {
        console.error("Container tidak ditemukan!");
        return;
    }
    
    var html = '<table class="info-panel">';
    items.forEach(function(item, index) {
        var nama = item.nama || item.nama_item || '';
        var nilai = item.nilai || 0;
        var no = (index + 1) + '. ';
        html += '<tr><td>' + no + nama + '</td><td>' + Util.number(nilai) + '</td></tr>';
    });
    html += '</table>';
    container.innerHTML = html;
    console.log("HTML sudah di-render!");
},

    /* ==========================================================
       DATA KECAMATAN
    ========================================================== */

    loadDistrict: function(id) {
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

        var cleanId = id;
        if (id && id.startsWith("kec_")) {
            cleanId = id.substring(4);
        }

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

    // ===== DATA DASAR =====
    this.setText("namaKecamatan", d.nama || d.nama_kecamatan || "-");
    this.setNumber("jumlahPenduduk", d.penduduk || d.jumlah_penduduk || 0);
    this.setNumber("jumlahKK", d.kk || d.jumlah_kk || 0);
    this.setNumber("jumlahPuskesmas", d.puskesmas || d.jumlah_puskesmas || 0);
    this.setNumber("jumlahPustu", d.pustu || d.jumlah_pustu || 0);
    this.setNumber("jumlahPosyandu", d.posyandu || d.jumlah_posyandu || 0);
    this.setNumber("jumlahDesa", d.desa || d.jumlah_desa || 0);

    // ===== FASYANKES =====
    this.setNumber("statFasyankesPuskesmas", d.puskesmas || d.jumlah_puskesmas || 0);
    this.setNumber("statFasyankesPustu", d.pustu || d.jumlah_pustu || 0);
    this.setNumber("statFasyankesKlinik", d.klinik || d.jumlah_klinik || 0);
    this.setNumber("statFasyankesRS", d.rumah_sakit || d.jumlah_rumah_sakit || 0);

       this.lastData = d;

    // ===== PANGGIL PENYAKIT PER KECAMATAN =====
    var namaKec = d.nama || d.nama_kecamatan;
    if (namaKec && typeof PortalAPI !== "undefined" && PortalAPI.loadPenyakit) {
        PortalAPI.loadPenyakit(namaKec);
    }

    // ===== SDM KESEHATAN =====
    if (namaKec && typeof PortalAPI !== "undefined" && PortalAPI.loadSdm) {
        PortalAPI.loadSdm(namaKec);
    }
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
   FASYANKES MODAL (PER KECAMATAN TERPILIH DI PETA)
========================================================== */

const FasyankesModal = {

    currentKecamatan: null,
    allItems: [],
    activeFilter: "Semua",

    init: function() {

        const card = DOM.id("appFasyankes");
        if (card) {
            card.addEventListener("click", function(e) {
                e.preventDefault();
                e.stopPropagation();
                FasyankesModal.open();
            });
        }

        // Active state kartu Informasi Portal hanya dari interaksi klik user.
        // Tidak ada kartu yang aktif saat halaman pertama dibuka.
        Array.prototype.forEach.call(
            document.querySelectorAll(".portal-feature div"),
            function(featureCard) {
                featureCard.addEventListener("click", function() {
                    Array.prototype.forEach.call(
                        document.querySelectorAll(".portal-feature div"),
                        function(other) {
                            other.classList.remove("active");
                        }
                    );
                    featureCard.classList.add("active");
                });
            }
        );

        const closeBtn = DOM.id("closeFasyankes");
        if (closeBtn) {
            closeBtn.addEventListener("click", function() {
                FasyankesModal.close();
            });
        }

        const modal = DOM.id("fasyankesModal");
        if (modal) {
            modal.addEventListener("click", function(e) {
                if (e.target === modal) {
                    FasyankesModal.close();
                }
            });
        }

        document.addEventListener("keydown", function(e) {
            if (e.key === "Escape") {
                FasyankesModal.close();
            }
        });

        Log.info("%cFasyankes Modal Ready", "color:#00d4ff");
    },

    /* ==========================================================
       OPEN / CLOSE
    ========================================================== */

    open: function() {

        const modal = DOM.id("fasyankesModal");
        if (!modal) return;

        // Kecamatan aktif disimpan oleh Dashboard saat peta diklik
        let kecamatan = null;

        if (
            typeof Dashboard !== "undefined" &&
            Dashboard.lastData &&
            Dashboard.lastData.nama
        ) {
            kecamatan = Dashboard.lastData.nama;
        } else if (
            typeof Dashboard !== "undefined" &&
            Dashboard.currentDistrict
        ) {
            kecamatan = Dashboard.currentDistrict;
        }

        modal.classList.add("show");

        const card = DOM.id("appFasyankes");
        if (card) {
            card.classList.add("active");
        }

        if (!kecamatan) {
            this.currentKecamatan = null;
            this.allItems = [];
            this.activeFilter = "Semua";
            this.setTitle("Fasyankes - Pilih Kecamatan");
            this.renderEmpty();
            return;
        }

        this.currentKecamatan = kecamatan;
        this.setTitle("Fasyankes " + kecamatan);
        this.showLoading();
        this.load(kecamatan);
    },

    close: function() {

        const modal = DOM.id("fasyankesModal");
        if (modal) {
            modal.classList.remove("show");
        }

        const card = DOM.id("appFasyankes");
        if (card) {
            card.classList.remove("active");
        }
    },

    setTitle: function(text) {

        const el = DOM.id("fasyankesModalTitle");
        if (el) {
            el.textContent = text;
        }
    },

    showLoading: function() {

        const list = DOM.id("fasyankesList");
        if (!list) return;

        list.innerHTML =
            '<div class="faskes-empty">' +
            '<i class="fas fa-spinner fa-spin"></i>' +
            "<p>Memuat data fasyankes...</p>" +
            "</div>";

        const filters = DOM.id("fasyankesFilters");
        if (filters) {
            filters.innerHTML = "";
        }
    },

    /* ==========================================================
       LOAD DATA DARI API
    ========================================================== */

    load: function(kecamatan) {

        const url =
            "api/faskes.php?kecamatan=" +
            encodeURIComponent(kecamatan) +
            "&ts=" +
            Date.now();

        fetch(url, { cache: "no-store" })
            .then(function(res) {
                if (!res.ok) {
                    throw new Error("HTTP " + res.status);
                }
                return res.json();
            })
            .then(function(json) {
                if (json.status && json.data) {
                    FasyankesModal.allItems = json.data || [];
                    FasyankesModal.activeFilter = "Semua";
                    FasyankesModal.renderFilters();
                    FasyankesModal.renderList();
                } else {
                    FasyankesModal.allItems = [];
                    FasyankesModal.renderFilters();
                    FasyankesModal.renderEmpty();
                }
            })
            .catch(function(err) {
                Log.error("Gagal load fasyankes:", err);
                FasyankesModal.renderError();
            });
    },

    /* ==========================================================
       GROUPING PER JENIS
    ========================================================== */

    groupItems: function() {

        const order = ["Puskesmas", "Pustu", "Klinik", "Rumah Sakit"];
        const groups = {};
        const has = {};

        this.allItems.forEach(function(item) {
            const jenis = item.jenis || "Lainnya";
            has[jenis] = true;
            if (!groups[jenis]) {
                groups[jenis] = [];
            }
            groups[jenis].push(item);
        });

        const keys = order.filter(function(k) {
            return has[k];
        });

        Object.keys(groups).forEach(function(k) {
            if (keys.indexOf(k) < 0) {
                keys.push(k);
            }
        });

        return {
            keys: keys,
            groups: groups
        };
    },

    /* ==========================================================
       FILTER CHIP
    ========================================================== */

    renderFilters: function() {

        const wrap = DOM.id("fasyankesFilters");
        if (!wrap) return;

        const g = this.groupItems();
        const total = this.allItems.length;

        let html = "";

        html +=
            '<button class="faskes-chip' +
            (this.activeFilter === "Semua" ? " active" : "") +
            '" data-jenis="Semua">Semua (' +
            Util.number(total) +
            ")</button>";

        g.keys.forEach(function(jenis) {

            html +=
                '<button class="faskes-chip' +
                (FasyankesModal.activeFilter === jenis ? " active" : "") +
                '" data-jenis="' +
                FasyankesModal.escapeHtml(jenis) +
                '">' +
                FasyankesModal.escapeHtml(jenis) +
                " (" +
                (g.groups[jenis] || []).length +
                ")</button>";
        });

        wrap.innerHTML = html;

        Array.prototype.forEach.call(
            wrap.querySelectorAll(".faskes-chip"),
            function(btn) {

                btn.addEventListener("click", function() {

                    FasyankesModal.activeFilter =
                        this.getAttribute("data-jenis");

                    FasyankesModal.renderFilters();
                    FasyankesModal.renderList();
                });
            }
        );
    },

    /* ==========================================================
       RENDER DAFTAR FASILITAS
    ========================================================== */

    renderList: function() {

        const list = DOM.id("fasyankesList");
        if (!list) return;

        if (this.allItems.length === 0) {
            this.renderEmpty();
            return;
        }

        const g = this.groupItems();
        const filter = this.activeFilter;
        const keys = filter === "Semua" ? g.keys : [filter];

        let html = "";

        keys.forEach(function(jenis) {

            const items = g.groups[jenis] || [];
            if (items.length === 0) return;

            html += '<div class="faskes-group">';
            html +=
                "<h5>" +
                FasyankesModal.escapeHtml(jenis) +
                " <span>" +
                items.length +
                " fasilitas</span></h5>";

            items.forEach(function(item) {
                html += FasyankesModal.renderItem(item);
            });

            html += "</div>";
        });

        list.innerHTML = html;
    },

    renderItem: function(item) {

        const jenis = item.jenis || "Lainnya";

        let badgeClass = "faskes-badge-lain";
        if (jenis === "Puskesmas") badgeClass = "faskes-badge-puskesmas";
        else if (jenis === "Pustu") badgeClass = "faskes-badge-pustu";
        else if (jenis === "Klinik") badgeClass = "faskes-badge-klinik";
        else if (jenis === "Rumah Sakit") badgeClass = "faskes-badge-rs";

        let fotoHtml =
            '<div class="faskes-photo"><i class="fas fa-hospital"></i></div>';

        if (item.foto) {
            fotoHtml =
                '<div class="faskes-photo"><img src="uploads/faskes/' +
                this.escapeHtml(item.foto) +
                '" alt="Foto fasilitas"></div>';
        }

        let infoHtml = "";

        if (item.alamat) {
            infoHtml +=
                '<p><i class="fas fa-map-marker-alt"></i> ' +
                this.escapeHtml(item.alamat) +
                "</p>";
        }

        if (item.telepon) {
            infoHtml +=
                '<p><i class="fas fa-phone"></i> ' +
                this.escapeHtml(item.telepon) +
                "</p>";
        }

        if (item.email) {
            infoHtml +=
                '<p><i class="fas fa-envelope"></i> ' +
                this.escapeHtml(item.email) +
                "</p>";
        }

        return (
            '<div class="faskes-item">' +
            fotoHtml +
            '<div class="faskes-info">' +
            "<h6>" +
            this.escapeHtml(item.nama_faskes) +
            '<span class="faskes-badge ' +
            badgeClass +
            '">' +
            this.escapeHtml(jenis) +
            "</span></h6>" +
            infoHtml +
            "</div></div>"
        );
    },

    /* ==========================================================
       EMPTY STATE / ERROR
    ========================================================== */

    renderEmpty: function() {

        const list = DOM.id("fasyankesList");
        if (!list) return;

        const kecamatan = this.currentKecamatan;

        let text =
            "Pilih kecamatan pada peta terlebih dahulu, lalu klik card Fasyankes.";
        let icon = "fa-map-marked-alt";

        if (kecamatan) {
            text =
                "Belum ada fasilitas kesehatan terdaftar di " +
                kecamatan +
                ".";
            icon = "fa-hospital";
        }

        list.innerHTML =
            '<div class="faskes-empty">' +
            '<i class="fas ' +
            icon +
            '"></i>' +
            "<p>" +
            text +
            "</p>" +
            "</div>";

        this.renderFilters();
    },

    renderError: function() {

        const list = DOM.id("fasyankesList");
        if (!list) return;

        list.innerHTML =
            '<div class="faskes-empty">' +
            '<i class="fas fa-triangle-exclamation"></i>' +
            "<p>Gagal memuat data fasyankes. Coba lagi nanti.</p>" +
            "</div>";
    },

    escapeHtml: function(v) {

        return String(v == null ? "" : v)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }
};

/* ==========================================================
   SDM MODAL — Detail per Fasyankes (Kecamatan → Fasyankes → SDM)
========================================================== */

const SdmModal = {
    currentKecamatan: null,
    data: null,
    activeJenis: "Semua",

    init: function() {
        const appSdm = DOM.id("appSdm");
        if (appSdm) {
            appSdm.addEventListener("click", function(e){ e.preventDefault(); e.stopPropagation(); SdmModal.open(); });
            appSdm.addEventListener("keydown", function(e){ if(e.key==="Enter") SdmModal.open(); });
            // Active state konsisten dengan Fasyankes
            appSdm.addEventListener("click", function(){
                Array.prototype.forEach.call(document.querySelectorAll(".portal-feature div"), function(el){ el.classList.remove("active"); });
                appSdm.classList.add("active");
            });
        }
        const closeBtn = DOM.id("closeSdm");
        if (closeBtn) closeBtn.addEventListener("click", function(){ SdmModal.close(); });
        const modal = DOM.id("sdmModal");
        if (modal) modal.addEventListener("click", function(e){ if(e.target===modal) SdmModal.close(); });
        document.addEventListener("keydown", function(e){ if(e.key==="Escape") SdmModal.close(); });
        Log.info("SdmModal Ready");
    },

    open: function() {
        const modal = DOM.id("sdmModal");
        if (!modal) return;
        let kecamatan = null;
        if (typeof Dashboard!=="undefined" && Dashboard.lastData && Dashboard.lastData.nama) kecamatan = Dashboard.lastData.nama;
        else if (typeof Dashboard!=="undefined" && Dashboard.currentDistrict) kecamatan = Dashboard.currentDistrict;
        else if (typeof MapEngine!=="undefined" && MapEngine.current) kecamatan = MapEngine.current;

        // normalisasi kec_ prefix
        if (kecamatan && kecamatan.indexOf("kec_")===0) kecamatan = kecamatan.substring(4);
        // mapping lowercase → TitleCase
        const map = {mojolaban:"Mojolaban",baki:"Baki",gatak:"Gatak",bendosari:"Bendosari",polokarto:"Polokarto",grogol:"Grogol",kartasura:"Kartasura",sukoharjo:"Sukoharjo",tawangsari:"Tawangsari",bulu:"Bulu",weru:"Weru",nguter:"Nguter"};
        if (kecamatan && map[kecamatan.toLowerCase()]) kecamatan = map[kecamatan.toLowerCase()];

        modal.classList.add("show");
        const app = DOM.id("appSdm");
        if (app) app.classList.add("active");

        if (!kecamatan) {
            this.currentKecamatan = null;
            this.setTitle("SDM — Pilih Kecamatan");
            this.renderEmpty("Pilih kecamatan pada peta terlebih dahulu.");
            return;
        }
        this.currentKecamatan = kecamatan;
        this.setTitle("SDM — " + kecamatan);
        this.showLoading();
        this.load(kecamatan);
    },

    close: function(){
        const modal = DOM.id("sdmModal");
        if (modal) modal.classList.remove("show");
        const app = DOM.id("appSdm");
        if (app) app.classList.remove("active");
    },

    setTitle: function(t){ const el=DOM.id("sdmModalTitle"); if(el) el.textContent=t; },

    showLoading: function(){
        const list=DOM.id("sdmList"); if(list) list.innerHTML='<div class="faskes-empty"><i class="fas fa-spinner fa-spin"></i><p>Memuat data SDM...</p></div>';
        const sum=DOM.id("sdmModalSummary"); if(sum) sum.innerHTML='';
        const f=DOM.id("sdmFilters"); if(f) f.innerHTML='';
    },

    load: function(kecamatan){
        const url="api/get_sdm.php?kecamatan="+encodeURIComponent(kecamatan)+"&ts="+Date.now();
        fetch(url,{cache:"no-store"}).then(function(r){ if(!r.ok) throw new Error("HTTP "+r.status); return r.json(); })
        .then(function(json){
            if(json.status){
                SdmModal.data=json;
                SdmModal.activeJenis="Semua";
                SdmModal.renderSummary(json);
                SdmModal.renderFilters(json);
                SdmModal.renderList(json);
            } else { SdmModal.renderEmpty("Data tidak ditemukan."); }
        }).catch(function(err){ Log.error("Gagal load SDM modal",err); SdmModal.renderError(); });
    },

    renderSummary: function(json){
        const el=DOM.id("sdmModalSummary"); if(!el) return;
        const total = json.total ?? 0;
        const per = json.total_per_jenis || json.data || [];
        let html='<div style="flex:1;min-width:140px;"><div style="font-size:11px;color:#87e3ff;letter-spacing:0.5px;">TOTAL SDM '+this.escapeHtml(json.kecamatan||'')+'</div><div style="font-size:24px;font-weight:700;color:#fff;">'+Util.number(total)+' <span style="font-size:11px;color:rgba(255,255,255,0.5);">orang</span></div><div style="font-size:11px;color:rgba(255,255,255,0.4);">Sumber: '+this.escapeHtml(json.source||'')+'</div></div>';
        html+='<div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">';
        per.forEach(function(p){
            html+='<span style="padding:6px 10px;background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.08);border-radius:8px;font-size:12px;color:#fff;">'+SdmModal.escapeHtml(p.nama)+' <b style="color:#00d4ff;">'+Util.number(p.nilai)+'</b></span>';
        });
        html+='</div>';
        // Spesialis breakdown jika ada
        if(json.spesialis && json.spesialis.length>0){
            html+='<div style="width:100%;margin-top:10px;padding-top:10px;border-top:1px solid rgba(255,255,255,0.06);display:flex;gap:8px;flex-wrap:wrap;align-items:center;">';
            html+='<span style="font-size:11px;color:#ffd54f;font-weight:600;letter-spacing:0.5px;"><i class="fas fa-user-doctor"></i> Spesialis Dokter:</span>';
            json.spesialis.forEach(function(s){
                const label = s.kode ? s.kode : s.nama;
                html+='<span style="padding:5px 9px;background:rgba(255,193,7,0.12);border:1px solid rgba(255,193,7,0.2);border-radius:8px;font-size:11px;color:#ffd54f;" title="'+SdmModal.escapeHtml(s.nama)+'">'+SdmModal.escapeHtml(label)+' <b>'+Util.number(s.nilai)+'</b></span>';
            });
            html+='</div>';
        }
        el.innerHTML=html;
    },

    renderFilters: function(json){
        const wrap=DOM.id("sdmFilters"); if(!wrap) return;
        const jenisSet = {};
        (json.total_per_jenis||[]).forEach(function(p){ jenisSet[p.nama]=true; });
        // juga dari faskes per_jenis
        const allJenis = Object.keys(jenisSet);
        let html='<button class="faskes-chip'+(this.activeJenis==="Semua"?" active":"")+'" data-jenis="Semua">Semua ('+Util.number(json.faskes?json.faskes.length:0)+' faskes)</button>';
        allJenis.forEach(function(j){
            html+='<button class="faskes-chip'+(SdmModal.activeJenis===j?' active':'')+'" data-jenis="'+SdmModal.escapeHtml(j)+'">'+SdmModal.escapeHtml(j)+'</button>';
        });
        wrap.innerHTML=html;
        Array.prototype.forEach.call(wrap.querySelectorAll(".faskes-chip"), function(btn){
            btn.addEventListener("click", function(){
                SdmModal.activeJenis=this.getAttribute("data-jenis");
                SdmModal.renderFilters(json);
                SdmModal.renderList(json);
            });
        });
    },

    renderList: function(json){
        const list=DOM.id("sdmList"); if(!list) return;
        const faskes=json.faskes||[];
        if(faskes.length===0){
            list.innerHTML='<div class="faskes-empty"><i class="fas fa-users"></i><p>Belum ada data SDM per fasyankes di '+this.escapeHtml(json.kecamatan||'')+'. Tambahkan di Admin → SDM → SDM per Fasyankes.</p></div>';
            return;
        }
        let html='';
        faskes.forEach(function(f){
            // filter jenis
            let per = f.per_jenis || [];
            if(SdmModal.activeJenis!=="Semua"){
                per = per.filter(function(p){ return p.nama===SdmModal.activeJenis; });
                // jika filter aktif dan faskes ini 0 untuk jenis itu, skip? tetap tampil tapi highlight
                if(per.length===1 && per[0].nilai===0) return;
            }
            const total = f.total;
            if(SdmModal.activeJenis!=="Semua"){
                // hitung total filtered
                const v = per[0]?per[0].nilai:0;
                if(v===0) return;
            }
            html+='<div class="faskes-group" style="margin-bottom:14px;">';
            html+='<h5 style="display:flex;justify-content:space-between;align-items:center;"><span>'+SdmModal.escapeHtml(f.nama_faskes)+' <span style="font-weight:400;color:rgba(255,255,255,0.4);font-size:11px;">('+SdmModal.escapeHtml(f.jenis)+')</span></span><span style="background:rgba(0,212,255,0.12);color:#00d4ff;padding:4px 10px;border-radius:20px;font-size:11px;">'+Util.number(total)+' orang</span></h5>';
            html+='<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:8px;margin-top:8px;">';
            (f.per_jenis||[]).forEach(function(p){
                if(SdmModal.activeJenis!=="Semua" && p.nama!==SdmModal.activeJenis) return;
                const isZero = p.nilai===0;
                html+='<div style="padding:8px 10px;background:'+(isZero?'rgba(255,255,255,0.03)':'rgba(255,255,255,0.06)')+';border:1px solid rgba(255,255,255,0.06);border-radius:8px;display:flex;justify-content:space-between;align-items:center;opacity:'+(isZero?'0.5':'1')+';"><span style="font-size:12px;color:rgba(255,255,255,0.8);">'+SdmModal.escapeHtml(p.nama)+'</span><span style="font-weight:700;color:'+(isZero?'rgba(255,255,255,0.4)':'#72e8ff')+';">'+Util.number(p.nilai)+'</span></div>';
            });
            html+='</div>';
            // Spesialis per faskes jika ada
            if(f.per_spesialis && f.per_spesialis.length>0){
                html+='<div style="margin-top:8px;padding:8px 10px;background:rgba(255,193,7,0.07);border:1px solid rgba(255,193,7,0.15);border-radius:10px;">';
                html+='<div style="font-size:10px;color:#ffd54f;font-weight:600;letter-spacing:0.5px;margin-bottom:6px;"><i class="fas fa-stethoscope"></i> SPESIALIS DOKTER</div>';
                html+='<div style="display:flex;gap:6px;flex-wrap:wrap;">';
                f.per_spesialis.forEach(function(s){
                    const label = s.kode ? s.kode : s.nama;
                    html+='<span style="padding:4px 8px;background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.08);border-radius:6px;font-size:11px;color:#ffd54f;" title="'+SdmModal.escapeHtml(s.nama)+'">'+SdmModal.escapeHtml(label)+' <b style="color:#fff;">'+Util.number(s.nilai)+'</b></span>';
                });
                html+='</div></div>';
            }
            html+='</div>';
        });
        if(!html) html='<div class="faskes-empty"><i class="fas fa-filter"></i><p>Tidak ada data untuk filter "'+this.escapeHtml(this.activeJenis)+'"</p></div>';
        list.innerHTML=html;
    },

    renderEmpty: function(msg){
        const list=DOM.id("sdmList"); if(list) list.innerHTML='<div class="faskes-empty"><i class="fas fa-map-marked-alt"></i><p>'+this.escapeHtml(msg)+'</p></div>';
    },
    renderError: function(){
        const list=DOM.id("sdmList"); if(list) list.innerHTML='<div class="faskes-empty"><i class="fas fa-triangle-exclamation"></i><p>Gagal memuat data SDM.</p></div>';
    },
    escapeHtml: FasyankesModal.escapeHtml
};

/* ==========================================================
   BAGIAN 8 - STARTUP ENGINE
========================================================= */

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
    
        Orbit.init();
        OrbitMenu.init();
        DiseaseOrbit.init();
        MapEngine.init();
        Dashboard.init();
        PortalAPI.init();
        PortalAPI.loadFasyankes();
        FasyankesModal.init();
        SdmModal.init();

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