<?php
/**
 * ==========================================================
 * PORTAL TERPADU
 * DINAS KESEHATAN KABUPATEN SUKOHARJO
 * INDEX V3
 * ==========================================================
 */

date_default_timezone_set("Asia/Jakarta");

$build = "4.0.0";
?>
<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0">

<title>

Portal Terpadu DKK Kabupaten Sukoharjo

</title>

<meta
    name="theme-color"
    content="#081726">

<link
    rel="icon"
    href="assets/img/favicon.png">

<link
    rel="stylesheet"
    href="assets/css/style_v4.css">

<link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

</head>

<body>

<div id="portal">

<!-- ==========================================================
     HEADER
=========================================================== -->

<header id="portalHeader">

    <!-- ================= LEFT ================= -->

    <div class="header-left">

        <img

            src="assets/img/logo.png"

            alt="Logo DKK"

            class="logo float">

    </div>


    <!-- ================= CENTER ================= -->

    <div class="header-center">

        <h1>

            PORTAL TERPADU

        </h1>

        <h2>

            DINAS KESEHATAN
            KABUPATEN SUKOHARJO

        </h2>

    </div>


    <!-- ================= RIGHT ================= -->

    <div class="header-right">

    <div id="digitalClock">

        00:00:00

    </div>

    <div id="digitalDate">

        -

    </div>

    <div id="networkStatus">

        <i class="fa-solid fa-circle"></i>

        ONLINE

    </div>

</div>
</header>



<!-- ==========================================================
     MAIN
=========================================================== -->

<main id="portalContent">




<!-- ==========================================================
     LEFT PANEL
=========================================================== -->

<aside id="leftPanel">



<!-- ==========================================================
     CLOCK CARD
=========================================================== -->

<div class="card fade">

<div class="card-header">

<h3>

<i class="fa-solid fa-clock"></i>

Jam Digital

</h3>

</div>

<div class="card-body">

<div class="clock-box">

    <div
        class="clock-time"
        id="clockTime">

        00:00:00

    </div>

    <div
        class="clock-date"
        id="clockDate">

        -

    </div>

</div>
</div>

<div id="networkStatus">

    <i class="fa-solid fa-circle"></i>

    ONLINE

</div>

</div>




<!-- ==========================================================
     WEATHER
=========================================================== -->

<div class="card fade">

<div class="card-header">

<h3>

<i class="fa-solid fa-cloud-sun"></i>

Cuaca

</h3>

</div>

<div class="card-body">

<div class="weather-box">

<div class="weather-icon">

<i class="fa-solid fa-cloud-sun"></i>

</div>

<div class="weather-info">

<div
class="weather-temp"
id="weatherTemp">

--

</div>

<div
class="weather-city"
id="weatherCity">

Sukoharjo

</div>

</div>

</div>

</div>

</div>




<!-- ==========================================================
     STATISTIK
=========================================================== -->

<div class="card fade">

<div class="card-header">

<h3>

<i class="fa-solid fa-chart-column"></i>

Statistik

</h3>

</div>

<div class="card-body">

<div class="stat-grid">

<div class="stat-box">

<div
class="stat-number"
id="totalPenduduk">

0

</div>

<div class="stat-label">

Penduduk

</div>

</div>


<div class="stat-box">

<div
class="stat-number"
id="totalPuskesmas">

0

</div>

<div class="stat-label">

Puskesmas

</div>

</div>


<div class="stat-box">

<div
class="stat-number"
id="totalPosyandu">

0

</div>

<div class="stat-label">

Posyandu

</div>

</div>


<div class="stat-box">

<div
class="stat-number"
id="totalDesa">

0

</div>

<div class="stat-label">

Desa

</div>

</div>

</div>

</div>

</div>

<!-- ==========================================================
     AGENDA HARI INI
=========================================================== -->

<div class="card fade">

    <div class="card-header">

        <h3>

            <i class="fa-solid fa-calendar-days"></i>

            Agenda Hari Ini

        </h3>

    </div>

    <div class="card-body">

        <div
            class="agenda-list"
            id="agendaList">

            <div class="agenda-item">

                <div class="agenda-time">

                    --

                </div>

                <div class="agenda-content">

                    <div class="agenda-title">

                        Belum ada agenda

                    </div>

                    <div class="agenda-location">

                        Dinas Kesehatan Kabupaten Sukoharjo

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>



<!-- ==========================================================
     RUNNING TEXT
=========================================================== -->

<div class="card fade">

    <div class="card-header">

        <h3>

            <i class="fa-solid fa-bullhorn"></i>

            Informasi

        </h3>

    </div>

    <div class="card-body">

        <div class="running-wrapper">

            <div
                class="running-text"
                id="runningText">

                Selamat datang di Portal Terpadu Dinas Kesehatan Kabupaten Sukoharjo.

            </div>

        </div>

    </div>

</div>



<!-- ==========================================================
     QUICK MENU
=========================================================== -->

<div class="card fade">

    <div class="card-header">

        <h3>

            <i class="fa-solid fa-table-cells-large"></i>

            Menu Cepat

        </h3>

    </div>

    <div class="card-body">

        <table class="table-mini">

            <tr>

                <td>

                    <i class="fa-solid fa-envelope"></i>

                    Surat Masuk

                </td>

                <td class="text-right">

                    <span
                        class="badge"
                        id="suratMasuk">

                        0

                    </span>

                </td>

            </tr>

            <tr>

                <td>

                    <i class="fa-solid fa-paper-plane"></i>

                    Surat Keluar

                </td>

                <td class="text-right">

                    <span
                        class="badge"
                        id="suratKeluar">

                        0

                    </span>

                </td>

            </tr>

            <tr>

                <td>

                    <i class="fa-solid fa-users"></i>

                    Pegawai

                </td>

                <td class="text-right">

                    <span
                        class="badge"
                        id="jumlahPegawai">

                        0

                    </span>

                </td>

            </tr>

            <tr>

                <td>

                    <i class="fa-solid fa-hospital"></i>

                    Puskesmas

                </td>

                <td class="text-right">

                    <span
                        class="badge"
                        id="jumlahFaskes">

                        0

                    </span>

                </td>

            </tr>

        </table>

    </div>

</div>



<!-- ==========================================================
     STATUS SERVER
=========================================================== -->

<div class="card fade">

    <div class="card-header">

        <h3>

            <i class="fa-solid fa-server"></i>

            Status Sistem

        </h3>

    </div>

    <div class="card-body">

        <table class="table-mini">

            <tr>

                <td>

                    Database

                </td>

                <td class="text-right">

                    <span
                        id="dbStatus"
                        class="status-online">

                        ● Online

                    </span>

                </td>

            </tr>

            <tr>

                <td>

                    Portal

                </td>

                <td class="text-right">

                    <span
                        id="portalStatus"
                        class="status-online">

                        ● Aktif

                    </span>

                </td>

            </tr>

            <tr>

                <td>

                    Build

                </td>

                <td class="text-right">

                    <?= $build ?>

                </td>

            </tr>

            <tr>

                <td>

                    Last Update

                </td>

                <td
                    class="text-right"
                    id="lastUpdate">

                    -

                </td>

            </tr>

        </table>

    </div>

</div>



</aside>
<!-- ================= END LEFT PANEL ================= -->

<!-- ==========================================================
     CENTER PANEL
=========================================================== -->

<section id="centerPanel">

    <div class="map-panel">


    <div class="map-control">


        <button id="btnZoomIn">
            <i class="fa-solid fa-plus"></i>
        </button>


        <button id="btnZoomOut">
            <i class="fa-solid fa-minus"></i>
        </button>


        <button id="btnResetMap">
            <i class="fa-solid fa-expand"></i>
        </button>


    </div>

    <div id="markerLayer"></div>

<object
    id="svgMap"
    type="image/svg+xml"
    data="assets/svg/sukoharjo_interactive.svg">
</object>

    <object 
        id="svgMap"
        type="image/svg+xml"
        data="assets/svg/sukoharjo_interactive.svg">
    </object>


</div>



    

        <!-- ==========================================
             GRID GIS
        =========================================== -->

        <div class="map-grid"></div>

        <!-- ==========================================
             RADAR
        =========================================== -->

        <div class="radar-ring"></div>

        <div class="radar-ring"></div>

        <div class="radar-ring"></div>


        <!-- ==========================================
             ORBIT
        =========================================== -->

        <div class="orbit small"></div>

        <div class="orbit"></div>

        <div class="orbit large"></div>


        <!-- ==========================================
             GLOW
        =========================================== -->

        <div class="map-glow"></div>

        <div id="mapOverlay"></div>


        <!-- ==========================================
             LOADING
        =========================================== -->

        <div

            class="loading hidden"

            id="mapLoading">

            <i class="fa-solid fa-spinner spin"></i>

        </div>


        <!-- ==========================================
             TOOLTIP
        =========================================== -->

        <div
            id="gisTooltip"
            class="gis-tooltip">

        </div>


        <!-- ==========================================
             MAP FRAME
        =========================================== -->

        <div class="map-frame">

    <div id="mapLayer"></div>

</div>


        <!-- ==========================================
             MAP STATUS
        =========================================== -->

        <div class="map-status">

            <div>

                <i class="fa-solid fa-location-crosshairs"></i>

                Kabupaten Sukoharjo

            </div>

            <div id="mapScale">

                Scale 100%

            </div>

        </div>

        

        <!-- ==========================================
             MAP LEGEND
        =========================================== -->

        <div class="map-legend">

            <div class="legend-item">

                <span class="legend-color"
                      style="background:#00d4ff">

                </span>

                Kecamatan

            </div>

            <div class="legend-item">

                <span class="legend-color"
                      style="background:#00d27a">

                </span>

                Puskesmas

            </div>

            <div class="legend-item">

                <span class="legend-color"
                      style="background:#ffb300">

                </span>

                Rumah Sakit

            </div>

        </div>


        <!-- ==========================================
             MAP CONTROL
        =========================================== -->

        <div class="map-control">

            <button
                id="btnZoomIn">

                <i class="fa-solid fa-plus"></i>

            </button>

            <button
                id="btnZoomOut">

                <i class="fa-solid fa-minus"></i>

            </button>

            <button
                id="btnResetMap">

                <i class="fa-solid fa-expand"></i>

            </button>

        </div>

    </div>

</section>

<!-- ==========================================================
     RIGHT PANEL
=========================================================== -->

<aside id="rightPanel">

    <!-- ======================================================
         PETA INTERAKTIF
    ======================================================= -->

    <div class="card fade">

        <div class="card-header">

            <h3>

                <i class="fa-solid fa-map-location-dot"></i>

                Peta Interaktif

            </h3>

        </div>

        <div class="card-body">

            <div class="interactive-frame">

                <object

                    id="svgInteractive"

                    data="assets/svg/sukoharjo_interactive.svg"

                    type="image/svg+xml">

                </object>

            </div>

        </div>

    </div>


    <!-- ======================================================
         INFORMASI KECAMATAN
    ======================================================= -->

    <div class="card fade">

        <div class="card-header">

            <h3>

                <i class="fa-solid fa-circle-info"></i>

                Informasi Kecamatan

            </h3>

        </div>

       <div class="card-body">

    <!-- Nama Kecamatan -->

    <h2
        id="namaKecamatan"
        class="text-center">

        Pilih Kecamatan

    </h2>


    <!-- Ringkasan Penduduk -->

    <div class="info-highlight">

        <h2 id="districtPopulation">

            -

        </h2>

        <p>

            Jumlah Penduduk

        </p>

    </div>


    <!-- Statistik Cepat -->

    <div class="mini-stat">

        <div class="mini-stat-item">

            <h3 id="miniPuskesmas">

                -

            </h3>

            <span>

                Puskesmas

            </span>

        </div>

        <div class="mini-stat-item">

            <h3 id="miniDesa">

                -

            </h3>

            <span>

                Desa

            </span>

        </div>

        <div class="mini-stat-item">

            <h3 id="miniPosyandu">

                -

            </h3>

            <span>

                Posyandu

            </span>

        </div>

        <div class="mini-stat-item">

            <h3 id="miniKepadatan">

                -

            </h3>

            <span>

                Kepadatan

            </span>

        </div>

    </div>


    <!-- Detail Kecamatan -->

    <table class="info-table">

        <tr>

            <td>Luas Wilayah</td>

            <td id="luasWilayah">-</td>

        </tr>

        <tr>

            <td>Jumlah Desa</td>

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

        <tr>

            <td>Rumah Sakit</td>

            <td id="jumlahRS">-</td>

        </tr>

    </table>

</div>
            
        </div>

    </div>


    <!-- ======================================================
         GRAFIK
    ======================================================= -->

    <div class="card fade">

        <div class="card-header">

            <h3>

                <i class="fa-solid fa-chart-pie"></i>

                Statistik Wilayah

            </h3>

        </div>

        <div class="card-body">

            <div class="chart-wrapper">

            <canvas id="districtChart"></canvas>

            </div>

        </div>

    </div>


    <!-- ======================================================
         STATUS
    ======================================================= -->

    <div class="card fade">

        <div class="card-header">

            <h3>

                <i class="fa-solid fa-heart-pulse"></i>

                Status Portal

            </h3>

        </div>

        <div class="card-body">

            <table class="table-mini">

                <tr>

                    <td>Portal</td>

                    <td class="text-right">

                        <span class="status-online">

                            ● Online

                        </span>

                    </td>

                </tr>

                <tr>

                    <td>Peta GIS</td>

                    <td class="text-right">

                        <span
                            id="gisStatus"
                            class="status-online">

                            Aktif

                        </span>

                    </td>

                </tr>

                <tr>

                    <td>Refresh</td>

                    <td
                        class="text-right"
                        id="refreshInfo">

                        -

                    </td>

                </tr>

            </table>

        </div>

    </div>

</aside>

<!-- ==========================================================
     END RIGHT PANEL
=========================================================== -->


</main>


<!-- ==========================================================
     FOOTER
=========================================================== -->

<footer id="portalFooter">

<div>

© <?=date('Y')?> Dinas Kesehatan Kabupaten Sukoharjo

</div>

<div>

Portal DKK
v<?=$build?>

<span id="fpsInfo"></span>

</div>

</footer>


<!-- ==========================================================
     LOADER
=========================================================== -->

<div

    class="loading hidden"

    id="portalLoading">

    <i class="fa-solid fa-spinner spin"></i>

</div>


<!-- ==========================================================
     SCRIPT
=========================================================== -->

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script src="assets/js/utils.js"></script>

<script src="assets/js/clock.js"></script>

<script src="assets/js/interactive.js"></script>

<script src="assets/js/map.js"></script>

<script src="assets/js/marker.js"></script>

<script src="assets/js/app_v4.js"></script>

</body>

</html>