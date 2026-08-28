<?php
require_once '../config.php';
requireLogin();
$current_page = basename($_SERVER['PHP_SELF']);

// ============================================================
// AMBIL ID KECAMATAN DARI URL
// ============================================================
$id_kecamatan = (int)($_GET['id'] ?? 0);

$kecQuery = mysqli_query($config, "SELECT * FROM tbl_kecamatan WHERE id_kecamatan=$id_kecamatan AND aktif='Y'");
$kecamatan = mysqli_fetch_assoc($kecQuery);

if (!$kecamatan) {
    header("Location: sdm.php");
    exit;
}

// ============================================================
// PROSES SIMPAN SEMUA JUMLAH SDMK (UPSERT)
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_sdmk'])) {
    $itemsPost = $_POST['jumlah'] ?? []; // array [id_item => jumlah]

    foreach ($itemsPost as $id_item => $jumlah) {
        $id_item = (int)$id_item;
        $jumlah = (int)$jumlah;

        mysqli_query($config, "
            INSERT INTO tbl_sdm_kecamatan (id_kecamatan, id_item, jumlah)
            VALUES ($id_kecamatan, $id_item, $jumlah)
            ON DUPLICATE KEY UPDATE jumlah = $jumlah
        ");
    }

    header("Location: sdm_kecamatan.php?id=$id_kecamatan&msg=saved");
    exit;
}

// ============================================================
// AMBIL DAFTAR ITEM SDM + JUMLAH YANG SUDAH ADA UNTUK KECAMATAN INI
// ============================================================
$items = [];
$itemQuery = mysqli_query($config, "SELECT * FROM tbl_sdm_items WHERE aktif='Y' ORDER BY urutan");
while ($row = mysqli_fetch_assoc($itemQuery)) {
    $items[] = $row;
}

$existing = []; // [id_item => jumlah]
$exQuery = mysqli_query($config, "SELECT id_item, jumlah FROM tbl_sdm_kecamatan WHERE id_kecamatan=$id_kecamatan");
while ($row = mysqli_fetch_assoc($exQuery)) {
    $existing[$row['id_item']] = $row['jumlah'];
}

$totalSdmk = array_sum($existing);
$username = $_SESSION['admin_username'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SDMK <?php echo htmlspecialchars($kecamatan['nama_kecamatan']); ?> - Admin DKK</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Poppins',sans-serif; background:#061426; min-height:100vh; display:flex; color:#fff; }

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
        .page-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:30px; flex-wrap:wrap; gap:12px; }
        .page-header h1 { color:#fff; font-size:28px; font-weight:700; }
        .page-header p { color:#87e3ff; font-size:14px; margin-top:4px; }
        .page-header .back-link { color:#87e3ff; text-decoration:none; font-size:14px; display:flex; align-items:center; gap:8px; transition:0.3s; }
        .page-header .back-link:hover { color:#00d4ff; }

        .card { background:rgba(255,255,255,0.05); backdrop-filter:blur(16px); border-radius:20px; padding:30px; border:1px solid rgba(255,255,255,0.08); margin-bottom:24px; }
        .card h3 { color:#84e7ff; font-size:18px; font-weight:600; margin-bottom:16px; }

        .summary-box {
            display: flex;
            align-items: center;
            gap: 20px;
            padding: 20px 24px;
            border-radius: 16px;
            background: linear-gradient(135deg, rgba(0,212,255,0.15), rgba(0,136,204,0.08));
            border: 1px solid rgba(0,212,255,0.2);
            margin-bottom: 24px;
        }
        .summary-box .icon {
            width: 56px; height: 56px;
            border-radius: 14px;
            background: rgba(0,212,255,0.2);
            display: flex; align-items: center; justify-content: center;
            font-size: 24px; color: #00d4ff;
            flex-shrink: 0;
        }
        .summary-box .num { font-size: 28px; font-weight: 700; color: #fff; }
        .summary-box .label { font-size: 13px; color: #87e3ff; }

        table { width:100%; border-collapse:collapse; }
        table th { text-align:left; padding:10px 8px; color:#87e3ff; font-weight:600; font-size:13px; border-bottom:2px solid rgba(255,255,255,0.08); }
        table td { padding:10px 8px; border-bottom:1px solid rgba(255,255,255,0.05); font-size:14px; }

        .input-jumlah {
            padding: 8px 14px;
            border-radius: 8px;
            border: 1px solid rgba(255,255,255,0.1);
            background: rgba(255,255,255,0.06);
            color: #fff;
            font-size: 14px;
            font-family: 'Poppins', sans-serif;
            width: 120px;
        }
        .input-jumlah:focus { outline: none; border-color: #00d4ff; }

        .btn-primary {
            padding: 10px 24px;
            border-radius: 10px;
            border: none;
            background: linear-gradient(135deg, #00d4ff, #0088cc);
            color: #fff;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
            margin-top: 20px;
        }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(0,212,255,0.25); }

        .alert { padding:14px 20px; border-radius:12px; margin-bottom:20px; display:flex; align-items:center; gap:12px; font-size:14px; font-weight:500; }
        .alert-success { background:rgba(0,212,255,0.12); border:1px solid rgba(0,212,255,0.2); color:#72e8ff; }

        @media (max-width:768px) {
            .sidebar { display:none; }
            .main-content { padding:20px; }
            .input-jumlah { width: 90px; }
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

<div class="main-content">
    <div class="page-header">
        <div>
            <h1>SDMK - <?php echo htmlspecialchars($kecamatan['nama_kecamatan']); ?></h1>
            <p>Isi jumlah tiap profesi untuk kecamatan ini</p>
        </div>
        <a href="sdm.php" class="back-link"><i class="fas fa-arrow-left"></i> Kembali ke SDM</a>
    </div>

    <?php if (isset($_GET['msg'])): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle"></i> Data SDMK berhasil disimpan!</div>
    <?php endif; ?>

    <div class="summary-box">
        <div class="icon"><i class="fas fa-user-md"></i></div>
        <div>
            <div class="num"><?php echo number_format($totalSdmk); ?></div>
            <div class="label">Total SDMK di Kecamatan <?php echo htmlspecialchars($kecamatan['nama_kecamatan']); ?></div>
        </div>
    </div>

    <div class="card">
        <h3><i class="fas fa-edit" style="color:#00d4ff;margin-right:10px;"></i>Input Jumlah SDMK per Profesi</h3>

        <?php if (count($items) === 0): ?>
            <p style="color:rgba(255,255,255,0.4);padding:20px 0;">
                <i class="fas fa-info-circle"></i> Belum ada daftar profesi SDM. Tambahkan dulu di halaman
                <a href="sdm.php" style="color:#00d4ff;">Kelola SDM</a>.
            </p>
        <?php else: ?>
        <form method="POST">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Nama Profesi</th>
                        <th>Jumlah di Kecamatan Ini</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $index => $item): ?>
                    <?php $val = $existing[$item['id']] ?? 0; ?>
                    <tr>
                        <td><?php echo $index + 1; ?></td>
                        <td><?php echo htmlspecialchars($item['nama_item']); ?></td>
                        <td>
                            <input
                                type="number"
                                name="jumlah[<?php echo $item['id']; ?>]"
                                value="<?php echo (int)$val; ?>"
                                min="0"
                                class="input-jumlah">
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <button type="submit" name="save_sdmk" class="btn-primary">
                <i class="fas fa-save"></i> Simpan Semua
            </button>
        </form>
        <?php endif; ?>
    </div>
</div>

</body>
</html>