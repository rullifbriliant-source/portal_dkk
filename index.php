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

    <!-- Font Poppins self-hosted via assets/css/style_v2.css (@font-face).
         Link Google Fonts dihapus agar tab tidak tertahan font remote. -->

    <link rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">

    <link rel="stylesheet"
    href="assets/css/style_v2.css">

    <link rel="stylesheet"
    href="assets/css/spm.css">

    <link rel="preload"
    href="assets/img/background.jpg"
    as="image">

    </head>

    <body class="loading">

    <!-- =======================================================
    BACKGROUND
    ======================================================= -->

    <div class="video-container">

    <!-- EARTH BACKGROUND (dekoratif, di belakang seluruh UI & peta) -->
    <div class="earth-bg" aria-hidden="true"></div>

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
        </div>

    </div>


    <!-- KANAN: PANEL TANGGAL/JAM | CUACA -->
    <div class="header-datetime" aria-label="Informasi waktu dan cuaca">
        <div class="hd-left">
            <i class="fas fa-calendar-alt hd-cal" aria-hidden="true"></i>
            <div class="hd-text">
                <span id="tanggalHeader">Memuat...</span>
                <span class="hd-time"><span id="clockHeader">00:00:00</span> WIB</span>
            </div>
        </div>
        <span class="hd-sep" aria-hidden="true"></span>
        <div class="hd-right">
            <i class="fas fa-cloud-sun hd-wicon" aria-hidden="true"></i>
            <div class="hd-text">
                <span id="weatherHeader">-</span>
                <span class="hd-cond" id="weatherCond">-</span>
            </div>
        </div>
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

                <div
                    id="appFasyankes"
                    class="feature-fasyankes">

                    <i class="fas fa-hospital fa-2x"></i>

                    <br><br>

                    Fasyankes

                </div>

                <div
                    id="appSdm"
                    class="feature-sdm"
                    title="Lihat detail SDM per Fasyankes"
                    role="button"
                    tabindex="0">

                    <i class="fas fa-users fa-2x"></i>

                    <br><br>

                    SDM

                </div>

                <div
                    id="appDashboard"
                    onclick="window.location.href='admin/login.php'"
                    title="Login Admin"
                    role="link"
                    tabindex="0"
                    onkeydown="if(event.key==='Enter'){window.location.href='admin/login.php'}">

                    <i class="fas fa-chart-line fa-2x"></i>

                    <br><br>

                    Dashboard

                </div>

                <a class="gis-link"
                    href="https://pisda.sukoharjokab.go.id/catalogue/#/all?filter%7Bowner.pk.in%7D=1005"
                    target="_blank"
                    rel="noopener noreferrer"
                    title="Buka Katalog GIS PISDA Sukoharjo (tab baru)">
                <div>

                    <i class="fas fa-map-location-dot fa-2x"></i>

                    <br><br>

                    GIS

                </div>
                </a>

            </div>

            <div class="stats-row" style="grid-template-columns:repeat(2,1fr);">

                <div class="stat-box" id="statBoxPenduduk" role="button" tabindex="0" title="Lihat detail penduduk" style="cursor:pointer;">
                    <i class="fas fa-users"></i>
                    <h4 id="statPenduduk">0</h4>
                    <p>Penduduk</p>
                </div>

                <div class="stat-box" id="statBoxSpm" role="button" tabindex="0" title="Lihat data Standar Pelayanan Minimal (SPM)">
                    <i class="fas fa-chart-pie"></i>
                    <p>SPM</p>
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
     ORBIT MENU (PENYAKIT TERBANYAK) — DINAMIS dari Top 6 (10 Penyakit Populer)
    =================================================== -->
    <div id="orbitMenu">
        <!-- Akan diisi dinamis via PortalAPI.renderOrbit() dari api/get_penyakit_populer.php (Top 6) -->
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

                    <a href="#" data-spm-open title="Lihat data Standar Pelayanan Minimal">

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
    MODAL FASYANKES PER KECAMATAN
    ======================================================= -->

    <div
        class="apps-modal"
        id="fasyankesModal">

        <div class="apps-window faskes-window">

            <div class="apps-header">

                <h3>
                    <i class="fas fa-hospital" style="color:#00d4ff;margin-right:10px;"></i>
                    <span id="fasyankesModalTitle">Fasyankes</span>
                    <small class="faskes-period">Data Fasyankes 2026</small>
                </h3>

                <button
                    id="closeFasyankes"
                    class="faskes-close">

                    &times;

                </button>

            </div>

            <div class="faskes-body">

                <!-- VIEW 1: daftar Fasyankes 2026 (existing) -->
                <div class="faskes-view faskes-view-list" id="fasyankesViewList">
                    <div
                        class="faskes-filters"
                        id="fasyankesFilters">

                    </div>

                    <div
                        class="faskes-list"
                        id="fasyankesList">

                        <div class="faskes-empty">
                            <i class="fas fa-map-marked-alt"></i>
                            <p>Pilih kecamatan pada peta terlebih dahulu.</p>
                        </div>

                    </div>

                    <div class="faskes-foot">
                        <a href="#" id="fasyankesRekapLink">
                            <i class="fas fa-table"></i> Detail Rekap 2021–2025
                        </a>
                    </div>
                </div>

                <!-- VIEW 2: rekap kabupaten 2021–2025 -->
                <div class="faskes-view faskes-view-rekap" id="fasyankesViewRekap" hidden>
                    <div class="rekap-head">
                        <div>
                            <h4>Rekap Sarana Pelayanan Kesehatan</h4>
                            <p>Kabupaten Sukoharjo, 2021–2025</p>
                        </div>
                        <button type="button" class="rekap-back" id="fasyankesRekapBack">
                            <i class="fas fa-arrow-left"></i> Kembali ke Fasyankes 2026
                        </button>
                    </div>
                    <div class="rekap-scroll">
                        <div id="faskesRekapContent">
                            <div class="faskes-empty">
                                <i class="fas fa-table"></i>
                                <p>Memuat data rekap...</p>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

        </div>

    </div>

    <!-- =======================================================
    MODAL SDM PER FASYANKES
    ======================================================= -->

    <div class="apps-modal" id="sdmModal">
        <div class="apps-window faskes-window" style="max-width:720px;">
            <div class="apps-header">
                <h3>
                    <i class="fas fa-user-doctor" style="color:#00d4ff;margin-right:10px;"></i>
                    <span id="sdmModalTitle">SDM Kesehatan</span>
                </h3>
                <button id="closeSdm" class="faskes-close">&times;</button>
            </div>
            <div class="faskes-body">
                <div id="sdmModalSummary" style="padding:12px 16px;margin-bottom:12px;background:rgba(0,212,255,0.08);border:1px solid rgba(0,212,255,0.15);border-radius:12px;display:flex;gap:16px;flex-wrap:wrap;"></div>
                <div class="faskes-filters" id="sdmFilters"></div>
                <div class="faskes-list" id="sdmList">
                    <div class="faskes-empty"><i class="fas fa-users"></i><p>Pilih kecamatan pada peta terlebih dahulu.</p></div>
                </div>
            </div>
        </div>
    </div>

    <!-- =======================================================
    MODAL DATA PENDUDUK
    ======================================================= -->

    <div class="apps-modal" id="pendudukModal">
        <div class="apps-window faskes-window" style="max-width:640px;">
            <div class="apps-header">
                <h3>
                    <i class="fas fa-users" style="color:#00d4ff;margin-right:10px;"></i>
                    <span>Data Penduduk</span>
                </h3>
                <button id="closePenduduk" class="faskes-close">&times;</button>
            </div>
            <div class="faskes-body">
                <p style="font-size:12px;color:rgba(255,255,255,0.5);margin-bottom:12px;">Statistik Penduduk Kabupaten Sukoharjo</p>
                <div id="pendudukModalSummary" style="padding:12px 16px;margin-bottom:12px;background:rgba(0,212,255,0.08);border:1px solid rgba(0,212,255,0.15);border-radius:12px;display:flex;gap:16px;flex-wrap:wrap;"></div>
                <div class="faskes-list" id="pendudukList">
                    <div class="faskes-empty"><i class="fas fa-spinner fa-spin"></i><p>Memuat data penduduk...</p></div>
                </div>
            </div>
        </div>
    </div>

    <!-- =======================================================
    MODAL SPM (STANDAR PELAYANAN MINIMAL) — PUBLIC READ-ONLY
    ======================================================= -->

    <div
        class="apps-modal"
        id="spmModal">

        <div class="spm-window" role="dialog" aria-modal="true" aria-label="Standar Pelayanan Minimal">

            <div class="spm-header">

                <h3>
                    <i class="fas fa-chart-pie"></i>
                    Standar Pelayanan Minimal (SPM)
                    <small>Versi digital rekapitulasi target SPM — Dinas Kesehatan Kab. Sukoharjo</small>
                </h3>

                <button
                    id="closeSpm"
                    class="spm-close"
                    aria-label="Tutup">

                    &times;

                </button>

            </div>

            <div class="spm-tabs" id="spmTabs"></div>

            <div class="spm-body" id="spmBody">
                <div class="spm-loading">
                    <i class="fas fa-spinner fa-spin"></i>Memuat data SPM...
                </div>
            </div>

            <div class="spm-footer">
                <span id="spmFootNote">Sumber: Database portal_dkk</span>
                <span>TOTAL = jumlah 12 kecamatan, kecuali baris dengan total manual sesuai spreadsheet.</span>
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
<script src="assets/js/spm.js"></script>
<script src="assets/js/responsive.js"></script>
<script src="assets/js/map-cursor-follow.js"></script>

    <!-- =======================================================
    STARTUP
    ======================================================= -->
    <script>

    // Loading screen: selesai ketika Portal cukup siap ditampilkan,
    // BUKAN sekadar DOM selesai dan BUKAN timer palsu. Sinyal kesiapan
    // dibaca dari DOM: nama kecamatan + angka Penduduk sudah berisi
    // nilai render pertama (jalur sukses maupun offline-fallback),
    // artinya Startup/Dashboard + API pertama sudah settled dan UI
    // utama tidak lagi blank. Tanpa fixed delay: finish langsung
    // saat sinyal terpenuhi. Timeout 4 dtk hanya langit-langit
    // pengaman bila backend gagal total (bukan penahan rutin).
    // Tidak menunggu window.load / SVG / font / API remote.
    (function () {

        var bar = document.getElementById("loadingProgress");
        var screen = document.getElementById("loadingScreen");

        if (!bar || !screen) return;

        var p = 0;
        var done = false;

        function finish() {

            if (done) return;
            done = true;

            clearInterval(timer);
            clearInterval(poll);

            bar.style.width = "100%";

            screen.style.opacity = "0";

            setTimeout(function() {

                screen.style.display = "none";

                document.body.classList.remove("loading");
                document.body.classList.add("portal-ready");

            }, 300);

        }

        function textReady(id, badVals) {
            var el = document.getElementById(id);
            if (!el) return false;
            var t = (el.textContent || "").trim();
            if (t === "") return false;
            for (var i = 0; i < badVals.length; i++) {
                if (t === badVals[i]) return false;
            }
            return true;
        }

        function portalReady() {
            // DOM harus selesai dulu.
            if (document.readyState === "loading") return false;
            // Data Dasar sudah dirender (nilai sukses atau fallback offline).
            if (!textReady("namaKecamatan", ["Memuat...", "-"])) return false;
            // Card Penduduk sudah berisi agregat resmi P1 (bukan placeholder 0).
            if (!textReady("statPenduduk", ["0"])) return false;
            return true;
        }

        // Progres visual menuju 90% selama menunggu sinyal; finish
        // yang menuntaskan 100% tepat saat Portal siap.
        var timer = setInterval(function() {

            p += Math.max(1, (90 - p) / 6);

            if (p >= 90) {
                p = 90;
            }

            bar.style.width = p + "%";

        }, 30);

        var poll = setInterval(function() {
            if (portalReady()) finish();
        }, 100);

        setTimeout(finish, 4000);

    })();

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