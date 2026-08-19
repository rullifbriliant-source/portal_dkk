<div id="portal">

<header class="hero">

<h1>PORTAL TERPADU</h1>

<h2>DINAS KESEHATAN KABUPATEN SUKOHARJO</h2>

</header>

<?php require ROOT_PATH."/app/Views/components/orbit.php"; ?>

<section class="map-section">

<div id="mapContainer">

    <div class="map-toolbar">

        <button id="zoomIn">+</button>

        <button id="zoomOut">−</button>

        <button id="zoomReset">

            <i class="fa fa-home"></i>

        </button>

    </div>

    <?php

    $svg = ROOT_PATH."/assets/svg/sukoharjo_interactive.svg";

    if(file_exists($svg)){

        include $svg;

    }else{

        echo "<div class='map-warning'>";
        echo "Interactive SVG belum dibuat.";
        echo "</div>";

    }

    ?>

</div>

<aside id="districtInfo">

<h2>Kabupaten Sukoharjo</h2>

<p>

Klik salah satu kecamatan

</p>

<div id="districtContent">

Belum ada data.

</div>

</aside>

</section>

<div class="launcher">

<button id="btnApps">

SEMUA APLIKASI

</button>

</div>

</div>