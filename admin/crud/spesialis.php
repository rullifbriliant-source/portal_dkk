<?php
// KELOLA SPESIALIS DOKTER — master tbl_spesialis.
// Dipisah dari admin/crud/sdm.php (legacy). Satu-satunya penulis tbl_spesialis.
// Dibaca READ-ONLY oleh: sdmk.php (quick-pick 3 form), api/get_sdm.php (label popup).
require_once '../config.php';
requireLogin();
$current_page = basename($_SERVER['PHP_SELF']);

// Daftar Spesialis Dokter (master)
$spesialisList = [];
$qSp = mysqli_query($config, "SELECT * FROM tbl_spesialis WHERE aktif='Y' ORDER BY urutan, nama_spesialis");
if ($qSp) while ($row = mysqli_fetch_assoc($qSp)) $spesialisList[] = $row;
else $spesialisList = [];

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
            header("Location: spesialis.php?msg_sp=saved");
            exit;
        } else {
            header("Location: spesialis.php?msg_sp=exists");
            exit;
        }
    }
    header("Location: spesialis.php?msg_sp=invalid");
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
    header("Location: spesialis.php?msg_sp=updated");
    exit;
}
if (isset($_GET['delete_sp'])) {
    $id = (int)$_GET['delete_sp'];
    mysqli_query($config, "UPDATE tbl_spesialis SET aktif='N' WHERE id=$id");
    header("Location: spesialis.php?msg_sp=deleted");
    exit;
}

$username = $_SESSION['admin_username'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Spesialis Dokter - Admin DKK</title>
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
        .sidebar-menu .menu-group {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: rgba(255,255,255,0.3);
            padding: 14px 16px 6px;
        }
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

        .badge-total {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 20px;
            background: rgba(0,212,255,0.12);
            color: #72e8ff;
            font-weight: 600;
            font-size: 12px;
        }
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
        <li class="menu-group">Utama</li>
        <li><a href="../index.php"><i class="fas fa-chart-pie"></i> Dashboard</a></li>
        <li class="menu-group">Fasyankes</li>
        <li><a href="fasyankes.php"><i class="fas fa-hospital"></i> Fasyankes</a></li>
        <li><a href="faskes_rekap.php"><i class="fas fa-table"></i> Rekap Fasyankes</a></li>
        <li class="menu-group">SDM Kesehatan</li>
        <li><a href="sdmk.php"><i class="fas fa-hospital-user"></i> SDMK</a></li>
        <li><a href="sdmk_kecamatan_rekap.php"><i class="fas fa-chart-bar"></i> Rekap SDMK</a></li>
        <li><a href="spesialis.php" class="active"><i class="fas fa-user-doctor"></i> Spesialis Dokter</a></li>
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
            <h1>Spesialis Dokter</h1>
            <p>Master spesialisasi — dipakai sebagai pilihan cepat di SDMK</p>
        </div>
        <a href="../index.php" class="back-link"><i class="fas fa-arrow-left"></i> Kembali</a>
    </div>

    <!-- KELOLA SPESIALIS DOKTER -->
    <div class="card" id="cardSpesialis">
        <h3><i class="fas fa-user-doctor" style="color:#00d4ff;margin-right:10px;"></i>Spesialis Dokter — Master Spesialisasi</h3>
        <p style="color:rgba(255,255,255,0.5);font-size:12px;margin-bottom:16px;">Kelola daftar spesialis dokter (Sp.A, Sp.OG, Sp.PD, dll). Spesialis hanya berlaku untuk profesi <strong>Dokter</strong>.</p>

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
</div>

</body>
</html>
