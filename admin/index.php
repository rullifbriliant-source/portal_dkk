<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ============================================================
// INCLUDE CONFIG & AUTH
// ============================================================

require_once __DIR__ . '/config.php';
requireLogin();

require_once __DIR__ . '/../config/database.php';

// ============================================================
// DATA ADMIN
// ============================================================

$username = $_SESSION['admin_username'] ?? 'Admin';

// ============================================================
// AMBIL DATA FASYANKES ITEMS (dinamis)
// ============================================================

$itemsFasyankes = [];
$checkTableItems = mysqli_query($config, "SHOW TABLES LIKE 'tbl_fasyankes_items'");
if (mysqli_num_rows($checkTableItems) > 0) {
    $queryItems = mysqli_query($config, "SELECT nama_item, nilai FROM tbl_fasyankes_items WHERE aktif='Y' ORDER BY urutan");
    while ($row = mysqli_fetch_assoc($queryItems)) {
        $itemsFasyankes[] = $row;
    }
}

// Jika belum ada data, gunakan default
if (empty($itemsFasyankes)) {
    $itemsFasyankes = [
        ['nama_item' => 'Puskesmas', 'nilai' => 12],
        ['nama_item' => 'Pustu', 'nilai' => 24],
        ['nama_item' => 'Klinik', 'nilai' => 18],
        ['nama_item' => 'Rumah Sakit', 'nilai' => 8]
    ];
}

// ============================================================
// AMBIL DATA PENYAKIT (dengan error handling)
// ============================================================

$penyakitList = [];
$checkTablePenyakit = mysqli_query($config, "SHOW TABLES LIKE 'tbl_penyakit'");
if (mysqli_num_rows($checkTablePenyakit) > 0) {
    $queryPenyakit = mysqli_query($config, "SELECT nama_penyakit, total_kasus FROM tbl_penyakit ORDER BY total_kasus DESC LIMIT 5");
    while ($row = mysqli_fetch_assoc($queryPenyakit)) {
        $penyakitList[] = $row;
    }
}

// ============================================================
// AMBIL DATA SDM ITEMS
// ============================================================

$itemsSdm = [];
$checkTableSdm = mysqli_query($config, "SHOW TABLES LIKE 'tbl_sdm_items'");
if (mysqli_num_rows($checkTableSdm) > 0) {
    $querySdm = mysqli_query($config, "SELECT nama_item, nilai FROM tbl_sdm_items WHERE aktif='Y' ORDER BY urutan");
    while ($row = mysqli_fetch_assoc($querySdm)) {
        $itemsSdm[] = $row;
    }
}
$sdmCount = count($itemsSdm);

// Jika belum ada data, default
if (empty($itemsSdm)) {
    $itemsSdm = [
        ['nama_item' => 'Dokter', 'nilai' => 85],
        ['nama_item' => 'Perawat', 'nilai' => 320],
        ['nama_item' => 'Bidan', 'nilai' => 210],
        ['nama_item' => 'Nakes Lainnya', 'nilai' => 145]
    ];
    $sdmCount = count($itemsSdm);
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Portal DKK Sukoharjo</title>

    <!-- Google Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">

    <style>
        /* ==========================================================
           DASHBOARD ADMIN - STYLE
        ========================================================== */

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: radial-gradient(circle at top right, rgba(0, 212, 255, 0.08), transparent 35%), #051321;
            min-height: 100vh;
            color: #fff;
        }

        .admin-dashboard {
            max-width: 1400px;
            margin: 0 auto;
            padding: 28px 35px;
            min-height: 100vh;
        }

        /* ==========================================================
           HEADER
        ========================================================== */

        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 18px 24px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 18px;
            margin-bottom: 30px;
            backdrop-filter: blur(18px);
        }

        .admin-header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 700;
            color: #fff;
        }

        .admin-header h1 span {
            color: #00d4ff;
        }

        .admin-subtitle {
            margin-top: 4px;
            font-size: 13px;
            color: rgba(255, 255, 255, 0.45);
        }

        .admin-user {
            display: flex;
            align-items: center;
            gap: 15px;
            color: #87e3ff;
            font-size: 14px;
        }

        .admin-user i {
            color: #00d4ff;
        }

        .btn-logout {
            padding: 9px 18px;
            border-radius: 9px;
            border: 1px solid rgba(255, 70, 70, 0.3);
            background: rgba(255, 70, 70, 0.1);
            color: #ff6b6b;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            transition: .3s;
        }

        .btn-logout:hover {
            background: rgba(255, 70, 70, 0.2);
            transform: translateY(-2px);
        }

        /* ==========================================================
           WELCOME
        ========================================================== */

        .welcome-box {
            margin-bottom: 25px;
        }

        .welcome-box h2 {
            font-size: 25px;
            color: #fff;
            margin-bottom: 5px;
        }

        .welcome-box p {
            color: rgba(255, 255, 255, 0.5);
            font-size: 14px;
        }

        /* ==========================================================
           CARD GRID
        ========================================================== */

        .card-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 22px;
        }

        /* ==========================================================
           CARD EDITOR
        ========================================================== */

        .card-editor {
            position: relative;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 20px;
            padding: 25px;
            backdrop-filter: blur(18px);
            transition: .3s;
        }

        .card-editor:hover {
            transform: translateY(-4px);
            background: rgba(255, 255, 255, 0.07);
            border-color: rgba(0, 212, 255, 0.25);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.25);
        }

        .card-title {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .card-title-left {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .card-title-left i {
            width: 42px;
            height: 42px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            background: rgba(0, 212, 255, 0.1);
            color: #00d4ff;
            font-size: 18px;
        }

        .card-title h3 {
            margin: 0;
            font-size: 17px;
            color: #fff;
        }

        .card-title p {
            margin: 3px 0 0;
            font-size: 11px;
            color: rgba(255, 255, 255, 0.4);
        }

        /* BADGE */
        .badge {
            padding: 5px 12px;
            border-radius: 20px;
            background: rgba(0, 212, 255, 0.12);
            color: #00d4ff;
            font-size: 10px;
            font-weight: 600;
            white-space: nowrap;
        }

        .badge-warning {
            background: rgba(255, 193, 7, 0.15);
            color: #ffc107;
        }

        /* TABLE */
        .card-editor table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .card-editor table td {
            padding: 9px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.06);
            font-size: 13px;
        }

        .card-editor table td:first-child {
            color: rgba(255, 255, 255, 0.55);
        }

        .card-editor table td:last-child {
            text-align: right;
            color: #fff;
            font-weight: 600;
        }

        /* BUTTON EDIT */
        .btn-edit {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            padding: 10px 15px;
            border-radius: 10px;
            border: 1px solid rgba(0, 212, 255, 0.2);
            background: rgba(0, 212, 255, 0.1);
            color: #00d4ff;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            transition: .3s;
        }

        .btn-edit:hover {
            background: rgba(0, 212, 255, 0.2);
            border-color: rgba(0, 212, 255, 0.4);
            transform: translateY(-2px);
        }

        .btn-disabled {
            opacity: .45;
            cursor: not-allowed;
            pointer-events: none;
        }

        /* FOOTER */
        .admin-footer {
            margin-top: 30px;
            text-align: center;
            color: rgba(255, 255, 255, 0.25);
            font-size: 12px;
        }

        /* ==========================================================
           RESPONSIVE
        ========================================================== */

        @media (max-width: 900px) {
            .card-grid {
                grid-template-columns: 1fr;
            }
            .admin-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            .admin-user {
                width: 100%;
                justify-content: space-between;
            }
        }

        @media (max-width: 600px) {
            .admin-dashboard {
                padding: 18px;
            }
            .admin-user {
                flex-direction: column;
                align-items: flex-start;
            }
            .welcome-box h2 {
                font-size: 21px;
            }
        }
    </style>

</head>

<body>
    <div class="admin-dashboard">

        <!-- ======================================================
        HEADER
        ======================================================= -->
        <header class="admin-header">
            <div>
                <h1>Portal <span>DKK Sukoharjo</span></h1>
                <div class="admin-subtitle">Dashboard Administrasi</div>
            </div>
            <div class="admin-user">
                <span><i class="fas fa-user-circle"></i> <?= htmlspecialchars($username) ?></span>
                <a href="logout.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </header>

        <!-- ======================================================
        WELCOME
        ======================================================= -->
        <div class="welcome-box">
            <h2>Dashboard Admin</h2>
            <p>Kelola data yang ditampilkan pada Portal Terpadu Dinas Kesehatan Kabupaten Sukoharjo.</p>
        </div>

        <!-- ======================================================
        CARD GRID
        ======================================================= -->
        <div class="card-grid">

            <!-- ==================================================
            FASYANKES (dinamis dari tbl_fasyankes_items)
            =================================================== -->
            <div class="card-editor">
                <div class="card-title">
                    <div class="card-title-left">
                        <i class="fas fa-hospital"></i>
                        <div>
                            <h3>Fasilitas Kesehatan</h3>
                            <p>Data Fasyankes</p>
                        </div>
                    </div>
                    <span class="badge"><?= count($itemsFasyankes) ?> item</span>
                </div>

                <table>
                    <?php if (count($itemsFasyankes) > 0): ?>
                        <?php foreach ($itemsFasyankes as $item): ?>
                            <tr>
                                <td><?= htmlspecialchars($item['nama_item']) ?></td>
                                <td><?= number_format((int)$item['nilai'], 0, ',', '.') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="2" style="text-align:center;color:rgba(255,255,255,0.3);padding:12px 0;">Belum ada data</td>
                        </tr>
                    <?php endif; ?>
                </table>

                <a href="crud/fasyankes.php" class="btn-edit">
                    <i class="fas fa-pen"></i> Kelola Fasyankes
                </a>
            </div>

           <!-- ==================================================
     SDM (dinamis dari tbl_sdm_items)
================================================== -->
<div class="card-editor">
    <div class="card-title">
        <div class="card-title-left">
            <i class="fas fa-users"></i>
            <div>
                <h3>SDM Kesehatan</h3>
                <p>Data sumber daya manusia</p>
            </div>
        </div>
        <span class="badge"><?= $sdmCount ?? 0 ?> item</span>
    </div>
    <table>
        <?php if (isset($itemsSdm) && count($itemsSdm) > 0): ?>
            <?php foreach ($itemsSdm as $item): ?>
            <tr>
                <td><?= htmlspecialchars($item['nama_item']) ?></td>
                <td><?= number_format((int)$item['nilai']) ?></td>
            </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="2" style="text-align:center;color:rgba(255,255,255,0.3);padding:10px 0;">Belum ada data</td></tr>
        <?php endif; ?>
    </table>
    <a href="crud/sdm.php" class="btn-edit">
        <i class="fas fa-pen"></i> Kelola SDM
    </a>
</div>
            <!-- ==================================================
            PENYAKIT
            =================================================== -->
            <div class="card-editor">
                <div class="card-title">
                    <div class="card-title-left">
                        <i class="fas fa-virus"></i>
                        <div>
                            <h3>Data Penyakit</h3>
                            <p>Data penyakit terbanyak</p>
                        </div>
                    </div>
                    <span class="badge badge-warning">SEGERA</span>
                </div>

                <table>
                    <?php if (count($penyakitList) > 0): ?>
                        <?php foreach ($penyakitList as $p): ?>
                            <tr>
                                <td><?= htmlspecialchars($p['nama_penyakit']) ?></td>
                                <td><?= number_format((int)$p['total_kasus'], 0, ',', '.') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="2" style="text-align:center;color:rgba(255,255,255,0.3);padding:12px 0;">
                                <i class="fas fa-database"></i> Belum ada data penyakit
                            </td>
                        </tr>
                    <?php endif; ?>
                </table>

                <a href="#" class="btn-edit btn-disabled">
                    <i class="fas fa-pen"></i> Edit Data Penyakit
                </a>
            </div>

            <!-- ==================================================
            DATA DASAR
            =================================================== -->
            <div class="card-editor">
                <div class="card-title">
                    <div class="card-title-left">
                        <i class="fas fa-map-location-dot"></i>
                        <div>
                            <h3>Data Dasar Wilayah</h3>
                            <p>Data wilayah Kabupaten Sukoharjo</p>
                        </div>
                    </div>
                    <span class="badge badge-warning">SEGERA</span>
                </div>

                <table>
                    <tr><td>Kecamatan</td><td>12</td></tr>
                    <tr><td>Desa / Kelurahan</td><td>-</td></tr>
                    <tr><td>Penduduk</td><td>-</td></tr>
                    <tr><td>Kepala Keluarga</td><td>-</td></tr>
                </table>

                <a href="#" class="btn-edit btn-disabled">
                    <i class="fas fa-pen"></i> Edit Data Wilayah
                </a>
            </div>

        </div><!-- end card-grid -->

        <!-- ======================================================
        FOOTER
        ======================================================= -->
        <div class="admin-footer">
            Portal Terpadu Dinas Kesehatan Kabupaten Sukoharjo &copy; <?= date('Y') ?>
        </div>

    </div><!-- end admin-dashboard -->
</body>
</html>