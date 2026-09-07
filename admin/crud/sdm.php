<?php
// LEGACY - pertimbangkan untuk dihapus/digabung juga ke sdmk.php di iterasi berikutnya jika CRUD jenis SDM lama & SDMK per kecamatan sudah tidak dipakai lagi.
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
// SDM PER FASYANKES DIPINDAHKAN KE admin/crud/sdmk.php
// ============================================================
// Handler add_sdm_faskes / edit_sdm_faskes / delete_faskes yang lama menulis
// langsung ke kolom `jumlah` (GENERATED COLUMN) telah DIHAPUS untuk mencegah
// silent failure. Kelola SDMK per Fasyankes sekarang hanya via sdmk.php (tab faskes).
// yang menulis ke asn_l/asn_p/nonasn_l/nonasn_p dengan validasi & transaction.
// Blok ini sengaja dikosongkan — jangan tambah handler tbl_sdm_faskes di sini.

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
        <li><a href="sdmk.php"><i class="fas fa-hospital-user"></i> SDMK</a></li>
        <li><a href="sdm.php" class="active"><i class="fas fa-users"></i> SDM (legacy)</a></li>
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

    <!-- SDM PER FASYANKES DIPINDAHKAN -->
    <div class="card" style="border:1px solid rgba(255,193,7,0.25);background:linear-gradient(135deg, rgba(255,193,7,0.08), rgba(255,152,0,0.04));">
        <h3 style="color:#ffd54f"><i class="fas fa-arrow-right" style="color:#ffd54f"></i> Kelola SDMK per Puskesmas dipindahkan</h3>
        <p style="color:rgba(255,255,255,0.7);font-size:13px;line-height:1.7">
            Form <strong>SDM per Fasyankes</strong> yang lama di halaman ini telah <strong>dinonaktifkan</strong> karena menulis langsung ke kolom <code style="background:rgba(255,82,82,0.15);padding:2px 6px;border-radius:6px;color:#ff8a80">jumlah</code>
            yang sekarang adalah <strong>GENERATED COLUMN</strong> (<code>asn_l+asn_p+nonasn_l+nonasn_p</code>) dan menyebabkan <em>silent failure</em>.
        </p>
        <p style="color:rgba(255,255,255,0.6);font-size:12px;margin-top:8px">Silakan kelola data SDMK per Fasyankes (semua jenis) via halaman terpadu — Master & Rekap dalam satu file:</p>
        <div style="margin-top:16px;display:flex;gap:12px;flex-wrap:wrap">
            <a href="sdmk.php?tab=faskes" class="btn-primary" style="background:linear-gradient(135deg,#FF9800,#EF6C00)"><i class="fas fa-hospital-user"></i> Buka SDMK Terpadu</a>
            <a href="sdmk.php?tab=items" class="btn-primary" style="background:rgba(76,175,80,0.15);color:#81c784;border:1px solid rgba(76,175,80,0.25)"><i class="fas fa-list"></i> Master Jenis SDM</a>
        </div>
        <p style="margin-top:12px;font-size:11px;color:rgba(255,255,255,0.35)"><i class="fas fa-info-circle"></i> Bagian lain di halaman ini (CRUD Jenis SDM legacy, Spesialis, SDMK per Kecamatan) tetap aktif karena tidak menyentuh kolom generated.</p>
    </div>
</div>

<!-- JS cascading SDM per Fasyankes DIHAPUS — sekarang dikelola via sdmk.php -->

</body>
</html>