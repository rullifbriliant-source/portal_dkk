<?php
require_once '../config.php';
requireLogin();

$message = '';
$error = '';

// Ambil data
$sql = "SELECT deskripsi FROM tbl_portal_info WHERE id = 1";
$query = mysqli_query($config, $sql);
$data = mysqli_fetch_assoc($query);

// Proses update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $deskripsi = mysqli_real_escape_string($config, $_POST['deskripsi']);
    $sql = "UPDATE tbl_portal_info SET deskripsi = '$deskripsi' WHERE id = 1";
    if (mysqli_query($config, $sql)) {
        $message = "Data berhasil diupdate!";
        $data['deskripsi'] = $deskripsi;
    } else {
        $error = "Gagal update: " . mysqli_error($config);
    }
}

$username = $_SESSION['admin_username'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Informasi Portal - Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Poppins',sans-serif; background:#061426; min-height:100vh; display:flex; color:#fff; }
        .sidebar {
            width:260px; min-height:100vh; background:rgba(255,255,255,0.04); backdrop-filter:blur(12px);
            border-right:1px solid rgba(255,255,255,0.06); padding:30px 20px; flex-shrink:0;
            position:sticky; top:0; height:100vh; overflow-y:auto;
        }
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
        .page-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:30px; }
        .page-header h1 { color:#fff; font-size:28px; font-weight:700; }
        .page-header p { color:#87e3ff; font-size:14px; margin-top:4px; }
        .page-header .back-link { color:#87e3ff; text-decoration:none; font-size:14px; display:flex; align-items:center; gap:8px; transition:0.3s; }
        .page-header .back-link:hover { color:#00d4ff; }

        .card { background:rgba(255,255,255,0.05); backdrop-filter:blur(16px); border-radius:20px; padding:30px; border:1px solid rgba(255,255,255,0.08); margin-bottom:24px; }
        .card h3 { color:#84e7ff; font-size:18px; font-weight:600; margin-bottom:16px; }

        .form-group { margin-bottom:16px; }
        .form-group label { display:block; color:#87e3ff; font-size:13px; font-weight:600; margin-bottom:6px; }
        .form-group textarea { width:100%; padding:12px 16px; border-radius:12px; border:1px solid rgba(255,255,255,0.1); background:rgba(255,255,255,0.06); color:#fff; font-size:14px; font-family:'Poppins',sans-serif; min-height:150px; resize:vertical; }
        .form-group textarea:focus { outline:none; border-color:#00d4ff; }

        .btn-primary { padding:12px 32px; border-radius:12px; border:none; background:linear-gradient(135deg,#00d4ff,#0088cc); color:#fff; font-weight:600; cursor:pointer; transition:0.3s; font-family:'Poppins',sans-serif; }
        .btn-primary:hover { transform:translateY(-2px); box-shadow:0 8px 25px rgba(0,212,255,0.25); }
        .btn-secondary { padding:12px 32px; border-radius:12px; border:1px solid rgba(255,255,255,0.1); background:transparent; color:rgba(255,255,255,0.6); cursor:pointer; transition:0.3s; font-family:'Poppins',sans-serif; text-decoration:none; display:inline-block; }
        .btn-secondary:hover { background:rgba(255,255,255,0.05); color:#fff; }

        .alert { padding:14px 20px; border-radius:12px; margin-bottom:20px; display:flex; align-items:center; gap:12px; font-size:14px; font-weight:500; }
        .alert-success { background:rgba(0,212,255,0.12); border:1px solid rgba(0,212,255,0.2); color:#72e8ff; }
        .alert-error { background:rgba(255,70,70,0.12); border:1px solid rgba(255,70,70,0.2); color:#ff6b6b; }

        .preview-box { background:rgba(255,255,255,0.03); padding:16px; border-radius:12px; border:1px dashed rgba(255,255,255,0.1); margin-top:12px; color:rgba(255,255,255,0.6); font-size:13px; }

        @media (max-width:768px) { .sidebar{display:none;} .main-content{padding:20px;} }
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
        <li><a href="fasyankes.php"><i class="fas fa-hospital"></i> Fasyankes</a></li>
        <li><a href="sdm.php"><i class="fas fa-users"></i> SDM</a></li>
        <li><a href="kecamatan.php"><i class="fas fa-map"></i> Kecamatan</a></li>
        <li><a href="penyakit.php"><i class="fas fa-disease"></i> Penyakit</a></li>
        <li><a href="portal_info.php" class="active"><i class="fas fa-circle-info"></i> Informasi Portal</a></li>
        <li class="logout"><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
    </ul>
</div>

<!-- MAIN CONTENT -->
<div class="main-content">
    <div class="page-header">
        <div>
            <h1>Edit Informasi Portal</h1>
            <p>Ubah teks deskripsi yang muncul di card Informasi Portal (panel kiri)</p>
        </div>
        <a href="../index.php" class="back-link"><i class="fas fa-arrow-left"></i> Kembali</a>
    </div>

    <?php if ($message): ?>
    <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= $message ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
    <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= $error ?></div>
    <?php endif; ?>

    <div class="card">
        <h3><i class="fas fa-pen" style="color:#00d4ff;margin-right:10px;"></i> Edit Deskripsi</h3>
        <form method="POST">
            <div class="form-group">
                <label for="deskripsi">Teks Deskripsi</label>
                <textarea name="deskripsi" id="deskripsi" rows="6"><?= htmlspecialchars($data['deskripsi'] ?? '') ?></textarea>
            </div>
            <div style="display:flex; gap:12px;">
                <button type="submit" class="btn-primary"><i class="fas fa-save"></i> Simpan</button>
                <a href="../index.php" class="btn-secondary">Batal</a>
            </div>
        </form>

        <div class="preview-box">
            <strong>Preview:</strong><br>
            <span id="previewText"><?= htmlspecialchars($data['deskripsi'] ?? '') ?></span>
        </div>
    </div>
</div>

<script>
// Preview live
document.getElementById('deskripsi').addEventListener('input', function() {
    document.getElementById('previewText').textContent = this.value;
});
</script>

</body>
</html>