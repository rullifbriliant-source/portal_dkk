<?php
require_once '../config.php';
requireLogin();
$current_page = basename($_SERVER['PHP_SELF']);

$uploadDir = __DIR__ . '/../../uploads/faskes/';
$uploadUrl = '../../uploads/faskes/';

if (!is_dir($uploadDir)) {
    @mkdir($uploadDir, 0777, true);
}

// Helper upload foto
function handleUploadFoto($file, $uploadDir, $oldFoto = null) {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return $oldFoto;
    }

    $allowedExts = ['jpg', 'jpeg', 'png', 'webp'];
    $fileInfo = pathinfo($file['name']);
    $ext = strtolower($fileInfo['extension'] ?? '');

    if (!in_array($ext, $allowedExts)) {
        return $oldFoto;
    }

    // Limit size 3MB
    if ($file['size'] > 3 * 1024 * 1024) {
        return $oldFoto;
    }

    $newFileName = 'faskes_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $targetPath = $uploadDir . $newFileName;

    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        // Hapus file lama jika ada dan file tersebut ada di direktori upload
        if ($oldFoto && file_exists($uploadDir . $oldFoto)) {
            @unlink($uploadDir . $oldFoto);
        }
        return $newFileName;
    }

    return $oldFoto;
}

// ==========================================
// HANDLE ACTIONS (ADD, EDIT, DELETE)
// ==========================================
$msg = $_GET['msg'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // TAMBAH FASKES
    if ($action === 'add') {
        $nama = mysqli_real_escape_string($config, trim($_POST['nama_faskes'] ?? ''));
        $jenis = mysqli_real_escape_string($config, trim($_POST['jenis'] ?? 'Puskesmas'));
        $id_kecamatan = !empty($_POST['id_kecamatan']) ? (int)$_POST['id_kecamatan'] : "NULL";
        $alamat = mysqli_real_escape_string($config, trim($_POST['alamat'] ?? ''));
        $telepon = mysqli_real_escape_string($config, trim($_POST['telepon'] ?? ''));
        $email = mysqli_real_escape_string($config, trim($_POST['email'] ?? ''));

        if ($id_kecamatan === "NULL") {
            header("Location: fasyankes.php?msg=kecamatan_required");
            exit;
        }

        // Dapatkan nama kecamatan untuk kompatibilitas kolom lama
        $nama_kecamatan = '';
        $qKec = mysqli_query($config, "SELECT nama_kecamatan FROM tbl_kecamatan WHERE id_kecamatan=$id_kecamatan");
        if ($rKec = mysqli_fetch_assoc($qKec)) {
            $nama_kecamatan = mysqli_real_escape_string($config, strtolower($rKec['nama_kecamatan']));
        }

        $foto = null;
        if (isset($_FILES['foto'])) {
            $foto = handleUploadFoto($_FILES['foto'], $uploadDir);
        }
        $fotoSql = $foto ? "'$foto'" : "NULL";

        if (!empty($nama)) {
            $sql = "INSERT INTO tbl_faskes 
                    (nama_faskes, jenis, id_kecamatan, kecamatan, alamat, telepon, email, foto, aktif) 
                    VALUES ('$nama', '$jenis', $id_kecamatan, '$nama_kecamatan', '$alamat', '$telepon', '$email', $fotoSql, 'Y')";
            mysqli_query($config, $sql);
            header("Location: fasyankes.php?msg=added");
            exit;
        }
    }

    // EDIT FASKES
    if ($action === 'edit') {
        $id = (int)$_POST['id_faskes'];
        $nama = mysqli_real_escape_string($config, trim($_POST['nama_faskes'] ?? ''));
        $jenis = mysqli_real_escape_string($config, trim($_POST['jenis'] ?? 'Puskesmas'));
        $id_kecamatan = !empty($_POST['id_kecamatan']) ? (int)$_POST['id_kecamatan'] : "NULL";
        $alamat = mysqli_real_escape_string($config, trim($_POST['alamat'] ?? ''));
        $telepon = mysqli_real_escape_string($config, trim($_POST['telepon'] ?? ''));
        $email = mysqli_real_escape_string($config, trim($_POST['email'] ?? ''));
        $aktif = ($_POST['aktif'] ?? 'Y') === 'N' ? 'N' : 'Y';

        if ($id_kecamatan === "NULL") {
            header("Location: fasyankes.php?msg=kecamatan_required");
            exit;
        }

        // Ambil data faskes lama
        $oldQuery = mysqli_query($config, "SELECT foto FROM tbl_faskes WHERE id_faskes=$id");
        $oldRow = mysqli_fetch_assoc($oldQuery);
        $oldFoto = $oldRow['foto'] ?? null;

        // Dapatkan nama kecamatan untuk kompatibilitas
        $nama_kecamatan = '';
        $qKec = mysqli_query($config, "SELECT nama_kecamatan FROM tbl_kecamatan WHERE id_kecamatan=$id_kecamatan");
        if ($rKec = mysqli_fetch_assoc($qKec)) {
            $nama_kecamatan = mysqli_real_escape_string($config, strtolower($rKec['nama_kecamatan']));
        }

        // Upload foto baru jika ada
        $foto = $oldFoto;
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            $foto = handleUploadFoto($_FILES['foto'], $uploadDir, $oldFoto);
        }
        $fotoSql = $foto ? "'$foto'" : "NULL";

        $sql = "UPDATE tbl_faskes SET 
                nama_faskes='$nama',
                jenis='$jenis',
                id_kecamatan=$id_kecamatan,
                kecamatan='$nama_kecamatan',
                alamat='$alamat',
                telepon='$telepon',
                email='$email',
                foto=$fotoSql,
                aktif='$aktif'
                WHERE id_faskes=$id";
        mysqli_query($config, $sql);
        header("Location: fasyankes.php?msg=updated");
        exit;
    }

    // HAPUS (SOFT DELETE)
    if ($action === 'delete') {
        $id = (int)$_POST['id_faskes'];
        mysqli_query($config, "UPDATE tbl_faskes SET aktif='N' WHERE id_faskes=$id");
        header("Location: fasyankes.php?msg=deleted");
        exit;
    }
}

// ==========================================
// DATA RETRIEVAL (FILTERS & COUNTS)
// ==========================================
$filterKecamatan = isset($_GET['kecamatan']) && $_GET['kecamatan'] !== '' ? (int)$_GET['kecamatan'] : null;
$filterJenis = isset($_GET['jenis']) && $_GET['jenis'] !== '' ? mysqli_real_escape_string($config, $_GET['jenis']) : null;
$search = isset($_GET['search']) && $_GET['search'] !== '' ? mysqli_real_escape_string($config, trim($_GET['search'])) : null;

// Ambil daftar semua kecamatan aktif untuk dropdown
$daftarKecamatan = [];
$resKec = mysqli_query($config, "SELECT id_kecamatan, nama_kecamatan FROM tbl_kecamatan WHERE aktif='Y' ORDER BY nama_kecamatan");
while ($r = mysqli_fetch_assoc($resKec)) {
    $daftarKecamatan[] = $r;
}

// Bangun query list faskes
$whereClauses = ["f.aktif='Y'"];
if ($filterKecamatan) {
    $whereClauses[] = "f.id_kecamatan = $filterKecamatan";
}
if ($filterJenis) {
    $whereClauses[] = "f.jenis = '$filterJenis'";
}
if ($search) {
    $whereClauses[] = "(f.nama_faskes LIKE '%$search%' OR f.alamat LIKE '%$search%')";
}
$whereSql = implode(' AND ', $whereClauses);

$sqlFaskes = "SELECT f.*, k.nama_kecamatan 
              FROM tbl_faskes f 
              LEFT JOIN tbl_kecamatan k ON f.id_kecamatan = k.id_kecamatan 
              WHERE $whereSql 
              ORDER BY k.nama_kecamatan ASC, f.nama_faskes ASC";
$dataFaskes = mysqli_query($config, $sqlFaskes);

// Ringkasan Statistik Global Faskes
$statQuery = mysqli_query($config, "SELECT 
    COUNT(*) AS total,
    SUM(CASE WHEN jenis='Puskesmas' THEN 1 ELSE 0 END) AS total_puskesmas,
    SUM(CASE WHEN jenis='Pustu' THEN 1 ELSE 0 END) AS total_pustu,
    SUM(CASE WHEN jenis='Klinik' THEN 1 ELSE 0 END) AS total_klinik,
    SUM(CASE WHEN jenis='Rumah Sakit' THEN 1 ELSE 0 END) AS total_rs
    FROM tbl_faskes WHERE aktif='Y'");
$stats = mysqli_fetch_assoc($statQuery);

$username = $_SESSION['admin_username'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Fasilitas Kesehatan (Fasyankes) - Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Poppins',sans-serif; background:#061426; min-height:100vh; display:flex; color:#fff; }
        
        /* SIDEBAR */
        .sidebar { width:260px; min-height:100vh; background:rgba(255,255,255,0.04); backdrop-filter:blur(12px); border-right:1px solid rgba(255,255,255,0.06); padding:30px 20px; flex-shrink:0; position:sticky; top:0; height:100vh; overflow-y:auto; }
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
        .sidebar-menu .logout a:hover { background:rgba(255,82,82,0.12); color:#ff6b6b; }

        .main-content { flex:1; padding:30px 40px; }
        .page-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; }
        .page-header h1 { color:#fff; font-size:28px; font-weight:700; }
        .page-header p { color:#87e3ff; font-size:14px; margin-top:4px; }
        .page-header .back-link { color:#87e3ff; text-decoration:none; font-size:14px; display:flex; align-items:center; gap:8px; transition:0.3s; }
        .page-header .back-link:hover { color:#00d4ff; }

        /* STATS BAR */
        .stats-grid { display:grid; grid-template-columns:repeat(5, 1fr); gap:16px; margin-bottom:24px; }
        .stat-card { background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.06); border-radius:16px; padding:18px 20px; display:flex; align-items:center; gap:16px; }
        .stat-icon { width:44px; height:44px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:20px; }
        .stat-info h4 { font-size:11px; color:#87e3ff; text-transform:uppercase; letter-spacing:0.5px; }
        .stat-info .num { font-size:22px; font-weight:700; color:#fff; margin-top:2px; }

        .card { background:rgba(255,255,255,0.05); backdrop-filter:blur(16px); border-radius:20px; padding:28px; border:1px solid rgba(255,255,255,0.08); margin-bottom:24px; }
        .card h3 { color:#84e7ff; font-size:18px; font-weight:600; margin-bottom:18px; display:flex; align-items:center; gap:10px; }

        /* FORM */
        .form-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(220px, 1fr)); gap:16px; }
        .form-group { display:flex; flex-direction:column; }
        .form-group.full-width { grid-column: 1 / -1; }
        .form-group label { color:#87e3ff; font-size:12px; font-weight:600; margin-bottom:6px; }
        .form-group input, .form-group select, .form-group textarea {
            padding:10px 14px; border-radius:10px; border:1px solid rgba(255,255,255,0.1);
            background:rgba(255,255,255,0.06); color:#fff; font-size:13px; font-family:'Poppins',sans-serif; width:100%;
        }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus { outline:none; border-color:#00d4ff; background:rgba(0,212,255,0.05); }
        .form-group select option { background:#0b223c; color:#fff; }

        .btn-primary { padding:10px 24px; border-radius:10px; border:none; background:linear-gradient(135deg,#00d4ff,#0088cc); color:#fff; font-weight:600; cursor:pointer; transition:0.3s; display:inline-flex; align-items:center; gap:8px; font-size:14px; }
        .btn-primary:hover { transform:translateY(-2px); box-shadow:0 8px 25px rgba(0,212,255,0.25); }

        /* FILTERS & SEARCH */
        .toolbar { display:flex; justify-content:space-between; align-items:center; gap:16px; flex-wrap:wrap; margin-bottom:20px; }
        .filters-wrap { display:flex; gap:12px; align-items:center; flex-wrap:wrap; }
        .filters-wrap select, .filters-wrap input { padding:8px 14px; border-radius:10px; border:1px solid rgba(255,255,255,0.1); background:rgba(255,255,255,0.06); color:#fff; font-size:13px; }
        .filters-wrap select option { background:#0b223c; color:#fff; }

        /* TABLE */
        table { width:100%; border-collapse:collapse; }
        table th { text-align:left; padding:12px 10px; color:#87e3ff; font-weight:600; font-size:13px; border-bottom:2px solid rgba(255,255,255,0.08); }
        table td { padding:12px 10px; border-bottom:1px solid rgba(255,255,255,0.05); font-size:13px; vertical-align:middle; }
        table td:last-child { text-align:right; }

        .faskes-thumb { width:46px; height:46px; border-radius:10px; object-fit:cover; border:1px solid rgba(255,255,255,0.1); background:rgba(255,255,255,0.03); }
        .faskes-thumb-placeholder { width:46px; height:46px; border-radius:10px; display:flex; align-items:center; justify-content:center; background:rgba(0,212,255,0.1); color:#00d4ff; font-size:18px; }

        .badge-jenis { display:inline-block; padding:3px 10px; border-radius:8px; font-size:11px; font-weight:600; text-transform:capitalize; }
        .badge-puskesmas { background:rgba(0,212,255,0.15); color:#72e8ff; border:1px solid rgba(0,212,255,0.3); }
        .badge-pustu { background:rgba(255,193,7,0.15); color:#ffd54f; border:1px solid rgba(255,193,7,0.3); }
        .badge-klinik { background:rgba(76,175,80,0.15); color:#81c784; border:1px solid rgba(76,175,80,0.3); }
        .badge-rs { background:rgba(233,30,99,0.15); color:#f48fb1; border:1px solid rgba(233,30,99,0.3); }
        .badge-default { background:rgba(156,39,176,0.15); color:#ce93d8; border:1px solid rgba(156,39,176,0.3); }

        .btn-icon { padding:6px 12px; border-radius:8px; border:none; background:rgba(0,212,255,0.15); color:#00d4ff; cursor:pointer; font-size:12px; font-weight:600; transition:0.3s; text-decoration:none; display:inline-inline; }
        .btn-icon:hover { background:rgba(0,212,255,0.3); }
        .btn-danger { background:rgba(255,82,82,0.15); color:#ff6b6b; }
        .btn-danger:hover { background:rgba(255,82,82,0.3); }

        .alert { padding:14px 20px; border-radius:12px; margin-bottom:20px; display:flex; align-items:center; gap:12px; font-size:14px; font-weight:500; }
        .alert-success { background:rgba(0,212,255,0.12); border:1px solid rgba(0,212,255,0.2); color:#72e8ff; }

        /* MODAL */
        #editModal { display:none; position:fixed; inset:0; background:rgba(0,0,0,0.65); backdrop-filter:blur(6px); z-index:999; justify-content:center; align-items:center; }
        .modal-box { background:#0b223c; padding:35px; border-radius:24px; max-width:650px; width:95%; border:1px solid rgba(255,255,255,0.1); box-shadow:0 30px 60px rgba(0,0,0,0.6); max-height:90vh; overflow-y:auto; }
        .modal-box h2 { color:#84e7ff; margin-bottom:20px; font-size:20px; }
        .modal-actions { display:flex; gap:12px; margin-top:24px; justify-content:flex-end; }
        .modal-actions .btn-secondary { padding:10px 20px; border-radius:10px; border:1px solid rgba(255,255,255,0.1); background:transparent; color:rgba(255,255,255,0.6); cursor:pointer; transition:0.3s; }
        .modal-actions .btn-secondary:hover { background:rgba(255,255,255,0.05); color:#fff; }

        .img-preview-box { display:flex; align-items:center; gap:12px; margin-top:8px; }
        .img-preview { width:60px; height:60px; border-radius:8px; object-fit:cover; border:1px solid rgba(255,255,255,0.15); display:none; }

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
        <li><a href="../index.php"><i class="fas fa-chart-pie"></i> Dashboard</a></li>
        <li><a href="fasyankes.php" class="active"><i class="fas fa-hospital"></i> Fasyankes</a></li>
        <li><a href="sdm.php"><i class="fas fa-users"></i> SDM</a></li>
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
            <h1>Kelola Fasilitas Kesehatan (Fasyankes)</h1>
            <p>Kelola data fasilitas kesehatan individual per kecamatan (Puskesmas, Pustu, Klinik, Rumah Sakit, dll).</p>
        </div>
        <a href="../index.php" class="back-link"><i class="fas fa-arrow-left"></i> Kembali</a>
    </div>

    <?php if ($msg === 'added'): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle"></i> Fasilitas kesehatan berhasil ditambahkan!</div>
    <?php elseif ($msg === 'updated'): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle"></i> Data fasilitas kesehatan berhasil diperbarui!</div>
    <?php elseif ($msg === 'deleted'): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle"></i> Fasilitas kesehatan berhasil dihapus!</div>
    <?php elseif ($msg === 'kecamatan_required'): ?>
        <div class="alert" style="background:rgba(255,193,7,0.12);border:1px solid rgba(255,193,7,0.25);color:#ffd54f;"><i class="fas fa-exclamation-triangle"></i> Kecamatan wajib dipilih untuk setiap fasilitas kesehatan.</div>
    <?php endif; ?>

    <!-- STATS OVERVIEW -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon" style="background:rgba(0,212,255,0.15);color:#00d4ff;"><i class="fas fa-hospital-alt"></i></div>
            <div class="stat-info">
                <h4>Total Faskes</h4>
                <div class="num"><?= number_format((int)($stats['total'] ?? 0)) ?></div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background:rgba(0,212,255,0.15);color:#72e8ff;"><i class="fas fa-house-medical"></i></div>
            <div class="stat-info">
                <h4>Puskesmas</h4>
                <div class="num"><?= number_format((int)($stats['total_puskesmas'] ?? 0)) ?></div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background:rgba(255,193,7,0.15);color:#ffd54f;"><i class="fas fa-clinic-medical"></i></div>
            <div class="stat-info">
                <h4>Pustu</h4>
                <div class="num"><?= number_format((int)($stats['total_pustu'] ?? 0)) ?></div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background:rgba(76,175,80,0.15);color:#81c784;"><i class="fas fa-stethoscope"></i></div>
            <div class="stat-info">
                <h4>Klinik</h4>
                <div class="num"><?= number_format((int)($stats['total_klinik'] ?? 0)) ?></div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background:rgba(233,30,99,0.15);color:#f48fb1;"><i class="fas fa-hospital"></i></div>
            <div class="stat-info">
                <h4>Rumah Sakit</h4>
                <div class="num"><?= number_format((int)($stats['total_rs'] ?? 0)) ?></div>
            </div>
        </div>
    </div>

    <!-- FORM TAMBAH FASKES -->
    <div class="card">
        <h3><i class="fas fa-plus-circle" style="color:#00d4ff;"></i>Tambah Fasilitas Kesehatan Baru</h3>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="add">
            <div class="form-grid">
                <div class="form-group" style="grid-column: span 2;">
                    <label>Nama Fasilitas Kesehatan *</label>
                    <input type="text" name="nama_faskes" placeholder="Contoh: Puskesmas Kartasura" required>
                </div>
                <div class="form-group">
                    <label>Jenis Fasilitas *</label>
                    <select name="jenis" required>
                        <option value="Puskesmas">Puskesmas</option>
                        <option value="Pustu">Pustu (Puskesmas Pembantu)</option>
                        <option value="Klinik">Klinik</option>
                        <option value="Rumah Sakit">Rumah Sakit</option>
                        <option value="Poskesdes">Poskesdes</option>
                        <option value="Apotek">Apotek</option>
                        <option value="Laboratorium">Laboratorium</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Kecamatan *</label>
                    <select name="id_kecamatan" required>
                        <option value="">-- Pilih Kecamatan --</option>
                        <?php foreach ($daftarKecamatan as $kec): ?>
                            <option value="<?= $kec['id_kecamatan'] ?>"><?= htmlspecialchars($kec['nama_kecamatan']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Telepon</label>
                    <input type="text" name="telepon" placeholder="Contoh: (0271) 781234">
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" placeholder="Contoh: info@pkm-kartasura.go.id">
                </div>
                <div class="form-group" style="grid-column: span 2;">
                    <label>Alamat Lengkap</label>
                    <input type="text" name="alamat" placeholder="Contoh: Jl. Ahmad Yani No. 45, Kartasura">
                </div>
                <div class="form-group" style="grid-column: span 2;">
                    <label>Foto Fasilitas (JPG, PNG, WEBP, Maks. 3MB)</label>
                    <input type="file" name="foto" accept="image/jpeg,image/png,image/webp">
                </div>
            </div>
            <div style="margin-top:20px;">
                <button type="submit" class="btn-primary"><i class="fas fa-save"></i> Simpan Fasilitas</button>
            </div>
        </form>
    </div>

    <!-- TABEL DAFTAR FASKES -->
    <div class="card">
        <div class="toolbar">
            <h3><i class="fas fa-list" style="color:#00d4ff;"></i>Daftar Fasilitas Kesehatan</h3>
            
            <!-- FILTER TOOLBAR -->
            <form method="GET" class="filters-wrap">
                <select name="kecamatan" onchange="this.form.submit()">
                    <option value="">Semua Kecamatan</option>
                    <?php foreach ($daftarKecamatan as $kec): ?>
                        <option value="<?= $kec['id_kecamatan'] ?>" <?= $filterKecamatan == $kec['id_kecamatan'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($kec['nama_kecamatan']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <select name="jenis" onchange="this.form.submit()">
                    <option value="">Semua Jenis</option>
                    <option value="Puskesmas" <?= $filterJenis === 'Puskesmas' ? 'selected' : '' ?>>Puskesmas</option>
                    <option value="Pustu" <?= $filterJenis === 'Pustu' ? 'selected' : '' ?>>Pustu</option>
                    <option value="Klinik" <?= $filterJenis === 'Klinik' ? 'selected' : '' ?>>Klinik</option>
                    <option value="Rumah Sakit" <?= $filterJenis === 'Rumah Sakit' ? 'selected' : '' ?>>Rumah Sakit</option>
                    <option value="Poskesdes" <?= $filterJenis === 'Poskesdes' ? 'selected' : '' ?>>Poskesdes</option>
                    <option value="Apotek" <?= $filterJenis === 'Apotek' ? 'selected' : '' ?>>Apotek</option>
                    <option value="Laboratorium" <?= $filterJenis === 'Laboratorium' ? 'selected' : '' ?>>Laboratorium</option>
                </select>

                <input type="text" name="search" placeholder="Cari nama/alamat..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                <button type="submit" class="btn-icon"><i class="fas fa-search"></i></button>
                <?php if ($filterKecamatan || $filterJenis || $search): ?>
                    <a href="fasyankes.php" class="btn-icon btn-danger" title="Reset Filter"><i class="fas fa-times"></i></a>
                <?php endif; ?>
            </form>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Foto</th>
                    <th>Nama Fasilitas</th>
                    <th>Jenis</th>
                    <th>Kecamatan</th>
                    <th>Kontak</th>
                    <th>Alamat</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = mysqli_fetch_assoc($dataFaskes)): ?>
                <?php
                    $badgeClass = 'badge-default';
                    if ($row['jenis'] === 'Puskesmas') $badgeClass = 'badge-puskesmas';
                    elseif ($row['jenis'] === 'Pustu') $badgeClass = 'badge-pustu';
                    elseif ($row['jenis'] === 'Klinik') $badgeClass = 'badge-klinik';
                    elseif ($row['jenis'] === 'Rumah Sakit') $badgeClass = 'badge-rs';

                    $fotoPath = !empty($row['foto']) && file_exists($uploadDir . $row['foto']) ? $uploadUrl . $row['foto'] : null;
                ?>
                <tr>
                    <td style="width:60px;">
                        <?php if ($fotoPath): ?>
                            <img src="<?= htmlspecialchars($fotoPath) ?>" alt="Foto" class="faskes-thumb">
                        <?php else: ?>
                            <div class="faskes-thumb-placeholder"><i class="fas fa-hospital"></i></div>
                        <?php endif; ?>
                    </td>
                    <td><strong><?= htmlspecialchars($row['nama_faskes']) ?></strong></td>
                    <td><span class="badge-jenis <?= $badgeClass ?>"><?= htmlspecialchars($row['jenis']) ?></span></td>
                    <td><i class="fas fa-map-marker-alt" style="color:#00d4ff;margin-right:4px;"></i> <?= htmlspecialchars($row['nama_kecamatan'] ?? ucfirst($row['kecamatan'] ?? '-')) ?></td>
                    <td>
                        <?php if (!empty($row['telepon'])): ?>
                            <div><i class="fas fa-phone" style="font-size:11px;color:#87e3ff;"></i> <?= htmlspecialchars($row['telepon']) ?></div>
                        <?php endif; ?>
                        <?php if (!empty($row['email'])): ?>
                            <div><i class="fas fa-envelope" style="font-size:11px;color:#87e3ff;"></i> <?= htmlspecialchars($row['email']) ?></div>
                        <?php endif; ?>
                        <?php if (empty($row['telepon']) && empty($row['email'])): ?>
                            <span style="color:rgba(255,255,255,0.3);">-</span>
                        <?php endif; ?>
                    </td>
                    <td style="max-width:200px;color:rgba(255,255,255,0.7);"><?= htmlspecialchars($row['alamat'] ?? '-') ?></td>
                    <td>
                        <button class="btn-icon edit-btn" 
                            data-id="<?= $row['id_faskes'] ?>"
                            data-nama="<?= htmlspecialchars($row['nama_faskes']) ?>"
                            data-jenis="<?= htmlspecialchars($row['jenis']) ?>"
                            data-kecamatan="<?= (int)$row['id_kecamatan'] ?>"
                            data-alamat="<?= htmlspecialchars($row['alamat'] ?? '') ?>"
                            data-telepon="<?= htmlspecialchars($row['telepon'] ?? '') ?>"
                            data-email="<?= htmlspecialchars($row['email'] ?? '') ?>"
                            data-foto="<?= $fotoPath ? htmlspecialchars($fotoPath) : '' ?>">
                            <i class="fas fa-pen"></i> Edit
                        </button>
                        <form method="POST" style="display:inline-block;" onsubmit="return confirm('Yakin hapus fasilitas kesehatan ini?')">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id_faskes" value="<?= $row['id_faskes'] ?>">
                            <button type="submit" class="btn-icon btn-danger"><i class="fas fa-trash"></i></button>
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
                <?php if (mysqli_num_rows($dataFaskes) == 0): ?>
                <tr>
                    <td colspan="7" style="text-align:center;color:rgba(255,255,255,0.4);padding:30px;">
                        <i class="fas fa-circle-info" style="margin-right:8px;"></i> Tidak ada data fasilitas kesehatan yang sesuai dengan filter.
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- MODAL EDIT FASKES -->
<div id="editModal">
    <div class="modal-box">
        <h2 id="modalTitle"><i class="fas fa-pen" style="color:#00d4ff;"></i> Edit Fasilitas Kesehatan</h2>
        <form method="POST" enctype="multipart/form-data" id="editForm">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id_faskes" id="editId">
            <div class="form-grid">
                <div class="form-group" style="grid-column: span 2;">
                    <label>Nama Fasilitas Kesehatan *</label>
                    <input type="text" name="nama_faskes" id="editNama" required>
                </div>
                <div class="form-group">
                    <label>Jenis Fasilitas *</label>
                    <select name="jenis" id="editJenis" required>
                        <option value="Puskesmas">Puskesmas</option>
                        <option value="Pustu">Pustu (Puskesmas Pembantu)</option>
                        <option value="Klinik">Klinik</option>
                        <option value="Rumah Sakit">Rumah Sakit</option>
                        <option value="Poskesdes">Poskesdes</option>
                        <option value="Apotek">Apotek</option>
                        <option value="Laboratorium">Laboratorium</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Kecamatan *</label>
                    <select name="id_kecamatan" id="editKecamatan" required>
                        <option value="">-- Pilih Kecamatan --</option>
                        <?php foreach ($daftarKecamatan as $kec): ?>
                            <option value="<?= $kec['id_kecamatan'] ?>"><?= htmlspecialchars($kec['nama_kecamatan']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Telepon</label>
                    <input type="text" name="telepon" id="editTelepon">
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" id="editEmail">
                </div>
                <div class="form-group" style="grid-column: span 2;">
                    <label>Alamat Lengkap</label>
                    <input type="text" name="alamat" id="editAlamat">
                </div>
                <div class="form-group" style="grid-column: span 2;">
                    <label>Ganti Foto (Opsional)</label>
                    <input type="file" name="foto" accept="image/jpeg,image/png,image/webp">
                    <div class="img-preview-box">
                        <img id="editFotoPreview" src="" alt="Preview" class="img-preview">
                        <span id="editFotoHint" style="font-size:12px;color:rgba(255,255,255,0.4);">Belum ada foto</span>
                    </div>
                </div>
            </div>
            <div class="modal-actions">
                <button type="submit" class="btn-primary"><i class="fas fa-save"></i> Simpan Perubahan</button>
                <button type="button" class="btn-secondary" onclick="document.getElementById('editModal').style.display='none'">Batal</button>
            </div>
        </form>
    </div>
</div>

<script>
document.querySelectorAll('.edit-btn').forEach(btn => {
    btn.onclick = function() {
        document.getElementById('editId').value = this.dataset.id;
        document.getElementById('editNama').value = this.dataset.nama;
        document.getElementById('editJenis').value = this.dataset.jenis;
        document.getElementById('editKecamatan').value = this.dataset.kecamatan;
        document.getElementById('editTelepon').value = this.dataset.telepon;
        document.getElementById('editEmail').value = this.dataset.email;
        document.getElementById('editAlamat').value = this.dataset.alamat;

        var fotoUrl = this.dataset.foto;
        var preview = document.getElementById('editFotoPreview');
        var hint = document.getElementById('editFotoHint');
        if (fotoUrl) {
            preview.src = fotoUrl;
            preview.style.display = 'block';
            hint.textContent = 'Foto saat ini (pilih file baru jika ingin mengganti)';
        } else {
            preview.src = '';
            preview.style.display = 'none';
            hint.textContent = 'Belum ada foto diunggah';
        }

        document.getElementById('modalTitle').innerHTML = '<i class="fas fa-pen" style="color:#00d4ff;"></i> Edit Fasilitas: ' + this.dataset.nama;
        document.getElementById('editModal').style.display = 'flex';
    };
});

document.getElementById('editModal').onclick = function(e) {
    if (e.target === this) this.style.display = 'none';
};
</script>

</body>
</html>