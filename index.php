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
    as="image">

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

    <img src="assets/img/kabupaten.png">

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

<header class="top-header">

    <!-- KIRI: LOGO KABUPATEN + INFORMASI HEADER -->
    <div class="header-left">

        <div class="header-logo-kabupaten">
            <img src="assets/img/kabupaten.png"
                 alt="Logo Kabupaten Sukoharjo">
        </div>

        <div class="header-info">
            <div class="government-name">
                PEMERINTAH KABUPATEN SUKOHARJO
            </div>

            <h1>PORTAL TERPADU</h1>

            <div class="department-name">
                DINAS KESEHATAN KABUPATEN SUKOHARJO
            </div>

            <div class="header-date">
                <span id="tanggalIndonesia"></span>

                <span class="weather">
                    ☀️
                    <span id="weather">-</span>
                </span>
            </div>
        </div>

    </div>


    <!-- KANAN: LOGO SUKOHARJO SPEKTAKULER -->
    <div class="header-logo-spektakuler">
        <img src="assets/img/spektakuler.png"
             alt="Sukoharjo Spektakuler">
    </div>

</header>
    
    <!-- Jam di tengah, terpisah -->
    <div class="center-clock">
        <div id="clock">00:00:00</div>
    </div>
    </header>

    <!-- =======================================================
    LIVE INFO
    ======================================================= -->

    <div class="live-info">

    <div class="info-card">

    <i class="fas fa-calendar-alt"></i>


    <span id="tanggalIndonesia"></span>


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

    
        

    </section><section
    class="left-panel"
    id="leftPanel">

        <div class="glass-card">

            <h4>
                <i class="fas fa-circle-info"></i>
                Informasi Portal
            </h4>
<p id="portalDeskripsi">
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

            <div class="stats-row">

                <div class="stat-box">
                    <i class="fas fa-users"></i>
                    <h4 id="statPenduduk">0</h4>
                    <p>Penduduk</p>
                </div>

                <div class="stat-box">
                    <i class="fas fa-hospital"></i>
                    <h4 id="statPuskesmas2">12</h4>
                    <p>Puskesmas</p>
                </div>

                <div class="stat-box">
                    <i class="fas fa-house-medical"></i>
                    <h4 id="statPustu2">0</h4>
                    <p>Pustu</p>
                </div>

                <div class="stat-box">
                    <i class="fas fa-chart-line"></i>
                    <h4 id="statProgram">0</h4>
                    <p>Program</p>
                </div>

            </div>

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
    <object id="svgInteractive" data="assets/svg/peta_sukoharjo_satelit_interaktif.svg" type="image/svg+xml"></object>

    <!-- ===================================================
    ORBIT MENU (PENYAKIT TERBANYAK)
    =================================================== -->
    <div id="orbitMenu">
        <div class="orbit-item disease-orbit-item">
            <i class="fa-solid fa-virus-covid"></i>
            <span class="disease-name">COVID-19</span>
            <span class="disease-count">2.000</span>
        </div>

        <div class="orbit-item disease-orbit-item">
            <i class="fa-solid fa-lungs-virus"></i>
            <span class="disease-name">ISPA</span>
            <span class="disease-count">1.540</span>
        </div>

        <div class="orbit-item disease-orbit-item">
            <i class="fa-solid fa-heart-pulse"></i>
            <span class="disease-name">Hipertensi</span>
            <span class="disease-count">1.230</span>
        </div>

        <div class="orbit-item disease-orbit-item">
            <i class="fa-solid fa-droplet"></i>
            <span class="disease-name">Diare</span>
            <span class="disease-count">890</span>
        </div>

        <div class="orbit-item disease-orbit-item">
            <i class="fa-solid fa-syringe"></i>
            <span class="disease-name">TBC</span>
            <span class="disease-count">640</span>
        </div>

        <div class="orbit-item disease-orbit-item">
            <i class="fa-solid fa-bone"></i>
            <span class="disease-name">Diabetes</span>
            <span class="disease-count">510</span>
        </div>
    </div>

</section>

    <!-- =======================================================
    RIGHT PANEL
    ======================================================= -->

    <section
        class="right-panel"
        id="rightPanel">

<!-- FASYANKES -->
<div class="glass-card">
    <h4>
        <i class="fas fa-hospital"></i>
        Fasyankes
    </h4>
    <table class="info-panel">
        <tr>
            <td>Puskesmas</td>
            <td id="statFasyankesPuskesmas">-</td>
        </tr>
        <tr>
            <td>Pustu</td>
            <td id="statFasyankesPustu">-</td>
        </tr>
        <tr>
            <td>Klinik</td>
            <td id="statFasyankesKlinik">-</td>
        </tr>
        <tr>
            <td>Rumah Sakit</td>
            <td id="statFasyankesRS">-</td>
        </tr>
    </table>
</div>

       <!-- SDM -->
<div class="glass-card">
    <h4>
        <i class="fas fa-user-doctor"></i>
        SDM Kesehatan
    </h4>
    <div id="sdmContainer">
        <table class="info-panel">
            <tr><td colspan="2" style="text-align:center;color:rgba(255,255,255,0.3);">Memuat data...</td></tr>
        </table>
    </div>
</div>

        <!-- DATA DASAR -->
        <div class="glass-card card-full">

            <h4>
                <i class="fas fa-circle-nodes"></i>
                Data Dasar
            </h4>

            <table class="info-panel">
                <tr>
                    <td>Kecamatan</td>
                    <td id="namaKecamatan">Kabupaten Sukoharjo</td>
                </tr>
                <tr>
                    <td>Desa / Kelurahan</td>
                    <td id="jumlahDesa">-</td>
                </tr>
                <tr>
                    <td>Penduduk</td>
                    <td id="jumlahPenduduk">-</td>
                </tr>
                <tr>
                    <td>Puskesmas</td>
                    <td id="jumlahPuskesmas">-</td>
                </tr>
                <tr>
                    <td>Pustu</td>
                    <td id="jumlahPustu">-</td>
                </tr>
                <tr>
                    <td>Posyandu</td>
                    <td id="jumlahPosyandu">-</td>
                </tr>
            </table>

        </div>
                <!-- 10 PENYAKIT POPULER -->
<div class="glass-card card-full card-scrollable">
    <h4>
        <i class="fas fa-virus"></i>
        10 Penyakit Populer
    </h4>
    <div id="penyakitContainer">
        <table class="info-panel">
            <tr>
                <td colspan="2" style="text-align:center;color:rgba(255,255,255,0.3);">
                    <i class="fas fa-spinner fa-spin"></i> Memuat data...
                </td>
            </tr>
        </table>
    </div>
</div>

    </section>

    </main>

    <!-- =======================================================
    BOTTOM BAR
    ======================================================= -->

    <footer
class="bottom-bar"
id="footerBar">

    <div class="running-icon">
        <i class="fas fa-bullhorn"></i>
    </div>

    <div class="running-wrapper">

        <div id="runningText"
            class="running-text">
            Loading...
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
<script src="assets/js/map-parallax.js"></script>
<script src="assets/js/network.js"></script>
<script src="assets/js/map.js"></script>
<script src="assets/js/app_v2.js"></script>
<script src="assets/js/responsive.js"></script>
<script src="assets/js/map-cursor-follow.js"></script>

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

        if (typeof Orbit !== "undefined") {
            Orbit.init();
        }

        if (typeof MapEngine !== "undefined") {
            MapEngine.init();
        }

    });

    </script>


    </body>

    </html>