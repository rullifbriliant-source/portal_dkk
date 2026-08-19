"use strict";

/* ==========================================================
   PORTAL TERPADU
   DINAS KESEHATAN KABUPATEN SUKOHARJO
   app_v2.js
   Version 3.0 Final
========================================================== */

/* ==========================================================
   CORE CONFIG
========================================================== */

const Portal = {

    version : "3.0.0",

    build   : "2026.07",

    debug   : true,

    online  : navigator.onLine,

    started : false

};

/* ==========================================================
   LOGGER
========================================================== */

const Log = {

    info:function(){

        if(!Portal.debug) return;

        console.log.apply(console,arguments);

    },

    warn:function(){

        if(!Portal.debug) return;

        console.warn.apply(console,arguments);

    },

    error:function(){

        console.error.apply(console,arguments);

    }

};

/* ==========================================================
   DOM HELPER
========================================================== */

const DOM = {

    id:function(id){

        return document.getElementById(id);

    },

    one:function(selector){

        return document.querySelector(selector);

    },

    all:function(selector){

        return document.querySelectorAll(selector);

    }

};

/* ==========================================================
   CACHE ENGINE
========================================================== */

const Cache = {

    store:{},

    set:function(key,value){

        this.store[key]={

            value:value,

            time:Date.now()

        };

    },

    get:function(key,maxAge){

        if(!this.store[key]){

            return null;

        }

        if(Date.now()-this.store[key].time>maxAge){

            return null;

        }

        return this.store[key].value;

    },

    remove:function(key){

        delete this.store[key];

    },

    clear:function(){

        this.store={};

    }

};

/* ==========================================================
   UTIL
========================================================== */

const Util = {

    pad:function(v){

        return v<10 ? "0"+v : String(v);

    },

    number:function(v){

        return Number(v||0)

        .toLocaleString("id-ID");

    },

    random:function(min,max){

        return Math.floor(

            Math.random()*

            (max-min+1)

        )+min;

    },

    show:function(el){

        if(el){

            el.style.display="";

        }

    },

    hide:function(el){

        if(el){

            el.style.display="none";

        }

    },

    fadeOut:function(el,time){

        if(!el) return;

        el.style.transition="opacity "+time+"ms";

        el.style.opacity="0";

    },

    fadeIn:function(el,time){

        if(!el) return;

        el.style.display="";

        el.style.transition="opacity "+time+"ms";

        el.style.opacity="1";

    }

};

/* ==========================================================
   LOADER
========================================================== */

const Loader = {

    init:function(){

        window.addEventListener(

            "load",

            function(){

                Loader.hide();

            }

        );

    },

    hide:function(){

        const box=DOM.id("loadingScreen");

        if(!box){

            document.body.classList.add(

                "portal-ready"

            );

            return;

        }

        setTimeout(function(){

            box.style.transition=

            "opacity .7s ease";

            box.style.opacity="0";

            box.style.pointerEvents="none";

            setTimeout(function(){

                box.style.display="none";

                document.body.classList.add(

                    "portal-ready"

                );

            },700);

        },900);

    }

};

/* ==========================================================
   CONNECTION
========================================================== */

const Connection={

    init:function(){

        window.addEventListener(

            "online",

            function(){

                Portal.online=true;

                Log.info(

                    "Portal Online"

                );

            }

        );

        window.addEventListener(

            "offline",

            function(){

                Portal.online=false;

                Log.warn(

                    "Portal Offline"

                );

            }

        );

    }

};

/* ==========================================================
   ERROR HANDLER
========================================================== */

const ErrorHandler={

    init:function(){

        window.onerror=function(

            msg,

            url,

            line,

            col,

            err

        ){

            Log.error(

                "ERROR :",

                msg,

                "Line :",line

            );

            return false;

        };

        window.addEventListener(

            "unhandledrejection",

            function(e){

                Log.error(

                    "Promise Error :",

                    e.reason

                );

            }

        );

    }

};

/* ==========================================================
   CORE
========================================================== */

const Core={

    init:function(){

        if(Portal.started){

            return;

        }

        Portal.started=true;

        Loader.init();

        Connection.init();

        ErrorHandler.init();

        Log.info(

            "%cPORTAL TERPADU DKK SUKOHARJO",

            "color:#00d4ff;font-size:16px;font-weight:bold"

        );

        Log.info(

            "Version",

            Portal.version

        );

    }

};

/* ==========================================================
   BAGIAN 2
   HEADER ENGINE
   Clock • Tanggal • Greeting • Weather
========================================================== */

/* ==========================================================
   CLOCK
========================================================== */

const Clock = {

    timer:null,

    init:function(){

        this.update();

        this.timer=setInterval(function(){

            Clock.update();

        },1000);

    },

    update:function(){

        const el=DOM.id("clock");

        if(!el) return;

        const now=new Date();

        el.textContent=

            Util.pad(now.getHours())+
            ":"+
            Util.pad(now.getMinutes())+
            ":"+
            Util.pad(now.getSeconds());

    }

};

/* ==========================================================
   TANGGAL INDONESIA
========================================================== */

const Tanggal={

    update:function(){

        const el=

            DOM.id("tanggalIndonesia") ||

            DOM.id("tanggal");

        if(!el) return;

        const now=new Date();

        el.textContent=

            now.toLocaleDateString(

                "id-ID",

                {

                    weekday:"long",

                    day:"numeric",

                    month:"long",

                    year:"numeric"

                }

            );

    },

    init:function(){

        this.update();

    }

};

/* ==========================================================
   GREETING
========================================================== */

const Greeting={

    init:function(){

        const el=DOM.id("greeting");

        if(!el) return;

        const h=new Date().getHours();

        let text="";

        if(h<11){

            text="Selamat Pagi";

        }

        else if(h<15){

            text="Selamat Siang";

        }

        else if(h<18){

            text="Selamat Sore";

        }

        else{

            text="Selamat Malam";

        }

        el.textContent=text;

    }

};

/* ==========================================================
   WEATHER
========================================================== */

const Weather={

    latitude:-7.683,

    longitude:110.833,

    interval:600000,

    init:function(){

        this.load();

        setInterval(function(){

            Weather.load();

        },this.interval);

    },

    load:function(){

        const el=DOM.id("weather");

        if(!el) return;

        fetch(

            "https://api.open-meteo.com/v1/forecast?latitude="+
            this.latitude+
            "&longitude="+
            this.longitude+
            "&current_weather=true",

            {

                cache:"no-store"

            }

        )

        .then(function(res){

            return res.json();

        })

        .then(function(json){

            if(!json.current_weather){

                el.innerHTML="-";

                return;

            }

            el.innerHTML=

                Math.round(

                    json.current_weather.temperature

                )+

                "&deg;C";

        })

        .catch(function(){

            el.innerHTML="-";

        });

    }

};

/* ==========================================================
   HEADER ENGINE
========================================================== */

const Header={

    init:function(){

        Clock.init();

        Tanggal.init();

        Greeting.init();

        Weather.init();

    }

};

/* ==========================================================
   BAGIAN 3
   VISUAL ENGINE
   Mouse Glow • Glass Card • Parallax • Intro
========================================================== */

/* ==========================================================
   MOUSE GLOW
========================================================== */

const MouseGlow={

    glow:null,

    x:window.innerWidth/2,
    y:window.innerHeight/2,

    cx:window.innerWidth/2,
    cy:window.innerHeight/2,

    init:function(){

        this.glow=DOM.id("mouseGlow");

        if(!this.glow) return;

        document.addEventListener(

            "mousemove",

            function(e){

                MouseGlow.x=e.clientX;
                MouseGlow.y=e.clientY;

            }

        );

        MouseGlow.animate();

    },

    animate:function(){

        if(!MouseGlow.glow) return;

        MouseGlow.cx+=(MouseGlow.x-MouseGlow.cx)*0.15;
        MouseGlow.cy+=(MouseGlow.y-MouseGlow.cy)*0.15;

        MouseGlow.glow.style.left=
            MouseGlow.cx+"px";

        MouseGlow.glow.style.top=
            MouseGlow.cy+"px";

        requestAnimationFrame(

            MouseGlow.animate

        );

    }

};

/* ==========================================================
   GLASS CARD
========================================================== */

const GlassCard={

    init:function(){

        DOM.all(".glass-card")

        .forEach(function(card){

            card.style.transition=

                "transform .25s ease";

            card.addEventListener(

                "mouseenter",

                function(){

                    card.style.transform=

                        "translateY(-8px) scale(1.03)";

                }

            );

            card.addEventListener(

                "mouseleave",

                function(){

                    card.style.transform="";

                }

            );

        });

    }

};

/* ==========================================================
   PARALLAX MAP
========================================================== */

const Parallax={

    init:function(){

        const map=

            DOM.id("mapContainer") ||

            DOM.id("map-container");

        if(!map) return;

        document.addEventListener(

            "mousemove",

            function(e){

                const x=

                    (window.innerWidth/2-

                    e.clientX)/80;

                const y=

                    (window.innerHeight/2-

                    e.clientY)/80;

                map.style.transform=

                    "perspective(1800px) "+

                    "rotateX("+

                    y+

                    "deg) rotateY("+

                    (-x)+

                    "deg)";

            }

        );

    }

};

/* ==========================================================
   BUTTON HOVER
========================================================== */

const ButtonFX={

    init:function(){

        DOM.all(

            ".btn,.orbit-item"

        )

        .forEach(function(btn){

            btn.style.transition=

                "all .25s ease";

            btn.addEventListener(

                "mouseenter",

                function(){

                    btn.style.transform=

                        "scale(1.08)";

                }

            );

            btn.addEventListener(

                "mouseleave",

                function(){

                    btn.style.transform="";

                }

            );

        });

    }

};

/* ==========================================================
   GSAP INTRO
========================================================== */

const Intro={

    init:function(){

        if(typeof gsap==="undefined"){

            return;

        }

        window.addEventListener(

            "load",

            function(){

                const tl=

                    gsap.timeline();

                tl.from(

                    ".top-header",

                    {

                        opacity:0,

                        y:-60,

                        duration:.8

                    }

                )

                .from(

                    ".left-panel",

                    {

                        opacity:0,

                        x:-80,

                        duration:.7

                    },

                    "-=.4"

                )

                .from(

                    ".right-panel",

                    {

                        opacity:0,

                        x:80,

                        duration:.7

                    },

                    "-=.5"

                )

                .from(

                    ".map-stage",

                    {

                        scale:.9,

                        opacity:0,

                        duration:1

                    },

                    "-=.5"

                )

                .from(

                    ".bottom-bar",

                    {

                        opacity:0,

                        y:60,

                        duration:.6

                    },

                    "-=.5"

                );

            }

        );

    }

};

/* ==========================================================
   VISUAL ENGINE
========================================================== */

const Visual={

    init:function(){

        MouseGlow.init();

        GlassCard.init();

        Parallax.init();

        ButtonFX.init();

        Intro.init();

    }

};

/* ==========================================================
   BAGIAN 4
   ORBIT ENGINE
========================================================== */

const Orbit={

    menu:null,

    items:[],

    radius:250,

    centerX:300,

    centerY:300,

    angle:0,

    speed:0.004,

    running:true,

    raf:null,

    init:function(){

        this.menu=DOM.id("orbitMenu");

        if(!this.menu){

            Log.warn("orbitMenu tidak ditemukan");

            return;

        }

        this.items=this.menu.querySelectorAll(".orbit-item");

        if(this.items.length===0){

            Log.warn("orbit-item tidak ditemukan");

            return;

        }

        this.bind();

        this.animate();

    },

    bind:function(){

        this.menu.addEventListener(

            "mouseenter",

            function(){

                Orbit.running=false;

            }

        );

        this.menu.addEventListener(

            "mouseleave",

            function(){

                Orbit.running=true;

            }

        );

        this.items.forEach(function(item){

            item.style.position="absolute";

            item.style.transition=

                "transform .25s ease";

            item.addEventListener(

                "mouseenter",

                function(){

                    item.style.transform=

                        "scale(1.15)";

                }

            );

            item.addEventListener(

                "mouseleave",

                function(){

                    item.style.transform=

                        "scale(1)";

                }

            );

        });

    },

    animate:function(){

        if(Orbit.running){

            Orbit.angle+=Orbit.speed;

        }

        const total=Orbit.items.length;

        Orbit.items.forEach(function(item,index){

            const a=

                Orbit.angle+

                (Math.PI*2/total)*index;

            const x=

                Orbit.centerX+

                Math.cos(a)*Orbit.radius;

            const y=

                Orbit.centerY+

                Math.sin(a)*Orbit.radius;

            item.style.left=(x-45)+"px";

            item.style.top=(y-45)+"px";

        });

        Orbit.raf=requestAnimationFrame(

            Orbit.animate

        );

    },

    stop:function(){

        cancelAnimationFrame(

            this.raf

        );

    },

    start:function(){

        cancelAnimationFrame(

            this.raf

        );

        this.animate();

    },

    setRadius:function(radius){

        this.radius=radius;

    },

    setSpeed:function(speed){

        this.speed=speed;

    }

};

/* ==========================================================
   ORBIT NAVIGATION
========================================================== */

const OrbitMenu={

    init:function(){

        DOM.all(

            "#orbitMenu .orbit-item"

        )

        .forEach(function(item){

            item.addEventListener(

                "click",

                function(e){

                    e.preventDefault();

                    const nama=

                        item.textContent.trim();

                    Log.info(

                        "Menu : "+nama

                    );

                }

            );

        });

    }

};

/* ==========================================================
   BAGIAN 5
   INTERACTIVE MAP ENGINE
========================================================== */

const MapEngine={

    current:null,

    tooltip:null,

    init:function(){

        this.createTooltip();

        document.addEventListener(

            "district-click",

            this.onDistrictClick.bind(this)

        );

        Log.info("Map Engine Ready");

    },

    createTooltip:function(){

        let tip=DOM.id("mapTooltip");

        if(!tip){

            tip=document.createElement("div");

            tip.id="mapTooltip";

            tip.style.position="fixed";
            tip.style.zIndex="99999";
            tip.style.pointerEvents="none";

            tip.style.padding="8px 14px";

            tip.style.background="rgba(0,0,0,.82)";

            tip.style.color="#fff";

            tip.style.borderRadius="8px";

            tip.style.fontSize="13px";

            tip.style.opacity="0";

            tip.style.transition="opacity .2s";

            document.body.appendChild(tip);

        }

        this.tooltip=tip;

    },

    onDistrictClick:function(e){

        const data=e.detail;

        this.current=data.id;

        Log.info(

            "Klik Kecamatan :",data.nama

        );

        this.highlight(data.id);

        this.showTooltip(data.nama);

        Dashboard.loadDistrict(data.id);

    },

    highlight:function(id){

        const obj=DOM.id("svgMap");

        if(!obj || !obj.contentDocument){

            return;

        }

        const svg=obj.contentDocument;

        svg.querySelectorAll(".district")

        .forEach(function(item){

            item.style.fill="#7fb3d5";

            item.style.stroke="#ffffff";

            item.style.strokeWidth="1";

        });

        const aktif=svg.getElementById(id);

        if(!aktif){

            return;

        }

        aktif.style.fill="#00d4ff";

        aktif.style.stroke="#ffd700";

        aktif.style.strokeWidth="3";

    },

    showTooltip:function(text){

        if(!this.tooltip){

            return;

        }

        this.tooltip.innerHTML=text;

        this.tooltip.style.opacity="1";

        clearTimeout(this.tooltip.timer);

        this.tooltip.timer=setTimeout(()=>{

            this.tooltip.style.opacity="0";

        },2000);

    },

    moveTooltip:function(x,y){

        if(!this.tooltip){

            return;

        }

        this.tooltip.style.left=(x+20)+"px";

        this.tooltip.style.top=(y+20)+"px";

    }

};

/* ==========================================================
   TOOLTIP FOLLOW MOUSE
========================================================== */

document.addEventListener(

    "mousemove",

    function(e){

        MapEngine.moveTooltip(

            e.clientX,

            e.clientY

        );

    }

);

/* ==========================================================
   MAP DASHBOARD
========================================================== */

const MapDashboard={

    current:null,

    load:function(id){

        if(!id){

            return;

        }

        this.current=id;

        Log.info(

            "Load Dashboard :",id

        );

        /*
           Tahap berikutnya:

           get_kecamatan.php?id=xxxx
        */

        this.render({

            nama:id.toUpperCase(),

            penduduk:0,

            kk:0,

            puskesmas:0,

            posyandu:0

        });

    },

    render:function(data){

        this.set(

            "namaKecamatan",

            data.nama

        );

        this.set(

            "jumlahPenduduk",

            Util.number(data.penduduk)

        );

        this.set(

            "jumlahKK",

            Util.number(data.kk)

        );

        this.set(

            "jumlahPuskesmas",

            data.puskesmas

        );

        this.set(

            "jumlahPosyandu",

            data.posyandu

        );

    },

    set:function(id,val){

        const el=DOM.id(id);

        if(el){

            el.textContent=val;

        }

    }

};

/* ==========================================================
   BAGIAN 6
   API ENGINE
========================================================== */

const PortalAPI={

    base:"",

    interval:300000,

    init:function(){

        this.loadAll();

        setInterval(function(){

            PortalAPI.loadAll();

        },this.interval);

    },

    loadAll:function(){

        this.loadRunning();

        this.loadAgenda();

        this.loadDashboard();

    },

/* ==========================================================
   SAFE FETCH JSON
========================================================== */

    fetchJSON:function(url){

        return fetch(

            url,

            {

                cache:"no-store"

            }

        )

        .then(function(res){

            if(!res.ok){

                throw new Error(

                    "HTTP "+res.status

                );

            }

            return res.json();

        });

    },

/* ==========================================================
   RUNNING TEXT
========================================================== */

    loadRunning:function(){

        const bar=DOM.id("runningText");

        if(!bar) return;

        this.fetchJSON(

            this.base+

            "get_running.php?ts="+

            Date.now()

        )

        .then(function(json){

            if(!json.status){

                return;

            }

            bar.innerHTML=

                json.text || "";

        })

        .catch(function(){

            Log.warn(

                "Running text offline"

            );

        });

    },

/* ==========================================================
   AGENDA
========================================================== */

    loadAgenda:function(){

        const box=

            DOM.id("agendaHariIni");

        if(!box) return;

        this.fetchJSON(

            this.base+

            "get_jadwal.php?ts="+

            Date.now()

        )

        .then(function(json){

            if(

                !json.status ||

                json.total===0

            ){

                box.innerHTML=

                "<div class='agenda-empty'>Tidak ada agenda hari ini</div>";

                return;

            }

            let html="";

            json.agenda.forEach(function(item){

                html+=

                "<div class='agenda-item'>"+

                    "<div class='agenda-jam'>"+

                    item.mulai+

                    "</div>"+

                    "<div class='agenda-info'>"+

                        "<b>"+

                        item.topik+

                        "</b><br>"+

                        "<small>"+

                        item.ruangan+

                        "</small>"+

                    "</div>"+

                "</div>";

            });

            box.innerHTML=html;

        })

        .catch(function(){

            box.innerHTML=

            "<div class='agenda-error'>Agenda gagal dimuat</div>";

        });

    },

/* ==========================================================
   DASHBOARD
========================================================== */

    loadDashboard:function(){

        const cache=

            Cache.get(

                "dashboard",

                60000

            );

        if(cache){

            this.renderDashboard(cache);

            return;

        }

        this.fetchJSON(

            this.base+

            "get_dashboard.php?ts="+

            Date.now()

        )

        .then(function(json){

            Cache.set(

                "dashboard",

                json

            );

            PortalAPI.renderDashboard(

                json

            );

        })

        .catch(function(){

            Log.warn(

                "Dashboard offline"

            );

        });

    },

    renderDashboard:function(data){

        if(!data.status){

            return;

        }

        Counter.start(

            "statPenduduk",

            data.penduduk

        );

        Counter.start(

            "statPuskesmas",

            data.puskesmas

        );

        Counter.start(

            "statPustu",

            data.pustu

        );

        Counter.start(

            "statProgram",

            data.program

        );

    },

/* ==========================================================
   DATA KECAMATAN
========================================================== */

    loadDistrict:function(id){

        return this.fetchJSON(

            this.base+

            "get_kecamatan.php?id="+

            encodeURIComponent(id)+

            "&ts="+

            Date.now()

        );

    }

};

/* ==========================================================
   COUNTER
========================================================== */

const Counter={

    start:function(id,target){

        const el=DOM.id(id);

        if(!el){

            return;

        }

        target=parseInt(target)||0;

        let value=0;

        const step=Math.max(

            1,

            Math.ceil(target/80)

        );

        const timer=

        setInterval(function(){

            value+=step;

            if(value>=target){

                value=target;

                clearInterval(timer);

            }

            el.textContent=

                Util.number(value);

        },18);

    }

};

/* ==========================================================
   BAGIAN 7
   DASHBOARD ENGINE
========================================================== */

const Dashboard={

    currentDistrict:null,

    loading:false,

    init:function(){

        Log.info("Dashboard Engine Ready");

    },

/* ==========================================================
   LOAD DISTRICT
========================================================== */

    loadDistrict:function(id){

        if(!id){

            return;

        }

        this.currentDistrict=id;

        this.showLoading();

        PortalAPI.loadDistrict(id)

        .then(function(json){

            Dashboard.render(json);

        })

        .catch(function(){

            Dashboard.showOffline();

        });

    },

/* ==========================================================
   RENDER
========================================================== */

    render:function(json){

        this.hideLoading();

        if(!json){

            this.showOffline();

            return;

        }

        if(json.status===false){

            this.showOffline();

            return;

        }

        this.setText(

            "namaKecamatan",

            json.nama || "-"

        );

        this.setNumber(

            "jumlahPenduduk",

            json.penduduk || 0

        );

        this.setNumber(

            "jumlahKK",

            json.kk || 0

        );

        this.setNumber(

            "jumlahPuskesmas",

            json.puskesmas || 0

        );

        this.setNumber(

            "jumlahPosyandu",

            json.posyandu || 0

        );

    },

/* ==========================================================
   LOADING
========================================================== */

    showLoading:function(){

        this.loading=true;

        const el=DOM.id("dashboardLoading");

        if(el){

            el.style.display="flex";

        }

    },

    hideLoading:function(){

        this.loading=false;

        const el=DOM.id("dashboardLoading");

        if(el){

            el.style.display="none";

        }

    },

/* ==========================================================
   OFFLINE
========================================================== */

    showOffline:function(){

        this.hideLoading();

        this.setText(

            "namaKecamatan",

            "-"

        );

        this.setText(

            "jumlahPenduduk",

            "-"

        );

        this.setText(

            "jumlahKK",

            "-"

        );

        this.setText(

            "jumlahPuskesmas",

            "-"

        );

        this.setText(

            "jumlahPosyandu",

            "-"

        );

    },

/* ==========================================================
   TEXT
========================================================== */

    setText:function(id,value){

        const el=DOM.id(id);

        if(!el){

            return;

        }

        el.textContent=value;

    },

/* ==========================================================
   NUMBER
========================================================== */

    setNumber:function(id,value){

        Counter.start(

            id,

            value

        );

    }

};

/* ==========================================================
   HUBUNGKAN DENGAN MAP ENGINE
========================================================== */

document.addEventListener(

    "district-click",

    function(e){

        Dashboard.loadDistrict(

            e.detail.id

        );

    }

);

/* ==========================================================
   BAGIAN 8
   STARTUP ENGINE
========================================================== */

const Startup={

    initialized:false,

    init:function(){

        if(this.initialized){

            return;

        }

        this.initialized=true;

        Log.info("Memulai Portal...");

        /* CORE */

        Core.init();

        /* HEADER */

        Header.init();

        /* VISUAL */

        Visual.init();

        /* ORBIT */

        Orbit.init();

        OrbitMenu.init();

        /* MAP */

        MapEngine.init();

        /* DASHBOARD */

        Dashboard.init();

        /* API */

        PortalAPI.init();

        Log.info(

            "%cPORTAL TERPADU DKK SUKOHARJO",

            "color:#00d4ff;font-size:16px;font-weight:bold"

        );

        Log.info(

            "Version : " + Portal.version

        );

        Log.info(

            "Startup selesai."

        );

    }

};

/* ==========================================================
   DOM READY
========================================================== */

document.addEventListener(

    "DOMContentLoaded",

    function(){

        Startup.init();

    }

);

/* ==========================================================
   WINDOW LOAD
========================================================== */

window.addEventListener(

    "load",

    function(){

        document.body.classList.add(

            "portal-ready"

        );

    }

);

/* ==========================================================
   END OF FILE
========================================================== */

Log.info(

    "app_v2.js loaded successfully."

);