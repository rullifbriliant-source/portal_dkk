<?php
require_once 'config.php';
requireLogin();

// Proses CRUD
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $nama = mysqli_real_escape_string($config, $_POST['nama_kecamatan']);
        $kode = mysqli_real_escape_string($config, $_POST['kode_kecamatan']);
        $penduduk = (int)$_POST['jumlah_penduduk'];
        $desa = (int)$_POST['jumlah_desa'];
        $puskesmas = (int)$_POST['jumlah_puskesmas'];
        $pustu = (int)$_POST['jumlah_pustu'];
        $posyandu = (int)$_POST['jumlah_posyandu'];

        $sql = "INSERT INTO tbl_kecamatan (nama_kecamatan, kode_kecamatan, jumlah_penduduk, jumlah_desa, jumlah_puskesmas, jumlah_pustu, jumlah_posyandu, aktif) 
                VALUES ('$nama', '$kode', $penduduk, $desa, $puskesmas, $pustu, $posyandu, 'Y')";
        mysqli_query($config, $sql);
        header('Location: kecamatan.php?msg=added');
        exit;
    }

    if ($action === 'edit') {
        $id = (int)$_POST['id'];
        $nama = mysqli_real_escape_string($config, $_POST['nama_kecamatan']);
        $penduduk = (int)$_POST['jumlah_penduduk'];
        $desa = (int)$_POST['jumlah_desa'];
        $puskesmas = (int)$_POST['jumlah_puskesmas'];
        $pustu = (int)$_POST['jumlah_pustu'];
        $posyandu = (int)$_POST['jumlah_posyandu'];

        $sql = "UPDATE tbl_kecamatan SET 
                nama_kecamatan='$nama', 
                jumlah_penduduk=$penduduk, 
                jumlah_desa=$desa, 
                jumlah_puskesmas=$puskesmas, 
                jumlah_pustu=$pustu, 
                jumlah_posyandu=$posyandu 
                WHERE id_kecamatan=$id";
        mysqli_query($config, $sql);
        header('Location: kecamatan.php?msg=updated');
        exit;
    }

    if ($action === 'delete') {
        $id = (int)$_POST['id'];
        $sql = "UPDATE tbl_kecamatan SET aktif='N' WHERE id_kecamatan=$id";
        mysqli_query($config, $sql);
        header('Location: kecamatan.php?msg=deleted');
        exit;
    }
}

// Ambil data
$data = mysqli_query($config, "SELECT * FROM tbl_kecamatan WHERE aktif='Y' ORDER BY nama_kecamatan");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kelola Kecamatan</title>
    <link rel="stylesheet" href="assets/css/admin.css">
</head>
<body>
    <div class="admin-wrapper">
        <aside class="sidebar">
            <h2>🛠 Admin Panel</h2>
            <nav>
                <a href="index.php">📊 Dashboard</a>
                <a href="kecamatan.php" class="active">🏘 Kecamatan</a>
                <a href="penyakit.php">🦠 Penyakit</a>
                <a href="fasyankes.php">🏥 Fasyankes</a>
                <a href="sdm.php">👨‍⚕️ SDM</a>
                <a href="logout.php" class="logout">🚪 Logout</a>
            </nav>
        </aside>
        <main class="admin-content">
            <h1>🏘 Kelola Kecamatan</h1>

            <?php if (isset($_GET['msg'])): ?>
                <div class="alert success">✅ Berhasil!</div>
            <?php endif; ?>

            <!-- Form Tambah -->
            <div class="form-card">
                <h3>Tambah Kecamatan</h3>
                <form method="POST">
                    <input type="hidden" name="action" value="add">
                    <div class="form-grid">
                        <input type="text" name="nama_kecamatan" placeholder="Nama Kecamatan" required>
                        <input type="text" name="kode_kecamatan" placeholder="Kode (contoh: MJL)" required>
                        <input type="number" name="jumlah_penduduk" placeholder="Jumlah Penduduk">
                        <input type="number" name="jumlah_desa" placeholder="Jumlah Desa">
                        <input type="number" name="jumlah_puskesmas" placeholder="Jumlah Puskesmas">
                        <input type="number" name="jumlah_pustu" placeholder="Jumlah Pustu">
                        <input type="number" name="jumlah_posyandu" placeholder="Jumlah Posyandu">
                    </div>
                    <button type="submit">➕ Tambah</button>
                </form>
            </div>

            <!-- Tabel Data -->
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nama</th>
                        <th>Kode</th>
                        <th>Penduduk</th>
                        <th>Desa</th>
                        <th>Puskesmas</th>
                        <th>Pustu</th>
                        <th>Posyandu</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = mysqli_fetch_assoc($data)): ?>
                    <tr>
                        <td><?= $row['id_kecamatan'] ?></td>
                        <td><?= $row['nama_kecamatan'] ?></td>
                        <td><?= $row['kode_kecamatan'] ?></td>
                        <td><?= number_format($row['jumlah_penduduk']) ?></td>
                        <td><?= $row['jumlah_desa'] ?></td>
                        <td><?= $row['jumlah_puskesmas'] ?></td>
                        <td><?= $row['jumlah_pustu'] ?></td>
                        <td><?= $row['jumlah_posyandu'] ?></td>
                        <td>
                            <button class="edit-btn" data-id="<?= $row['id_kecamatan'] ?>" 
                                data-nama="<?= $row['nama_kecamatan'] ?>"
                                data-penduduk="<?= $row['jumlah_penduduk'] ?>"
                                data-desa="<?= $row['jumlah_desa'] ?>"
                                data-puskesmas="<?= $row['jumlah_puskesmas'] ?>"
                                data-pustu="<?= $row['jumlah_pustu'] ?>"
                                data-posyandu="<?= $row['jumlah_posyandu'] ?>">✏️</button>
                            <button class="delete-btn" data-id="<?= $row['id_kecamatan'] ?>">🗑️</button>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>

            <!-- Modal Edit (sederhana) -->
            <div id="editModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,.5); backdrop-filter:blur(4px); z-index:999; justify-content:center; align-items:center;">
                <div style="background:#0b223c; padding:30px; border-radius:20px; max-width:600px; width:90%;">
                    <h2>✏️ Edit Kecamatan</h2>
                    <form method="POST" id="editForm">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="id" id="editId">
                        <div class="form-grid">
                            <input type="text" name="nama_kecamatan" id="editNama" placeholder="Nama Kecamatan" required>
                            <input type="number" name="jumlah_penduduk" id="editPenduduk" placeholder="Jumlah Penduduk">
                            <input type="number" name="jumlah_desa" id="editDesa" placeholder="Jumlah Desa">
                            <input type="number" name="jumlah_puskesmas" id="editPuskesmas" placeholder="Jumlah Puskesmas">
                            <input type="number" name="jumlah_pustu" id="editPustu" placeholder="Jumlah Pustu">
                            <input type="number" name="jumlah_posyandu" id="editPosyandu" placeholder="Jumlah Posyandu">
                        </div>
                        <div style="display:flex; gap:10px; margin-top:15px;">
                            <button type="submit">💾 Simpan</button>
                            <button type="button" onclick="document.getElementById('editModal').style.display='none'">❌ Batal</button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Edit button
        document.querySelectorAll('.edit-btn').forEach(btn => {
            btn.onclick = function() {
                document.getElementById('editId').value = this.dataset.id;
                document.getElementById('editNama').value = this.dataset.nama;
                document.getElementById('editPenduduk').value = this.dataset.penduduk;
                document.getElementById('editDesa').value = this.dataset.desa;
                document.getElementById('editPuskesmas').value = this.dataset.puskesmas;
                document.getElementById('editPustu').value = this.dataset.pustu;
                document.getElementById('editPosyandu').value = this.dataset.posyandu;
                document.getElementById('editModal').style.display = 'flex';
            };
        });

        // Delete button
        document.querySelectorAll('.delete-btn').forEach(btn => {
            btn.onclick = function() {
                if (confirm('Yakin hapus data ini?')) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.innerHTML = `<input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="${this.dataset.id}">`;
                    document.body.appendChild(form);
                    form.submit();
                }
            };
        });
    </script>
</body>
</html>