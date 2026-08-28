<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);



require_once __DIR__ . '/config.php';
requireLogin();

require_once __DIR__ . '/../config/database.php';


$username = $_SESSION['admin_username'] ?? 'Admin';

// Ambil data kecamatan
$dataKecamatan = mysqli_query($config, "SELECT * FROM tbl_kecamatan WHERE aktif='Y' ORDER BY nama_kecamatan");
$totalKecamatan = mysqli_num_rows($dataKecamatan);

// Ambil data fasyankes
$itemsFasyankes = [];
$checkTableItems = mysqli_query($config, "SHOW TABLES LIKE 'tbl_fasyankes_items'");
if (mysqli_num_rows($checkTableItems) > 0) {
    $queryItems = mysqli_query($config, "SELECT nama_item, nilai FROM tbl_fasyankes_items WHERE aktif='Y' ORDER BY urutan");
    while ($row = mysqli_fetch_assoc($queryItems)) {
        $itemsFasyankes[] = $row;
    }
}
if (empty($itemsFasyankes)) {
    $itemsFasyankes = [
        ['nama_item' => 'Puskesmas', 'nilai' => 12],
        ['nama_item' => 'Pustu', 'nilai' => 24],
        ['nama_item' => 'Klinik', 'nilai' => 18],
        ['nama_item' => 'Rumah Sakit', 'nilai' => 8]
    ];
}

// Ambil data penyakit
$itemsPenyakit = [];
$checkTablePenyakit = mysqli_query($config, "SHOW TABLES LIKE 'tbl_penyakit_items'");
if (mysqli_num_rows($checkTablePenyakit) > 0) {
    $queryPenyakit = mysqli_query($config, "SELECT nama_item, nilai FROM tbl_penyakit_items WHERE aktif='Y' ORDER BY urutan LIMIT 20");
    while ($row = mysqli_fetch_assoc($queryPenyakit)) {
        $itemsPenyakit[] = $row;
    }
}
$penyakitCount = count($itemsPenyakit);
if (empty($itemsPenyakit)) {
    $itemsPenyakit = [
        ['nama_item' => 'ISPA', 'nilai' => 1540],
        ['nama_item' => 'Hipertensi', 'nilai' => 1230],
        ['nama_item' => 'COVID-19', 'nilai' => 1100],
        ['nama_item' => 'Diare', 'nilai' => 890],
        ['nama_item' => 'Gastritis', 'nilai' => 760],
        ['nama_item' => 'TBC', 'nilai' => 640],
        ['nama_item' => 'Diabetes', 'nilai' => 510],
        ['nama_item' => 'Asma', 'nilai' => 430],
        ['nama_item' => 'Pneumonia', 'nilai' => 380],
        ['nama_item' => 'Demam Berdarah', 'nilai' => 320]
    ];
    $penyakitCount = count($itemsPenyakit);
}

// Ambil data SDM
$itemsSdm = [];
$checkTableSdm = mysqli_query($config, "SHOW TABLES LIKE 'tbl_sdm_items'");
if (mysqli_num_rows($checkTableSdm) > 0) {
    $querySdm = mysqli_query($config, "SELECT nama_item, nilai FROM tbl_sdm_items WHERE aktif='Y' ORDER BY urutan");
    while ($row = mysqli_fetch_assoc($querySdm)) {
        $itemsSdm[] = $row;
    }
}
$sdmCount = count($itemsSdm);
if (empty($itemsSdm)) {
    $itemsSdm = [
        ['nama_item' => 'Dokter', 'nilai' => 85],
        ['nama_item' => 'Perawat', 'nilai' => 320],
        ['nama_item' => 'Bidan', 'nilai' => 210],
        ['nama_item' => 'Nakes Lainnya', 'nilai' => 145]
    ];
    $sdmCount = count($itemsSdm);
}

// Ambil data portal info
$dataPortal = [];
$checkPortal = mysqli_query($config, "SHOW TABLES LIKE 'tbl_portal_info'");
if (mysqli_num_rows($checkPortal) > 0) {
    $qPortal = mysqli_query($config, "SELECT deskripsi FROM tbl_portal_info WHERE id = 1");
    $dataPortal = mysqli_fetch_assoc($qPortal) ?? [];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Portal DKK Sukoharjo</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Poppins', sans-serif;
            background: radial-gradient(circle at top right, rgba(0, 212, 255, 0.08), transparent 35%), #051321;
            min-height: 100vh;
            color: #fff;
        }
        .admin-dashboard { max-width: 1400px; margin: 0 auto; padding: 28px 35px; min-height: 100vh; }

        .admin-header {
            display: flex; justify-content: space-between; align-items: center;
            padding: 18px 24px; background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.08); border-radius: 18px;
            margin-bottom: 30px; backdrop-filter: blur(18px);
        }
        .admin-header h1 { margin: 0; font-size: 24px; font-weight: 700; color: #fff; }
        .admin-header h1 span { color: #00d4ff; }
        .admin-subtitle { margin-top: 4px; font-size: 13px; color: rgba(255,255,255,0.45); }
        .admin-user { display: flex; align-items: center; gap: 15px; color: #87e3ff; font-size: 14px; }
        .admin-user i { color: #00d4ff; }
        .btn-logout {
            padding: 9px 18px; border-radius: 9px; border: 1px solid rgba(255,70,70,0.3);
            background: rgba(255,70,70,0.1); color: #ff6b6b; text-decoration: none;
            font-size: 13px; font-weight: 600; transition: .3s;
        }
        .btn-logout:hover { background: rgba(255,70,70,0.2); transform: translateY(-2px); }

        .welcome-box { margin-bottom: 25px; }
        .welcome-box h2 { font-size: 25px; color: #fff; margin-bottom: 5px; }
        .welcome-box p { color: rgba(255,255,255,0.5); font-size: 14px; }

        /* === GRID SEMUA CARD === */
        .card-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 22px;
        }

        .card-editor {
            position: relative; background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.08); border-radius: 20px;
            padding: 25px; backdrop-filter: blur(18px); transition: .3s;
        }
        .card-editor:hover {
            transform: translateY(-4px); background: rgba(255,255,255,0.07);
            border-color: rgba(0,212,255,0.25); box-shadow: 0 15px 40px rgba(0,0,0,0.25);
        }

        .card-title {
            display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;
        }
        .card-title-left { display: flex; align-items: center; gap: 12px; }
        .card-title-left i {
            width: 42px; height: 42px; display: flex; align-items: center; justify-content: center;
            border-radius: 12px; background: rgba(0,212,255,0.1); color: #00d4ff; font-size: 18px;
        }
        .card-title h3 { margin: 0; font-size: 17px; color: #fff; }
        .card-title p { margin: 3px 0 0; font-size: 11px; color: rgba(255,255,255,0.4); }

        .badge {
            padding: 5px 12px; border-radius: 20px; background: rgba(0,212,255,0.12);
            color: #00d4ff; font-size: 10px; font-weight: 600; white-space: nowrap;
        }
        .badge-warning { background: rgba(255,193,7,0.15); color: #ffc107; }

        .card-editor table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .card-editor table td { padding: 9px 0; border-bottom: 1px solid rgba(255,255,255,0.06); font-size: 13px; }
        .card-editor table td:first-child { color: rgba(255,255,255,0.55); }
        .card-editor table td:last-child { text-align: right; color: #fff; font-weight: 600; }

        .btn-edit {
            display: inline-flex; align-items: center; justify-content: center; gap: 8px;
            width: 100%; padding: 10px 15px; border-radius: 10px;
            border: 1px solid rgba(0,212,255,0.2); background: rgba(0,212,255,0.1);
            color: #00d4ff; text-decoration: none; font-size: 13px; font-weight: 600; transition: .3s;
        }
        .btn-edit:hover { background: rgba(0,212,255,0.2); border-color: rgba(0,212,255,0.4); transform: translateY(-2px); }
        .btn-disabled { opacity: .45; cursor: not-allowed; pointer-events: none; }

        .admin-footer { margin-top: 30px; text-align: center; color: rgba(255,255,255,0.25); font-size: 12px; }

        @media (max-width: 900px) {
            .card-grid { grid-template-columns: 1fr; }
            .admin-header { flex-direction: column; align-items: flex-start; gap: 15px; }
            .admin-user { width: 100%; justify-content: space-between; }
        }
        @media (max-width: 600px) {
            .admin-dashboard { padding: 18px; }
            .admin-user { flex-direction: column; align-items: flex-start; }
            .welcome-box h2 { font-size: 21px; }
        }
    </style>
</head>

<body>
    <div class="admin-dashboard">

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

        <div class="welcome-box">
            <h2>Dashboard Admin</h2>
            <p>Kelola data yang ditampilkan pada Portal Terpadu Dinas Kesehatan Kabupaten Sukoharjo.</p>
        </div>

        <!-- ======================================================
        SEMUA CARD DALAM SATU GRID 2 KOLOM
        ====================================================== -->
        <div class="card-grid">

            <!-- 1. FASYANKES -->
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
                    <?php foreach ($itemsFasyankes as $item): ?>
                    <tr>
                        <td><?= htmlspecialchars($item['nama_item']) ?></td>
                        <td><?= number_format((int)$item['nilai'], 0, ',', '.') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </table>
                <a href="crud/fasyankes.php" class="btn-edit"><i class="fas fa-pen"></i> Kelola Fasyankes</a>
            </div>

            <!-- 2. SDM -->
            <div class="card-editor">
                <div class="card-title">
                    <div class="card-title-left">
                        <i class="fas fa-users"></i>
                        <div>
                            <h3>SDM Kesehatan</h3>
                            <p>Data sumber daya manusia</p>
                        </div>
                    </div>
                    <span class="badge"><?= $sdmCount ?> item</span>
                </div>
                <table>
                    <?php foreach ($itemsSdm as $item): ?>
                    <tr>
                        <td><?= htmlspecialchars($item['nama_item']) ?></td>
                        <td><?= number_format((int)$item['nilai']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </table>
                <a href="crud/sdm.php" class="btn-edit"><i class="fas fa-pen"></i> Kelola SDM</a>
            </div>

            <!-- 3. PENYAKIT -->
            <div class="card-editor" id="penyakitCard">
                <div class="card-title">
                    <div class="card-title-left">
                        <i class="fas fa-virus"></i>
                        <div>
                            <h3>Data Penyakit</h3>
                            <p>Data penyakit terbanyak</p>
                        </div>
                    </div>
                    <span class="badge"><?= $penyakitCount ?> item</span>
                </div>
                <table id="penyakitTable">
                    <?php 
                    $limit = 5;
                    $total = count($itemsPenyakit);
                    $showAll = isset($_GET['show_all_penyakit']) && $_GET['show_all_penyakit'] == 1;
                    $displayItems = $showAll ? $itemsPenyakit : array_slice($itemsPenyakit, 0, $limit);
                    ?>
                    <?php foreach ($displayItems as $index => $item): ?>
                    <tr>
                        <td><?= ($index+1) . '. ' . htmlspecialchars($item['nama_item']) ?></td>
                        <td><?= number_format((int)$item['nilai']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if ($total > $limit && !$showAll): ?>
                    <tr id="showMoreRow">
                        <td colspan="2" style="text-align:center;padding:8px 0;">
                            <a href="?show_all_penyakit=1#penyakitCard" 
                               style="color:#00d4ff;text-decoration:none;font-size:13px;font-weight:600;cursor:pointer;display:inline-flex;align-items:center;gap:6px;padding:6px 16px;border-radius:8px;background:rgba(0,212,255,0.1);border:1px solid rgba(0,212,255,0.2);transition:0.3s;">
                                <i class="fas fa-chevron-down"></i> Tampilkan <?= $total - $limit ?> penyakit lainnya
                            </a>
                        </td>
                    </tr>
                    <?php endif; ?>
                    <?php if ($showAll): ?>
                    <tr id="hideShowRow">
                        <td colspan="2" style="text-align:center;padding:8px 0;">
                            <a href="?show_all_penyakit=0#penyakitCard" 
                               style="color:rgba(255,255,255,0.5);text-decoration:none;font-size:12px;cursor:pointer;display:inline-flex;align-items:center;gap:6px;padding:4px 12px;border-radius:6px;background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.1);transition:0.3s;">
                                <i class="fas fa-chevron-up"></i> Sembunyikan
                            </a>
                        </td>
                    </tr>
                    <?php endif; ?>
                </table>
                <a href="crud/penyakit.php" class="btn-edit"><i class="fas fa-pen"></i> Edit Data Penyakit</a>
            </div>

            <!-- 4. DATA DASAR WILAYAH -->
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
                    <tr><td>Kecamatan</td><td><?= $totalKecamatan ?></td></tr>
                    <tr><td>Desa / Kelurahan</td><td>-</td></tr>
                    <tr><td>Penduduk</td><td>-</td></tr>
                    <tr><td>Kepala Keluarga</td><td>-</td></tr>
                </table>
                <a href="#" class="btn-edit btn-disabled"><i class="fas fa-pen"></i> Edit Data Wilayah</a>
            </div>

            <!-- 5. DATA KECAMATAN -->
            <div class="card-editor">
                <div class="card-title">
                    <div class="card-title-left">
                        <i class="fas fa-map"></i>
                        <div>
                            <h3>Data Kecamatan</h3>
                            <p>Kelola data kecamatan</p>
                        </div>
                    </div>
                    <span class="badge"><?= $totalKecamatan ?> item</span>
                </div>
                <table>
                    <?php 
                    $limitKec = 5;
                    $i = 0;
                    mysqli_data_seek($dataKecamatan, 0);
                    while ($row = mysqli_fetch_assoc($dataKecamatan)) {
                        if ($i >= $limitKec) break;
                        ?>
                        <tr>
                            <td><?= ($i+1) . '. ' . htmlspecialchars($row['nama_kecamatan']) ?></td>
                            <td><?= $row['kode_kecamatan'] ?></td>
                        </tr>
                        <?php 
                        $i++;
                    } 
                    ?>
                    <?php if ($totalKecamatan > $limitKec): ?>
                    <tr>
                        <td colspan="2" style="text-align:center;padding:8px 0;color:rgba(255,255,255,0.4);font-size:12px;">
                            ... dan <?= $totalKecamatan - $limitKec ?> lainnya
                        </td>
                    </tr>
                    <?php endif; ?>
                </table>
                <a href="crud/kecamatan.php" class="btn-edit"><i class="fas fa-external-link-alt"></i> Kelola Lengkap</a>
            </div>

            <!-- 6. INFORMASI PORTAL -->
            <div class="card-editor">
                <div class="card-title">
                    <div class="card-title-left">
                        <i class="fas fa-circle-info"></i>
                        <div>
                            <h3>Informasi Portal</h3>
                            <p>Teks deskripsi panel kiri</p>
                        </div>
                    </div>
                    <span class="badge">AKTIF</span>
                </div>
                <table>
                    <tr>
                        <td colspan="2" style="font-size:12px;color:rgba(255,255,255,0.5);padding:8px 0;line-height:1.6;">
                            <?= htmlspecialchars(substr($dataPortal['deskripsi'] ?? '', 0, 120)) ?>...
                        </td>
                    </tr>
                </table>
                <a href="crud/portal_info.php" class="btn-edit">
                    <i class="fas fa-pen"></i> Edit Deskripsi
                </a>
            </div>

        </div><!-- end card-grid -->

        <div class="admin-footer">
            Portal Terpadu Dinas Kesehatan Kabupaten Sukoharjo &copy; <?= date('Y') ?>
        </div>

    </div>
</body>
</html>