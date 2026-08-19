<?php
/**
 * ==========================================================
 * PORTAL DKK
 * MAP BUILDER v1.0
 * ==========================================================
 */

define('ROOT_PATH', dirname(__DIR__, 2));

$svgFile = ROOT_PATH . '/assets/svg/sukoharjo.svg';

if (!file_exists($svgFile)) {
    die("File assets/svg/sukoharjo.svg tidak ditemukan.");
}

$svg = file_get_contents($svgFile);
?>
<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="utf-8">

<meta name="viewport"
      content="width=device-width,initial-scale=1">

<title>Portal DKK - Map Builder</title>

<link rel="stylesheet"
      href="builder.css">

<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

</head>

<body>

<div class="wrapper">

    <!-- =======================================================
         HEADER
    ======================================================== -->

    <header>

        <div>

            <h1>

                <i class="fa-solid fa-map-location-dot"></i>

                MAP BUILDER

            </h1>

            <span>

                Portal DKK v2

            </span>

        </div>

    </header>


    <!-- =======================================================
         CONTENT
    ======================================================== -->

    <main>

        <!-- ===========================================
             SVG
        ============================================ -->

        <section class="map-panel">

            <div class="panel-title">

                <i class="fa-solid fa-map"></i>

                Sukoharjo.svg

            </div>

            <div id="svgContainer">

                <?= $svg ?>

            </div>

        </section>


        <!-- ===========================================
             PROPERTY PANEL
        ============================================ -->

        <aside class="property-panel">

            <div class="panel-title">

                <i class="fa-solid fa-sliders"></i>

                Property

            </div>

            <table>

                <tr>

                    <td width="120">

                        Path

                    </td>

                    <td>

                        <span id="pathIndex">

                            -

                        </span>

                    </td>

                </tr>

                <tr>

    <td>

        Kecamatan

    </td>

    <td>

        <select id="districtSelect">

            <option value="">-- Pilih Kecamatan --</option>

        </select>

    </td>

</tr>

<tr>

    <td>

        ID

    </td>

    <td>

        <input
            id="districtId"
            type="text"
            readonly>

    </td>

</tr>

<tr>

    <td>

        Nama

    </td>

    <td>

        <input
            id="districtName"
            type="text"
            readonly>

    </td>

</tr>

            </table>


            <div class="button-group">

                <button id="btnReset">

                    <i class="fa-solid fa-expand"></i>

                    Reset View

                    </button>

                <button id="btnSave">

                    <i class="fa-solid fa-floppy-disk"></i>

                    Simpan

                </button>

                <button id="btnExport">

                    <i class="fa-solid fa-file-export"></i>

                    Export SVG

                </button>

            </div>

            <hr>

            <h3>

                Kecamatan

            </h3>

            <div id="districtList">

                Belum ada data.

            </div>

        </aside>

    </main>

</div>


<!-- ==============================================
     STATUS BAR
================================================ -->

<footer>

    <span>

        Total Path :

        <strong id="totalPath">

            0

        </strong>

    </span>

    <span>

        Dipilih :

        <strong id="selectedPath">

            -

        </strong>

    </span>

    <span id="saveStatus">Belum disimpan</span>

</footer>


<script src="districts.js"></script>

<script src="builder.js"></script>

<script src="viewport.js"></script>



</body>

</html>