<?php
require_once '../config.php';
requireLogin();
require_once __DIR__ . '/../../App/Services/SpmLib.php';

SpmLib::ensureSasaranTable($config);
$msg = $_GET['msg'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $nama = mb_substr(trim($_POST['nama'] ?? ''), 0, 255);
        if ($nama !== '') {
            $e = mysqli_real_escape_string($config, $nama);
            $cek = mysqli_query($config, "SELECT id FROM tbl_spm_sasaran WHERE nama='$e' LIMIT 1");
            if ($cek && mysqli_num_rows($cek) > 0) {
                header('Location: spm_sasaran.php?msg=exists'); exit;
            }
            mysqli_query($config, "INSERT INTO tbl_spm_sasaran (nama, aktif) VALUES ('$e','Y')");
            header('Location: spm_sasaran.php?msg=added'); exit;
        }
        header('Location: spm_sasaran.php?msg=invalid'); exit;
    }
    if ($action === 'edit') {
        $id = (int)($_POST['id'] ?? 0);
        $nama = mb_substr(trim($_POST['nama'] ?? ''), 0, 255);
        $aktif = ($_POST['aktif'] ?? 'Y') === 'N' ? 'N' : 'Y';
        if ($id && $nama !== '') {
            // ambil nama lama untuk sinkron opsional ke tbl_spm
            $qOld = mysqli_query($config, "SELECT nama FROM tbl_spm_sasaran WHERE id=$id LIMIT 1");
            $oldName = $qOld ? (mysqli_fetch_assoc($qOld)['nama'] ?? '') : '';
            $e = mysqli_real_escape_string($config, $nama);
            $dup = mysqli_query($config, "SELECT id FROM tbl_spm_sasaran WHERE nama='$e' AND id<>$id LIMIT 1");
            if ($dup && mysqli_num_rows($dup) > 0) { header('Location: spm_sasaran.php?msg=exists'); exit; }
            mysqli_query($config, "UPDATE tbl_spm_sasaran SET nama='$e', aktif='$aktif' WHERE id=$id");
            // sinkron nama sasaran yang dipakai baris SPM (agar select saat input tetap konsisten)
            if ($oldName !== '' && $oldName !== $nama) {
                $oEsc = mysqli_real_escape_string($config, $oldName);
                mysqli_query($config, "UPDATE tbl_spm SET sasaran='$e' WHERE sasaran='$oEsc'");
            }
            header('Location: spm_sasaran.php?msg=updated'); exit;
        }
        header('Location: spm_sasaran.php?msg=invalid'); exit;
    }
    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) mysqli_query($config, "UPDATE tbl_spm_sasaran SET aktif='N' WHERE id=$id");
        header('Location: spm_sasaran.php?msg=deleted'); exit;
    }
}

$rows = [];
$q = mysqli_query($config, "SELECT s.*, (SELECT COUNT(*) FROM tbl_spm WHERE sasaran=s.nama AND aktif='Y') pakai
    FROM tbl_spm_sasaran s WHERE s.aktif='Y' ORDER BY s.nama");
if ($q) while ($r = mysqli_fetch_assoc($q)) $rows[] = $r;
$username = $_SESSION['admin_username'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Sasaran SPM - Admin DKK</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Poppins',sans-serif; background:#061426; min-height:100vh; display:flex; color:#fff; }
        .sidebar { width:260px; min-height:100vh; background:rgba(255,255,255,0.04); border-right:1px solid rgba(255,255,255,0.06); padding:30px 20px; flex-shrink:0; position:sticky; top:0; height:100vh; }
        .sidebar-brand { display:flex; align-items:center; gap:14px; padding-bottom:30px; border-bottom:1px solid rgba(255,255,255,0.06); margin-bottom:24px; }
        .sidebar-brand img { width:48px; height:48px; object-fit:contain; }
        .sidebar-brand h2 { color:#fff; font-size:16px; font-weight:700; }
        .sidebar-brand small { display:block; color:#87e3ff; font-size:10px; letter-spacing:1px; }
        .sidebar-menu { list-style:none; }
        .sidebar-menu li { margin-bottom:4px; }
        .sidebar-menu a { display:flex; align-items:center; gap:12px; padding:12px 16px; border-radius:12px; color:rgba(255,255,255,0.6); text-decoration:none; font-size:14px; font-weight:500; }
        .sidebar-menu a:hover, .sidebar-menu a.active { background:rgba(0,212,255,0.12); color:#fff; }
        .sidebar-menu a i { width:20px; color:rgba(255,255,255,0.3); }
        .sidebar-menu a.active i { color:#00d4ff; }
        .sidebar-menu .logout { margin-top:30px; border-top:1px solid rgba(255,255,255,0.06); padding-top:20px; }
        .sidebar-menu .logout a { color:rgba(255,82,82,0.7); }
        .main-content { flex:1; padding:30px 40px; max-width:1000px; }
        .page-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; }
        .page-header h1 { font-size:24px; }
        .page-header p { color:#87e3ff; font-size:13px; }
        .back-link { color:#87e3ff; text-decoration:none; font-size:14px; }
        .card { background:rgba(255,255,255,0.05); border-radius:20px; padding:24px 26px; border:1px solid rgba(255,255,255,0.08); margin-bottom:22px; }
        .card h3 { color:#84e7ff; font-size:16px; margin-bottom:16px; }
        .alert { padding:14px 20px; border-radius:12px; margin-bottom:20px; font-size:13px; }
        .alert-success { background:rgba(0,212,255,0.12); border:1px solid rgba(0,212,255,0.2); color:#72e8ff; }
        .alert-error { background:rgba(255,82,82,0.12); border:1px solid rgba(255,82,82,0.2); color:#ff8a80; }
        table { width:100%; border-collapse:collapse; }
        th { text-align:left; padding:10px 8px; color:#87e3ff; font-size:12px; border-bottom:2px solid rgba(255,255,255,0.08); }
        td { padding:9px 8px; border-bottom:1px solid rgba(255,255,255,0.05); font-size:13px; }
        .btn { padding:9px 18px; border-radius:10px; border:1px solid rgba(0,212,255,0.25); background:rgba(0,212,255,0.1); color:#72e8ff; font-size:13px; font-weight:600; cursor:pointer; font-family:inherit; text-decoration:none; display:inline-flex; gap:8px; align-items:center; }
        .btn-primary { background:linear-gradient(135deg,#00d4ff,#0088cc); color:#fff; border-color:transparent; }
        .btn-sm { padding:5px 12px; border-radius:7px; font-size:11px; font-weight:600; cursor:pointer; border:1px solid rgba(0,212,255,0.3); background:rgba(0,212,255,0.12); color:#72e8ff; font-family:inherit; }
        .btn-sm.del { border-color:rgba(255,82,82,0.35); background:rgba(255,82,82,0.1); color:#ff8a80; }
        input[type=text] { padding:9px 13px; border-radius:9px; border:1px solid rgba(255,255,255,0.12); background:rgba(255,255,255,0.06); color:#fff; font-size:13px; font-family:inherit; }
        input[type=text]:focus { outline:none; border-color:#00d4ff; }
        .badge { padding:3px 10px; border-radius:20px; background:rgba(0,212,255,0.15); color:#72e8ff; font-size:10px; font-weight:700; }
        @media (max-width:900px) { .sidebar{display:none;} .main-content{padding:20px;} }
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
        <li><a href="sdmk.php"><i class="fas fa-hospital-user"></i> SDMK</a></li>
        <li><a href="sdm.php"><i class="fas fa-users"></i> SDM</a></li>
        <li><a href="kecamatan.php"><i class="fas fa-map"></i> Kecamatan</a></li>
        <li><a href="penyakit.php"><i class="fas fa-disease"></i> Penyakit</a></li>
        <li><a href="spm.php" class="active"><i class="fas fa-chart-pie"></i> SPM</a></li>
        <li><a href="portal_info.php"><i class="fas fa-circle-info"></i> Informasi Portal</a></li>
        <li class="logout"><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
    </ul>
</div>
<div class="main-content">
    <div class="page-header">
        <div>
            <h1>Kelola Sasaran SPM</h1>
            <p>Daftar pilihan sasaran untuk form input SPM (tersimpan di database)</p>
        </div>
        <a href="spm.php" class="back-link"><i class="fas fa-arrow-left"></i> Kembali ke SPM</a>
    </div>

    <?php if ($msg === 'added' || $msg === 'updated'): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> Sasaran berhasil disimpan.</div><?php endif; ?>
    <?php if ($msg === 'deleted'): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> Sasaran berhasil dihapus.</div><?php endif; ?>
    <?php if ($msg === 'exists'): ?><div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> Nama sasaran sudah ada.</div><?php endif; ?>
    <?php if ($msg === 'invalid'): ?><div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> Nama sasaran wajib diisi.</div><?php endif; ?>

    <div class="card">
        <h3><i class="fas fa-plus-circle" style="color:#00d4ff;margin-right:8px;"></i>Tambah Sasaran</h3>
        <form method="POST" style="display:flex;gap:10px;flex-wrap:wrap;align-items:end;">
            <input type="hidden" name="action" value="add">
            <div style="flex:1;min-width:240px;">
                <input type="text" name="nama" style="width:100%;" placeholder="cth: Puskesmas / Aspak / Diluar sekolah" required maxlength="255">
            </div>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Tambah</button>
        </form>
    </div>

    <div class="card">
        <h3><i class="fas fa-list" style="color:#00d4ff;margin-right:8px;"></i>Daftar Sasaran (<?= count($rows) ?>)</h3>
        <table>
            <thead><tr><th>#</th><th>Nama Sasaran</th><th>Dipakai</th><th style="text-align:right;">Aksi</th></tr></thead>
            <tbody>
                <?php if (empty($rows)): ?>
                <tr><td colspan="4" style="text-align:center;color:rgba(255,255,255,0.35);padding:20px;">Belum ada sasaran.</td></tr>
                <?php else: ?>
                <?php foreach ($rows as $i => $r): ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td>
                        <form method="POST" style="display:inline-flex;gap:6px;align-items:center;">
                            <input type="hidden" name="action" value="edit">
                            <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                            <input type="text" name="nama" value="<?= htmlspecialchars($r['nama']) ?>" style="width:260px;">
                            <button type="submit" class="btn-sm"><i class="fas fa-pen"></i> Edit</button>
                        </form>
                    </td>
                    <td><span class="badge"><?= (int)$r['pakai'] ?> baris SPM</span></td>
                    <td style="text-align:right;">
                        <form method="POST" style="display:inline;" onsubmit="return confirm('Yakin hapus sasaran ini?\n\nBaris SPM yang memakai sasaran ini TIDAK ikut terhapus (tetap tersimpan sebagai teks).')">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                            <button type="submit" class="btn-sm del"><i class="fas fa-trash"></i> Hapus</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
