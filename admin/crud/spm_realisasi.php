<?php
require_once '../config.php';
requireLogin();
require_once __DIR__ . '/../../App/Services/SpmLib.php';

$msg = $_GET['msg'] ?? '';
$kecMap = SpmLib::kecMap($config);
$kecOrder = SpmLib::KEC_ORDER;
$kecForm = array_values(array_filter($kecOrder, function ($k) use ($kecMap) {
    return isset($kecMap[$k]);
}));
$periods = SpmLib::periods($config);
$counts = SpmLib::counts($config);

// ============================================================
// POST: save_realisasi / delete_realisasi / delete_all_realisasi
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    // Simpan realisasi per id_spm
    if ($action === 'save_realisasi') {
        $id = (int)($_POST['id_spm'] ?? 0);
        $cek = $id ? mysqli_query($config, "SELECT * FROM tbl_spm WHERE id=$id AND aktif='Y' LIMIT 1") : null;
        $old = $cek ? mysqli_fetch_assoc($cek) : null;
        if (!$old) { header('Location: spm_realisasi.php?msg=notfound'); exit; }
        $periode = $old['periode'];
        $real = $_POST['realisasi'] ?? [];
        $saved = 0;
        $deleted = 0;
        foreach ($kecForm as $kn) {
            if (!isset($kecMap[$kn])) continue;
            $kid = (int)$kecMap[$kn];
            $raw = $real[$kn] ?? null;
            // raw could be '' (kosong -> hapus realisasi kecamatan tersebut)
            $rawTrim = trim((string)$raw);
            if ($rawTrim === '' || $rawTrim === '-') {
                // hapus realisasi untuk kecamatan ini (tidak mengganggu target)
                mysqli_query($config, "DELETE FROM tbl_spm_realisasi WHERE id_spm=$id AND id_kecamatan=$kid");
                $deleted++;
                continue;
            }
            $v = (float)SpmLib::parseNumber($raw);
            // UPSERT
            mysqli_query($config, "INSERT INTO tbl_spm_realisasi (id_spm, id_kecamatan, realisasi, aktif)
                VALUES ($id,$kid,'$v','Y') ON DUPLICATE KEY UPDATE realisasi='$v', aktif='Y', updated_at=NOW()");
            $saved++;
        }
        header('Location: spm_realisasi.php?periode=' . urlencode($periode) . '&msg=updated'); exit;
    }
    if ($action === 'delete_realisasi') {
        $id = (int)($_POST['id'] ?? 0);
        $periodeBack = $_POST['periode_back'] ?? '';
        if ($id) {
            // HAPUS HANYA realisasi, target tetap di tbl_spm_target
            mysqli_query($config, "DELETE FROM tbl_spm_realisasi WHERE id_spm=$id");
            // alternatif soft-delete: UPDATE tbl_spm_realisasi SET aktif='N' WHERE id_spm=$id
        }
        header('Location: spm_realisasi.php?periode=' . urlencode($periodeBack) . '&msg=deleted'); exit;
    }
    if ($action === 'delete_all_realisasi') {
        $periode = $_POST['periode'] ?? '';
        // hapus semua realisasi; opsi filter periode
        if ($periode !== '') {
            $pEsc = mysqli_real_escape_string($config, $periode);
            $q = mysqli_query($config, "SELECT id FROM tbl_spm WHERE periode='$pEsc' AND aktif='Y'");
            $ids = [];
            if ($q) while ($r = mysqli_fetch_assoc($q)) $ids[] = (int)$r['id'];
            if (!empty($ids)) {
                $list = implode(',', $ids);
                mysqli_query($config, "DELETE FROM tbl_spm_realisasi WHERE id_spm IN ($list)");
            }
        } else {
            mysqli_query($config, "DELETE FROM tbl_spm_realisasi");
        }
        header('Location: spm_realisasi.php?periode=' . urlencode($periode) . '&msg=deleted_all'); exit;
    }
}

// ============================================================
// LIST + FILTER (pakai fetchData per periode biar realisasi ikut)
// ============================================================
$fPeriode = $_GET['periode'] ?? '';
$fSearch = trim($_GET['q'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 50;

// ambil periode efektif untuk query
// jika filter periode kosong, tampilkan semua? kita paginate manual lewat fetchData gabungan
$allRows = [];
if ($fPeriode !== '') {
    $data = SpmLib::fetchData($config, $fPeriode);
    $allRows = $data['rows'];
} else {
    // gabungkan semua periode, urut periode+urutan
    foreach ($periods as $p) {
        $d = SpmLib::fetchData($config, $p);
        foreach ($d['rows'] as $r) $allRows[] = $r;
    }
    // sort by periode order lalu urutan
    $periodOrder = array_flip($periods);
    usort($allRows, function($a,$b) use($periodOrder){
        $oa = $periodOrder[$a['periode']] ?? 99;
        $ob = $periodOrder[$b['periode']] ?? 99;
        if ($oa !== $ob) return $oa - $ob;
        if ((int)$a['urutan'] !== (int)$b['urutan']) return (int)$a['urutan'] - (int)$b['urutan'];
        return (int)$a['id'] - (int)$b['id'];
    });
}

// filter search
if ($fSearch !== '') {
    $qs = mb_strtolower($fSearch, 'UTF-8');
    $allRows = array_values(array_filter($allRows, function($r) use($qs){
        return (strpos(mb_strtolower($r['jenis_layanan'],'UTF-8'), $qs) !== false)
            || (strpos(mb_strtolower($r['indikator'],'UTF-8'), $qs) !== false)
            || (strpos(mb_strtolower($r['satuan'],'UTF-8'), $qs) !== false);
    }));
}
$totalRows = count($allRows);
$totalPages = max(1, (int)ceil($totalRows / $perPage));
if ($page > $totalPages) $page = $totalPages;
$offset = ($page - 1) * $perPage;
$rows = array_slice($allRows, $offset, $perPage);

// edit row
$editRow = null;
if (isset($_GET['edit'])) {
    $eid = (int)$_GET['edit'];
    foreach ($allRows as $r) { if ((int)$r['id'] === $eid) { $editRow = $r; break; } }
    if (!$editRow) {
        // fallback query langsung
        $q = mysqli_query($config, "SELECT * FROM tbl_spm WHERE id=$eid LIMIT 1");
        $tmp = $q ? mysqli_fetch_assoc($q) : null;
        if ($tmp) {
            $tmp['targets'] = [];
            $tmp['realisasi'] = [];
            foreach ($kecForm as $kn) { $tmp['targets'][$kn]=null; $tmp['realisasi'][$kn]=null; }
            $qt = mysqli_query($config, "SELECT k.nama_kecamatan, t.target FROM tbl_spm_target t JOIN tbl_kecamatan k ON k.id_kecamatan=t.id_kecamatan WHERE t.id_spm=$eid AND t.aktif='Y'");
            if ($qt) while ($t=mysqli_fetch_assoc($qt)) {
                $kn = SpmLib::normKec($t['nama_kecamatan']);
                if (isset($tmp['targets'][$kn])) $tmp['targets'][$kn]=(float)$t['target'];
            }
            $tmp['realisasi'] = SpmLib::fetchRealisasi($config, $eid);
            $sumT = array_sum(array_map(function($v){ return $v===null?0:(float)$v; }, $tmp['targets']));
            $sumR = array_sum(array_map(function($v){ return $v===null?0:(float)$v; }, $tmp['realisasi']));
            $tmp['total_target'] = ($tmp['total_manual']!==null && $tmp['total_manual']!=='') ? (float)$tmp['total_manual'] : $sumT;
            $tmp['total'] = $tmp['total_target'];
            $tmp['total_realisasi'] = $sumR;
            $editRow = $tmp;
        }
    }
}

$username = $_SESSION['admin_username'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Realisasi SPM - Admin DKK</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Poppins',sans-serif; background:#061426; min-height:100vh; display:flex; color:#fff; }
        .sidebar { width:260px; min-height:100vh; background:rgba(255,255,255,0.04); border-right:1px solid rgba(255,255,255,0.06); padding:30px 20px; flex-shrink:0; position:sticky; top:0; height:100vh; overflow-y:auto; scrollbar-width:none; -ms-overflow-style:none; }
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
        .main-content { flex:1; padding:30px 40px; max-width:1600px; }
        .page-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; flex-wrap:wrap; gap:12px; }
        .page-header h1 { color:#fff; font-size:26px; font-weight:700; }
        .page-header p { color:#87e3ff; font-size:13px; margin-top:4px; }
        .back-link { color:#87e3ff; text-decoration:none; font-size:14px; }
        .card { background:rgba(255,255,255,0.05); border-radius:20px; padding:24px 26px; border:1px solid rgba(255,255,255,0.08); margin-bottom:22px; }
        .card h3 { color:#84e7ff; font-size:16px; font-weight:600; margin-bottom:16px; }
        .toolbar { display:flex; gap:10px; flex-wrap:wrap; margin-bottom:18px; }
        .btn { padding:9px 18px; border-radius:10px; border:1px solid rgba(0,212,255,0.25); background:rgba(0,212,255,0.1); color:#72e8ff; text-decoration:none; font-size:13px; font-weight:600; cursor:pointer; display:inline-flex; align-items:center; gap:8px; transition:0.3s; font-family:inherit; }
        .btn:hover { background:rgba(0,212,255,0.22); }
        .btn-primary { background:linear-gradient(135deg,#00d4ff,#0088cc); color:#fff; border-color:transparent; }
        .btn-danger { border-color:rgba(255,82,82,0.4); background:rgba(255,82,82,0.1); color:#ff8a80; }
        .alert { padding:14px 20px; border-radius:12px; margin-bottom:20px; font-size:13px; }
        .alert-success { background:rgba(0,212,255,0.12); border:1px solid rgba(0,212,255,0.2); color:#72e8ff; }
        .alert-error { background:rgba(255,82,82,0.12); border:1px solid rgba(255,82,82,0.2); color:#ff8a80; }
        .form-grid { display:grid; grid-template-columns:repeat(2,1fr); gap:14px; }
        .form-grid .full { grid-column:1 / -1; }
        .fg label { display:block; font-size:12px; color:#87e3ff; font-weight:600; margin-bottom:5px; }
        .fg input, .fg select, .fg textarea { width:100%; padding:9px 13px; border-radius:9px; border:1px solid rgba(255,255,255,0.12); background:rgba(255,255,255,0.06); color:#fff; font-size:13px; font-family:inherit; }
        .fg input:focus, .fg select:focus { outline:none; border-color:#00d4ff; }
        .fg input[readonly] { background:rgba(255,255,255,0.03); color:rgba(255,255,255,0.5); }
        .kec-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:14px; }
        .kec-card { background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.07); border-radius:12px; padding:12px; }
        .kec-card h4 { font-size:12px; color:#ffd966; margin-bottom:8px; text-transform:uppercase; letter-spacing:0.5px; }
        .kec-card .row { display:flex; gap:8px; }
        .kec-card .col { flex:1; }
        .kec-card .col label { font-size:10px; color:rgba(255,255,255,0.5); margin-bottom:3px; }
        .kec-card .col input { text-align:right; }
        .kec-card .col input.realisasi { background:rgba(255,215,0,0.08); border-color:rgba(255,215,0,0.25); color:#ffd966; }
        .kec-card .col input.realisasi:focus { border-color:#ffd966; }
        table.data { width:100%; border-collapse:collapse; }
        table.data th { text-align:left; padding:10px 8px; color:#87e3ff; font-weight:600; font-size:11px; border-bottom:2px solid rgba(255,255,255,0.08); white-space:nowrap; }
        table.data td { padding:9px 8px; border-bottom:1px solid rgba(255,255,255,0.05); font-size:12px; vertical-align:top; }
        table.data td.num { text-align:right; white-space:nowrap; font-variant-numeric:tabular-nums; }
        table.data td.num.realisasi { color:#ffd966; font-weight:600; }
        .badge { padding:3px 10px; border-radius:20px; background:rgba(0,212,255,0.15); color:#72e8ff; font-size:10px; font-weight:700; white-space:nowrap; }
        .row-actions { display:flex; gap:6px; justify-content:flex-end; }
        .btn-sm { padding:5px 12px; border-radius:7px; font-size:11px; font-weight:600; cursor:pointer; text-decoration:none; border:1px solid rgba(0,212,255,0.3); background:rgba(0,212,255,0.12); color:#72e8ff; font-family:inherit; }
        .btn-sm.del { border-color:rgba(255,82,82,0.35); background:rgba(255,82,82,0.1); color:#ff8a80; }
        .filter-bar { display:flex; gap:10px; flex-wrap:wrap; align-items:end; margin-bottom:16px; }
        .pager { display:flex; gap:8px; align-items:center; justify-content:center; margin-top:16px; font-size:12px; color:rgba(255,255,255,0.5); }
        .pager a { color:#72e8ff; text-decoration:none; padding:6px 12px; border:1px solid rgba(0,212,255,0.25); border-radius:8px; }
        .hint { font-size:11px; color:rgba(255,255,255,0.4); margin-top:4px; }
        .total-box { display:flex; gap:12px; flex-wrap:wrap; margin-top:12px; }
        .total-box .tb { flex:1; min-width:160px; background:rgba(0,212,255,0.08); border:1px solid rgba(0,212,255,0.15); border-radius:10px; padding:12px; text-align:center; }
        .total-box .tb.real { background:rgba(255,215,0,0.08); border-color:rgba(255,215,0,0.2); }
        .total-box .tb small { display:block; font-size:10px; color:rgba(255,255,255,0.5); letter-spacing:0.5px; }
        .total-box .tb b { font-size:18px; color:#72e8ff; }
        .total-box .tb.real b { color:#ffd966; }
        @media (max-width:900px) { .sidebar{display:none;} .main-content{padding:20px;} .form-grid{grid-template-columns:1fr;} .kec-grid{grid-template-columns:repeat(1,1fr);} }
    </style>
</head>
<body>
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
        <li><a href="faskes_rekap.php"><i class="fas fa-table"></i> Rekap Fasyankes</a></li>
        <li class="menu-group">SDM Kesehatan</li>
        <li><a href="sdmk.php"><i class="fas fa-hospital-user"></i> SDMK</a></li>
        <li><a href="sdmk_kecamatan_rekap.php"><i class="fas fa-chart-bar"></i> Rekap SDMK</a></li>
        <li><a href="spesialis.php"><i class="fas fa-user-doctor"></i> Spesialis Dokter</a></li>
        <li class="menu-group">Wilayah &amp; Data Lain</li>
        <li><a href="kecamatan.php"><i class="fas fa-map"></i> Kecamatan</a></li>
        <li><a href="penyakit.php"><i class="fas fa-disease"></i> Penyakit</a></li>
        <li class="menu-group">SPM</li>
        <li><a href="spm.php"><i class="fas fa-chart-pie"></i> SPM Target</a></li>
        <li><a href="spm_realisasi.php" class="active"><i class="fas fa-chart-line"></i> SPM Realisasi</a></li>
        <li class="menu-group">Lainnya</li>
        <li><a href="portal_info.php"><i class="fas fa-circle-info"></i> Informasi Portal</a></li>
        <li class="logout"><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
    </ul>
</div>

<div class="main-content">
    <div class="page-header">
        <div>
            <h1>Kelola Realisasi SPM</h1>
            <p>Input realisasi per kecamatan — <b>target hanya referensi</b> (tidak dapat diedit di sini). Realisasi terpisah di tbl_spm_realisasi.</p>
        </div>
        <div style="display:flex;gap:8px;">
            <a href="spm.php" class="back-link" style="border:1px solid rgba(255,255,255,0.12);padding:8px 14px;border-radius:10px;"><i class="fas fa-bullseye"></i> Kelola Target</a>
            <a href="../index.php" class="back-link"><i class="fas fa-arrow-left"></i> Dashboard</a>
        </div>
    </div>

    <?php if ($msg === 'updated'): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> Realisasi berhasil disimpan. Target tidak berubah.</div><?php endif; ?>
    <?php if ($msg === 'deleted'): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> Realisasi dihapus. Target tetap utuh.</div><?php endif; ?>
    <?php if ($msg === 'deleted_all'): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> Seluruh realisasi pada filter tersebut berhasil dihapus. Target tidak terhapus.</div><?php endif; ?>
    <?php if ($msg === 'notfound'): ?><div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> Data SPM tidak ditemukan.</div><?php endif; ?>

    <!-- TOOLBAR -->
    <div class="card">
        <h3><i class="fas fa-info-circle" style="color:#ffd966;margin-right:8px;"></i>Petunjuk</h3>
        <div style="font-size:12px;color:rgba(255,255,255,0.65);line-height:1.7;">
            <ul style="margin-left:18px;">
                <li><b>Target</b> diambil dari modul <b>SPM Target</b> (tbl_spm_target) — hanya ditampilkan sebagai referensi, <b>tidak dapat diedit</b> di sini.</li>
                <li><b>Realisasi</b> disimpan terpisah di <b>tbl_spm_realisasi</b> (id_spm + id_kecamatan). Mengubah/menghapus realisasi <b>tidak mengubah target</b>.</li>
                <li><b>TOTAL TARGET</b> = total_manual (jika diisi) atau SUM 12 kecamatan. <b>TOTAL REALISASI</b> = SUM 12 realisasi.</li>
                <li>Kosongkan kolom realisasi untuk menghapus realisasi kecamatan tersebut (jadi 0/kosong).</li>
                <li>Periode mengikuti data SPM (tahun/periode tidak tercampur).</li>
            </ul>
        </div>
        <div style="margin-top:12px;display:flex;gap:8px;flex-wrap:wrap;">
            <a class="btn" href="spm.php"><i class="fas fa-bullseye"></i> Kelola Target SPM</a>
            <button type="button" class="btn btn-danger" onclick="if(confirm('Hapus SEMUA realisasi<?= $fPeriode!=='' ? ' pada periode '.htmlspecialchars($fPeriode) : '' ?>?\n\nTarget TIDAK akan terhapus.')){document.getElementById('formDelAll').submit();}"><i class="fas fa-trash-alt"></i> Hapus Realisasi<?= $fPeriode!=='' ? ' ('.htmlspecialchars($fPeriode).')' : ' Semua' ?></button>
            <form id="formDelAll" method="POST" style="display:none;">
                <input type="hidden" name="action" value="delete_all_realisasi">
                <input type="hidden" name="periode" value="<?= htmlspecialchars($fPeriode) ?>">
            </form>
        </div>
    </div>

    <!-- FORM EDIT REALISASI -->
    <div class="card" id="formCard" style="<?= $editRow ? '' : 'display:none;' ?>">
        <h3><i class="fas fa-pen" style="color:#ffd966;margin-right:8px;"></i><?= $editRow ? 'Edit Realisasi #' . (int)$editRow['id'] : 'Edit Realisasi' ?> — <small style="font-weight:400;color:rgba(255,255,255,0.5);"><?= $editRow ? htmlspecialchars($editRow['jenis_layanan'].' — '.$editRow['indikator']) : '' ?></small></h3>
        <?php if ($editRow): ?>
        <form method="POST">
            <input type="hidden" name="action" value="save_realisasi">
            <input type="hidden" name="id_spm" value="<?= (int)$editRow['id'] ?>">
            <div class="form-grid">
                <div class="fg"><label>Periode</label><input value="<?= htmlspecialchars($editRow['periode']) ?>" readonly></div>
                <div class="fg"><label>Tahun</label><input value="<?= htmlspecialchars($editRow['tahun']) ?>" readonly></div>
                <div class="fg full"><label>Jenis Layanan</label><input value="<?= htmlspecialchars($editRow['jenis_layanan']) ?>" readonly></div>
                <div class="fg full"><label>Indikator</label><textarea rows="2" readonly><?= htmlspecialchars($editRow['indikator']) ?></textarea></div>
                <div class="fg"><label>Satuan</label><input value="<?= htmlspecialchars($editRow['satuan']) ?>" readonly></div>
                <div class="fg"><label>Sasaran</label><input value="<?= htmlspecialchars($editRow['sasaran']) ?>" readonly></div>
            </div>
            <div class="kec-grid" style="margin-top:16px;">
                <?php foreach ($kecForm as $kn):
                    $tgt = $editRow['targets'][$kn] ?? null;
                    $real = $editRow['realisasi'][$kn] ?? null;
                ?>
                <div class="kec-card">
                    <h4><?= ucwords(strtolower($kn)) ?></h4>
                    <div class="row">
                        <div class="col"><label>TARGET</label><input value="<?= $tgt===null?'':SpmLib::fmt($tgt) ?>" readonly title="Target dari tbl_spm_target"></div>
                        <div class="col"><label>REALISASI</label><input class="realisasi" name="realisasi[<?= $kn ?>]" value="<?= $real===null?'':htmlspecialchars($real) ?>" placeholder="kosong=hapus"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="total-box">
                <div class="tb"><small>TOTAL TARGET</small><b><?= SpmLib::fmt($editRow['total_target'] ?? $editRow['total']) ?></b><small>SUM target 12 kecamatan<?= $editRow['total_manual']!==null&&$editRow['total_manual']!==''?' (manual)':'' ?></small></div>
                <div class="tb real"><small>TOTAL REALISASI</small><b><?= SpmLib::fmt($editRow['total_realisasi'] ?? 0) ?></b><small>SUM realisasi 12 kecamatan</small></div>
            </div>
            <div style="display:flex;gap:10px;margin-top:16px;">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan Realisasi</button>
                <a class="btn" href="spm_realisasi.php<?= $fPeriode!==''?'?periode='.urlencode($fPeriode):'' ?>">Batal</a>
            </div>
        </form>
        <?php endif; ?>
    </div>

    <!-- FILTER + TABEL -->
    <div class="card">
        <h3><i class="fas fa-list" style="color:#00d4ff;margin-right:8px;"></i>Data SPM (<?= number_format($totalRows) ?> baris) — daftar untuk input realisasi</h3>
        <form method="GET" class="filter-bar">
            <div class="fg">
                <label>Periode</label>
                <select name="periode" onchange="this.form.submit()">
                    <option value="">— Semua —</option>
                    <?php foreach ($periods as $p): ?>
                    <option value="<?= htmlspecialchars($p) ?>" <?= $fPeriode===$p?'selected':'' ?>><?= htmlspecialchars($p) ?> (<?= $counts[$p] ?? 0 ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="fg">
                <label>Cari layanan / indikator</label>
                <input type="text" name="q" value="<?= htmlspecialchars($fSearch) ?>" placeholder="kata kunci...">
            </div>
            <button type="submit" class="btn"><i class="fas fa-search"></i> Filter</button>
            <?php if ($fSearch!=='' || $fPeriode!==''): ?><a class="btn" href="spm_realisasi.php">Reset</a><?php endif; ?>
        </form>
        <div style="overflow-x:auto;">
        <table class="data">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Jenis Layanan</th>
                    <th>Sub</th>
                    <th>Indikator</th>
                    <th>Satuan</th>
                    <th>Periode</th>
                    <th style="text-align:right;">Total Target</th>
                    <th style="text-align:right;color:#ffd966;">Total Realisasi</th>
                    <th style="text-align:right;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rows)): ?>
                <tr><td colspan="9" style="text-align:center;color:rgba(255,255,255,0.35);padding:24px;"><i class="fas fa-database"></i> Belum ada data. Data diambil dari Target SPM.</td></tr>
                <?php else: ?>
                <?php foreach ($rows as $i => $r): ?>
                <tr <?= $editRow && (int)$editRow['id']===(int)$r['id'] ? 'style="background:rgba(255,215,0,0.08);"' : '' ?>>
                    <td><?= $offset+$i+1 ?></td>
                    <td><?= htmlspecialchars(mb_strimwidth($r['jenis_layanan'],0,50,'…')) ?></td>
                    <td style="text-align:center;"><?= htmlspecialchars($r['sub_no'] ?? '') ?></td>
                    <td><?= htmlspecialchars(mb_strimwidth($r['indikator'],0,60,'…')) ?></td>
                    <td><?= htmlspecialchars($r['satuan']!==''?$r['satuan']:'-') ?></td>
                    <td><span class="badge"><?= htmlspecialchars($r['periode']) ?></span></td>
                    <td class="num"><b><?= SpmLib::fmt($r['total_target'] ?? $r['total']) ?></b></td>
                    <td class="num realisasi"><b><?= SpmLib::fmt($r['total_realisasi'] ?? 0) ?></b></td>
                    <td>
                        <div class="row-actions">
                            <a class="btn-sm" href="spm_realisasi.php?edit=<?= (int)$r['id'] ?>&periode=<?= urlencode($fPeriode) ?>&q=<?= urlencode($fSearch) ?>&page=<?= $page ?>#formCard"><i class="fas fa-pen"></i> Edit Realisasi</a>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Hapus SEMUA realisasi baris ini?\n\n<?= htmlspecialchars(addslashes(mb_strimwidth($r['indikator'],0,50,'…'))) ?>\n\nTarget TIDAK akan terhapus.')">
                                <input type="hidden" name="action" value="delete_realisasi">
                                <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                                <input type="hidden" name="periode_back" value="<?= htmlspecialchars($fPeriode) ?>">
                                <button type="submit" class="btn-sm del"><i class="fas fa-trash"></i> Hapus Realisasi</button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        </div>
        <?php if ($totalPages>1): ?>
        <div class="pager">
            <?php if ($page>1): ?><a href="spm_realisasi.php?periode=<?= urlencode($fPeriode) ?>&q=<?= urlencode($fSearch) ?>&page=<?= $page-1 ?>">‹ Prev</a><?php endif; ?>
            <span>Halaman <?= $page ?> / <?= $totalPages ?></span>
            <?php if ($page<$totalPages): ?><a href="spm_realisasi.php?periode=<?= urlencode($fPeriode) ?>&q=<?= urlencode($fSearch) ?>&page=<?= $page+1 ?>">Next ›</a><?php endif; ?>
        </div>
        <?php endif; ?>
        <div class="hint" style="margin-top:10px;">Tabel ini <b>hanya mengelola realisasi</b>. Untuk mengelola target, buka <a href="spm.php" style="color:#72e8ff;">SPM Target</a>. Menghapus realisasi tidak menghapus target.</div>
    </div>
</div>
<script>
(function(){
    const card=document.getElementById('formCard');
    if(card && location.hash==='#formCard') card.scrollIntoView({behavior:'smooth'});
    // jika ada param edit, tampilkan
    const params=new URLSearchParams(location.search);
    if(params.get('edit')){ card.style.display=''; setTimeout(()=>card.scrollIntoView({behavior:'smooth'}),150); }
})();
</script>
</body>
</html>
