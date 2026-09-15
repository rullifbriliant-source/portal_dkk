<?php
require_once '../config.php';
requireLogin();
$current_page = basename($_SERVER['PHP_SELF']);

// ==========================================================
// REKAP SARANA PELAYANAN KESEHATAN 2021–2025 (agregat kabupaten)
// Tabel: tbl_faskes_rekap (tahun x jenis_sarana x jumlah).
// Terpisah penuh dari tbl_faskes (individual 2026) — TIDAK ADA
// query ke tbl_faskes / tbl_faskes_bed di file ini.
// Direksi tahun: kolom matriks 2021–2025 (konstanta di bawah).
// ==========================================================
define('REKAP_TAHUN_AWAL', 2021);
define('REKAP_TAHUN_AKHIR', 2025);
$TAHUN_LIST = range(REKAP_TAHUN_AWAL, REKAP_TAHUN_AKHIR);

// Tabel mungkin belum ada (migration Tahap 1 belum dijalankan).
// Tampilkan peringatan ramah, bukan fatal error.
$hasTable = false;
$chkTable = @mysqli_query($config, "SHOW TABLES LIKE 'tbl_faskes_rekap'");
if ($chkTable && mysqli_num_rows($chkTable) > 0) {
    $hasTable = true;
}

// Helper validasi
function rekap_valid_tahun($t) {
    $t = (int)$t;
    return ($t >= REKAP_TAHUN_AWAL && $t <= REKAP_TAHUN_AKHIR) ? $t : null;
}
function rekap_valid_jumlah($v) {
    if ($v === null || $v === '') return null;
    if (!preg_match('/^\d+$/', trim((string)$v))) return false;
    return (int)trim((string)$v);
}

// ==========================================
// HANDLE ACTIONS (ADD, EDIT, DELETE, IMPORT)
// ==========================================
$msg = $_GET['msg'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $hasTable) {
    $action = $_POST['action'] ?? '';

    // TAMBAH SATU BARIS (tahun x jenis_sarana)
    if ($action === 'add') {
        $tahun = rekap_valid_tahun($_POST['tahun'] ?? '');
        $jenis = trim((string)($_POST['jenis_sarana'] ?? ''));
        $jumlah = rekap_valid_jumlah($_POST['jumlah'] ?? '');
        if ($tahun === null || $jenis === '' || mb_strlen($jenis) > 100 || $jumlah === null || $jumlah === false) {
            header("Location: faskes_rekap.php?msg=invalid");
            exit;
        }
        $jenisEsc = mysqli_real_escape_string($config, $jenis);
        $dup = mysqli_query($config, "SELECT id FROM tbl_faskes_rekap WHERE tahun=$tahun AND jenis_sarana='$jenisEsc' LIMIT 1");
        if ($dup && mysqli_fetch_assoc($dup)) {
            header("Location: faskes_rekap.php?msg=exists");
            exit;
        }
        $ok = mysqli_query($config, "INSERT INTO tbl_faskes_rekap (tahun, jenis_sarana, jumlah, aktif) VALUES ($tahun, '$jenisEsc', $jumlah, 'Y')");
        header("Location: faskes_rekap.php?msg=" . ($ok ? 'added' : 'error'));
        exit;
    }

    // EDIT SATU BARIS
    if ($action === 'edit') {
        $id = (int)($_POST['id'] ?? 0);
        $tahun = rekap_valid_tahun($_POST['tahun'] ?? '');
        $jenis = trim((string)($_POST['jenis_sarana'] ?? ''));
        $jumlah = rekap_valid_jumlah($_POST['jumlah'] ?? '');
        $aktif = ($_POST['aktif'] ?? 'Y') === 'N' ? 'N' : 'Y';
        if ($id <= 0 || $tahun === null || $jenis === '' || mb_strlen($jenis) > 100 || $jumlah === null || $jumlah === false) {
            header("Location: faskes_rekap.php?msg=invalid");
            exit;
        }
        $jenisEsc = mysqli_real_escape_string($config, $jenis);
        $dup = mysqli_query($config, "SELECT id FROM tbl_faskes_rekap WHERE tahun=$tahun AND jenis_sarana='$jenisEsc' AND id<>$id LIMIT 1");
        if ($dup && mysqli_fetch_assoc($dup)) {
            header("Location: faskes_rekap.php?msg=exists");
            exit;
        }
        $ok = mysqli_query($config, "UPDATE tbl_faskes_rekap SET tahun=$tahun, jenis_sarana='$jenisEsc', jumlah=$jumlah, aktif='$aktif' WHERE id=$id");
        header("Location: faskes_rekap.php?msg=" . ($ok ? 'updated' : 'error'));
        exit;
    }

    // HAPUS (SOFT DELETE)
    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            mysqli_query($config, "UPDATE tbl_faskes_rekap SET aktif='N' WHERE id=$id");
        }
        header("Location: faskes_rekap.php?msg=deleted");
        exit;
    }

    // IMPORT EXCEL — format matriks:
    //   Kolom A = "Jenis Sarana", kolom berikutnya header tahun (2021–2025).
    //   Upsert berdasarkan (tahun, jenis_sarana). Sel kosong = dilewati
    //   (kosong TIDAK dianggap 0). Hanya tbl_faskes_rekap yang ditulis.
    if ($action === 'import_excel') {
        $autoload = __DIR__ . '/../../vendor/autoload.php';
        if (!file_exists($autoload)) {
            header("Location: faskes_rekap.php?msg=excel_no_vendor");
            exit;
        }
        require_once $autoload;
        if (!isset($_FILES['excel_file']) || $_FILES['excel_file']['error'] !== UPLOAD_ERR_OK) {
            header("Location: faskes_rekap.php?msg=import_no_file");
            exit;
        }
        $ext = strtolower(pathinfo($_FILES['excel_file']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['xlsx', 'xls', 'csv'], true)) {
            header("Location: faskes_rekap.php?msg=import_invalid");
            exit;
        }
        $added = 0; $updated = 0; $skipped = 0;
        try {
            $reader = $ext === 'csv'
                ? new \PhpOffice\PhpSpreadsheet\Reader\Csv()
                : ($ext === 'xls'
                    ? new \PhpOffice\PhpSpreadsheet\Reader\Xls()
                    : new \PhpOffice\PhpSpreadsheet\Reader\Xlsx());
            $reader->setReadDataOnly(true);
            $ss = $reader->load($_FILES['excel_file']['tmp_name']);
            $rows = $ss->getActiveSheet()->toArray(null, true, true, true);
            // Deteksi baris header: kolom A berisi "Jenis Sarana"
            $headerRow = null;
            foreach ($rows as $rNum => $row) {
                if (strcasecmp(trim((string)($row['A'] ?? '')), 'Jenis Sarana') === 0) { $headerRow = (int)$rNum; break; }
            }
            if ($headerRow === null) {
                header("Location: faskes_rekap.php?msg=import_invalid");
                exit;
            }
            // Petakan kolom tahun dari header (hanya 2021–2025 yang diterima)
            $yearCols = [];
            foreach ($rows[$headerRow] as $col => $val) {
                if ($col === 'A') continue;
                $t = rekap_valid_tahun(trim((string)$val));
                if ($t !== null) $yearCols[$col] = $t;
            }
            if (empty($yearCols)) {
                header("Location: faskes_rekap.php?msg=import_invalid");
                exit;
            }
            $stmt = mysqli_prepare($config, "INSERT INTO tbl_faskes_rekap (tahun, jenis_sarana, jumlah, aktif) VALUES (?, ?, ?, 'Y') ON DUPLICATE KEY UPDATE jumlah=VALUES(jumlah), aktif='Y'");
            if (!$stmt) {
                header("Location: faskes_rekap.php?msg=import_error");
                exit;
            }
            $config->begin_transaction();
            foreach ($rows as $rNum => $row) {
                if ((int)$rNum <= $headerRow) continue;
                $jenis = trim((string)($row['A'] ?? ''));
                if ($jenis === '' || mb_strlen($jenis) > 100) { continue; }
                foreach ($yearCols as $col => $tahun) {
                    $raw = trim((string)($row[$col] ?? ''));
                    if ($raw === '') continue; // kosong = lewati, bukan 0
                    $jumlah = rekap_valid_jumlah($raw);
                    if ($jumlah === false || $jumlah === null) { $skipped++; continue; }
                    mysqli_stmt_bind_param($stmt, 'isi', $tahun, $jenis, $jumlah);
                    if (mysqli_stmt_execute($stmt)) {
                        if (mysqli_stmt_affected_rows($stmt) === 1) $added++;
                        else $updated++;
                    } else {
                        $skipped++;
                    }
                }
            }
            mysqli_stmt_close($stmt);
            $config->commit();
        } catch (Exception $e) {
            $config->rollback();
            header("Location: faskes_rekap.php?msg=import_error");
            exit;
        }
        header("Location: faskes_rekap.php?msg=import_done&added=$added&updated=$updated&skipped=$skipped");
        exit;
    }
}

// ==========================================
// EXCEL TEMPLATE / EXPORT (download, read-only)
// Template: header saja (Jenis Sarana | 2021..2025), TANPA angka contoh.
// Export: matriks dari data aktif; sel kosong dibiarkan kosong (bukan 0).
// ==========================================
$excelDl = $_GET['excel'] ?? '';
if (in_array($excelDl, ['template', 'export'], true)) {
    $autoload = __DIR__ . '/../../vendor/autoload.php';
    if (!file_exists($autoload)) {
        header("Location: faskes_rekap.php?msg=excel_no_vendor");
        exit;
    }
    require_once $autoload;
    $headers = array_merge(['Jenis Sarana'], $TAHUN_LIST);
    $ss = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $ss->getActiveSheet();
    $sheet->setTitle('Rekap Fasyankes');
    foreach ($headers as $i => $h) {
        $sheet->setCellValueByColumnAndRow($i + 1, 1, $h);
    }
    $sheet->getStyle('A1:F1')->getFont()->setBold(true);
    $sheet->getStyle('A1:F1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('BDD7EE');
    $sheet->getColumnDimension('A')->setWidth(38);
    foreach (['B', 'C', 'D', 'E', 'F'] as $c) { $sheet->getColumnDimension($c)->setWidth(12); }
    $sheet->freezePane('A2');
    $rowIdx = 2;
    if ($excelDl === 'export' && $hasTable) {
        $qExp = mysqli_query($config, "SELECT jenis_sarana, tahun, jumlah FROM tbl_faskes_rekap WHERE aktif='Y' ORDER BY jenis_sarana, tahun");
        $mat = [];
        while ($r = mysqli_fetch_assoc($qExp)) { $mat[$r['jenis_sarana']][(int)$r['tahun']] = (int)$r['jumlah']; }
        foreach ($mat as $jenis => $perTahun) {
            $sheet->setCellValueByColumnAndRow(1, $rowIdx, $jenis);
            foreach ($TAHUN_LIST as $i => $t) {
                if (isset($perTahun[$t])) { $sheet->setCellValueByColumnAndRow($i + 2, $rowIdx, $perTahun[$t]); }
            }
            $rowIdx++;
        }
        $filename = 'Export_Rekap_Fasyankes_' . date('Ymd') . '.xlsx';
    } else {
        $filename = 'Template_Rekap_Fasyankes.xlsx';
    }
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($ss);
    $writer->save('php://output');
    exit;
}

// ==========================================
// DATA RETRIEVAL (matriks tahun x jenis)
// ==========================================
$search = isset($_GET['search']) && $_GET['search'] !== '' ? mysqli_real_escape_string($config, trim($_GET['search'])) : null;

$matriks = [];   // [jenis_sarana][tahun] = ['id'=>..,'jumlah'=>..]
$statJenis = 0; $statBaris = 0; $statTahun = 0;
if ($hasTable) {
    $whereSearch = $search ? "AND jenis_sarana LIKE '%$search%'" : '';
    $qAll = mysqli_query($config, "SELECT id, tahun, jenis_sarana, jumlah FROM tbl_faskes_rekap WHERE aktif='Y' $whereSearch ORDER BY jenis_sarana, tahun");
    $tahunTerisi = [];
    while ($r = mysqli_fetch_assoc($qAll)) {
        $matriks[$r['jenis_sarana']][(int)$r['tahun']] = ['id' => (int)$r['id'], 'jumlah' => (int)$r['jumlah']];
        $tahunTerisi[(int)$r['tahun']] = true;
    }
    $statJenis = count($matriks);
    $statTahun = count($tahunTerisi);
    $qCount = mysqli_query($config, "SELECT COUNT(*) AS c FROM tbl_faskes_rekap WHERE aktif='Y'");
    if ($qCount) { $statBaris = (int)mysqli_fetch_assoc($qCount)['c']; }
}

$username = $_SESSION['admin_username'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekap Sarana Pelayanan Kesehatan 2021–2025 - Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Poppins',sans-serif; background:#061426; min-height:100vh; display:flex; color:#fff; }

        /* SIDEBAR (pola sama seperti CRUD lain) */
        .sidebar { width:260px; min-height:100vh; background:rgba(255,255,255,0.04); backdrop-filter:blur(12px); border-right:1px solid rgba(255,255,255,0.06); padding:30px 20px; flex-shrink:0; position:sticky; top:0; height:100vh; overflow-y:auto; scrollbar-width:none; -ms-overflow-style:none; }
        /* Sembunyikan scrollbar, sidebar tetap bisa scroll */
        .sidebar::-webkit-scrollbar { display:none; width:0; height:0; }
        .sidebar-brand { display:flex; align-items:center; gap:14px; padding-bottom:30px; border-bottom:1px solid rgba(255,255,255,0.06); margin-bottom:24px; }
        .sidebar-brand img { width:48px; height:48px; object-fit:contain; }
        .sidebar-brand h2 { color:#fff; font-size:16px; font-weight:700; line-height:1.2; }
        .sidebar-brand small { display:block; color:#87e3ff; font-size:10px; font-weight:500; letter-spacing:1px; }
        .sidebar-menu { list-style:none; }
        .sidebar-menu li { margin-bottom:4px; }
        .sidebar-menu a { display:flex; align-items:center; gap:12px; padding:12px 16px; border-radius:12px; color:rgba(255,255,255,0.6); text-decoration:none; font-size:14px; font-weight:500; transition:0.3s; }
        .sidebar-menu a:hover, .sidebar-menu a.active { background:rgba(0,212,255,0.12); color:#fff; }
        .sidebar-menu a i { width:20px; color:rgba(255,255,255,0.3); font-size:16px; }
        .sidebar-menu a.active i { color:#00d4ff; }
        .sidebar-menu .menu-group {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: rgba(255,255,255,0.3);
            padding: 14px 16px 6px;
        }
        .sidebar-menu .logout { margin-top:30px; border-top:1px solid rgba(255,255,255,0.06); padding-top:20px; }
        .sidebar-menu .logout a { color:rgba(255,82,82,0.7); }
        .sidebar-menu .logout a:hover { background:rgba(255,82,82,0.12); color:#ff6b6b; }

        .main-content { flex:1; padding:30px 40px; }
        .page-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; }
        .page-header h1 { color:#fff; font-size:28px; font-weight:700; }
        .page-header p { color:#87e3ff; font-size:14px; margin-top:4px; }
        .page-header .back-link { color:#87e3ff; text-decoration:none; font-size:14px; display:flex; align-items:center; gap:8px; transition:0.3s; }
        .page-header .back-link:hover { color:#00d4ff; }

        .alert { padding:12px 18px; border-radius:12px; margin-bottom:18px; font-size:14px; }
        .alert-success { background:rgba(76,175,80,0.12); border:1px solid rgba(76,175,80,0.25); color:#81c784; }
        .alert-warning { background:rgba(255,193,7,0.12); border:1px solid rgba(255,193,7,0.25); color:#ffd54f; }

        /* STATS BAR */
        .stats-grid { display:grid; grid-template-columns:repeat(3, 1fr); gap:16px; margin-bottom:24px; }
        .stat-card { background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.06); border-radius:16px; padding:18px 20px; display:flex; align-items:center; gap:16px; }
        .stat-icon { width:44px; height:44px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:20px; }
        .stat-info b { display:block; font-size:22px; color:#fff; }
        .stat-info span { font-size:12px; color:rgba(255,255,255,0.5); }

        /* CARD + FORM */
        .card { background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.06); border-radius:18px; padding:24px; margin-bottom:24px; }
        .card h3 { color:#fff; font-size:17px; margin-bottom:18px; display:flex; align-items:center; gap:10px; }
        .form-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(220px,1fr)); gap:16px; }
        .form-group label { display:block; font-size:12px; color:#87e3ff; margin-bottom:6px; font-weight:600; }
        .form-group input, .form-group select { width:100%; padding:10px 14px; border-radius:10px; border:1px solid rgba(255,255,255,0.12); background:rgba(255,255,255,0.05); color:#fff; font-size:14px; font-family:'Poppins',sans-serif; }
        .form-group select option { background:#0b2343; }
        .form-actions { display:flex; gap:10px; flex-wrap:wrap; margin-top:18px; }
        .btn-primary { padding:10px 24px; border-radius:10px; border:none; background:linear-gradient(135deg,#00d4ff,#0088cc); color:#fff; font-weight:600; cursor:pointer; transition:0.3s; display:inline-flex; align-items:center; gap:8px; font-size:14px; text-decoration:none; font-family:'Poppins',sans-serif; }
        .btn-primary:hover { transform:translateY(-2px); box-shadow:0 8px 25px rgba(0,212,255,0.2); }
        .btn-excel-import { background:linear-gradient(135deg,#FF9800,#EF6C00); }
        .btn-excel-export { background:linear-gradient(135deg,#4CAF50,#2E7D32); }
        .btn-excel-template { background:rgba(255,255,255,0.08); border:1px solid rgba(255,255,255,0.15); }

        /* TOOLBAR + TABLE */
        .toolbar { display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; margin-bottom:18px; }
        .toolbar h3 { margin-bottom:0; }
        .filters-wrap { display:flex; gap:8px; align-items:center; }
        .filters-wrap input { padding:9px 14px; border-radius:10px; border:1px solid rgba(255,255,255,0.12); background:rgba(255,255,255,0.05); color:#fff; font-size:13px; font-family:'Poppins',sans-serif; }
        .btn-icon { padding:9px 12px; border-radius:10px; border:1px solid rgba(0,212,255,0.3); background:rgba(0,212,255,0.1); color:#00d4ff; cursor:pointer; font-size:13px; text-decoration:none; display:inline-flex; align-items:center; gap:6px; }
        .btn-icon:hover { background:rgba(0,212,255,0.2); }
        .btn-danger { border-color:rgba(255,82,82,0.3); background:rgba(255,82,82,0.1); color:#ff8a80; }
        table { width:100%; border-collapse:collapse; }
        th, td { padding:12px 14px; text-align:left; font-size:13px; border-bottom:1px solid rgba(255,255,255,0.06); }
        th { color:#87e3ff; font-weight:600; font-size:12px; text-transform:uppercase; letter-spacing:0.5px; }
        td { color:rgba(255,255,255,0.85); }
        td.num { text-align:right; font-variant-numeric:tabular-nums; }
        th.num { text-align:right; }
        .cell-empty { color:rgba(255,255,255,0.25); }
        .cell-add { font-size:11px; color:#72e8ff; text-decoration:none; margin-left:8px; }
        .cell-add:hover { text-decoration:underline; }

        /* MODAL */
        .modal-box { background:#0b2343; border:1px solid rgba(255,255,255,0.1); border-radius:18px; padding:28px; max-width:520px; width:92%; }
        .modal-box h2 { color:#fff; font-size:19px; margin-bottom:18px; display:flex; align-items:center; gap:10px; }
        #editModal .modal-box, #importModal .modal-box { scrollbar-width:none; -ms-overflow-style:none; }
        #editModal .modal-box::-webkit-scrollbar, #importModal .modal-box::-webkit-scrollbar { display:none; }
        .btn-secondary { padding:10px 20px; border-radius:10px; border:1px solid rgba(255,255,255,0.15); background:transparent; color:#fff; cursor:pointer; font-size:14px; font-family:'Poppins',sans-serif; }

        @media (max-width:1024px) { .stats-grid { grid-template-columns:repeat(2,1fr); } }
        @media (max-width:768px) { .sidebar{display:none;} .main-content{padding:20px;} .form-grid{grid-template-columns:1fr;} .stats-grid{grid-template-columns:1fr;} }
    </style>
</head>
<body>

<!-- SIDEBAR -->
<div class="sidebar">
    <div class="sidebar-brand">
        <img src="../../assets/img/kabupaten.png" alt="Logo">
        <h2>Portal DKK<br><small>Dashboard Admin</small></h2>
    </div>
    <ul class="sidebar-menu">
        <li class="menu-group">Utama</li>
        <li><a href="../index.php"><i class="fas fa-chart-pie"></i> Dashboard</a></li>
        <li class="menu-group">Fasyankes</li>
        <li><a href="fasyankes.php"><i class="fas fa-hospital"></i> Fasyankes</a></li>
        <li><a href="faskes_rekap.php" class="active"><i class="fas fa-table"></i> Rekap Fasyankes</a></li>
        <li class="menu-group">SDM Kesehatan</li>
        <li><a href="sdmk.php"><i class="fas fa-hospital-user"></i> SDMK</a></li>
        <li><a href="sdmk_kecamatan_rekap.php"><i class="fas fa-chart-bar"></i> Rekap SDMK</a></li>
        <li><a href="spesialis.php"><i class="fas fa-user-doctor"></i> Spesialis Dokter</a></li>
        <li class="menu-group">Wilayah &amp; Data Lain</li>
        <li><a href="kecamatan.php"><i class="fas fa-map"></i> Kecamatan</a></li>
        <li><a href="penyakit.php"><i class="fas fa-disease"></i> Penyakit</a></li>
        <li class="menu-group">SPM</li>
        <li><a href="spm.php"><i class="fas fa-chart-pie"></i> SPM Target</a></li>
        <li><a href="spm_realisasi.php"><i class="fas fa-chart-line"></i> SPM Realisasi</a></li>
        <li class="menu-group">Lainnya</li>
        <li><a href="portal_info.php"><i class="fas fa-circle-info"></i> Informasi Portal</a></li>
        <li class="logout"><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
    </ul>
</div>

<!-- MAIN CONTENT -->
<div class="main-content">
    <div class="page-header">
        <div>
            <h1>Rekap Sarana Pelayanan Kesehatan 2021–2025</h1>
            <p>Data agregat Kabupaten Sukoharjo per tahun × jenis sarana (terpisah dari data individual 2026).</p>
        </div>
        <a href="../index.php" class="back-link"><i class="fas fa-arrow-left"></i> Kembali</a>
    </div>

    <?php if ($msg === 'added'): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle"></i> Data rekap berhasil ditambahkan!</div>
    <?php elseif ($msg === 'updated'): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle"></i> Data rekap berhasil diperbarui!</div>
    <?php elseif ($msg === 'deleted'): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle"></i> Data rekap berhasil dihapus!</div>
    <?php elseif ($msg === 'exists'): ?>
        <div class="alert alert-warning"><i class="fas fa-exclamation-triangle"></i> Kombinasi tahun × jenis sarana tersebut sudah ada. Gunakan Edit untuk mengubah angkanya.</div>
    <?php elseif ($msg === 'invalid'): ?>
        <div class="alert alert-warning"><i class="fas fa-exclamation-triangle"></i> Input tidak valid. Tahun harus 2021–2025, jenis sarana 1–100 karakter, jumlah angka ≥ 0.</div>
    <?php elseif ($msg === 'error'): ?>
        <div class="alert alert-warning"><i class="fas fa-exclamation-circle"></i> Gagal menyimpan. Silakan coba lagi.</div>
    <?php elseif ($msg === 'import_done'): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle"></i> Import Excel selesai: <?= (int)($_GET['added'] ?? 0) ?> ditambah, <?= (int)($_GET['updated'] ?? 0) ?> diperbarui, <?= (int)($_GET['skipped'] ?? 0) ?> dilewati.</div>
    <?php elseif (in_array($msg, ['import_no_file', 'import_invalid', 'import_error'], true)): ?>
        <div class="alert alert-warning"><i class="fas fa-exclamation-circle"></i> Import Excel gagal. Pastikan file .xlsx/.xls/.csv sesuai Template (kolom A = "Jenis Sarana", kolom tahun = 2021–2025).</div>
    <?php elseif ($msg === 'excel_no_vendor'): ?>
        <div class="alert alert-warning"><i class="fas fa-exclamation-triangle"></i> Library Excel (PhpSpreadsheet) belum terinstal. Jalankan <code>composer install</code> terlebih dahulu.</div>
    <?php endif; ?>

    <?php if (!$hasTable): ?>
        <div class="alert alert-warning"><i class="fas fa-database"></i> Tabel <code>tbl_faskes_rekap</code> belum ada — migration <code>database/migrations/20260914_faskes_rekap.sql</code> belum dijalankan. CRUD aktif setelah migration dijalankan.</div>
    <?php endif; ?>

    <!-- STATS -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon" style="background:rgba(0,212,255,0.15);color:#00d4ff;"><i class="fas fa-list"></i></div>
            <div class="stat-info"><b><?= number_format($statJenis) ?></b><span>Jenis Sarana</span></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background:rgba(76,175,80,0.15);color:#81c784;"><i class="fas fa-table"></i></div>
            <div class="stat-info"><b><?= number_format($statBaris) ?></b><span>Baris Data Aktif</span></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background:rgba(255,193,7,0.15);color:#ffd54f;"><i class="fas fa-calendar-alt"></i></div>
            <div class="stat-info"><b><?= (int)$statTahun ?>/5</b><span>Tahun Terisi</span></div>
        </div>
    </div>

    <!-- FORM TAMBAH -->
    <div class="card">
        <h3><i class="fas fa-plus-circle" style="color:#00d4ff;"></i>Tambah Data Rekap</h3>
        <form method="POST">
            <input type="hidden" name="action" value="add">
            <div class="form-grid">
                <div class="form-group">
                    <label>Jenis Sarana</label>
                    <input type="text" name="jenis_sarana" maxlength="100" required placeholder="Contoh: Puskesmas Induk">
                </div>
                <div class="form-group">
                    <label>Tahun</label>
                    <select name="tahun" required>
                        <?php foreach ($TAHUN_LIST as $t): ?>
                            <option value="<?= $t ?>"><?= $t ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Jumlah</label>
                    <input type="number" name="jumlah" min="0" step="1" required placeholder="Contoh: 12">
                </div>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn-primary" <?= $hasTable ? '' : 'disabled' ?>><i class="fas fa-save"></i> Simpan</button>
                <button type="button" class="btn-primary btn-excel-import" onclick="document.getElementById('importModal').style.display='flex'"><i class="fas fa-file-import"></i> Import Excel</button>
                <a href="faskes_rekap.php?excel=export" class="btn-primary btn-excel-export"><i class="fas fa-file-export"></i> Export Excel</a>
                <a href="faskes_rekap.php?excel=template" class="btn-primary btn-excel-template"><i class="fas fa-file-excel"></i> Template Excel</a>
            </div>
        </form>
    </div>

    <!-- TABEL MATRIKS -->
    <div class="card">
        <div class="toolbar">
            <h3><i class="fas fa-table" style="color:#00d4ff;"></i>Matriks Rekap per Tahun</h3>
            <form method="GET" class="filters-wrap">
                <input type="text" name="search" placeholder="Cari jenis sarana..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                <button type="submit" class="btn-icon"><i class="fas fa-search"></i></button>
                <?php if ($search): ?>
                    <a href="faskes_rekap.php" class="btn-icon btn-danger" title="Reset Filter"><i class="fas fa-times"></i></a>
                <?php endif; ?>
            </form>
        </div>

        <div style="overflow-x:auto;">
        <table>
            <thead>
                <tr>
                    <th>Jenis Sarana</th>
                    <?php foreach ($TAHUN_LIST as $t): ?>
                        <th class="num"><?= $t ?></th>
                    <?php endforeach; ?>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($matriks)): ?>
                <tr>
                    <td colspan="<?= count($TAHUN_LIST) + 2 ?>" style="text-align:center;color:rgba(255,255,255,0.4);padding:28px;">
                        <i class="fas fa-inbox"></i> Belum ada data rekap. Tambahkan manual atau via Import Excel.
                    </td>
                </tr>
                <?php endif; ?>
                <?php foreach ($matriks as $jenis => $perTahun): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($jenis) ?></strong></td>
                    <?php foreach ($TAHUN_LIST as $t): ?>
                    <td class="num">
                        <?php if (isset($perTahun[$t])): ?>
                            <?= number_format($perTahun[$t]['jumlah']) ?>
                            <button class="btn-icon edit-btn" style="padding:4px 8px;font-size:11px;margin-left:6px;"
                                data-id="<?= $perTahun[$t]['id'] ?>"
                                data-jenis="<?= htmlspecialchars($jenis) ?>"
                                data-tahun="<?= $t ?>"
                                data-jumlah="<?= $perTahun[$t]['jumlah'] ?>"
                                title="Edit <?= htmlspecialchars($jenis) ?> <?= $t ?>"><i class="fas fa-pen"></i></button>
                            <form method="POST" style="display:inline-block;" onsubmit="return confirm('Yakin hapus data <?= htmlspecialchars(addslashes($jenis)) ?> tahun <?= $t ?>?')">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $perTahun[$t]['id'] ?>">
                                <button type="submit" class="btn-icon btn-danger" style="padding:4px 8px;font-size:11px;" title="Hapus"><i class="fas fa-trash"></i></button>
                            </form>
                        <?php else: ?>
                            <span class="cell-empty">—</span><a class="cell-add" href="#" data-jenis="<?= htmlspecialchars($jenis) ?>" data-tahun="<?= $t ?>" title="Isi <?= htmlspecialchars($jenis) ?> <?= $t ?>"><i class="fas fa-plus"></i> isi</a>
                        <?php endif; ?>
                    </td>
                    <?php endforeach; ?>
                    <td><span style="color:rgba(255,255,255,0.3);font-size:12px;">per sel</span></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>

<!-- MODAL EDIT -->
<div id="editModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.65);backdrop-filter:blur(6px);z-index:999;justify-content:center;align-items:center;">
    <div class="modal-box">
        <h2><i class="fas fa-pen" style="color:#00d4ff;"></i> Edit Data Rekap</h2>
        <form method="POST">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="eId">
            <div class="form-grid" style="grid-template-columns:1fr;">
                <div class="form-group">
                    <label>Jenis Sarana</label>
                    <input type="text" name="jenis_sarana" id="eJenis" maxlength="100" required>
                </div>
                <div class="form-group">
                    <label>Tahun</label>
                    <select name="tahun" id="eTahun" required>
                        <?php foreach ($TAHUN_LIST as $t): ?>
                            <option value="<?= $t ?>"><?= $t ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Jumlah</label>
                    <input type="number" name="jumlah" id="eJumlah" min="0" step="1" required>
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="aktif" id="eAktif">
                        <option value="Y">Aktif</option>
                        <option value="N">Nonaktif</option>
                    </select>
                </div>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn-primary"><i class="fas fa-save"></i> Simpan Perubahan</button>
                <button type="button" class="btn-secondary" onclick="document.getElementById('editModal').style.display='none'">Batal</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL IMPORT -->
<div id="importModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.65);backdrop-filter:blur(6px);z-index:999;justify-content:center;align-items:center;">
    <div class="modal-box">
        <h2><i class="fas fa-file-import" style="color:#00d4ff;"></i> Import Excel Rekap</h2>
        <form method="POST" enctype="multipart/form-data">
            <p style="font-size:13px;color:rgba(255,255,255,0.6);margin-bottom:14px;line-height:1.7;">
                Unduh <a href="faskes_rekap.php?excel=template" style="color:#00d4ff;">Template Excel</a> terlebih dahulu.
                Format: kolom A = "Jenis Sarana", kolom berikutnya header tahun 2021–2025.
                Baris = upsert per (tahun, jenis); sel kosong dilewati (bukan 0).
            </p>
            <input type="hidden" name="action" value="import_excel">
            <div class="form-group" style="margin-bottom:16px;">
                <label>File Excel (.xlsx / .xls / .csv, maks. 5MB)</label>
                <input type="file" name="excel_file" accept=".xlsx,.xls,.csv" required>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn-primary"><i class="fas fa-upload"></i> Import</button>
                <button type="button" class="btn-secondary" onclick="document.getElementById('importModal').style.display='none'">Batal</button>
            </div>
        </form>
    </div>
</div>

<script>
// Isi form tambah dari link "isi" pada sel kosong
document.querySelectorAll('.cell-add').forEach(function(a) {
    a.addEventListener('click', function(e) {
        e.preventDefault();
        var jenisInput = document.querySelector('input[name="jenis_sarana"]');
        var tahunSelect = document.querySelector('select[name="tahun"]');
        if (jenisInput) { jenisInput.value = a.dataset.jenis || ''; jenisInput.focus(); }
        if (tahunSelect && a.dataset.tahun) { tahunSelect.value = a.dataset.tahun; }
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });
});
// Isi modal edit dari tombol per sel
document.querySelectorAll('.edit-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
        document.getElementById('eId').value = btn.dataset.id || '';
        document.getElementById('eJenis').value = btn.dataset.jenis || '';
        document.getElementById('eTahun').value = btn.dataset.tahun || '';
        document.getElementById('eJumlah').value = btn.dataset.jumlah || '';
        document.getElementById('eAktif').value = 'Y';
        document.getElementById('editModal').style.display = 'flex';
    });
});
document.getElementById('editModal').onclick = function(e) {
    if (e.target === this) this.style.display = 'none';
};
document.getElementById('importModal').onclick = function(e) {
    if (e.target === this) this.style.display = 'none';
};
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        document.getElementById('editModal').style.display = 'none';
        document.getElementById('importModal').style.display = 'none';
    }
});
</script>
<script>window.PORTAL_NOTIFY_OK=["added","updated","deleted","import_done"];</script>
</body>
</html>
