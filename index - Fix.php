<?php
date_default_timezone_set('Asia/Jakarta');
?>
<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="utf-8">

<meta http-equiv="X-UA-Compatible" content="IE=edge">

<meta name="viewport" content="width=device-width,initial-scale=1">

<title>Portal Terpadu Dinas Kesehatan Kabupaten Sukoharjo</title>

<link rel="icon" href="assets/img/logo.png">

<link rel="preconnect" href="https://fonts.googleapis.com">

<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">

<link rel="stylesheet"
href="assets/css/style_v2.css">

<link rel="preload"
href="assets/img/background.jpg"
as="video/mp4">

</head>

<body class="loading">

<!-- =======================================================
BACKGROUND
======================================================= -->

<div class="video-container">

<video
    autoplay
    muted
    loop
    playsinline
    poster="assets/img/background.jpg">



    <source src="assets/video/background.mp4" type="video/mp4">

</video>

<img
class="video-fallback"
src="assets/img/background.jpg"
alt="">

</div>

<div class="overlay-dark"></div>

<div class="grid-effect"></div>

<canvas id="starCanvas"></canvas>

<canvas id="networkCanvas"></canvas>

<div class="aurora aurora1"></div>
<div class="aurora aurora2"></div>
<div class="aurora aurora3"></div>

<!-- =======================================================
LOADING
======================================================= -->

<div id="loadingScreen">

<div class="loading-content">

<img src="assets/img/logo.png">

<h2>PORTAL TERPADU</h2>

<h4>Dinas Kesehatan Kabupaten Sukoharjo</h4>

<div
id="loadingText">

Memulai Portal...

</div>

<div class="loading-bar">

<div class="loading-progress"
id="loadingProgress"></div>

</div>

</div>

</div>

<!-- =======================================================
HALAMAN
======================================================= -->

<div class="landing-page">

<!-- =======================================================
HEADER
======================================================= -->

<header class="top-header" id="topHeader">

<div class="header-title">

<div class="portal-title">

<div class="small-title">

PEMERINTAH KABUPATEN SUKOHARJO

</div>

<h1>

PORTAL TERPADU

</h1>

<h2>

DINAS KESEHATAN KABUPATEN SUKOHARJO

</h2>

</div>

<div>

<img
src="assets/img/logo.png"
style="height:82px">

</div>

</div>

<!-- =======================================================
LIVE INFO
======================================================= -->

<div class="live-info">

<div class="info-card">

<i class="fas fa-calendar-alt"></i>


<span id="tanggalIndonesia"></span>

</span>

</div>

<div class="info-card">

<div id="clock">

00:00:00

</div>

</div>

<div class="info-card weather-box">

<i class="fas fa-cloud-sun"></i>

<span id="weather">

Memuat cuaca...

</span>

</div>

</div>

</header>

<!-- =======================================================
MAIN CONTENT
======================================================= -->

<main class="main-content">

<!-- =======================================================
LEFT PANEL
======================================================= -->

<section
class="left-panel"
id="leftPanel">

    <div class="glass-card">

        <h4>
            <i class="fas fa-circle-info"></i>
            Informasi Portal
        </h4>

        <p>

            Portal Terpadu Dinas Kesehatan Kabupaten Sukoharjo
            merupakan pusat informasi digital yang mengintegrasikan
            seluruh layanan, data kesehatan, dashboard,
            aplikasi internal serta layanan publik
            dalam satu tampilan interaktif.

        </p>

        <div class="portal-feature">

            <div>

                <i class="fas fa-hospital fa-2x"></i>

                <br><br>

                Puskesmas

            </div>

            <div>

                <i class="fas fa-users fa-2x"></i>

                <br><br>

                SDM

            </div>

            <div>

                <i class="fas fa-chart-line fa-2x"></i>

                <br><br>

                Dashboard

            </div>

            <div>

                <i class="fas fa-map-location-dot fa-2x"></i>

                <br><br>

                GIS

            </div>

        </div>

    </div>

    <div class="glass-card info-panel">

        <h4>

            <i class="fas fa-circle-nodes"></i>

            Informasi Wilayah

        </h4>

       <table>

<tr>
    <td>Kecamatan</td>
    <td id="namaKecamatan">-</td>
</tr>

<tr>
    <td>Puskesmas</td>
    <td id="jumlahPuskesmas">-</td>
</tr>

<tr>
    <td>Desa/Kelurahan</td>
    <td id="jumlahDesa">-</td>
</tr>

<tr>
    <td>Penduduk</td>
    <td id="jumlahPenduduk">-</td>
</tr>

</table>

    </div>

</section>

<!-- =======================================================
CENTER PANEL
======================================================= -->

<section class="center-panel">

<div id="map-container">

<div
class="map-stage"
id="mapStage">

<div class="map-grid"></div>

<div class="map-glow"></div>

<div class="radar radar1"></div>

<div class="radar radar2"></div>

<div class="radar radar3"></div>

<div class="holo-floor">

<div class="floor-glow"></div>

<div class="floor-ring ring-1"></div>

<div class="floor-ring ring-2"></div>

<div class="floor-ring ring-3"></div>

<div class="floor-ring ring-4"></div>

<div class="floor-grid"></div>

</div>

<!-- ===================================================
SVG MAP
=================================================== -->

<object
    id="svgMap"
    class="svg-map"
    type="image/svg+xml"
    data="assets/svg/sukoharjo_interactive.svg">

    Browser tidak mendukung SVG.

</object>

<!-- ===================================================
ORBIT MENU
=================================================== -->

<div id="orbitMenu" class="orbit-menu">

<div class="logo-ring"></div>

<div class="logo-ring ring2"></div>

<div class="logo-ring ring3"></div>

<a
class="logo-button"
id="btnPortal"
href="#">

<img

src="assets/img/logo2.png"

alt="Logo">

<small>

Portal Terpadu

</small>

</a>

<a href="#" class="orbit-item orbit1">

<i class="fas fa-envelope"></i>

<span>Surat</span>

</a>

<a href="#" class="orbit-item orbit2">

<i class="fas fa-calendar-days"></i>

<span>Agenda</span>

</a>

<a href="#" class="orbit-item orbit3">

<i class="fas fa-chart-column"></i>

<span>Dashboard</span>

</a>

<a href="#" class="orbit-item orbit4">

<i class="fas fa-tv"></i>

<span>TV</span>

</a>

<a href="#" class="orbit-item orbit5">

<i class="fas fa-layer-group"></i>

<span>Aplikasi</span>

</a>

<a href="#" class="orbit-item orbit6">

<i class="fas fa-gears"></i>

<span>Pengaturan</span>

</a>

</div>

</div>

</section>

<!-- =======================================================
RIGHT PANEL
======================================================= -->

<section
class="right-panel"
id="rightPanel">

<div class="glass-card">

<h4>

<i class="fas fa-bullhorn"></i>

Informasi Hari Ini

</h4>

<div
id="agendaHariIni"
class="agenda-container">

Memuat agenda...

</div>

</div>

<div class="glass-card">

<h4>

<i class="fas fa-chart-pie"></i>

Statistik Cepat

</h4>

<table class="info-panel">

<tr>

<td>Puskesmas</td>

<td id="statPuskesmas">12</td>

</tr>

<tr>

<td>Pustu</td>

<td id="statPustu">-</td>

</tr>

<tr>

<td>Posyandu</td>

<td id="statPosyandu">-</td>

</tr>

<tr>

<td>Pegawai</td>

<td id="statPegawai">-</td>

</tr>

</table>

</div>

</section>

</main>

<!-- =======================================================
BOTTOM BAR
======================================================= -->

<footer
class="bottom-bar"
id="footerBar">

    <!-- ==============================================
         RUNNING TEXT
    =============================================== -->

    <div class="running-info">

    <div class="running-icon">
    ...
    </div>

    <div class="running-wrapper">

        <div id="runningText"
             class="running-text">
             Loading...
        </div>

    </div>

</div>



    <!-- ==============================================
         STATISTIK
    =============================================== -->

    <div class="stats-row">

        <div class="stat-box">

            <i class="fas fa-users"></i>

            <h4 id="statPenduduk">

                0

            </h4>

            <p>

                Penduduk

            </p>

        </div>

        <div class="stat-box">

            <i class="fas fa-hospital"></i>

            <h4 id="statPuskesmas2">

                12

            </h4>

            <p>

                Puskesmas

            </p>

        </div>

        <div class="stat-box">

            <i class="fas fa-house-medical"></i>

            <h4 id="statPustu2">

                0

            </h4>

            <p>

                Pustu

            </p>

        </div>

        <div class="stat-box">

            <i class="fas fa-chart-line"></i>

            <h4 id="statProgram">

                0

            </h4>

            <p>

                Program

            </p>

        </div>

    </div>

</footer>

</div>

<!-- =======================================================
MODAL DAFTAR APLIKASI
======================================================= -->

<div
    class="apps-modal"
    id="appsModal">

    <div class="apps-window">

        <div class="apps-header">

            <h3>

                Portal Aplikasi DKK Sukoharjo

            </h3>

            <button id="closeApps">

                &times;

            </button>

        </div>

        <div class="app-category">

            <h4>

                Aplikasi Internal

            </h4>

            <div class="app-grid">

                <a href="surat/" target="_blank">

                    📄 Surat Digital

                </a>

                <a href="agenda/" target="_blank">

                    📅 Agenda Rapat

                </a>

                <a href="dashboard/" target="_blank">

                    📊 Dashboard

                </a>

                <a href="gis/" target="_blank">

                    🗺 GIS Kesehatan

                </a>

                <a href="pegawai/" target="_blank">

                    👥 Kepegawaian

                </a>

                <a href="aset/" target="_blank">

                    🏢 Aset

                </a>

            </div>

        </div>

        <div class="app-category">

            <h4>

                Pelayanan Publik

            </h4>

            <div class="app-grid">

                <a href="#">

                    🏥 Puskesmas

                </a>

                <a href="#">

                    💊 Farmasi

                </a>

                <a href="#">

                    ❤️ GERMAS

                </a>

                <a href="#">

                    📈 SPM

                </a>

                <a href="#">

                    👶 KIA

                </a>

                <a href="#">

                    🩺 Surveilans

                </a>

            </div>

        </div>

    </div>

</div>

<!-- =======================================================
MOUSE GLOW
======================================================= -->

<div id="mouseGlow"></div>

<!-- =======================================================
JAVASCRIPT LIBRARY
======================================================= -->

<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.7/gsap.min.js"></script>

<!-- =======================================================
APPLICATION
======================================================= -->



<script src="assets/js/starfield.js"></script>

<script src="assets/js/network.js"></script>

<script src="assets/js/map.js"></script>

<script src="assets/js/app_v2.js"></script>

<!-- =======================================================
STARTUP
======================================================= -->
<script>

window.addEventListener("load",function(){

    var bar=document.getElementById("loadingProgress");
    var screen=document.getElementById("loadingScreen");

    var p=0;

    var timer=setInterval(function(){

        p++;

        bar.style.width=p+"%";

        if(p>=100){

            clearInterval(timer);

            screen.style.opacity="0";

            setTimeout(function(){

                screen.style.display="none";

                document.body.classList.remove("loading");
                document.body.classList.add("portal-ready");

            },500);

        }

    },20);

});

document.addEventListener("DOMContentLoaded", function () {

    Orbit.init();

});

document.addEventListener("DOMContentLoaded", function () {

    Orbit.init();

    MapEngine.init();

});

</script>


</body>

</html>