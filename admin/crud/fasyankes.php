<?php
require_once '../config.php';
requireLogin();

// Ambil semua item
$sql = "SELECT * FROM tbl_fasyankes_items WHERE aktif='Y' ORDER BY urutan";
$query = mysqli_query($config, $sql);
$items = [];
while ($row = mysqli_fetch_assoc($query)) {
    $items[] = $row;
}

// Proses tambah
if (isset($_POST['add'])) {
    $nama = mysqli_real_escape_string($config, $_POST['nama_item']);
    $nilai = (int)$_POST['nilai'];
    $urutan = (int)$_POST['urutan'];
    if (!empty($nama)) {
        mysqli_query($config, "INSERT INTO tbl_fasyankes_items (nama_item, nilai, urutan) VALUES ('$nama', $nilai, $urutan)");
    }
    header("Location: fasyankes.php");
    exit;
}

// Proses edit
if (isset($_POST['edit'])) {
    $id = (int)$_POST['id'];
    $nama = mysqli_real_escape_string($config, $_POST['nama_item']);
    $nilai = (int)$_POST['nilai'];
    $urutan = (int)$_POST['urutan'];
    mysqli_query($config, "UPDATE tbl_fasyankes_items SET nama_item='$nama', nilai=$nilai, urutan=$urutan WHERE id=$id");
    header("Location: fasyankes.php");
    exit;
}

// Proses hapus (soft delete)
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    mysqli_query($config, "UPDATE tbl_fasyankes_items SET aktif='N' WHERE id=$id");
    header("Location: fasyankes.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Fasyankes - Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
    <style>
        /* ===== SIDEBAR (sama seperti sebelumnya) ===== */
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

        table { width:100%; border-collapse:collapse; }
        table th { text-align:left; padding:10px 8px; color:#87e3ff; font-weight:600; font-size:13px; border-bottom:2px solid rgba(255,255,255,0.08); }
        table td { padding:10px 8px; border-bottom:1px solid rgba(255,255,255,0.05); }
        table td:last-child { text-align:right; }
        .btn-icon { padding:4px 12px; border-radius:6px; border:none; background:rgba(0,212,255,0.15); color:#00d4ff; cursor:pointer; font-size:13px; font-weight:600; transition:0.3s; text-decoration:none; display:inline-block; }
        .btn-icon:hover { background:rgba(0,212,255,0.3); }
        .btn-danger { background:rgba(255,82,82,0.15); color:#ff6b6b; }
        .btn-danger:hover { background:rgba(255,82,82,0.3); }

        .form-inline { display:flex; gap:12px; align-items:end; flex-wrap:wrap; }
        .form-inline .form-group { display:flex; flex-direction:column; gap:4px; }
        .form-inline label { font-size:12px; color:#87e3ff; font-weight:600; }
        .form-inline input { padding:8px 14px; border-radius:8px; border:1px solid rgba(255,255,255,0.1); background:rgba(255,255,255,0.06); color:#fff; font-size:14px; font-family:'Poppins',sans-serif; width:150px; }
        .form-inline input:focus { outline:none; border-color:#00d4ff; }
        .btn-primary { padding:8px 20px; border-radius:8px; border:none; background:linear-gradient(135deg,#00d4ff,#0088cc); color:#fff; font-weight:600; cursor:pointer; transition:0.3s; }
        .btn-primary:hover { transform:translateY(-2px); box-shadow:0 8px 25px rgba(0,212,255,0.25); }

        @media (max-width:768px) { .sidebar{display:none;} .main-content{padding:20px;} .form-inline{flex-direction:column;align-items:stretch;} .form-inline input{width:100%;} }
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
        <li><a href="penyakit.php"><i class="fas fa-disease"></i> Penyakit</a></li>
        <li><a href="#"><i class="fas fa-map"></i> Data Dasar</a></li>
        <li class="logout"><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
    </ul>
</div>

<!-- MAIN -->
<div class="main-content">
    <div class="page-header">
        <div>
            <h1>Kelola Fasyankes</h1>
            <p>Tambah, edit, atau hapus jenis fasilitas kesehatan</p>
        </div>
        <a href="../index.php" class="back-link"><i class="fas fa-arrow-left"></i> Kembali</a>
    </div>

    <!-- FORM TAMBAH -->
    <div class="card">
        <h3><i class="fas fa-plus-circle" style="color:#00d4ff;margin-right:10px;"></i>Tambah Item Baru</h3>
        <form method="POST" class="form-inline">
            <div class="form-group">
                <label>Nama Fasilitas</label>
                <input type="text" name="nama_item" placeholder="Contoh: Apotek" required>
            </div>
            <div class="form-group">
                <label>Jumlah</label>
                <input type="number" name="nilai" value="0" min="0">
            </div>
            <div class="form-group">
                <label>Urutan</label>
                <input type="number" name="urutan" value="<?php echo count($items)+1; ?>" min="1">
            </div>
            <button type="submit" name="add" class="btn-primary"><i class="fas fa-save"></i> Tambah</button>
        </form>
    </div>


    <!-- TABEL LIST -->
    <div class="card">
        <h3><i class="fas fa-list" style="color:#00d4ff;margin-right:10px;"></i>Daftar Fasilitas</h3>
        <table>
            <thead>
                <tr><th>#</th><th>Nama</th><th>Jumlah</th><th>Urutan</th><th>Aksi</th></tr>
            </thead>
            <tbody>
                <?php if (count($items) > 0): ?>
                <?php foreach ($items as $index => $item): ?>
                <tr>
                    <td><?php echo $index+1; ?></td>
                    <td><?php echo htmlspecialchars($item['nama_item']); ?></td>
                    <td><?php echo number_format($item['nilai']); ?></td>
                    <td><?php echo $item['urutan']; ?></td>
                    <td>
                        <!-- Edit modal / inline edit -->
                        <form method="POST" style="display:inline-block;">
                            <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                            <input type="text" name="nama_item" value="<?php echo htmlspecialchars($item['nama_item']); ?>" style="width:120px;background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.1);border-radius:6px;padding:4px 8px;color:#fff;">
                            <input type="number" name="nilai" value="<?php echo $item['nilai']; ?>" style="width:70px;background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.1);border-radius:6px;padding:4px 8px;color:#fff;">
                            <input type="number" name="urutan" value="<?php echo $item['urutan']; ?>" style="width:60px;background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.1);border-radius:6px;padding:4px 8px;color:#fff;">
                            <button type="submit" name="edit" class="btn-icon"><i class="fas fa-pen"></i> Edit</button>
                        </form>
                        <a href="?delete=<?php echo $item['id']; ?>" class="btn-icon btn-danger" onclick="return confirm('Hapus item ini?')"><i class="fas fa-trash"></i> Hapus</a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php else: ?>
                <tr><td colspan="5" style="text-align:center;color:rgba(255,255,255,0.3);padding:20px;">Belum ada data</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>