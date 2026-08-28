<?php
require_once '../config.php';
requireLogin();
$current_page = basename($_SERVER['PHP_SELF']);

// Ambil kecamatan dari URL
$kecamatan_aktif = isset($_GET['kecamatan']) ? mysqli_real_escape_string($config, $_GET['kecamatan']) : '';

if (empty($kecamatan_aktif)) {
    header("Location: penyakit.php");
    exit;
}

// Ambil total kasus di kecamatan ini
$q_total = mysqli_query($config, "SELECT SUM(nilai) as total FROM tbl_penyakit_kecamatan WHERE kode_kecamatan = '$kecamatan_aktif' AND aktif='Y'");
$total_kasus = mysqli_fetch_assoc($q_total)['total'] ?? 0;

// Ambil 10 nama penyakit MASTER (walaupun belum diisi per kecamatan)
$q_penyakit_master = mysqli_query($config, "SELECT nama_item FROM tbl_penyakit_items WHERE aktif='Y' ORDER BY urutan LIMIT 10");
$list_penyakit = [];
while ($row = mysqli_fetch_assoc($q_penyakit_master)) {
    $list_penyakit[] = $row['nama_item'];
}

// ============================================================
// PROSES SIMPAN SEMUA (BULK UPDATE)
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan_semua'])) {
    foreach ($_POST['jumlah'] as $nama_penyakit => $nilai) {
        $nama_penyakit_clean = mysqli_real_escape_string($config, $nama_penyakit);
        $nilai_clean = (int)$nilai;

        // Cek apakah data sudah ada di tabel kecamatan ini
        $check = mysqli_query($config, "SELECT id FROM tbl_penyakit_kecamatan WHERE kode_kecamatan = '$kecamatan_aktif' AND nama_item = '$nama_penyakit_clean' AND aktif='Y'");
        
        if (mysqli_num_rows($check) > 0) {
            // Jika sudah ada, UPDATE
            $row = mysqli_fetch_assoc($check);
            mysqli_query($config, "UPDATE tbl_penyakit_kecamatan SET nilai = $nilai_clean WHERE id = ".$row['id']);
        } else {
            // Jika belum ada, INSERT
            if ($nilai_clean > 0) {
                mysqli_query($config, "INSERT INTO tbl_penyakit_kecamatan (nama_item, nilai, urutan, kode_kecamatan, aktif) VALUES ('$nama_penyakit_clean', $nilai_clean, 1, '$kecamatan_aktif', 'Y')");
            }
        }
    }
    
    // Refresh total kasus setelah simpan
    $q_total = mysqli_query($config, "SELECT SUM(nilai) as total FROM tbl_penyakit_kecamatan WHERE kode_kecamatan = '$kecamatan_aktif' AND aktif='Y'");
    $total_kasus = mysqli_fetch_assoc($q_total)['total'] ?? 0;
    
    header("Location: penyakit_kecamatan.php?kecamatan=" . urlencode($kecamatan_aktif) . "&msg=saved");
    exit;
}

$username = $_SESSION['admin_username'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Penyakit - <?= htmlspecialchars($kecamatan_aktif) ?> - Admin DKK</title>
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
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .page-header h1 { color: #fff; font-size: 28px; font-weight: 700; }
        .page-header p { color: #87e3ff; font-size: 14px; margin-top: 4px; }
        .page-header .back-link { color: #87e3ff; text-decoration: none; font-size: 14px; display: flex; align-items: center; gap: 8px; transition: 0.3s; }
        .page-header .back-link:hover { color: #00d4ff; }

        .card { background: rgba(255,255,255,0.05); backdrop-filter: blur(16px); border-radius: 20px; padding: 30px; border: 1px solid rgba(255,255,255,0.08); margin-bottom: 24px; }
        .card h3 { color: #84e7ff; font-size: 18px; font-weight: 600; margin-bottom: 16px; }

        /* Card Total */
        .total-card { display: flex; align-items: center; gap: 20px; margin-bottom: 24px; padding: 20px; }
        .total-icon { width: 70px; height: 70px; border-radius: 18px; background: rgba(0,212,255,0.15); display: flex; align-items: center; justify-content: center; font-size: 32px; color: #00d4ff; }
        .total-info h2 { font-size: 36px; color: #fff; margin: 0; font-weight: 700; }
        .total-info p { color: rgba(255,255,255,0.6); font-size: 14px; margin: 0; }

        table { width: 100%; border-collapse: collapse; }
        table th { text-align: left; padding: 10px 8px; color: #87e3ff; font-weight: 600; font-size: 13px; border-bottom: 2px solid rgba(255,255,255,0.08); }
        table td { padding: 12px 8px; border-bottom: 1px solid rgba(255,255,255,0.05); font-size: 13px; }
        
        /* Input jumlah */
        .input-jumlah { width: 200px; padding: 10px 14px; border-radius: 10px; border: 1px solid rgba(255,255,255,0.1); background: rgba(255,255,255,0.06); color: #fff; font-size: 14px; font-family: 'Poppins', sans-serif; }
        .input-jumlah:focus { outline: none; border-color: #00d4ff; }

        .btn-primary { padding: 10px 24px; border-radius: 10px; border: none; background: linear-gradient(135deg, #00d4ff, #0088cc); color: #fff; font-weight: 600; cursor: pointer; transition: 0.3s; margin-top: 20px; }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(0,212,255,0.25); }

        .alert { padding: 14px 20px; border-radius: 12px; margin-bottom: 20px; font-size: 14px; font-weight: 500; }
        .alert-success { background: rgba(0,212,255,0.12); border: 1px solid rgba(0,212,255,0.2); color: #72e8ff; }

        @media (max-width: 768px) {
            .sidebar { display: none; }
            .main-content { padding: 20px; }
            .total-card { flex-direction: column; align-items: flex-start; }
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
        <li><a href="sdm.php"><i class="fas fa-users"></i> SDM</a></li>
        <li><a href="kecamatan.php"><i class="fas fa-map"></i> Kecamatan</a></li>
        <li><a href="penyakit.php" class="active"><i class="fas fa-disease"></i> Penyakit</a></li>
        <li><a href="portal_info.php"><i class="fas fa-circle-info"></i> Informasi Portal</a></li>
        <li class="logout"><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
    </ul>
</div>

<div class="main-content">
    <div class="page-header">
        <div>
            <h1>Penyakit - <?= htmlspecialchars($kecamatan_aktif) ?></h1>
            <p>Isi jumlah kasus tiap penyakit untuk kecamatan ini</p>
        </div>
        <a href="penyakit.php" class="back-link"><i class="fas fa-arrow-left"></i> Kembali ke Penyakit</a>
    </div>

    <?php if (isset($_GET['msg']) && $_GET['msg'] == 'saved'): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle"></i> Data berhasil disimpan!</div>
    <?php endif; ?>

    <!-- CARD TOTAL -->
    <div class="card total-card">
        <div class="total-icon">
            <i class="fas fa-virus"></i>
        </div>
        <div class="total-info">
            <h2><?php echo number_format($total_kasus); ?></h2>
            <p>Total Kasus di Kecamatan <?= htmlspecialchars($kecamatan_aktif) ?></p>
        </div>
    </div>

    <!-- FORM INPUT -->
    <div class="card">
        <h3><i class="fas fa-pen" style="color:#00d4ff;margin-right:10px;"></i>Input Jumlah Kasus per Penyakit</h3>
        <form method="POST">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Nama Penyakit</th>
                        <th>Jumlah di Kecamatan Ini</th>
                    </tr>
                </thead>
                                <tbody>
                    <?php 
                    // Ambil data yang sudah diinput sebelumnya untuk kecamatan ini
                    $q_existing = mysqli_query($config, "SELECT * FROM tbl_penyakit_kecamatan WHERE kode_kecamatan = '$kecamatan_aktif' AND aktif='Y'");
                    $existing_data = [];
                    while ($row = mysqli_fetch_assoc($q_existing)) {
                        $existing_data[$row['nama_item']] = $row['nilai'];
                    }

                    $no = 1;
                    foreach ($list_penyakit as $nama): 
                        $nilai_sekarang = isset($existing_data[$nama]) ? $existing_data[$nama] : 0;
                    ?>
                    <tr>
                        <td><?php echo $no++; ?></td>
                        <td><?php echo htmlspecialchars($nama); ?></td>
                        <td>
                            <input type="number" name="jumlah[<?php echo htmlspecialchars($nama); ?>]" value="<?php echo $nilai_sekarang; ?>" min="0" class="input-jumlah">
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <button type="submit" name="simpan_semua" class="btn-primary"><i class="fas fa-save"></i> Simpan Semua</button>
        </form>
    </div>
</div>

</body>
</html>     