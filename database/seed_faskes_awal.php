<?php
/*
 * SEED DATA AWAL FASYANKES
 * =====================================================================
 * Tujuan:
 *   Setiap kecamatan aktif pada tbl_kecamatan memiliki data awal minimal
 *   2 fasilitas untuk tiap jenis: Puskesmas, Pustu, Klinik, Rumah Sakit.
 *   (12 kecamatan x 4 jenis x 2 = 96 fasilitas awal)
 *
 * Sifat script:
 *   - Aman dijalankan ulang (idempotent), tidak pernah membuat duplikat.
 *   - Hanya INSERT; tidak pernah menghapus/mengubah data existing.
 *   - Menambah hanya selisih yang kurang berdasarkan (id_kecamatan + jenis).
 *   - Nama data demo: "<Jenis> <NamaKecamatan> NN" mis. "Puskesmas Baki 01".
 *   - kode_faskes dibuat unik dan dicek terhadap seluruh baris (termasuk
 *     soft-delete aktif='N') sehingga tetap unik walau dijalankan ulang.
 *   - Field opsional (alamat, telepon, email, foto, latitude, longitude,
 *     x_svg, y_svg) dibiarkan NULL — tidak mengarang data resmi.
 *
 * Jalankan dari CLI:
 *   php database/seed_faskes_awal.php
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit("Script ini hanya boleh dijalankan dari CLI.\n");
}

require_once __DIR__ . '/../config/database.php';

$JENIS = [
    'Puskesmas'   => 'PKM',
    'Pustu'       => 'PST',
    'Klinik'      => 'KLK',
    'Rumah Sakit' => 'RST',
];
$MIN_TARGET = 2;

$before = (int)mysqli_fetch_assoc(mysqli_query($config, "SELECT COUNT(*) AS c FROM tbl_faskes"))['c'];
$beforeAktif = (int)mysqli_fetch_assoc(mysqli_query($config, "SELECT COUNT(*) AS c FROM tbl_faskes WHERE aktif='Y'"))['c'];

// ---------------------------------------------------------------------
// Kecamatan aktif (relasi ID harus konsisten dengan tbl_kecamatan)
// ---------------------------------------------------------------------
$resKec = mysqli_query($config, "SELECT id_kecamatan, kode_kecamatan, nama_kecamatan FROM tbl_kecamatan WHERE aktif='Y' ORDER BY id_kecamatan");
$kecamatan = [];
while ($row = mysqli_fetch_assoc($resKec)) {
    $kecamatan[] = $row;
}

$inserted = 0;
$errors = [];

foreach ($kecamatan as $kc) {
    $kid = (int)$kc['id_kecamatan'];
    $namaKec = $kc['nama_kecamatan'];
    $kodeKec = $kc['kode_kecamatan'];
    // kolom `kecamatan` pada tbl_faskes mengikuti konvensi CRUD: nama lowercase
    $kecCol = strtolower($namaKec);

    foreach ($JENIS as $jenis => $prefixJenis) {
        // hitung jumlah existing aktif untuk (id_kecamatan, jenis)
        $qr = $config->prepare("SELECT COUNT(*) AS c FROM tbl_faskes WHERE id_kecamatan=? AND jenis=? AND aktif='Y'");
        $qr->bind_param('is', $kid, $jenis);
        $qr->execute();
        $count = (int)$qr->get_result()->fetch_assoc()['c'];
        $need = max(0, $MIN_TARGET - $count);
        if ($need === 0) {
            continue;
        }

        // kumpulkan semua kode_faskes dengan prefix jenis ini (termasuk soft-delete)
        // agar nomor urut kode tidak bertabrakan saat script dijalankan ulang.
        $prefix = $prefixJenis . '-' . $kodeKec . '-';
        $like = $prefix . '%';
        $qk = $config->prepare("SELECT kode_faskes FROM tbl_faskes WHERE kode_faskes LIKE ?");
        $qk->bind_param('s', $like);
        $qk->execute();
        $existingCodes = [];
        while ($ck = $qk->get_result()->fetch_assoc()) {
            if ($ck['kode_faskes'] !== null) {
                $existingCodes[] = $ck['kode_faskes'];
            }
        }

        $seq = 1;
        for ($i = 1; $i <= $need; $i++) {
            // cari nomor urut yang belum dipakai
            while (in_array($prefix . str_pad($seq, 2, '0', STR_PAD_LEFT), $existingCodes, true)) {
                $seq++;
            }
            $kodeFaskes = $prefix . str_pad($seq, 2, '0', STR_PAD_LEFT);
            $existingCodes[] = $kodeFaskes;
            $namaFaskes = $jenis . ' ' . $namaKec . ' ' . str_pad($seq, 2, '0', STR_PAD_LEFT);

            $ins = $config->prepare(
                "INSERT INTO tbl_faskes
                    (kode_faskes, nama_faskes, jenis, id_kecamatan, kecamatan, aktif)
                 VALUES (?, ?, ?, ?, ?, 'Y')"
            );
            $ins->bind_param('sssis', $kodeFaskes, $namaFaskes, $jenis, $kid, $kecCol);
            if (!$ins->execute()) {
                $errors[] = [$namaFaskes, $config->error];
            } else {
                $inserted += $ins->affected_rows;
            }
            $seq++;
        }
    }
}

// ---------------------------------------------------------------------
// Laporan / verifikasi
// ---------------------------------------------------------------------
$after = (int)mysqli_fetch_assoc(mysqli_query($config, "SELECT COUNT(*) AS c FROM tbl_faskes"))['c'];
$afterAktif = (int)mysqli_fetch_assoc(mysqli_query($config, "SELECT COUNT(*) AS c FROM tbl_faskes WHERE aktif='Y'"))['c'];

echo "=================================================================\n";
echo "SEED DATA AWAL FASYANKES\n";
echo "=================================================================\n";
echo "Kecamatan aktif            : " . count($kecamatan) . "\n";
echo "Total baris tbl_faskes     : $before  ->  $after\n";
echo "Total faskes aktif (Y)     : $beforeAktif  ->  $afterAktif\n";
echo "Baris baru di-insert       : " . $inserted . "\n";

if (!empty($errors)) {
    echo "GAGAL INSERT:\n";
    foreach ($errors as $e) {
        echo "  - " . $e[0] . " : " . $e[1] . "\n";
    }
}

echo "\nBreakdown per kecamatan (faskes aktif):\n";
echo str_repeat('-', 68) . "\n";
printf("%-14s | %-10s | %-6s | %-6s | %-12s | %s\n", 'Kecamatan', 'Puskesmas', 'Pustu', 'Klinik', 'Rumah Sakit', 'Total');
echo str_repeat('-', 68) . "\n";

$sql = "SELECT
            k.nama_kecamatan,
            SUM(CASE WHEN f.jenis='Puskesmas' THEN 1 ELSE 0 END) AS pk,
            SUM(CASE WHEN f.jenis='Pustu' THEN 1 ELSE 0 END) AS ps,
            SUM(CASE WHEN f.jenis='Klinik' THEN 1 ELSE 0 END) AS kl,
            SUM(CASE WHEN f.jenis='Rumah Sakit' THEN 1 ELSE 0 END) AS rs,
            COUNT(*) AS total
        FROM tbl_kecamatan k
        LEFT JOIN tbl_faskes f ON f.id_kecamatan = k.id_kecamatan AND f.aktif='Y'
        WHERE k.aktif='Y'
        GROUP BY k.id_kecamatan, k.nama_kecamatan
        ORDER BY k.id_kecamatan";

$resBreaks = mysqli_query($config, $sql);
$allMin = true;
while ($b = mysqli_fetch_assoc($resBreaks)) {
    $ok = $b['pk'] >= $MIN_TARGET && $b['ps'] >= $MIN_TARGET && $b['kl'] >= $MIN_TARGET && $b['rs'] >= $MIN_TARGET;
    if (!$ok) {
        $allMin = false;
    }
    printf(
        "%-14s | %-10d | %-6d | %-6d | %-12d | %d%s\n",
        $b['nama_kecamatan'],
        (int)$b['pk'],
        (int)$b['ps'],
        (int)$b['kl'],
        (int)$b['rs'],
        (int)$b['total'],
        $ok ? '  <OK>' : '  <KURANG>'
    );
}
echo str_repeat('-', 68) . "\n";

$dup = mysqli_fetch_assoc(mysqli_query($config, "SELECT COUNT(*) AS c FROM (SELECT kode_faskes FROM tbl_faskes WHERE kode_faskes IS NOT NULL AND kode_faskes <> '' GROUP BY kode_faskes HAVING COUNT(*) > 1) d"))['c'];
echo "Kode faskes terduplikasi   : " . $dup . "\n";

echo "\nTarget: setiap kecamatan minimal 2 per jenis (" . ($MIN_TARGET * count($JENIS) * count($kecamatan)) . " fasilitas awal).\n";
echo "Semua kecamatan >= target  : " . ($allMin ? 'YA' : 'TIDAK') . "\n";
echo "=================================================================\n";