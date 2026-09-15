<?php
require_once '../config.php';
requireLogin();
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../App/Services/SpmLib.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$msg = $_GET['msg'] ?? '';
$importResult = null;
if (session_status() === PHP_SESSION_NONE) session_start();
if (isset($_SESSION['spm_import'])) {
    $importResult = $_SESSION['spm_import'];
    unset($_SESSION['spm_import']);
}

$kecMap = SpmLib::kecMap($config);
$kecOrder = SpmLib::KEC_ORDER; // kanonik; yang tidak ada di DB di-skip di form
$kecForm = array_values(array_filter($kecOrder, function ($k) use ($kecMap) {
    return isset($kecMap[$k]);
}));
$periods = SpmLib::periods($config);
$sasaranList = SpmLib::sasaranList($config);

// daftar jenis layanan existing untuk datalist
$layananList = [];
$qL = mysqli_query($config, "SELECT DISTINCT jenis_layanan FROM tbl_spm WHERE aktif='Y' ORDER BY jenis_layanan LIMIT 100");
if ($qL) while ($r = mysqli_fetch_assoc($qL)) $layananList[] = $r['jenis_layanan'];
$satuanList = [];
$qS = mysqli_query($config, "SELECT DISTINCT satuan FROM tbl_spm WHERE aktif='Y' AND satuan<>'' ORDER BY satuan LIMIT 60");
if ($qS) while ($r = mysqli_fetch_assoc($qS)) $satuanList[] = $r['satuan'];

/**
 * Cari parent (baris induk: sub_no kosong) untuk layanan+periode yang sama.
 * Dipakai form manual agar struktur parent/child sama dengan hasil import.
 */
function spmResolveParent($config, $periode, $layanan, $subNo, $excludeId = 0)
{
    if (trim((string)$subNo) === '' || trim((string)$subNo) === '-') return null;
    $pEsc = mysqli_real_escape_string($config, $periode);
    $lEsc = mysqli_real_escape_string($config, $layanan);
    $ex = (int)$excludeId;
    $q = mysqli_query($config, "SELECT id FROM tbl_spm WHERE periode='$pEsc' AND jenis_layanan='$lEsc'
        AND (sub_no IS NULL OR sub_no='' OR sub_no='-') AND aktif='Y' AND id<>$ex ORDER BY urutan, id LIMIT 1");
    $r = $q ? mysqli_fetch_assoc($q) : null;
    return $r ? (int)$r['id'] : null;
}

// ============================================================
// POST: tambah / edit / hapus / import
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // ---------- TAMBAH MANUAL (UPSERT by bkey: sudah ada -> update+revive) ----------
    if ($action === 'add') {
        $tahun = (int)($_POST['tahun'] ?? date('Y'));
        $periode = trim($_POST['periode'] ?? '');
        if ($periode === '') $periode = (string)$tahun;
        $layanan = mb_substr(trim($_POST['jenis_layanan'] ?? ''), 0, 255);
        $subNo = mb_substr(trim($_POST['sub_no'] ?? ''), 0, 10);
        $indikator = mb_substr(trim($_POST['indikator'] ?? ''), 0, 500);
        $satuan = mb_substr(trim($_POST['satuan'] ?? ''), 0, 50);
        $sasaran = mb_substr(trim($_POST['sasaran'] ?? ''), 0, 255);
        $urutan = (int)($_POST['urutan'] ?? 0);
        $tmRaw = trim($_POST['total_manual'] ?? '');
        $totalManual = $tmRaw === '' ? null : SpmLib::parseNumber($tmRaw, true);

        if ($layanan === '' || $indikator === '') {
            header('Location: spm.php?msg=invalid'); exit;
        }
        $bkey = SpmLib::bkey($periode, $tahun, $layanan, $subNo, $indikator, $satuan, $sasaran);
        $bEsc = mysqli_real_escape_string($config, $bkey);
        $qDup = mysqli_query($config, "SELECT id, urutan FROM tbl_spm WHERE bkey='$bEsc' LIMIT 1");
        $dup = $qDup ? mysqli_fetch_assoc($qDup) : null;
        $parentId = spmResolveParent($config, $periode, $layanan, $subNo, $dup ? (int)$dup['id'] : 0);
        $pIdSql = $parentId === null ? "NULL" : (int)$parentId;
        $pEsc = mysqli_real_escape_string($config, $periode);
        $lEsc = mysqli_real_escape_string($config, $layanan);
        $snEsc = mysqli_real_escape_string($config, $subNo);
        $iEsc = mysqli_real_escape_string($config, $indikator);
        $sEsc = mysqli_real_escape_string($config, $satuan);
        $sasEsc = mysqli_real_escape_string($config, $sasaran);
        $tmSql = $totalManual === null ? "NULL" : ("'" . (float)$totalManual . "'");
        if ($dup) {
            // SUDAH ADA (termasuk yang pernah dihapus) -> UPDATE + revive, bukan insert duplikat
            $id = (int)$dup['id'];
            if ($urutan <= 0) $urutan = (int)$dup['urutan'];
            mysqli_query($config, "UPDATE tbl_spm SET jenis_layanan='$lEsc', sub_no='$snEsc', parent_id=$pIdSql,
                indikator='$iEsc', satuan='$sEsc', sasaran='$sasEsc', tahun=$tahun, periode='$pEsc',
                urutan=$urutan, total_manual=$tmSql, aktif='Y', updated_at=NOW() WHERE id=$id");
            mysqli_query($config, "UPDATE tbl_spm_target SET aktif='Y' WHERE id_spm=$id");
            $msgOut = 'revived';
        } else {
            if ($urutan <= 0) {
                $pEsc0 = mysqli_real_escape_string($config, $periode);
                $qU = mysqli_query($config, "SELECT COALESCE(MAX(urutan),0)+1 m FROM tbl_spm WHERE periode='$pEsc0' AND aktif='Y'");
                $urutan = $qU ? (int)mysqli_fetch_assoc($qU)['m'] : 1;
            }
            $ok = mysqli_query($config, "INSERT INTO tbl_spm
                (jenis_layanan, indikator, sub_no, parent_id, satuan, sasaran, tahun, periode, urutan, total_manual, bkey, aktif)
                VALUES ('$lEsc','$iEsc','$snEsc',$pIdSql,'$sEsc','$sasEsc',$tahun,'$pEsc',$urutan,$tmSql,'$bEsc','Y')");
            if (!$ok) {
                // 1062: tertabrak unique (balapan) -> fallback update
                $qDup2 = mysqli_query($config, "SELECT id FROM tbl_spm WHERE bkey='$bEsc' LIMIT 1");
                $dup2 = $qDup2 ? mysqli_fetch_assoc($qDup2) : null;
                if ($dup2) { header('Location: spm.php?periode=' . urlencode($periode) . '&msg=revived'); exit; }
                header('Location: spm.php?msg=error'); exit;
            }
            $id = (int)mysqli_insert_id($config);
            $msgOut = 'added';
        }
        // target kecamatan (UPSERT per id_spm+id_kecamatan)
        $tg = $_POST['target'] ?? [];
        foreach ($kecForm as $kn) {
            if (!isset($kecMap[$kn])) continue;
            $kid = (int)$kecMap[$kn];
            $v = (float)SpmLib::parseNumber($tg[$kn] ?? 0);
            mysqli_query($config, "INSERT INTO tbl_spm_target (id_spm, id_kecamatan, target, aktif)
                VALUES ($id,$kid,'$v','Y') ON DUPLICATE KEY UPDATE target='$v', aktif='Y', updated_at=NOW()");
        }
        if ($sasaran !== '') mysqli_query($config, "INSERT IGNORE INTO tbl_spm_sasaran (nama, aktif) VALUES ('$sasEsc','Y')");
        header('Location: spm.php?periode=' . urlencode($periode) . '&msg=' . $msgOut); exit;
    }

    // ---------- EDIT (bkey dihitung ulang; tabrakan dengan baris lain ditolak) ----------
    if ($action === 'edit') {
        $id = (int)($_POST['id'] ?? 0);
        $cek = $id ? mysqli_query($config, "SELECT * FROM tbl_spm WHERE id=$id AND aktif='Y' LIMIT 1") : null;
        $old = $cek ? mysqli_fetch_assoc($cek) : null;
        if (!$old) { header('Location: spm.php?msg=notfound'); exit; }
        $tahun = (int)($_POST['tahun'] ?? $old['tahun']);
        $periode = trim($_POST['periode'] ?? $old['periode']);
        if ($periode === '') $periode = $old['periode'];
        $layanan = mb_substr(trim($_POST['jenis_layanan'] ?? ''), 0, 255);
        $subNo = mb_substr(trim($_POST['sub_no'] ?? ''), 0, 10);
        $indikator = mb_substr(trim($_POST['indikator'] ?? ''), 0, 500);
        $satuan = mb_substr(trim($_POST['satuan'] ?? ''), 0, 50);
        $sasaran = mb_substr(trim($_POST['sasaran'] ?? ''), 0, 255);
        $urutan = (int)($_POST['urutan'] ?? $old['urutan']);
        $tmRaw = trim($_POST['total_manual'] ?? '');
        $totalManual = $tmRaw === '' ? null : SpmLib::parseNumber($tmRaw, true);
        if ($layanan === '' || $indikator === '') { header('Location: spm.php?msg=invalid'); exit; }
        $bkey = SpmLib::bkey($periode, $tahun, $layanan, $subNo, $indikator, $satuan, $sasaran);
        $bEsc = mysqli_real_escape_string($config, $bkey);
        $qCol = mysqli_query($config, "SELECT id FROM tbl_spm WHERE bkey='$bEsc' AND id<>$id LIMIT 1");
        if ($qCol && mysqli_num_rows($qCol) > 0) {
            header('Location: spm.php?periode=' . urlencode($old['periode']) . '&msg=exists'); exit;
        }
        $parentId = spmResolveParent($config, $periode, $layanan, $subNo, $id);
        if ($parentId === $id) $parentId = null;
        $pIdSql = $parentId === null ? "NULL" : (int)$parentId;
        $pEsc = mysqli_real_escape_string($config, $periode);
        $lEsc = mysqli_real_escape_string($config, $layanan);
        $snEsc = mysqli_real_escape_string($config, $subNo);
        $iEsc = mysqli_real_escape_string($config, $indikator);
        $sEsc = mysqli_real_escape_string($config, $satuan);
        $sasEsc = mysqli_real_escape_string($config, $sasaran);
        $tmSql = $totalManual === null ? "NULL" : ("'" . (float)$totalManual . "'");
        mysqli_query($config, "UPDATE tbl_spm SET jenis_layanan='$lEsc', indikator='$iEsc', sub_no='$snEsc', parent_id=$pIdSql,
            satuan='$sEsc', sasaran='$sasEsc', tahun=$tahun, periode='$pEsc', bkey='$bEsc',
            urutan=$urutan, total_manual=$tmSql, updated_at=NOW() WHERE id=$id");
        $tg = $_POST['target'] ?? [];
        foreach ($kecForm as $kn) {
            if (!isset($kecMap[$kn]) || !isset($tg[$kn])) continue;
            $kid = (int)$kecMap[$kn];
            $v = (float)SpmLib::parseNumber($tg[$kn]);
            mysqli_query($config, "INSERT INTO tbl_spm_target (id_spm, id_kecamatan, target, aktif)
                VALUES ($id,$kid,'$v','Y') ON DUPLICATE KEY UPDATE target='$v', aktif='Y', updated_at=NOW()");
        }
        if ($sasaran !== '') mysqli_query($config, "INSERT IGNORE INTO tbl_spm_sasaran (nama, aktif) VALUES ('$sasEsc','Y')");
        header('Location: spm.php?periode=' . urlencode($periode) . '&msg=updated'); exit;
    }

    // ---------- HAPUS (soft delete; target ikut nonaktif, FK aman) ----------
    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            mysqli_query($config, "UPDATE tbl_spm SET aktif='N', updated_at=NOW() WHERE id=$id");
            mysqli_query($config, "UPDATE tbl_spm_target SET aktif='N', updated_at=NOW() WHERE id_spm=$id");
        }
        header('Location: spm.php?periode=' . urlencode($_POST['periode_back'] ?? '') . '&msg=deleted'); exit;
    }

    // ---------- HAPUS SEMUA (permanen, transaksi, child dulu) ----------
    if ($action === 'delete_all') {
        mysqli_begin_transaction($config);
        try {
            // Hapus child dulu agar tidak error FK (jika ada), baru parent. Tanpa SET FOREIGN_KEY_CHECKS=0.
            // Hapus permanen (DELETE), bukan soft delete, sesuai permintaan.
            if (!mysqli_query($config, "DELETE FROM tbl_spm_target")) {
                throw new Exception(mysqli_error($config));
            }
            if (!mysqli_query($config, "DELETE FROM tbl_spm")) {
                throw new Exception(mysqli_error($config));
            }
            // tbl_spm_sasaran dibiarkan (master sasaran), tidak termasuk dataset SPM yang wajib dihapus.
            // Jika ingin kosongkan juga, uncomment: mysqli_query($config, "DELETE FROM tbl_spm_sasaran");
            mysqli_commit($config);
            header('Location: spm.php?msg=deleted_all'); exit;
        } catch (Throwable $e) {
            mysqli_rollback($config);
            // Simpan error ke session untuk ditampilkan
            if (session_status() === PHP_SESSION_NONE) session_start();
            $_SESSION['spm_delete_all_error'] = $e->getMessage();
            header('Location: spm.php?msg=delete_all_failed'); exit;
        }
    }

    // ---------- IMPORT EXCEL (UPSERT by bkey + transaksi per sheet) ----------
    if ($action === 'import') {
        $tahunOverride = (int)($_POST['tahun'] ?? 0);
        $fullSync = !empty($_POST['full_sync']);
        $res = ['inserted' => 0, 'updated' => 0, 'revived' => 0, 'failed' => 0,
            'excel_rows' => 0, 'skipped_empty' => 0, 'targets_ins' => 0, 'targets_upd' => 0,
            'sasaran_new' => 0, 'synced_off' => 0, 'errors' => [], 'warnings' => [], 'sheets' => []];
        if (!isset($_FILES['file_excel']) || $_FILES['file_excel']['error'] !== UPLOAD_ERR_OK) {
            $res['failed']++;
            $res['errors'][] = 'File tidak ter-upload. Pilih file .xlsx yang valid.';
        } else {
            $name = $_FILES['file_excel']['name'] ?? '';
            $tmp = $_FILES['file_excel']['tmp_name'] ?? '';
            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            if ($ext !== 'xlsx') {
                $res['failed']++;
                $res['errors'][] = "Ekstensi .$ext ditolak. Hanya file .xlsx yang diterima.";
            } elseif ($_FILES['file_excel']['size'] > 10 * 1024 * 1024) {
                $res['failed']++;
                $res['errors'][] = 'Ukuran file melebihi 10 MB.';
            } else {
                try {
                    $reader = IOFactory::createReader('Xlsx');
                    $reader->setReadDataOnly(false);
                    $wb = $reader->load($tmp);
                    $sheetNames = $wb->getSheetNames();
                    // proses sheet yang cocok dengan periode dikenal; bila tidak ada yang cocok, proses semua sheet
                    $matched = array_values(array_intersect($sheetNames, SpmLib::PERIODS));
                    $toProcess = !empty($matched) ? $matched : $sheetNames;
                    SpmLib::ensureSasaranTable($config); // di luar transaksi (DDL implicit-commit)
                    foreach ($toProcess as $sn) {
                        $sheet = $wb->getSheetByName($sn);
                        if (!$sheet) continue;
                        $periode = in_array($sn, SpmLib::PERIODS, true) ? $sn : trim($sn);
                        $tahun = $tahunOverride > 0 ? $tahunOverride : SpmLib::yearOf($periode);
                        $st = SpmLib::importSheet($config, $sheet, $periode, $tahun, ['fullSync' => $fullSync]);
                        foreach (['inserted', 'updated', 'revived', 'failed', 'excel_rows', 'skipped_empty', 'targets_ins', 'targets_upd', 'sasaran_new', 'synced_off'] as $k) {
                            $res[$k] += (int)($st[$k] ?? 0);
                        }
                        $res['sheets'][] = $st;
                        foreach ($st['errors'] as $e) {
                            if (count($res['errors']) < 30) $res['errors'][] = $e;
                        }
                        foreach (($st['warnings'] ?? []) as $w) {
                            if (count($res['warnings']) < 15) $res['warnings'][] = "Sheet '{$st['sheet']}': $w";
                        }
                    }
                    if (empty($toProcess)) {
                        $res['failed']++;
                        $res['errors'][] = 'File tidak memiliki sheet yang dapat dibaca.';
                    }
                } catch (Throwable $e) {
                    $res['failed']++;
                    $res['errors'][] = 'Gagal membaca file: ' . $e->getMessage();
                }
            }
        }
        $_SESSION['spm_import'] = $res;
        header('Location: spm.php?msg=imported'); exit;
    }
}

// ============================================================
// LIST + FILTER
// ============================================================
$fPeriode = $_GET['periode'] ?? '';
$fSearch = trim($_GET['q'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 50;
$where = ["s.aktif='Y'"];
if ($fPeriode !== '') $where[] = "s.periode='" . mysqli_real_escape_string($config, $fPeriode) . "'";
if ($fSearch !== '') {
    $qs = mysqli_real_escape_string($config, $fSearch);
    $where[] = "(s.jenis_layanan LIKE '%$qs%' OR s.indikator LIKE '%$qs%' OR s.satuan LIKE '%$qs%')";
}
$whereSql = implode(' AND ', $where);
$qC = mysqli_query($config, "SELECT COUNT(*) c FROM tbl_spm s WHERE $whereSql");
$totalRows = $qC ? (int)mysqli_fetch_assoc($qC)['c'] : 0;
$totalPages = max(1, (int)ceil($totalRows / $perPage));
if ($page > $totalPages) $page = $totalPages;
$offset = ($page - 1) * $perPage;

$rows = [];
$q = mysqli_query($config, "SELECT s.* FROM tbl_spm s WHERE $whereSql ORDER BY s.periode, s.urutan, s.id LIMIT $perPage OFFSET $offset");
if ($q) {
    while ($r = mysqli_fetch_assoc($q)) {
        $sid = (int)$r['id'];
        $r['targets'] = [];
        foreach ($kecForm as $kn) $r['targets'][$kn] = 0;
        $qt = mysqli_query($config, "SELECT k.nama_kecamatan, t.target FROM tbl_spm_target t
            JOIN tbl_kecamatan k ON k.id_kecamatan=t.id_kecamatan
            WHERE t.id_spm=$sid AND t.aktif='Y'");
        if ($qt) while ($t = mysqli_fetch_assoc($qt)) {
            $kn = SpmLib::normKec($t['nama_kecamatan']);
            if (isset($r['targets'][$kn])) $r['targets'][$kn] = (float)$t['target'];
        }
        $sum = array_sum($r['targets']);
        $r['total'] = ($r['total_manual'] !== null && $r['total_manual'] !== '') ? (float)$r['total_manual'] : $sum;
        $rows[] = $r;
    }
}

// row untuk form edit
$editRow = null;
if (isset($_GET['edit'])) {
    $eid = (int)$_GET['edit'];
    $qe = mysqli_query($config, "SELECT * FROM tbl_spm WHERE id=$eid LIMIT 1");
    $editRow = $qe ? mysqli_fetch_assoc($qe) : null;
    if ($editRow) {
        $editRow['targets'] = [];
        foreach ($kecForm as $kn) $editRow['targets'][$kn] = 0;
        $qt = mysqli_query($config, "SELECT k.nama_kecamatan, t.target FROM tbl_spm_target t
            JOIN tbl_kecamatan k ON k.id_kecamatan=t.id_kecamatan WHERE t.id_spm=$eid AND t.aktif='Y'");
        if ($qt) while ($t = mysqli_fetch_assoc($qt)) {
            $kn = SpmLib::normKec($t['nama_kecamatan']);
            if (isset($editRow['targets'][$kn])) $editRow['targets'][$kn] = (float)$t['target'];
        }
    }
}

$username = $_SESSION['admin_username'] ?? 'Admin';
$counts = SpmLib::counts($config);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola SPM - Admin DKK</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Poppins',sans-serif; background:#061426; min-height:100vh; display:flex; color:#fff; }
        .sidebar { width:260px; min-height:100vh; background:rgba(255,255,255,0.04); border-right:1px solid rgba(255,255,255,0.06); padding:30px 20px; flex-shrink:0; position:sticky; top:0; height:100vh; overflow-y:auto; }
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
        .sidebar-menu .logout { margin-top:30px; border-top:1px solid rgba(255,255,255,0.06); padding-top:20px; }
        .sidebar-menu .logout a { color:rgba(255,82,82,0.7); }
        .main-content { flex:1; padding:30px 40px; max-width:1400px; }
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
        .btn-success { border-color:rgba(76,175,80,0.4); background:rgba(76,175,80,0.12); color:#81c784; }
        .btn-warn { border-color:rgba(255,193,7,0.4); background:rgba(255,193,7,0.1); color:#ffd54f; }
        .btn-danger { border-color:rgba(255,82,82,0.4); background:rgba(255,82,82,0.1); color:#ff8a80; }
        .alert { padding:14px 20px; border-radius:12px; margin-bottom:20px; font-size:13px; }
        .alert-success { background:rgba(0,212,255,0.12); border:1px solid rgba(0,212,255,0.2); color:#72e8ff; }
        .alert-error { background:rgba(255,82,82,0.12); border:1px solid rgba(255,82,82,0.2); color:#ff8a80; }
        .import-box { background:rgba(255,193,7,0.05); border:1px dashed rgba(255,193,7,0.35); border-radius:12px; padding:14px 16px; font-size:12px; color:rgba(255,255,255,0.65); }
        .import-box ul { margin:8px 0 0 18px; }
        .form-grid { display:grid; grid-template-columns:repeat(2,1fr); gap:14px; }
        .form-grid .full { grid-column:1 / -1; }
        .fg label { display:block; font-size:12px; color:#87e3ff; font-weight:600; margin-bottom:5px; }
        .fg input, .fg select, .fg textarea { width:100%; padding:9px 13px; border-radius:9px; border:1px solid rgba(255,255,255,0.12); background:rgba(255,255,255,0.06); color:#fff; font-size:13px; font-family:inherit; }
        .fg input:focus, .fg select:focus, .fg textarea:focus { outline:none; border-color:#00d4ff; }
        .fg select option, .fg input[list] { color:#fff; }
        .fg select option { color:#111; }
        .kec-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:10px; }
        .kec-grid .fg input { text-align:right; }
        table.data { width:100%; border-collapse:collapse; }
        table.data th { text-align:left; padding:10px 8px; color:#87e3ff; font-weight:600; font-size:12px; border-bottom:2px solid rgba(255,255,255,0.08); white-space:nowrap; }
        table.data td { padding:9px 8px; border-bottom:1px solid rgba(255,255,255,0.05); font-size:12px; vertical-align:top; }
        table.data td.num { text-align:right; white-space:nowrap; font-variant-numeric:tabular-nums; }
        .badge { padding:3px 10px; border-radius:20px; background:rgba(0,212,255,0.15); color:#72e8ff; font-size:10px; font-weight:700; white-space:nowrap; }
        .row-actions { display:flex; gap:6px; justify-content:flex-end; }
        .btn-sm { padding:5px 12px; border-radius:7px; font-size:11px; font-weight:600; cursor:pointer; text-decoration:none; border:1px solid rgba(0,212,255,0.3); background:rgba(0,212,255,0.12); color:#72e8ff; font-family:inherit; }
        .btn-sm.del { border-color:rgba(255,82,82,0.35); background:rgba(255,82,82,0.1); color:#ff8a80; }
        .filter-bar { display:flex; gap:10px; flex-wrap:wrap; align-items:end; margin-bottom:16px; }
        .filter-bar .fg { min-width:180px; }
        .pager { display:flex; gap:8px; align-items:center; justify-content:center; margin-top:16px; font-size:12px; color:rgba(255,255,255,0.5); }
        .pager a { color:#72e8ff; text-decoration:none; padding:6px 12px; border:1px solid rgba(0,212,255,0.25); border-radius:8px; }
        .hint { font-size:11px; color:rgba(255,255,255,0.4); margin-top:4px; }
        @media (max-width:900px) { .sidebar{display:none;} .main-content{padding:20px;} .form-grid{grid-template-columns:1fr;} .kec-grid{grid-template-columns:repeat(2,1fr);} }
    </style>
</head>
<body>
<div class="sidebar">
    <div class="sidebar-brand">
        <img src="../../assets/img/kabupaten.png" alt="Logo">
        <h2>Portal DKK<br><small>Dashboard Admin</small></h2>
    </div>
    <ul class="sidebar-menu">
        <li><a href="../index.php"><i class="fas fa-chart-pie"></i> Dashboard</a></li>
        <li><a href="fasyankes.php"><i class="fas fa-hospital"></i> Fasyankes</a></li>
        <li><a href="faskes_rekap.php"><i class="fas fa-table"></i> Rekap Fasyankes</a></li>
        <li><a href="sdmk.php"><i class="fas fa-hospital-user"></i> SDMK</a></li>
        <li><a href="kecamatan.php"><i class="fas fa-map"></i> Kecamatan</a></li>
        <li><a href="penyakit.php"><i class="fas fa-disease"></i> Penyakit</a></li>
        <li><a href="spm.php" class="active"><i class="fas fa-chart-pie"></i> SPM Target</a></li>
        <li><a href="spm_realisasi.php"><i class="fas fa-chart-line"></i> SPM Realisasi</a></li>
        <li><a href="portal_info.php"><i class="fas fa-circle-info"></i> Informasi Portal</a></li>
        <li class="logout"><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
    </ul>
</div>

<div class="main-content">
    <div class="page-header">
        <div>
            <h1>Kelola SPM</h1>
            <p>Standar Pelayanan Minimal — input manual &amp; import Excel masuk ke database yang sama</p>
        </div>
        <a href="../index.php" class="back-link"><i class="fas fa-arrow-left"></i> Kembali</a>
    </div>

    <?php if ($msg === 'added'): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> Data SPM berhasil ditambahkan.</div><?php endif; ?>
    <?php if ($msg === 'revived'): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> Data sudah ada di database (cocok business key) — diperbarui, bukan diduplikat.</div><?php endif; ?>
    <?php if ($msg === 'exists'): ?><div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> Data dengan identitas yang sama sudah ada (business key duplikat). Perubahan dibatalkan.</div><?php endif; ?>
    <?php if ($msg === 'updated'): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> Data SPM berhasil diperbarui. Tampilan public otomatis mengikuti.</div><?php endif; ?>
    <?php if ($msg === 'deleted'): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> Data SPM berhasil dihapus (soft delete).</div><?php endif; ?>
    <?php if ($msg === 'deleted_all'): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> Semua data SPM berhasil dihapus.</div><?php endif; ?>
    <?php if ($msg === 'delete_all_failed'): ?><div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> Gagal menghapus semua data SPM: <?= htmlspecialchars($_SESSION['spm_delete_all_error'] ?? 'Unknown error') ?><?php unset($_SESSION['spm_delete_all_error']); ?></div><?php endif; ?>
    <?php if ($msg === 'invalid'): ?><div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> Jenis Layanan dan Indikator wajib diisi.</div><?php endif; ?>
    <?php if ($msg === 'error' || $msg === 'notfound'): ?><div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> Operasi gagal. Silakan coba lagi.</div><?php endif; ?>

    <?php if ($msg === 'imported' && $importResult): ?>
    <div class="alert <?= ($importResult['failed'] > 0 && ($importResult['inserted'] + $importResult['updated']) === 0) ? 'alert-error' : 'alert-success' ?>">
        <i class="fas fa-file-import"></i>
        <b>Hasil Import (upsert by business key):</b>
        Total baris Excel: <?= (int)$importResult['excel_rows'] ?> |
        Data baru: <?= (int)$importResult['inserted'] ?> |
        Data diperbarui: <?= (int)$importResult['updated'] ?> (termasuk <?= (int)$importResult['revived'] ?> diaktifkan kembali) |
        Gagal: <?= (int)$importResult['failed'] ?> |
        Baris kosong dilewati: <?= (int)$importResult['skipped_empty'] ?>
        <br><small>Target kecamatan: <?= (int)$importResult['targets_ins'] ?> baru / <?= (int)$importResult['targets_upd'] ?> diperbarui.
        Sasaran baru: <?= (int)$importResult['sasaran_new'] ?>.
        <?php if (!empty($importResult['synced_off'])): ?>Full Sync: <?= (int)$importResult['synced_off'] ?> baris tak ada di file dinonaktifkan.<?php endif; ?></small>
        <?php foreach (($importResult['sheets'] ?? []) as $sh): ?>
            <br><small>• Sheet "<?= htmlspecialchars($sh['sheet']) ?>" → periode "<?= htmlspecialchars($sh['periode']) ?>": Excel <?= (int)$sh['excel_rows'] ?> baris, +<?= (int)$sh['inserted'] ?> baru / ~<?= (int)$sh['updated'] ?> update (revive <?= (int)$sh['revived'] ?>) / ✗<?= (int)$sh['failed'] ?></small>
        <?php endforeach; ?>
        <?php if (!empty($importResult['warnings'])): ?>
            <br><small><b>Peringatan struktur:</b></small>
            <ul style="margin:4px 0 0 18px;">
            <?php foreach ($importResult['warnings'] as $w): ?>
                <li><small><?= htmlspecialchars($w) ?></small></li>
            <?php endforeach; ?>
            </ul>
        <?php endif; ?>
        <?php if (!empty($importResult['errors'])): ?>
            <ul style="margin:8px 0 0 18px;">
            <?php foreach ($importResult['errors'] as $e): ?>
                <li><?= htmlspecialchars($e) ?></li>
            <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- TOOLBAR -->
    <div class="card">
        <h3><i class="fas fa-tools" style="color:#00d4ff;margin-right:8px;"></i>Aksi SPM</h3>
        <div class="toolbar">
            <button type="button" class="btn btn-primary" onclick="document.getElementById('formCard').scrollIntoView({behavior:'smooth'})"><i class="fas fa-plus"></i> Tambah Data</button>
            <a class="btn btn-success" href="spm_export.php<?= $fPeriode !== '' ? '?periode=' . urlencode($fPeriode) : '' ?>"><i class="fas fa-file-export"></i> Export Excel</a>
            <a class="btn" href="spm_template.php"><i class="fas fa-file-download"></i> Download Template</a>
            <a class="btn btn-warn" href="spm_sasaran.php"><i class="fas fa-bullseye"></i> Kelola Sasaran</a>
            <a class="btn" href="spm_realisasi.php" style="border-color:rgba(255,215,0,0.35);background:rgba(255,215,0,0.1);color:#ffd966;"><i class="fas fa-chart-line"></i> Realisasi SPM</a>
            <button type="button" class="btn btn-danger" id="btnHapusSemua" onclick="openDeleteAllModal()"><i class="fas fa-trash-alt"></i> Hapus Semua</button>
        </div>
        <form method="POST" enctype="multipart/form-data" style="display:flex;gap:10px;flex-wrap:wrap;align-items:end;">
            <input type="hidden" name="action" value="import">
            <div class="fg" style="min-width:260px;">
                <label>Import Excel (.xlsx)</label>
                <input type="file" name="file_excel" accept=".xlsx" required>
            </div>
            <div class="fg" style="min-width:140px;">
                <label>Tahun (opsional)</label>
                <input type="number" name="tahun" placeholder="otomatis dari sheet" min="2000" max="2100">
            </div>
            <label class="fg" style="min-width:220px;font-size:12px;color:rgba(255,255,255,0.65);display:flex;gap:8px;align-items:flex-start;">
                <input type="checkbox" name="full_sync" value="1" style="width:auto;margin-top:3px;" onchange="if(this.checked){if(!confirm('FULL SYNC: baris SPM pada periode yang diproses tetapi TIDAK ADA di file akan DINONAKTIFKAN.\n\nLanjutkan?')){this.checked=false;}}">
                <span>Full Sync (nonaktifkan data periode ini yang tidak ada di file)<br><small style="color:rgba(255,255,255,0.4);">Default OFF = upsert biasa (tidak menghapus apa pun).</small></span>
            </label>
            <button type="submit" class="btn btn-warn" onclick="return confirm('Import file Excel ke database? Baris yang sama (business key) akan di-UPDATE, baris baru di-INSERT. Tidak membuat duplikat.')"><i class="fas fa-file-import"></i> Import</button>
        </form>
        <div class="import-box" style="margin-top:12px;">
            Import = <b>upsert/sinkronisasi</b> berdasarkan business key (periode + tahun + layanan + sub-no + indikator + satuan + sasaran, dinormalisasi).
            Baris yang cocok akan <b>diperbarui</b> (termasuk yang pernah dihapus → aktif kembali), sisanya <b>ditambah</b>.
            Import file yang sama berulang kali <b>tidak menambah jumlah data</b>.
            <ul>
                <li>Hanya file <b>.xlsx</b> (maks 10 MB). Sheet dibaca: <b>Perubahan Target 2026</b>, <b>2026</b>, <b>2027</b>.</li>
                <li>Satu sheet = satu transaksi database (gagal → rollback, tidak setengah masuk).</li>
                <li>Template yang didownload dapat diisi lalu di-upload kembali.</li>
            </ul>
        </div>
    </div>

    <!-- FORM TAMBAH / EDIT -->
    <div class="card" id="formCard">
        <h3><i class="fas <?= $editRow ? 'fa-pen' : 'fa-plus-circle' ?>" style="color:#00d4ff;margin-right:8px;"></i><?= $editRow ? 'Edit Data SPM #' . (int)$editRow['id'] : 'Tambah Data SPM (Manual)' ?></h3>
        <form method="POST">
            <input type="hidden" name="action" value="<?= $editRow ? 'edit' : 'add' ?>">
            <?php if ($editRow): ?><input type="hidden" name="id" value="<?= (int)$editRow['id'] ?>"><?php endif; ?>
            <div class="form-grid">
                <div class="fg">
                    <label>Tahun</label>
                    <input type="number" name="tahun" value="<?= htmlspecialchars($editRow['tahun'] ?? date('Y')) ?>" min="2000" max="2100" required>
                </div>
                <div class="fg">
                    <label>Sheet / Periode</label>
                    <input name="periode" list="datalistPeriode" value="<?= htmlspecialchars($editRow['periode'] ?? ($fPeriode !== '' ? $fPeriode : '2026')) ?>" required>
                    <datalist id="datalistPeriode">
                        <?php foreach ($periods as $p): ?><option value="<?= htmlspecialchars($p) ?>"><?php endforeach; ?>
                    </datalist>
                </div>
                <div class="fg full">
                    <label>Jenis Layanan</label>
                    <input name="jenis_layanan" list="datalistLayanan" value="<?= htmlspecialchars($editRow['jenis_layanan'] ?? '') ?>" required placeholder="cth: Pelayanan Kesehatan Ibu Hamil">
                    <datalist id="datalistLayanan">
                        <?php foreach ($layananList as $l): ?><option value="<?= htmlspecialchars($l) ?>"><?php endforeach; ?>
                    </datalist>
                </div>
                <div class="fg full">
                    <label>Indikator / Sub-Indikator</label>
                    <textarea name="indikator" rows="2" required placeholder="cth: Jumlah yang Harus Dilayani / Vaksin Tetanus Difteri (Td)"><?= htmlspecialchars($editRow['indikator'] ?? '') ?></textarea>
                </div>
                <div class="fg">
                    <label>Sub-No (kosongkan untuk baris induk)</label>
                    <input name="sub_no" value="<?= htmlspecialchars($editRow['sub_no'] ?? '') ?>" placeholder="cth: 1, 2, ... (induk dikosongkan)" maxlength="10">
                    <div class="hint">Baris anak otomatis terhubung ke baris induk se-layanan (parent/child seperti Excel).</div>
                </div>
                <div class="fg">
                    <label>Satuan</label>
                    <input name="satuan" list="datalistSatuan" value="<?= htmlspecialchars($editRow['satuan'] ?? '') ?>" placeholder="cth: Orang, Paket, Tablet">
                    <datalist id="datalistSatuan">
                        <?php foreach ($satuanList as $s): ?><option value="<?= htmlspecialchars($s) ?>"><?php endforeach; ?>
                    </datalist>
                </div>
                <div class="fg">
                    <label>Sasaran</label>
                    <input name="sasaran" list="datalistSasaran" value="<?= htmlspecialchars($editRow['sasaran'] ?? '') ?>" placeholder="pilih dari master atau ketik baru">
                    <datalist id="datalistSasaran">
                        <?php foreach ($sasaranList as $s): ?><option value="<?= htmlspecialchars($s['nama']) ?>"><?php endforeach; ?>
                    </datalist>
                    <div class="hint">Kelola daftar sasaran via tombol <b>Kelola Sasaran</b> di atas.</div>
                </div>
                <div class="fg">
                    <label>Urutan Tampil (0 = otomatis di akhir)</label>
                    <input type="number" name="urutan" value="<?= htmlspecialchars($editRow['urutan'] ?? 0) ?>" min="0">
                </div>
                <div class="fg">
                    <label>Total Manual (opsional)</label>
                    <input name="total_manual" value="<?= htmlspecialchars($editRow['total_manual'] ?? '') ?>" placeholder="kosongkan = otomatis SUM 12 kecamatan">
                    <div class="hint">Diisi hanya bila TOTAL pada spreadsheet bukan hasil penjumlahan kecamatan.</div>
                </div>
                <div class="fg full">
                    <label>Target Per Kecamatan (berelasi ke tbl_kecamatan)</label>
                    <div class="kec-grid">
                        <?php foreach ($kecForm as $kn): ?>
                        <div class="fg">
                            <label><?= ucwords(strtolower($kn)) ?></label>
                            <input name="target[<?= $kn ?>]" value="<?= htmlspecialchars($editRow['targets'][$kn] ?? 0) ?>">
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <div style="display:flex;gap:10px;margin-top:16px;flex-wrap:wrap;">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> <?= $editRow ? 'Simpan Perubahan' : 'Simpan Data' ?></button>
                <?php if ($editRow): ?><a class="btn" href="spm.php<?= $fPeriode !== '' ? '?periode=' . urlencode($fPeriode) : '' ?>">Batal</a><?php endif; ?>
            </div>
        </form>
    </div>

    <!-- FILTER + TABEL -->
    <div class="card">
        <h3><i class="fas fa-list" style="color:#00d4ff;margin-right:8px;"></i>Data SPM (<?= number_format($totalRows) ?> baris)</h3>
        <form method="GET" class="filter-bar">
            <div class="fg">
                <label>Periode</label>
                <select name="periode" onchange="this.form.submit()">
                    <option value="">— Semua —</option>
                    <?php foreach ($periods as $p): ?>
                    <option value="<?= htmlspecialchars($p) ?>" <?= $fPeriode === $p ? 'selected' : '' ?>><?= htmlspecialchars($p) ?> (<?= $counts[$p] ?? 0 ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="fg">
                <label>Cari layanan / indikator</label>
                <input type="text" name="q" value="<?= htmlspecialchars($fSearch) ?>" placeholder="kata kunci...">
            </div>
            <button type="submit" class="btn"><i class="fas fa-search"></i> Filter</button>
            <?php if ($fSearch !== '' || $fPeriode !== ''): ?><a class="btn" href="spm.php">Reset</a><?php endif; ?>
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
                    <th>Sasaran</th>
                    <th>Periode</th>
                    <th style="text-align:right;">TOTAL</th>
                    <th style="text-align:right;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rows)): ?>
                <tr><td colspan="9" style="text-align:center;color:rgba(255,255,255,0.35);padding:24px;"><i class="fas fa-database"></i> Belum ada data. Tambahkan manual atau import Excel.</td></tr>
                <?php else: ?>
                <?php foreach ($rows as $i => $r): ?>
                <tr>
                    <td><?= $offset + $i + 1 ?></td>
                    <td><?= htmlspecialchars(mb_strimwidth($r['jenis_layanan'], 0, 60, '…')) ?></td>
                    <td style="text-align:center;"><?= htmlspecialchars($r['sub_no'] ?? '') ?></td>
                    <td><?= htmlspecialchars(mb_strimwidth($r['indikator'], 0, 80, '…')) ?></td>
                    <td><?= htmlspecialchars($r['satuan'] !== '' ? $r['satuan'] : '-') ?></td>
                    <td><?= htmlspecialchars($r['sasaran'] !== '' ? $r['sasaran'] : '-') ?></td>
                    <td><span class="badge"><?= htmlspecialchars($r['periode']) ?></span></td>
                    <td class="num"><b><?= SpmLib::fmt($r['total']) ?></b></td>
                    <td>
                        <div class="row-actions">
                            <a class="btn-sm" href="spm.php?edit=<?= (int)$r['id'] ?>&periode=<?= urlencode($fPeriode) ?>&q=<?= urlencode($fSearch) ?>&page=<?= $page ?>"><i class="fas fa-pen"></i> Edit</a>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Yakin hapus data ini?\n\n<?= htmlspecialchars(addslashes(mb_strimwidth($r['indikator'], 0, 60, '…'))) ?>')">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                                <input type="hidden" name="periode_back" value="<?= htmlspecialchars($fPeriode) ?>">
                                <button type="submit" class="btn-sm del"><i class="fas fa-trash"></i> Hapus</button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        </div>
        <?php if ($totalPages > 1): ?>
        <div class="pager">
            <?php if ($page > 1): ?><a href="spm.php?periode=<?= urlencode($fPeriode) ?>&q=<?= urlencode($fSearch) ?>&page=<?= $page - 1 ?>">‹ Prev</a><?php endif; ?>
            <span>Halaman <?= $page ?> / <?= $totalPages ?></span>
            <?php if ($page < $totalPages): ?><a href="spm.php?periode=<?= urlencode($fPeriode) ?>&q=<?= urlencode($fSearch) ?>&page=<?= $page + 1 ?>">Next ›</a><?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Hapus Semua -->
<div id="deleteAllModal" style="display:none; position:fixed; inset:0; z-index:9999; align-items:center; justify-content:center; background:rgba(2,10,20,0.78); backdrop-filter:blur(4px);">
    <div style="background:linear-gradient(160deg,#0a2233 0%,#061426 100%); border:1px solid rgba(255,82,82,0.35); border-radius:18px; padding:26px; max-width:440px; width:90%; text-align:center; box-shadow:0 20px 60px rgba(0,0,0,0.5);">
        <div style="width:56px; height:56px; border-radius:50%; background:rgba(255,82,82,0.15); border:1px solid rgba(255,82,82,0.3); display:flex; align-items:center; justify-content:center; margin:0 auto 14px; color:#ff8a80; font-size:22px;"><i class="fas fa-exclamation-triangle"></i></div>
        <h3 style="color:#fff; font-size:18px; font-weight:700; margin-bottom:8px;">Hapus Semua Data SPM?</h3>
        <p style="color:#fff; font-size:14px; margin-bottom:6px;">Apakah Anda yakin ingin menghapus semua data SPM?</p>
        <p style="color:rgba(255,255,255,0.6); font-size:12px; margin-bottom:20px;">Data yang dihapus akan hilang secara permanen dan tidak dapat dipulihkan.</p>
        <div style="display:flex; gap:10px; justify-content:center;">
            <button type="button" class="btn" onclick="closeDeleteAllModal()" style="min-width:100px;">Batal</button>
            <form method="POST" style="display:inline;">
                <input type="hidden" name="action" value="delete_all">
                <button type="submit" class="btn btn-danger" style="min-width:130px;"><i class="fas fa-trash-alt"></i> Hapus Semua</button>
            </form>
        </div>
    </div>
</div>
<script>
function openDeleteAllModal(){ document.getElementById('deleteAllModal').style.display='flex'; document.body.style.overflow='hidden'; }
function closeDeleteAllModal(){ document.getElementById('deleteAllModal').style.display='none'; document.body.style.overflow=''; }
document.getElementById('deleteAllModal')?.addEventListener('click', function(e){ if(e.target===this) closeDeleteAllModal(); });
document.addEventListener('keydown', function(e){ if(e.key==='Escape') closeDeleteAllModal(); });
</script>

</body>
</html>
