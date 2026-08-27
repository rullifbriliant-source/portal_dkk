<?php
require_once '../config.php';
requireLogin();
$current_page = basename($_SERVER['PHP_SELF']);
$items = [];
$query = mysqli_query($config, "SELECT * FROM tbl_penyakit_items WHERE aktif='Y' ORDER BY urutan");
while ($row = mysqli_fetch_assoc($query)) {
    $items[] = $row;
}

if (isset($_POST['add'])) {
    $nama = mysqli_real_escape_string($config, $_POST['nama_item']);
    $nilai = (int)$_POST['nilai'];
    $urutan = (int)$_POST['urutan'];
    
    if (!empty($nama)) {
        $check = mysqli_query($config, "SELECT id FROM tbl_penyakit_items WHERE nama_item = '$nama'");
        if (mysqli_num_rows($check) == 0) {
            mysqli_query($config, "INSERT INTO tbl_penyakit_items (nama_item, nilai, urutan) VALUES ('$nama', $nilai, $urutan)");
            header("Location: penyakit.php?msg=added"); // Redirect dengan pesan
            exit;
        } else {
            header("Location: penyakit.php?msg=exists"); // Redirect dengan pesan
            exit;
        }
    }
    header("Location: penyakit.php");
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
    
    mysqli_query($config, "UPDATE tbl_penyakit_items SET 
        nama_item='$nama', 
        nilai=$nilai, 
        urutan=$urutan 
        WHERE id=$id");
    
    header("Location: penyakit.php?msg=updated");
    exit;
}

// ============================================================
// PROSES HAPUS (soft delete)
// ============================================================

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    mysqli_query($config, "UPDATE tbl_penyakit_items SET aktif='N' WHERE id=$id");
    header("Location: penyakit.php?msg=deleted");
    exit;
}

$username = $_SESSION['admin_username'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Penyakit - Admin DKK</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family: 'Poppins', sans-serif; background: #061426; min-height: 100vh; display: flex; color: #fff; }
        .sidebar { width: 260px; min-height: 100vh; background: rgba(255,255,255,0.04); backdrop-filter: blur(12px); border-right: 1px solid rgba(255,255,255,0.06); padding: 30px 20px; flex-shrink: 0; position: sticky; top: 0; height: 100vh; overflow-y: auto; }
        .sidebar-brand { display: flex; align-items: center; gap: 14px; padding-bottom: 30px; border-bottom: 1px solid rgba(255,255,255,0.06); margin-bottom: 24px; }
        .sidebar-brand img { width: 48px; height: 48px; object-fit: contain; }
        .sidebar-brand h2 { color: #fff; font-size: 16px; font-weight: 700; line-height: 1.2; }
        .sidebar-brand small { display: block; color: #87e3ff; font-size: 10px; font-weight: 500; letter-spacing: 1px; }
        .sidebar-menu { list-style: none; }
        .sidebar-menu li { margin-bottom: 4px; }
        .sidebar-menu a { display: flex; align-items: center; gap: 12px; padding: 12px 16px; border-radius: 12px; color: rgba(255,255,255,0.6); text-decoration: none; font-size: 14px; font-weight: 500; transition: 0.3s; }
        .sidebar-menu a:hover, .sidebar-menu a.active { background: rgba(0,212,255,0.12); color: #fff; }
        .sidebar-menu a i { width: 20px; color: rgba(255,255,255,0.3); font-size: 16px; }
        .sidebar-menu a.active i { color: #00d4ff; }
        .sidebar-menu .logout { margin-top: 30px; border-top: 1px solid rgba(255,255,255,0.06); padding-top: 20px; }
        .sidebar-menu .logout a { color: rgba(255,82,82,0.7); }
        .sidebar-menu .logout a:hover { background: rgba(255,82,82,0.12); color: #ff6b6b; }

        .main-content { flex: 1; padding: 30px 40px; }
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .page-header h1 { color: #fff; font-size: 28px; font-weight: 700; }
        .page-header p { color: #87e3ff; font-size: 14px; margin-top: 4px; }
        .page-header .back-link { color: #87e3ff; text-decoration: none; font-size: 14px; display: flex; align-items: center; gap: 8px; transition: 0.3s; }
        .page-header .back-link:hover { color: #00d4ff; }

        .card { background: rgba(255,255,255,0.05); backdrop-filter: blur(16px); border-radius: 20px; padding: 30px; border: 1px solid rgba(255,255,255,0.08); margin-bottom: 24px; }
        .card h3 { color: #84e7ff; font-size: 18px; font-weight: 600; margin-bottom: 16px; }

        table { width: 100%; border-collapse: collapse; }
        table th { text-align: left; padding: 10px 8px; color: #87e3ff; font-weight: 600; font-size: 13px; border-bottom: 2px solid rgba(255,255,255,0.08); }
        table td { padding: 10px 8px; border-bottom: 1px solid rgba(255,255,255,0.05); font-size: 13px; }
        table td:last-child { text-align: right; }

        .btn-icon { padding: 4px 12px; border-radius: 6px; border: none; background: rgba(0,212,255,0.15); color: #00d4ff; cursor: pointer; font-size: 13px; font-weight: 600; transition: 0.3s; text-decoration: none; display: inline-block; }
        .btn-icon:hover { background: rgba(0,212,255,0.3); }
        .btn-danger { background: rgba(255,82,82,0.15); color: #ff6b6b; }
        .btn-danger:hover { background: rgba(255,82,82,0.3); }

        .form-inline { display: flex; gap: 12px; align-items: end; flex-wrap: wrap; }
        .form-inline .form-group { display: flex; flex-direction: column; gap: 4px; }
        .form-inline label { font-size: 12px; color: #87e3ff; font-weight: 600; }
        .form-inline input { padding: 8px 14px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.1); background: rgba(255,255,255,0.06); color: #fff; font-size: 14px; font-family: 'Poppins', sans-serif; width: 150px; }
        .form-inline input:focus { outline: none; border-color: #00d4ff; }
        .btn-primary { padding: 8px 20px; border-radius: 8px; border: none; background: linear-gradient(135deg, #00d4ff, #0088cc); color: #fff; font-weight: 600; cursor: pointer; transition: 0.3s; }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(0,212,255,0.25); }

        .form-inline-edit { display: inline-flex; gap: 6px; align-items: center; flex-wrap: wrap; }
        .form-inline-edit input { padding: 4px 8px; border-radius: 6px; border: 1px solid rgba(255,255,255,0.1); background: rgba(255,255,255,0.06); color: #fff; font-size: 13px; font-family: 'Poppins', sans-serif; }
        .form-inline-edit input:focus { outline: none; border-color: #00d4ff; }
        .form-inline-edit .input-name { width: 150px; }
        .form-inline-edit .input-value { width: 80px; }
        .form-inline-edit .input-order { width: 60px; }

        .alert { padding: 12px 16px; border-radius: 10px; margin-bottom: 16px; font-size: 14px; font-weight: 500; }
        .alert-success { background: rgba(0,212,255,0.12); border: 1px solid rgba(0,212,255,0.2); color: #72e8ff; }
        .alert-error { background: rgba(255,82,82,0.12); border: 1px solid rgba(255,82,82,0.2); color: #ff6b6b; }

        @media (max-width: 768px) {
            .sidebar { display: none; }
            .main-content { padding: 20px; }
            .form-inline { flex-direction: column; align-items: stretch; }
            .form-inline input { width: 100%; }
        }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="sidebar-brand">
        <img src="../../assets/img/kabupaten.png" alt="Logo">
        <h2>Portal DKK<br><small>Dashboard Admin</small></h2>
    </div>
    <ul class="sidebar-menu">
        <li><a href="../index.php" class="<?= ($current_page == 'index.php') ? 'active' : '' ?>"><i class="fas fa-chart-pie"></i> Dashboard</a></li>
        <li><a href="fasyankes.php" class="<?= ($current_page == 'fasyankes.php') ? 'active' : '' ?>"><i class="fas fa-hospital"></i> Fasyankes</a></li>
        <li><a href="sdm.php" class="<?= ($current_page == 'sdm.php') ? 'active' : '' ?>"><i class="fas fa-users"></i> SDM</a></li>
        <li><a href="kecamatan.php" class="<?= ($current_page == 'kecamatan.php') ? 'active' : '' ?>"><i class="fas fa-map"></i> Kecamatan</a></li>
        <li><a href="penyakit.php" class="<?= ($current_page == 'penyakit.php') ? 'active' : '' ?>"><i class="fas fa-disease"></i> Penyakit</a></li>
        <li class="logout"><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
    </ul>
</div>

<div class="main-content">
    <div class="page-header">
        <div>
            <h1>Kelola 10 Penyakit Populer</h1>
            <p>Tambah, edit, atau hapus data penyakit yang ditampilkan di portal utama</p>
        </div>
        <a href="../index.php" class="back-link"><i class="fas fa-arrow-left"></i> Kembali</a>
    </div>

    <?php 
    if (isset($_GET['msg'])) {
        if ($_GET['msg'] == 'added') echo "<div class='alert alert-success'><i class='fas fa-check-circle'></i> Item berhasil ditambahkan!</div>";
        elseif ($_GET['msg'] == 'exists') echo "<div class='alert alert-error'><i class='fas fa-exclamation-circle'></i> Item sudah ada!</div>";
        elseif ($_GET['msg'] == 'updated') echo "<div class='alert alert-success'><i class='fas fa-check-circle'></i> Item berhasil diperbarui!</div>";
        elseif ($_GET['msg'] == 'deleted') echo "<div class='alert alert-success'><i class='fas fa-check-circle'></i> Item berhasil dihapus!</div>";
    }
    ?>

    <div class="card">
        <h3><i class="fas fa-plus-circle" style="color:#00d4ff;margin-right:10px;"></i>Tambah Penyakit</h3>
        <form method="POST" class="form-inline">
            <div class="form-group">
                <label>Nama Penyakit</label>
                <input type="text" name="nama_item" placeholder="Contoh: Demam Berdarah" required>
            </div>
            <div class="form-group">
                <label>Jumlah Kasus</label>
                <input type="number" name="nilai" value="0" min="0">
            </div>
            <div class="form-group">
                <label>Urutan</label>
                <input type="number" name="urutan" value="<?php echo count($items) + 1; ?>" min="1">
            </div>
            <button type="submit" name="add" class="btn-primary"><i class="fas fa-save"></i> Tambah</button>
        </form>
    </div>

    <div class="card">
        <h3><i class="fas fa-list" style="color:#00d4ff;margin-right:10px;"></i>Daftar Penyakit Populer</h3>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Nama Penyakit</th>
                    <th>Jumlah Kasus</th>
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
                    <td><?php echo number_format($item['nilai']); ?></td>
                    <td><?php echo $item['urutan']; ?></td>
                    <td>
                        <form method="POST" class="form-inline-edit">
                            <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                            <input type="text" name="nama_item" value="<?php echo htmlspecialchars($item['nama_item']); ?>" class="input-name">
                            <input type="number" name="nilai" value="<?php echo $item['nilai']; ?>" class="input-value">
                            <input type="number" name="urutan" value="<?php echo $item['urutan']; ?>" class="input-order">
                            <button type="submit" name="edit" class="btn-icon"><i class="fas fa-pen"></i> Edit</button>
                        </form>
                        <a href="?delete=<?php echo $item['id']; ?>" class="btn-icon btn-danger" onclick="return confirm('Hapus penyakit ini?')"><i class="fas fa-trash"></i> Hapus</a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php else: ?>
                <tr>
                    <td colspan="5" style="text-align:center;color:rgba(255,255,255,0.3);padding:20px;">
                        <i class="fas fa-database"></i> Belum ada data penyakit
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>