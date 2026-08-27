<?php
require_once '../config.php';
requireLogin();
$current_page = basename($_SERVER['PHP_SELF']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $nama = mysqli_real_escape_string($config, $_POST['nama_kecamatan'] ?? '');
        $kode = mysqli_real_escape_string($config, $_POST['kode_kecamatan'] ?? '');
        $penduduk = (int)($_POST['jumlah_penduduk'] ?? 0);
        // Hapus $kk karena kolom jumlah_kk TIDAK ADA di database
        $desa = (int)($_POST['jumlah_desa'] ?? 0);
        $puskesmas = (int)($_POST['jumlah_puskesmas'] ?? 0);
        $pustu = (int)($_POST['jumlah_pustu'] ?? 0);
        $posyandu = (int)($_POST['jumlah_posyandu'] ?? 0);
        $klinik = (int)($_POST['jumlah_klinik'] ?? 0);
        $rs = (int)($_POST['jumlah_rumah_sakit'] ?? 0);

        $sql = "INSERT INTO tbl_kecamatan 
                (nama_kecamatan, kode_kecamatan, jumlah_penduduk, jumlah_desa, 
                 jumlah_puskesmas, jumlah_pustu, jumlah_posyandu, jumlah_klinik, jumlah_rumah_sakit, aktif) 
                VALUES ('$nama', '$kode', $penduduk, $desa, $puskesmas, $pustu, $posyandu, $klinik, $rs, 'Y')";
        mysqli_query($config, $sql);
        header('Location: kecamatan.php?msg=added');
        exit;
    }

    if ($action === 'edit') {
        $id = (int)$_POST['id'];
        
        // ===== PENGAMANAN: NAMA KECAMATAN DIKUNCI =====
        $query_old = mysqli_query($config, "SELECT nama_kecamatan FROM tbl_kecamatan WHERE id_kecamatan=$id");
        $row_old = mysqli_fetch_assoc($query_old);
        $nama = $row_old['nama_kecamatan'] ?? ''; 

        $kode = mysqli_real_escape_string($config, $_POST['kode_kecamatan'] ?? '');
        $penduduk = (int)($_POST['jumlah_penduduk'] ?? 0);
        $desa = (int)($_POST['jumlah_desa'] ?? 0);
        $puskesmas = (int)($_POST['jumlah_puskesmas'] ?? 0);
        $pustu = (int)($_POST['jumlah_pustu'] ?? 0);
        $posyandu = (int)($_POST['jumlah_posyandu'] ?? 0);
        $klinik = (int)($_POST['jumlah_klinik'] ?? 0);
        $rs = (int)($_POST['jumlah_rumah_sakit'] ?? 0);

        $sql = "UPDATE tbl_kecamatan SET 
                kode_kecamatan='$kode',
                jumlah_penduduk=$penduduk, 
                jumlah_desa=$desa, 
                jumlah_puskesmas=$puskesmas, 
                jumlah_pustu=$pustu, 
                jumlah_posyandu=$posyandu,
                jumlah_klinik=$klinik,
                jumlah_rumah_sakit=$rs
                WHERE id_kecamatan=$id";
        mysqli_query($config, $sql);
        header('Location: kecamatan.php?msg=updated');
        exit;
    }

    if ($action === 'delete') {
        $id = (int)$_POST['id'];
        mysqli_query($config, "UPDATE tbl_kecamatan SET aktif='N' WHERE id_kecamatan=$id");
        header('Location: kecamatan.php?msg=deleted');
        exit;
    }
}

$data = mysqli_query($config, "SELECT * FROM tbl_kecamatan WHERE aktif='Y' ORDER BY nama_kecamatan");
$username = $_SESSION['admin_username'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Kecamatan - Admin</title>
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
        .page-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:30px; }
        .page-header h1 { color:#fff; font-size:28px; font-weight:700; }
        .page-header p { color:#87e3ff; font-size:14px; margin-top:4px; }
        .page-header .back-link { color:#87e3ff; text-decoration:none; font-size:14px; display:flex; align-items:center; gap:8px; transition:0.3s; }
        .page-header .back-link:hover { color:#00d4ff; }

        .card { background:rgba(255,255,255,0.05); backdrop-filter:blur(16px); border-radius:20px; padding:30px; border:1px solid rgba(255,255,255,0.08); margin-bottom:24px; }
        .card h3 { color:#84e7ff; font-size:18px; font-weight:600; margin-bottom:16px; }

        .form-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(180px,1fr)); gap:12px; }
        .form-grid input { padding:10px 14px; border-radius:10px; border:1px solid rgba(255,255,255,0.1); background:rgba(255,255,255,0.06); color:#fff; font-size:14px; font-family:'Poppins',sans-serif; width:100%; margin-top:4px; }
        .form-grid input:focus { outline:none; border-color:#00d4ff; }
        
        .form-group { display:flex; flex-direction:column; }
        .form-group label { color:#87e3ff; font-size:12px; font-weight:600; margin-bottom:4px; }

        .btn-primary { padding:10px 24px; border-radius:10px; border:none; background:linear-gradient(135deg,#00d4ff,#0088cc); color:#fff; font-weight:600; cursor:pointer; transition:0.3s; margin-top:12px; }
        .btn-primary:hover { transform:translateY(-2px); box-shadow:0 8px 25px rgba(0,212,255,0.25); }

        table { width:100%; border-collapse:collapse; }
        table th { text-align:left; padding:10px 8px; color:#87e3ff; font-weight:600; font-size:13px; border-bottom:2px solid rgba(255,255,255,0.08); }
        table td { padding:10px 8px; border-bottom:1px solid rgba(255,255,255,0.05); }
        table td:last-child { text-align:right; }
        .btn-icon { padding:4px 12px; border-radius:6px; border:none; background:rgba(0,212,255,0.15); color:#00d4ff; cursor:pointer; font-size:13px; font-weight:600; transition:0.3s; text-decoration:none; display:inline-block; }
        .btn-icon:hover { background:rgba(0,212,255,0.3); }
        .btn-danger { background:rgba(255,82,82,0.15); color:#ff6b6b; }
        .btn-danger:hover { background:rgba(255,82,82,0.3); }

        .alert { padding:14px 20px; border-radius:12px; margin-bottom:20px; display:flex; align-items:center; gap:12px; font-size:14px; font-weight:500; }
        .alert-success { background:rgba(0,212,255,0.12); border:1px solid rgba(0,212,255,0.2); color:#72e8ff; }

        #editModal { display:none; position:fixed; inset:0; background:rgba(0,0,0,0.6); backdrop-filter:blur(4px); z-index:999; justify-content:center; align-items:center; }
        .modal-box { background:#0b223c; padding:35px; border-radius:24px; max-width:700px; width:95%; border:1px solid rgba(255,255,255,0.1); box-shadow:0 30px 60px rgba(0,0,0,0.5); }
        .modal-box h2 { color:#84e7ff; margin-bottom:20px; }
        .modal-box .form-grid { grid-template-columns:1fr 1fr; }
        .modal-actions { display:flex; gap:12px; margin-top:20px; }
        .modal-actions .btn-secondary { padding:10px 20px; border-radius:10px; border:1px solid rgba(255,255,255,0.1); background:transparent; color:rgba(255,255,255,0.6); cursor:pointer; transition:0.3s; }
        .modal-actions .btn-secondary:hover { background:rgba(255,255,255,0.05); color:#fff; }

        .readonly-input {
            background: rgba(255,255,255,0.03) !important;
            cursor: not-allowed !important;
            opacity: 0.7 !important;
        }

        @media (max-width:768px) { .sidebar{display:none;} .main-content{padding:20px;} .form-grid{grid-template-columns:1fr;} .modal-box .form-grid{grid-template-columns:1fr;} }
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
            <h1>Kelola Kecamatan</h1>
            <p>Edit data kecamatan yang muncul di panel Informasi Wilayah</p>
        </div>
        <a href="../index.php" class="back-link"><i class="fas fa-arrow-left"></i> Kembali</a>
    </div>

    <?php if (isset($_GET['msg'])): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle"></i> Data berhasil disimpan!</div>
    <?php endif; ?>

    <div class="card">
        <h3><i class="fas fa-list" style="color:#00d4ff;margin-right:10px;"></i>Daftar Kecamatan</h3>
        <table>
            <thead>
                <tr>
                    <th>#</th><th>Nama</th><th>Kode</th><th>Penduduk</th>
                    <th>Desa</th><th>Puskesmas</th><th>Pustu</th><th>Posyandu</th><th>Klinik</th><th>RS</th><th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php $i=1; while ($row = mysqli_fetch_assoc($data)): ?>
                <tr>
                    <td><?= $i++ ?></td>
                    <td><?= htmlspecialchars($row['nama_kecamatan']) ?></td>
                    <td><?= $row['kode_kecamatan'] ?></td>
                    <td><?= number_format((float)($row['jumlah_penduduk'] ?? 0)) ?></td>
                    <td><?= $row['jumlah_desa'] ?></td>
                    <td><?= $row['jumlah_puskesmas'] ?></td>
                    <td><?= $row['jumlah_pustu'] ?></td>
                    <td><?= $row['jumlah_posyandu'] ?></td>
                    <td><?= $row['jumlah_klinik'] ?? 0 ?></td>
                    <td><?= $row['jumlah_rumah_sakit'] ?? 0 ?></td>
                    <td>
                        <button class="btn-icon edit-btn" 
                            data-id="<?= $row['id_kecamatan'] ?>"
                            data-nama="<?= htmlspecialchars($row['nama_kecamatan']) ?>"
                            data-kode="<?= $row['kode_kecamatan'] ?>"
                            data-penduduk="<?= $row['jumlah_penduduk'] ?>"
                            data-desa="<?= $row['jumlah_desa'] ?>"
                            data-puskesmas="<?= $row['jumlah_puskesmas'] ?>"
                            data-pustu="<?= $row['jumlah_pustu'] ?>"
                            data-posyandu="<?= $row['jumlah_posyandu'] ?>"
                            data-klinik="<?= $row['jumlah_klinik'] ?? 0 ?>"
                            data-rs="<?= $row['jumlah_rumah_sakit'] ?? 0 ?>">
                            <i class="fas fa-pen"></i>
                        </button>
                        <form method="POST" style="display:inline-block;" onsubmit="return confirm('Yakin hapus data ini?')">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $row['id_kecamatan'] ?>">
                            <button type="submit" class="btn-icon btn-danger"><i class="fas fa-trash"></i></button>
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
                <?php if (mysqli_num_rows($data) == 0): ?>
                <tr><td colspan="11" style="text-align:center;color:rgba(255,255,255,0.3);padding:20px;">Belum ada data kecamatan</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- MODAL EDIT (NAMA DIKUNCI) -->
<div id="editModal">
    <div class="modal-box">
        <h2 id="modalTitle"><i class="fas fa-pen" style="color:#00d4ff;"></i> Edit Kecamatan</h2>
        <form method="POST" id="editForm">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="editId">
            <div class="form-grid">
                <div class="form-group">
                    <label>Nama Kecamatan (Tidak Bisa Diubah)</label>
                    <input type="text" name="nama_kecamatan" id="editNama" required readonly class="readonly-input">
                </div>
                <div class="form-group"><label>Kode</label><input type="text" name="kode_kecamatan" id="editKode" required></div>
                <div class="form-group"><label>Penduduk</label><input type="number" name="jumlah_penduduk" id="editPenduduk"></div>
                <div class="form-group"><label>Desa</label><input type="number" name="jumlah_desa" id="editDesa"></div>
                <div class="form-group"><label>Puskesmas</label><input type="number" name="jumlah_puskesmas" id="editPuskesmas"></div>
                <div class="form-group"><label>Pustu</label><input type="number" name="jumlah_pustu" id="editPustu"></div>
                <div class="form-group"><label>Posyandu</label><input type="number" name="jumlah_posyandu" id="editPosyandu"></div>
                <div class="form-group"><label>Klinik</label><input type="number" name="jumlah_klinik" id="editKlinik"></div>
                <div class="form-group"><label>Rumah Sakit</label><input type="number" name="jumlah_rumah_sakit" id="editRS"></div>
            </div>
            <div class="modal-actions">
                <button type="submit" class="btn-primary"><i class="fas fa-save"></i> Simpan</button>
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
        document.getElementById('editKode').value = this.dataset.kode;
        document.getElementById('editPenduduk').value = this.dataset.penduduk;
        document.getElementById('editDesa').value = this.dataset.desa;
        document.getElementById('editPuskesmas').value = this.dataset.puskesmas;
        document.getElementById('editPustu').value = this.dataset.pustu;
        document.getElementById('editPosyandu').value = this.dataset.posyandu;
        document.getElementById('editKlinik').value = this.dataset.klinik;
        document.getElementById('editRS').value = this.dataset.rs;

        document.getElementById('modalTitle').innerHTML = '<i class="fas fa-pen" style="color:#00d4ff;"></i> Edit Kecamatan: ' + this.dataset.nama;
        document.getElementById('editModal').style.display = 'flex';
    };
});

document.getElementById('editModal').onclick = function(e) {
    if (e.target === this) this.style.display = 'none';
};
</script>

</body>
</html>