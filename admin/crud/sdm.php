<?php
require_once '../config.php';
requireLogin();
$current_page = basename($_SERVER['PHP_SELF']);

// Korelasi Kabupaten: hitung total kabupaten sebagai SUM per kecamatan (hybrid faskes/kecamatan)
// Jika ada data faskes di kecamatan → pakai SUM faskes, else pakai SUM kecamatan → total kabupaten berkorelasi
$itemsRaw = [];
$qItems = mysqli_query($config, "
    SELECT si.id, si.nama_item, si.nilai as nilai_legacy, si.urutan, si.aktif,
           COALESCE(sf2.sf_total,0) + COALESCE(sk2.sk_total,0) AS nilai_korelasi
    FROM tbl_sdm_items si
    LEFT JOIN (SELECT id_profesi, SUM(jumlah) sf_total FROM tbl_sdm_faskes WHERE aktif='Y' GROUP BY id_profesi) sf2 ON sf2.id_profesi = si.id
    LEFT JOIN (
        SELECT id_item, SUM(jumlah) sk_total FROM tbl_sdm_kecamatan sk
        WHERE sk.aktif='Y' AND sk.id_kecamatan NOT IN (SELECT id_kecamatan FROM tbl_sdm_faskes WHERE aktif='Y' GROUP BY id_kecamatan)
        GROUP BY id_item
    ) sk2 ON sk2.id_item = si.id
    WHERE si.aktif='Y'
    ORDER BY si.urutan
");
while ($row = mysqli_fetch_assoc($qItems)) $itemsRaw[] = $row;
$hasKorelasi = false;
foreach ($itemsRaw as $r) if ((int)$r['nilai_korelasi'] > 0) { $hasKorelasi = true; break; }
$items = [];
foreach ($itemsRaw as $row) {
    $row['nilai'] = $hasKorelasi ? (int)$row['nilai_korelasi'] : (int)$row['nilai_legacy'];
    $row['nilai_display'] = (int)$row['nilai'];
    $row['nilai_korelasi'] = (int)$row['nilai_korelasi'];
    $row['nilai_legacy'] = (int)$row['nilai_legacy'];
    $items[] = $row;
}
if (!$hasKorelasi && count($items)==0) {
    $qFallback = mysqli_query($config, "SELECT * FROM tbl_sdm_items WHERE aktif='Y' ORDER BY urutan");
    while ($row = mysqli_fetch_assoc($qFallback)) $items[] = $row;
}

// Daftar Spesialis Dokter (master)
$spesialisList = [];
$qSp = mysqli_query($config, "SELECT * FROM tbl_spesialis WHERE aktif='Y' ORDER BY urutan, nama_spesialis");
if ($qSp) while ($row = mysqli_fetch_assoc($qSp)) $spesialisList[] = $row;
else $spesialisList = [];

// Total kabupaten terkorelasi (sum efektif)
$totalKabupatenKorelasi = array_sum(array_column($items, 'nilai'));
$totalKabupatenLegacy = 0;
$qLegTot = mysqli_query($config, "SELECT SUM(nilai) s FROM tbl_sdm_items WHERE aktif='Y'");
if ($qLegTot) $totalKabupatenLegacy = (int)mysqli_fetch_assoc($qLegTot)['s'];

// ============================================================
// PROSES TAMBAH
// ============================================================

if (isset($_POST['add'])) {
    $nama = mysqli_real_escape_string($config, $_POST['nama_item']);
    $nilai = (int)$_POST['nilai'];
    $urutan = (int)$_POST['urutan'];
    
    if (!empty($nama)) {
        $check = mysqli_query($config, "SELECT id FROM tbl_sdm_items WHERE nama_item = '$nama'");
        if (mysqli_num_rows($check) == 0) {
            mysqli_query($config, "INSERT INTO tbl_sdm_items (nama_item, nilai, urutan) VALUES ('$nama', $nilai, $urutan)");
            $success = "Item '$nama' berhasil ditambahkan!";
        } else {
            $error = "Item '$nama' sudah ada!";
        }
    }
    header("Location: sdm.php");
    exit;
}

// ============================================================
// PROSES EDIT
// ============================================================

if (isset($_POST['edit'])) {
    $id = (int)$_POST['id'];
    $nama = mysqli_real_escape_string($config, $_POST['nama_item']);
    $nilai = (int)$_POST['nilai'];
    $urutan = (int)$_POST['urutan'];
    
    mysqli_query($config, "UPDATE tbl_sdm_items SET 
        nama_item='$nama', 
        nilai=$nilai, 
        urutan=$urutan 
        WHERE id=$id");
    
    header("Location: sdm.php");
    exit;
}

// ============================================================
// PROSES HAPUS (soft delete)
// ============================================================

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    mysqli_query($config, "UPDATE tbl_sdm_items SET aktif='N' WHERE id=$id");
    header("Location: sdm.php");
    exit;
}

// ============================================================
// CRUD SPESIALIS DOKTER — tbl_spesialis
// ============================================================
if (isset($_POST['add_spesialis'])) {
    $nama = mysqli_real_escape_string($config, trim($_POST['nama_spesialis'] ?? ''));
    $kode = mysqli_real_escape_string($config, trim($_POST['kode'] ?? ''));
    $urutan = (int)($_POST['urutan_sp'] ?? 0);
    if ($nama !== '') {
        $check = mysqli_query($config, "SELECT id FROM tbl_spesialis WHERE nama_spesialis='$nama' LIMIT 1");
        if ($check && mysqli_num_rows($check)==0) {
            $stmt = $config->prepare("INSERT INTO tbl_spesialis (nama_spesialis, kode, urutan) VALUES (?, ?, ?)");
            $stmt->bind_param("ssi", $nama, $kode, $urutan);
            $stmt->execute();
            header("Location: sdm.php?msg_sp=saved#cardSpesialis");
            exit;
        } else {
            header("Location: sdm.php?msg_sp=exists#cardSpesialis");
            exit;
        }
    }
    header("Location: sdm.php?msg_sp=invalid#cardSpesialis");
    exit;
}
if (isset($_POST['edit_spesialis'])) {
    $id = (int)($_POST['id_sp'] ?? 0);
    $nama = mysqli_real_escape_string($config, trim($_POST['nama_spesialis'] ?? ''));
    $kode = mysqli_real_escape_string($config, trim($_POST['kode'] ?? ''));
    $urutan = (int)($_POST['urutan_sp'] ?? 0);
    if ($id && $nama !== '') {
        $stmt = $config->prepare("UPDATE tbl_spesialis SET nama_spesialis=?, kode=?, urutan=? WHERE id=?");
        $stmt->bind_param("ssii", $nama, $kode, $urutan, $id);
        $stmt->execute();
    }
    header("Location: sdm.php?msg_sp=updated#cardSpesialis");
    exit;
}
if (isset($_GET['delete_sp'])) {
    $id = (int)$_GET['delete_sp'];
    mysqli_query($config, "UPDATE tbl_spesialis SET aktif='N' WHERE id=$id");
    header("Location: sdm.php?msg_sp=deleted#cardSpesialis");
    exit;
}

// ============================================================
// PROSES SDM PER FASYANKES (BARU) — tbl_sdm_faskes
// Kecamatan → Fasyankes → Jenis SDM → Jumlah
// Validasi: fasyankes harus berada di kecamatan yang dipilih
// ============================================================
if (isset($_POST['add_sdm_faskes'])) {
    $id_kecamatan = (int)($_POST['id_kecamatan'] ?? 0);
    $id_faskes    = (int)($_POST['id_faskes'] ?? 0);
    $id_profesi   = (int)($_POST['id_profesi'] ?? 0);
    $id_spesialis = isset($_POST['id_spesialis']) && $_POST['id_spesialis'] !== '' ? (int)$_POST['id_spesialis'] : null;
    $jumlah       = (int)($_POST['jumlah'] ?? 0);
    $msg_faskes = '';
    if ($id_kecamatan && $id_faskes && $id_profesi) {
        // Validasi faskes milik kecamatan (prepared statement)
        $cek = $config->prepare("SELECT id_kecamatan FROM tbl_faskes WHERE id_faskes=? AND aktif='Y' LIMIT 1");
        $cek->bind_param("i", $id_faskes);
        $cek->execute();
        $resCek = $cek->get_result()->fetch_assoc();
        if (!$resCek) {
            $msg_faskes = 'faskes_not_found';
        } elseif ((int)$resCek['id_kecamatan'] !== $id_kecamatan) {
            $msg_faskes = 'kecamatan_mismatch';
        } else {
            // Validasi profesi aktif
            $cekP = $config->prepare("SELECT id, nama_item FROM tbl_sdm_items WHERE id=? AND aktif='Y' LIMIT 1");
            $cekP->bind_param("i", $id_profesi);
            $cekP->execute();
            $profRow = $cekP->get_result()->fetch_assoc();
            if (!$profRow) {
                $msg_faskes = 'profesi_not_found';
            } else {
                // Validasi spesialis: hanya relevan jika Dokter (nama mengandung dokter), jika bukan dokter paksa NULL
                $isDokter = stripos($profRow['nama_item'], 'dokter') !== false;
                if (!$isDokter) $id_spesialis = null;
                if ($id_spesialis !== null) {
                    $cekS = $config->prepare("SELECT id FROM tbl_spesialis WHERE id=? AND aktif='Y' LIMIT 1");
                    $cekS->bind_param("i", $id_spesialis);
                    $cekS->execute();
                    if (!$cekS->get_result()->fetch_assoc()) {
                        $msg_faskes = 'spesialis_not_found';
                    }
                }
                if (!$msg_faskes) {
                    // ON DUPLICATE KEY untuk kombinasi (id_faskes, id_profesi, id_spesialis)
                    // Jika id_spesialis NULL, MySQL UNIQUE memperlakukan NULL != NULL, jadi gunakan INSERT ... ON DUPLICATE KEY hanya untuk non-NULL;
                    // untuk NULL kita cek manual UPDATE jika sudah ada baris dengan NULL
                    if ($id_spesialis === null) {
                        $cekExist = $config->prepare("SELECT id FROM tbl_sdm_faskes WHERE id_faskes=? AND id_profesi=? AND id_spesialis IS NULL AND aktif='Y' LIMIT 1");
                        $cekExist->bind_param("ii", $id_faskes, $id_profesi);
                        $cekExist->execute();
                        $existRow = $cekExist->get_result()->fetch_assoc();
                        if ($existRow) {
                            $stmt = $config->prepare("UPDATE tbl_sdm_faskes SET jumlah=?, id_kecamatan=?, aktif='Y' WHERE id=?");
                            $stmt->bind_param("iii", $jumlah, $id_kecamatan, $existRow['id']);
                            $stmt->execute();
                        } else {
                            $stmt = $config->prepare("INSERT INTO tbl_sdm_faskes (id_kecamatan, id_faskes, id_profesi, id_spesialis, jumlah) VALUES (?, ?, ?, NULL, ?)");
                            $stmt->bind_param("iiii", $id_kecamatan, $id_faskes, $id_profesi, $jumlah);
                            $stmt->execute();
                        }
                    } else {
                        $stmt = $config->prepare("INSERT INTO tbl_sdm_faskes (id_kecamatan, id_faskes, id_profesi, id_spesialis, jumlah) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE jumlah=VALUES(jumlah), aktif='Y', id_kecamatan=VALUES(id_kecamatan)");
                        $stmt->bind_param("iiiii", $id_kecamatan, $id_faskes, $id_profesi, $id_spesialis, $jumlah);
                        $stmt->execute();
                    }
                    header("Location: sdm.php?msg_faskes=saved#cardSdmFaskes");
                    exit;
                }
            }
        }
        if ($msg_faskes) {
            header("Location: sdm.php?msg_faskes=" . $msg_faskes . "#cardSdmFaskes");
            exit;
        }
    } else {
        header("Location: sdm.php?msg_faskes=invalid#cardSdmFaskes");
        exit;
    }
}
if (isset($_POST['edit_sdm_faskes'])) {
    $edit_id    = (int)($_POST['edit_id'] ?? 0);
    $jumlah     = (int)($_POST['edit_jumlah'] ?? 0);
    if ($edit_id) {
        $stmt = $config->prepare("UPDATE tbl_sdm_faskes SET jumlah=? WHERE id=?");
        $stmt->bind_param("ii", $jumlah, $edit_id);
        $stmt->execute();
    }
    header("Location: sdm.php?msg_faskes=updated");
    exit;
}
if (isset($_GET['delete_faskes'])) {
    $del_id = (int)$_GET['delete_faskes'];
    // soft delete
    mysqli_query($config, "UPDATE tbl_sdm_faskes SET aktif='N' WHERE id=$del_id");
    header("Location: sdm.php?msg_faskes=deleted");
    exit;
}

// ============================================================
// AMBIL DAFTAR KECAMATAN + TOTAL SDMK PER KECAMATAN (tanpa cross-join, berkorelasi)
// total_sdmk = SUM tbl_sdm_kecamatan, total_sdm_faskes = SUM tbl_sdm_faskes, total_efektif = yang dipakai API (korelasi)
// ============================================================

$kecamatanList = [];
$kecQuery = mysqli_query($config, "
    SELECT k.id_kecamatan, k.nama_kecamatan, k.kode_kecamatan,
           COALESCE(sk2.tot,0) AS total_sdmk,
           COALESCE(sf2.tot,0) AS total_sdm_faskes,
           CASE WHEN COALESCE(sf2.tot,0) > 0 OR sf_has.cnt > 0 THEN COALESCE(sf2.tot,0) ELSE COALESCE(sk2.tot,0) END AS total_efektif
    FROM tbl_kecamatan k
    LEFT JOIN (SELECT id_kecamatan, SUM(jumlah) tot FROM tbl_sdm_kecamatan WHERE aktif='Y' GROUP BY id_kecamatan) sk2 ON sk2.id_kecamatan = k.id_kecamatan
    LEFT JOIN (SELECT id_kecamatan, SUM(jumlah) tot FROM tbl_sdm_faskes WHERE aktif='Y' GROUP BY id_kecamatan) sf2 ON sf2.id_kecamatan = k.id_kecamatan
    LEFT JOIN (SELECT id_kecamatan, COUNT(*) cnt FROM tbl_sdm_faskes WHERE aktif='Y' GROUP BY id_kecamatan) sf_has ON sf_has.id_kecamatan = k.id_kecamatan
    WHERE k.aktif = 'Y'
    GROUP BY k.id_kecamatan, k.nama_kecamatan, k.kode_kecamatan, sk2.tot, sf2.tot, sf_has.cnt
    ORDER BY k.nama_kecamatan
");
while ($row = mysqli_fetch_assoc($kecQuery)) {
    $kecamatanList[] = $row;
}
$totalEfektifKabupaten = array_sum(array_column($kecamatanList, 'total_efektif'));

// Daftar kecamatan untuk dropdown + daftar faskes per kecamatan (untuk JS cascading)
$allKecamatan = [];
$qAllKec = mysqli_query($config, "SELECT id_kecamatan, nama_kecamatan FROM tbl_kecamatan WHERE aktif='Y' ORDER BY nama_kecamatan");
while ($r = mysqli_fetch_assoc($qAllKec)) $allKecamatan[] = $r;

$faskesByKecamatan = [];
$qFaskes = mysqli_query($config, "SELECT f.id_faskes, f.nama_faskes, f.jenis, f.id_kecamatan, k.nama_kecamatan FROM tbl_faskes f LEFT JOIN tbl_kecamatan k ON k.id_kecamatan=f.id_kecamatan WHERE f.aktif='Y' ORDER BY k.nama_kecamatan, f.jenis, f.nama_faskes");
while ($r = mysqli_fetch_assoc($qFaskes)) {
    $faskesByKecamatan[(int)$r['id_kecamatan']][] = $r;
}

// List SDM per faskes (untuk tabel bawah) — filter opsional via GET
$filterKecFaskes = isset($_GET['kec_faskes']) && $_GET['kec_faskes'] !== '' ? (int)$_GET['kec_faskes'] : 0;
$filterSpesialisRaw = $_GET['filter_sp'] ?? '';
$filterSpesialis = 0;
$filterSpesialisIsUmum = ($filterSpesialisRaw === 'umum');
if (!$filterSpesialisIsUmum && $filterSpesialisRaw !== '' && ctype_digit((string)$filterSpesialisRaw)) $filterSpesialis = (int)$filterSpesialisRaw;
$whereFaskes = "WHERE sf.aktif='Y'";
if ($filterKecFaskes) $whereFaskes .= " AND sf.id_kecamatan=$filterKecFaskes";
if ($filterSpesialisIsUmum) $whereFaskes .= " AND sf.id_spesialis IS NULL";
elseif ($filterSpesialis) $whereFaskes .= " AND sf.id_spesialis=$filterSpesialis";
$sdmFaskesList = [];
$qSdmF = mysqli_query($config, "
    SELECT sf.id, sf.jumlah, sf.id_kecamatan, sf.id_faskes, sf.id_profesi, sf.id_spesialis,
           k.nama_kecamatan, f.nama_faskes, f.jenis, si.nama_item as profesi,
           sp.nama_spesialis, sp.kode as kode_spesialis
    FROM tbl_sdm_faskes sf
    JOIN tbl_kecamatan k ON k.id_kecamatan=sf.id_kecamatan
    JOIN tbl_faskes f ON f.id_faskes=sf.id_faskes
    JOIN tbl_sdm_items si ON si.id=sf.id_profesi
    LEFT JOIN tbl_spesialis sp ON sp.id=sf.id_spesialis
    $whereFaskes
    ORDER BY k.nama_kecamatan, f.nama_faskes, si.urutan, sp.urutan
");
while ($r = mysqli_fetch_assoc($qSdmF)) $sdmFaskesList[] = $r;

$username = $_SESSION['admin_username'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola SDM - Admin DKK</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body {
            font-family: 'Poppins', sans-serif;
            background: #061426;
            min-height: 100vh;
            display: flex;
            color: #fff;
        }

        /* ===== SIDEBAR ===== */
        .sidebar {
            width: 260px;
            min-height: 100vh;
            background: rgba(255,255,255,0.04);
            backdrop-filter: blur(12px);
            border-right: 1px solid rgba(255,255,255,0.06);
            padding: 30px 20px;
            flex-shrink: 0;
            position: sticky;
            top: 0;
            height: 100vh;
            overflow-y: auto;
        }
        .sidebar-brand {
            display: flex;
            align-items: center;
            gap: 14px;
            padding-bottom: 30px;
            border-bottom: 1px solid rgba(255,255,255,0.06);
            margin-bottom: 24px;
        }
        .sidebar-brand img {
            width: 48px;
            height: 48px;
            object-fit: contain;
        }
        .sidebar-brand h2 {
            color: #fff;
            font-size: 16px;
            font-weight: 700;
            line-height: 1.2;
        }
        .sidebar-brand small {
            display: block;
            color: #87e3ff;
            font-size: 10px;
            font-weight: 500;
            letter-spacing: 1px;
        }
        .sidebar-menu {
            list-style: none;
        }
        .sidebar-menu li { margin-bottom: 4px; }
        .sidebar-menu a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            border-radius: 12px;
            color: rgba(255,255,255,0.6);
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: 0.3s;
        }
        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            background: rgba(0,212,255,0.12);
            color: #fff;
        }
        .sidebar-menu a i {
            width: 20px;
            color: rgba(255,255,255,0.3);
            font-size: 16px;
        }
        .sidebar-menu a.active i { color: #00d4ff; }
        .sidebar-menu .logout {
            margin-top: 30px;
            border-top: 1px solid rgba(255,255,255,0.06);
            padding-top: 20px;
        }
        .sidebar-menu .logout a {
            color: rgba(255,82,82,0.7);
        }
        .sidebar-menu .logout a:hover {
            background: rgba(255,82,82,0.12);
            color: #ff6b6b;
        }

        /* ===== MAIN CONTENT ===== */
        .main-content {
            flex: 1;
            padding: 30px 40px;
        }
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        .page-header h1 {
            color: #fff;
            font-size: 28px;
            font-weight: 700;
        }
        .page-header p {
            color: #87e3ff;
            font-size: 14px;
            margin-top: 4px;
        }
        .page-header .back-link {
            color: #87e3ff;
            text-decoration: none;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: 0.3s;
        }
        .page-header .back-link:hover {
            color: #00d4ff;
        }

        .card {
            background: rgba(255,255,255,0.05);
            backdrop-filter: blur(16px);
            border-radius: 20px;
            padding: 30px;
            border: 1px solid rgba(255,255,255,0.08);
            margin-bottom: 24px;
        }
        .card h3 {
            color: #84e7ff;
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 16px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }
        table th {
            text-align: left;
            padding: 10px 8px;
            color: #87e3ff;
            font-weight: 600;
            font-size: 13px;
            border-bottom: 2px solid rgba(255,255,255,0.08);
        }
        table td {
            padding: 10px 8px;
            border-bottom: 1px solid rgba(255,255,255,0.05);
            font-size: 13px;
        }
        table td:last-child {
            text-align: right;
        }

        .btn-icon {
            padding: 4px 12px;
            border-radius: 6px;
            border: none;
            background: rgba(0,212,255,0.15);
            color: #00d4ff;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            transition: 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        .btn-icon:hover {
            background: rgba(0,212,255,0.3);
        }
        .btn-danger {
            background: rgba(255,82,82,0.15);
            color: #ff6b6b;
        }
        .btn-danger:hover {
            background: rgba(255,82,82,0.3);
        }

        .form-inline {
            display: flex;
            gap: 12px;
            align-items: end;
            flex-wrap: wrap;
        }
        .form-inline .form-group {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        .form-inline label {
            font-size: 12px;
            color: #87e3ff;
            font-weight: 600;
        }
        .form-inline input {
            padding: 8px 14px;
            border-radius: 8px;
            border: 1px solid rgba(255,255,255,0.1);
            background: rgba(255,255,255,0.06);
            color: #fff;
            font-size: 14px;
            font-family: 'Poppins', sans-serif;
            width: 150px;
        }
        .form-inline input:focus {
            outline: none;
            border-color: #00d4ff;
        }
        .btn-primary {
            padding: 8px 20px;
            border-radius: 8px;
            border: none;
            background: linear-gradient(135deg, #00d4ff, #0088cc);
            color: #fff;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,212,255,0.25);
        }

        .form-inline-edit {
            display: inline-flex;
            gap: 6px;
            align-items: center;
            flex-wrap: wrap;
        }
        .form-inline-edit input {
            padding: 4px 8px;
            border-radius: 6px;
            border: 1px solid rgba(255,255,255,0.1);
            background: rgba(255,255,255,0.06);
            color: #fff;
            font-size: 13px;
            font-family: 'Poppins', sans-serif;
        }
        .form-inline-edit input:focus {
            outline: none;
            border-color: #00d4ff;
        }
        .form-inline-edit .input-name {
            width: 120px;
        }
        .form-inline-edit .input-value {
            width: 70px;
        }
        .form-inline-edit .input-order {
            width: 60px;
        }

        .badge-total {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 20px;
            background: rgba(0,212,255,0.12);
            color: #72e8ff;
            font-weight: 600;
            font-size: 12px;
        }
        .form-grid { display:grid; grid-template-columns: repeat(auto-fill,minmax(180px,1fr)); gap:16px; }
        .form-grid .form-group { display:flex; flex-direction:column; gap:6px; }
        .form-grid label { font-size:12px; color:#87e3ff; font-weight:600; }
        .form-grid select, .form-grid input { padding:10px 14px; border-radius:10px; border:1px solid rgba(255,255,255,0.1); background:rgba(255,255,255,0.06); color:#fff; font-size:13px; font-family:'Poppins',sans-serif; }
        .form-grid select option { background:#0b223c; }
        .alert { padding:14px 20px; border-radius:12px; margin-bottom:20px; display:flex; align-items:center; gap:12px; font-size:14px; }
        .alert-success { background:rgba(0,212,255,0.12); border:1px solid rgba(0,212,255,0.2); color:#72e8ff; }
        .alert-warning { background:rgba(255,193,7,0.12); border:1px solid rgba(255,193,7,0.25); color:#ffd54f; }

        @media (max-width: 768px) {
            .sidebar { display: none; }
            .main-content { padding: 20px; }
            .form-inline { flex-direction: column; align-items: stretch; }
            .form-inline input { width: 100%; }
        }
    </style>
</head>
<body>

<!-- ===== SIDEBAR ===== -->
<div class="sidebar">
    <div class="sidebar-brand">
        <img src="../../assets/img/kabupaten.png" alt="Logo">
        <h2>Portal DKK<br><small>Dashboard Admin</small></h2>
    </div>
    <ul class="sidebar-menu">
        <li><a href="../index.php"><i class="fas fa-chart-pie"></i> Dashboard</a></li>
        <li><a href="fasyankes.php"><i class="fas fa-hospital"></i> Fasyankes</a></li>
        <li><a href="sdm.php" class="active"><i class="fas fa-users"></i> SDM</a></li>
        <li><a href="kecamatan.php"><i class="fas fa-map"></i> Kecamatan</a></li>
        <li><a href="penyakit.php"><i class="fas fa-disease"></i> Penyakit</a></li>
        <li><a href="portal_info.php"><i class="fas fa-circle-info"></i> Informasi Portal</a></li>
        <li class="logout"><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
    </ul>
</div>

<!-- MAIN CONTENT -->
<div class="main-content">
    <div class="page-header">
        <div>
            <h1>Kelola SDM Kesehatan</h1>
            <p>Tambah, edit, atau hapus data sumber daya manusia</p>
        </div>
        <a href="../index.php" class="back-link"><i class="fas fa-arrow-left"></i> Kembali</a>
    </div>

    <!-- FORM TAMBAH -->
    <div class="card">
        <h3><i class="fas fa-plus-circle" style="color:#00d4ff;margin-right:10px;"></i>Tambah Item SDM</h3>
        <form method="POST" class="form-inline">
            <div class="form-group">
                <label>Nama Profesi</label>
                <input type="text" name="nama_item" placeholder="Contoh: Dokter Gigi" required>
            </div>
            <div class="form-group">
                <label>Jumlah</label>
                <input type="number" name="nilai" value="0" min="0">
            </div>
            <div class="form-group">
                <label>Urutan</label>
                <input type="number" name="urutan" value="<?php echo count($items) + 1; ?>" min="1">
            </div>
            <button type="submit" name="add" class="btn-primary"><i class="fas fa-save"></i> Tambah</button>
        </form>
    </div>

    <!-- TABEL LIST -->
    <div class="card">
        <h3><i class="fas fa-list" style="color:#00d4ff;margin-right:10px;"></i>Daftar SDM (Total Kabupaten)</h3>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Nama Profesi</th>
                    <th>Jumlah</th>
                    <th>Urutan</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($items) > 0): ?>
                <?php foreach ($items as $index => $item): ?>
                <tr>
                    <td><?php echo $index + 1; ?></td>
                    <td><?php echo htmlspecialchars($item['nama_item']); ?></td>
                    <td><span class="badge-total"><?php echo number_format($item['nilai']); ?></span></td>
                    <td><?php echo $item['urutan']; ?></td>
                    <td>
                        <form method="POST" class="form-inline-edit">
                            <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                            <input type="text" name="nama_item" value="<?php echo htmlspecialchars($item['nama_item']); ?>" class="input-name">
                            <input type="number" name="nilai" value="<?php echo $item['nilai_legacy'] ?? $item['nilai']; ?>" class="input-value">
                            <input type="number" name="urutan" value="<?php echo $item['urutan']; ?>" class="input-order">
                            <button type="submit" name="edit" class="btn-icon"><i class="fas fa-pen"></i> Edit</button>
                        </form>
                        <a href="?delete=<?php echo $item['id']; ?>" class="btn-icon btn-danger" onclick="return confirm('Hapus item ini?')"><i class="fas fa-trash"></i> Hapus</a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php else: ?>
                <tr>
                    <td colspan="5" style="text-align:center;color:rgba(255,255,255,0.3);padding:20px;">
                        <i class="fas fa-database"></i> Belum ada data SDM
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- TABEL SDMK PER KECAMATAN -->
    <div class="card">
        <h3><i class="fas fa-map-marker-alt" style="color:#00d4ff;margin-right:10px;"></i>SDMK per Kecamatan</h3>

        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Nama Kecamatan</th>
                    <th>Kode</th>
                    <th>Total SDMK (Kec)</th>
                    <th>Total SDM Fasyankes</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($kecamatanList) > 0): ?>
                <?php foreach ($kecamatanList as $index => $kec): ?>
                <tr>
                    <td><?php echo $index + 1; ?></td>
                    <td><?php echo htmlspecialchars($kec['nama_kecamatan']); ?></td>
                    <td><?php echo htmlspecialchars($kec['kode_kecamatan']); ?></td>
                    <td><span class="badge-total"><?php echo number_format($kec['total_sdmk']); ?> orang</span></td>
                    <td><span class="badge-total" style="background:rgba(76,175,80,0.15);color:#81c784;"><?php echo number_format($kec['total_sdm_faskes']); ?> orang</span></td>
                    <td>
                        <a href="sdm_kecamatan.php?id=<?php echo $kec['id_kecamatan']; ?>" class="btn-primary">
                            <i class="fas fa-user-md"></i> Kelola SDMK
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php else: ?>
                <tr>
                    <td colspan="6" style="text-align:center;color:rgba(255,255,255,0.3);padding:20px;">
                        <i class="fas fa-map"></i> Belum ada data kecamatan.
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>

    </div>

    <!-- KELOLA SPESIALIS DOKTER -->
    <div class="card" id="cardSpesialis">
        <h3><i class="fas fa-user-doctor" style="color:#00d4ff;margin-right:10px;"></i>Kelola daftar spesialis dokter (Sp.A, Sp.OG, Sp.PD, dll).</h3>

        <?php if (isset($_GET['msg_sp'])): ?>
            <?php if ($_GET['msg_sp']==='saved'): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> Spesialis berhasil ditambahkan!</div>
            <?php elseif ($_GET['msg_sp']==='updated'): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> Spesialis berhasil diperbarui!</div>
            <?php elseif ($_GET['msg_sp']==='deleted'): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> Spesialis dihapus (soft delete)!</div>
            <?php elseif ($_GET['msg_sp']==='exists'): ?><div class="alert alert-warning"><i class="fas fa-exclamation-triangle"></i> Nama spesialis sudah ada!</div>
            <?php else: ?><div class="alert alert-warning"><i class="fas fa-exclamation-triangle"></i> Gagal. Periksa input.</div>
            <?php endif; ?>
        <?php endif; ?>

        <form method="POST" class="form-inline" style="margin-bottom:20px;">
            <div class="form-group">
                <label>Nama Spesialis *</label>
                <input type="text" name="nama_spesialis" placeholder="Contoh: Spesialis Anak" required style="width:200px;">
            </div>
            <div class="form-group">
                <label>Kode</label>
                <input type="text" name="kode" placeholder="Sp.A" style="width:100px;">
            </div>
            <div class="form-group">
                <label>Urutan</label>
                <input type="number" name="urutan_sp" value="<?php echo count($spesialisList)+1; ?>" min="0" style="width:80px;">
            </div>
            <button type="submit" name="add_spesialis" class="btn-primary"><i class="fas fa-plus"></i> Tambah Spesialis</button>
        </form>

        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Nama Spesialis</th>
                    <th>Kode</th>
                    <th>Urutan</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($spesialisList)>0): ?>
                <?php foreach ($spesialisList as $idx=>$sp): ?>
                <tr>
                    <td><?php echo $idx+1; ?></td>
                    <td><?php echo htmlspecialchars($sp['nama_spesialis']); ?></td>
                    <td><span class="badge-total"><?php echo htmlspecialchars($sp['kode'] ?? '-'); ?></span></td>
                    <td><?php echo (int)$sp['urutan']; ?></td>
                    <td>
                        <form method="POST" class="form-inline-edit">
                            <input type="hidden" name="id_sp" value="<?php echo $sp['id']; ?>">
                            <input type="text" name="nama_spesialis" value="<?php echo htmlspecialchars($sp['nama_spesialis']); ?>" class="input-name" style="width:160px;">
                            <input type="text" name="kode" value="<?php echo htmlspecialchars($sp['kode'] ?? ''); ?>" style="width:70px;padding:4px 8px;border-radius:6px;background:rgba(255,255,255,0.06);color:#fff;border:1px solid rgba(255,255,255,0.1);">
                            <input type="number" name="urutan_sp" value="<?php echo (int)$sp['urutan']; ?>" class="input-order" style="width:60px;">
                            <button type="submit" name="edit_spesialis" class="btn-icon"><i class="fas fa-pen"></i> Edit</button>
                        </form>
                        <a href="?delete_sp=<?php echo $sp['id']; ?>" class="btn-icon btn-danger" onclick="return confirm('Hapus spesialis ini? Data SDM terkait akan jadi NULL.')"><i class="fas fa-trash"></i> Hapus</a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php else: ?>
                <tr><td colspan="5" style="text-align:center;color:rgba(255,255,255,0.3);padding:20px;">Belum ada data spesialis</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- FORM SDM PER FASYANKES (BARU) — Kecamatan → Fasyankes → Jenis → Spesialis → Jumlah -->
    <div class="card" id="cardSdmFaskes">
        <h3><i class="fas fa-hospital-user" style="color:#00d4ff;margin-right:10px;"></i>SDM per Fasyankes — Kecamatan → Fasyankes → Jenis → Spesialis → Jumlah</h3>
        <p style="color:rgba(255,255,255,0.5);font-size:12px;margin-bottom:16px;">Pilih Kecamatan, lalu Fasyankes otomatis terfilter hanya fasilitas di kecamatan tersebut. Kolom <strong>Spesialis</strong> hanya wajib jika Jenis = Dokter / Dokter Gigi; untuk profesi lain akan otomatis diabaikan.</p>

        <?php if (isset($_GET['msg_faskes'])): ?>
            <?php if ($_GET['msg_faskes']==='saved'): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> Data SDM per fasyankes berhasil disimpan!</div>
            <?php elseif ($_GET['msg_faskes']==='updated'): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> Jumlah berhasil diperbarui!</div>
            <?php elseif ($_GET['msg_faskes']==='deleted'): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> Data berhasil dihapus (soft delete)!</div>
            <?php elseif ($_GET['msg_faskes']==='kecamatan_mismatch'): ?><div class="alert alert-warning"><i class="fas fa-exclamation-triangle"></i> Gagal: Fasyankes tidak berada di kecamatan yang dipilih (validasi keamanan).</div>
            <?php elseif ($_GET['msg_faskes']==='faskes_not_found'): ?><div class="alert alert-warning"><i class="fas fa-exclamation-triangle"></i> Fasyankes tidak ditemukan / tidak aktif.</div>
            <?php elseif ($_GET['msg_faskes']==='spesialis_not_found'): ?><div class="alert alert-warning"><i class="fas fa-exclamation-triangle"></i> Spesialis tidak ditemukan / tidak aktif.</div>
            <?php else: ?><div class="alert alert-warning"><i class="fas fa-exclamation-triangle"></i> Gagal menyimpan. Periksa input.</div>
            <?php endif; ?>
        <?php endif; ?>

        <form method="POST" id="formSdmFaskes">
            <div class="form-grid">
                <div class="form-group">
                    <label>Kecamatan *</label>
                    <select name="id_kecamatan" id="selKecamatan" required>
                        <option value="">-- Pilih Kecamatan --</option>
                        <?php foreach ($allKecamatan as $kc): ?>
                            <option value="<?php echo $kc['id_kecamatan']; ?>"><?php echo htmlspecialchars($kc['nama_kecamatan']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Fasyankes *</label>
                    <select name="id_faskes" id="selFaskes" required disabled>
                        <option value="">-- Pilih Kecamatan dulu --</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Jenis SDM *</label>
                    <select name="id_profesi" id="selProfesi" required>
                        <option value="">-- Pilih Jenis --</option>
                        <?php foreach ($items as $it): ?>
                            <option value="<?php echo $it['id']; ?>" data-nama="<?php echo htmlspecialchars(strtolower($it['nama_item'])); ?>"><?php echo htmlspecialchars($it['nama_item']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" id="groupSpesialis">
                    <label>Spesialis <span style="font-weight:400;color:rgba(255,255,255,0.4);">(hanya untuk Dokter)</span></label>
                    <select name="id_spesialis" id="selSpesialis">
                        <option value="">-- Umum / Tidak Spesialis --</option>
                        <?php foreach ($spesialisList as $sp): ?>
                            <option value="<?php echo $sp['id']; ?>"><?php echo htmlspecialchars($sp['nama_spesialis']); ?><?php echo $sp['kode'] ? ' ('.htmlspecialchars($sp['kode']).')' : ''; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Jumlah *</label>
                    <input type="number" name="jumlah" min="0" value="0" required>
                </div>
            </div>
            <div style="margin-top:16px;">
                <button type="submit" name="add_sdm_faskes" class="btn-primary"><i class="fas fa-save"></i> Simpan SDM Fasyankes</button>
            </div>
        </form>
    </div>

    <!-- TABEL SDM PER FASYANKES -->
    <div class="card">
        <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;margin-bottom:16px;">
            <h3 style="margin:0;"><i class="fas fa-table" style="color:#00d4ff;margin-right:10px;"></i>Daftar SDM per Fasyankes</h3>
            <form method="GET" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                <select name="kec_faskes" onchange="this.form.submit()" style="padding:8px 12px;border-radius:8px;background:rgba(255,255,255,0.06);color:#fff;border:1px solid rgba(255,255,255,0.1);">
                    <option value="">Semua Kecamatan</option>
                    <?php foreach ($allKecamatan as $kc): ?>
                        <option value="<?php echo $kc['id_kecamatan']; ?>" <?php echo $filterKecFaskes==(int)$kc['id_kecamatan']?'selected':''; ?>><?php echo htmlspecialchars($kc['nama_kecamatan']); ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="filter_sp" onchange="this.form.submit()" style="padding:8px 12px;border-radius:8px;background:rgba(255,255,255,0.06);color:#fff;border:1px solid rgba(255,255,255,0.1);">
                    <option value="">Semua Spesialis</option>
                    <option value="umum" <?php echo isset($_GET['filter_sp']) && $_GET['filter_sp']==='umum'?'selected':''; ?>>Umum (tanpa spesialis)</option>
                    <?php foreach ($spesialisList as $sp): ?>
                        <option value="<?php echo $sp['id']; ?>" <?php echo $filterSpesialis==(int)$sp['id']?'selected':''; ?>><?php echo htmlspecialchars($sp['nama_spesialis']); ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if ($filterKecFaskes || $filterSpesialis || $filterSpesialisIsUmum): ?><a href="sdm.php#cardSdmFaskes" class="btn-icon btn-danger">Reset</a><?php endif; ?>
            </form>
        </div>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Kecamatan</th>
                    <th>Fasyankes</th>
                    <th>Jenis</th>
                    <th>Spesialis</th>
                    <th>Jumlah</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($sdmFaskesList)>0): ?>
                <?php foreach ($sdmFaskesList as $i=>$row): ?>
                <tr>
                    <td><?php echo $i+1; ?></td>
                    <td><?php echo htmlspecialchars($row['nama_kecamatan']); ?></td>
                    <td><strong><?php echo htmlspecialchars($row['nama_faskes']); ?></strong> <span style="color:rgba(255,255,255,0.4);font-size:11px;">(<?php echo htmlspecialchars($row['jenis']); ?>)</span></td>
                    <td><?php echo htmlspecialchars($row['profesi']); ?></td>
                    <td><?php if ($row['nama_spesialis']): ?><span class="badge-total" style="background:rgba(255,193,7,0.12);color:#ffd54f;"><?php echo htmlspecialchars($row['nama_spesialis']); ?> <?php echo $row['kode_spesialis'] ? '('.htmlspecialchars($row['kode_spesialis']).')' : ''; ?></span><?php else: ?><span style="color:rgba(255,255,255,0.35);font-size:12px;">Umum</span><?php endif; ?></td>
                    <td><span class="badge-total"><?php echo number_format($row['jumlah']); ?></span></td>
                    <td>
                        <form method="POST" style="display:inline-flex;gap:6px;align-items:center;">
                            <input type="hidden" name="edit_id" value="<?php echo $row['id']; ?>">
                            <input type="number" name="edit_jumlah" value="<?php echo (int)$row['jumlah']; ?>" min="0" style="width:80px;padding:6px 8px;border-radius:6px;background:rgba(255,255,255,0.06);color:#fff;border:1px solid rgba(255,255,255,0.1);">
                            <button type="submit" name="edit_sdm_faskes" class="btn-icon"><i class="fas fa-pen"></i></button>
                        </form>
                        <a href="?delete_faskes=<?php echo $row['id']; ?>" class="btn-icon btn-danger" onclick="return confirm('Hapus data SDM fasyankes ini?')"><i class="fas fa-trash"></i></a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php else: ?>
                <tr><td colspan="7" style="text-align:center;color:rgba(255,255,255,0.4);padding:24px;">Belum ada data SDM per fasyankes. Tambahkan via form di atas.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
// Cascading Kecamatan → Fasyankes (JS)
const faskesByKec = <?php echo json_encode($faskesByKecamatan, JSON_UNESCAPED_UNICODE); ?>;
const selKec = document.getElementById('selKecamatan');
const selFas = document.getElementById('selFaskes');
if (selKec && selFas) {
    selKec.addEventListener('change', function(){
        const kecId = this.value;
        selFas.innerHTML = '';
        selFas.disabled = !kecId;
        if (!kecId) {
            selFas.innerHTML = '<option value=\"\">-- Pilih Kecamatan dulu --</option>';
            return;
        }
        const list = faskesByKec[kecId] || [];
        if (list.length===0) {
            selFas.innerHTML = '<option value=\"\">Tidak ada fasyankes di kecamatan ini</option>';
            return;
        }
        selFas.innerHTML = '<option value=\"\">-- Pilih Fasyankes --</option>';
        list.forEach(function(f){
            const opt = document.createElement('option');
            opt.value = f.id_faskes;
            opt.textContent = f.nama_faskes + ' (' + f.jenis + ')';
            selFas.appendChild(opt);
        });
    });
}
// Spesialis hanya untuk Dokter/Dokter Gigi
const selProfesi = document.getElementById('selProfesi');
const selSpesialis = document.getElementById('selSpesialis');
const groupSpesialis = document.getElementById('groupSpesialis');
function toggleSpesialis(){
    if(!selProfesi || !selSpesialis) return;
    const opt = selProfesi.options[selProfesi.selectedIndex];
    const nama = opt ? (opt.getAttribute('data-nama')||'').toLowerCase() : '';
    const isDokter = nama.indexOf('dokter') !== -1;
    selSpesialis.disabled = !isDokter;
    if(groupSpesialis) groupSpesialis.style.opacity = isDokter ? '1' : '0.4';
    if(!isDokter) selSpesialis.value = '';
}
if(selProfesi) {
    selProfesi.addEventListener('change', toggleSpesialis);
    toggleSpesialis();
}
</script>

</body>
</html>