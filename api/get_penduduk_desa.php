<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';

/*
|--------------------------------------------------------------------------
| DATA DESA/KELURAHAN + PENDUDUK (READ-ONLY, sumber: tbl_desa_kelurahan)
|--------------------------------------------------------------------------
| - Hanya SELECT. Tidak ada INSERT/UPDATE/DELETE.
| - Satu-satunya source of truth adalah tbl_desa_kelurahan (aktif='Y').
|   TIDAK fallback ke agregat manual tbl_kecamatan.jumlah_penduduk/jumlah_desa.
| - Agregat dihitung dari baris desa (SUM/COUNT) sehingga tidak ada
|   double counting akibat JOIN.
| - Mode A (tanpa parameter) : ringkasan kabupaten + 12 kecamatan.
| - Mode B (?id_kecamatan=N) : satu kecamatan + daftar desa/kelurahan.
|   Parameter wajib numerik dan memakai prepared statement.
|--------------------------------------------------------------------------
*/

$errors = [];
$warnings = [];

function fail($message)
{
    echo json_encode([
        'status' => false,
        'message' => $message,
        'data' => null,
        'errors' => [$message],
        'warnings' => [],
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$idKecamatanRaw = $_GET['id_kecamatan'] ?? null;
$modeKecamatan = ($idKecamatanRaw !== null && $idKecamatanRaw !== '');

/*
|--------------------------------------------------------------------------
| MODE B : satu kecamatan
|--------------------------------------------------------------------------
*/
if ($modeKecamatan) {
    if (!ctype_digit((string) $idKecamatanRaw)) {
        fail('Parameter id_kecamatan tidak valid (harus angka)');
    }
    $idKecamatan = (int) $idKecamatanRaw;

    $stKec = mysqli_prepare(
        $config,
        "SELECT id_kecamatan, kode_kecamatan, nama_kecamatan
         FROM tbl_kecamatan WHERE id_kecamatan = ? AND aktif = 'Y' LIMIT 1"
    );
    if (!$stKec) {
        fail('Gagal menyiapkan query kecamatan');
    }
    mysqli_stmt_bind_param($stKec, 'i', $idKecamatan);
    if (!mysqli_stmt_execute($stKec)) {
        mysqli_stmt_close($stKec);
        fail('Gagal membaca data kecamatan');
    }
    $resKec = mysqli_stmt_get_result($stKec);
    $kec = $resKec ? mysqli_fetch_assoc($resKec) : null;
    mysqli_stmt_close($stKec);

    if (!$kec) {
        fail("Kecamatan dengan id_kecamatan=$idKecamatan tidak ditemukan");
    }

    $stDesa = mysqli_prepare(
        $config,
        "SELECT id_desa_kelurahan, kode_wilayah, nama, jenis,
                laki_laki, perempuan, jumlah
         FROM tbl_desa_kelurahan
         WHERE id_kecamatan = ? AND aktif = 'Y'
         ORDER BY kode_wilayah ASC, nama ASC"
    );
    if (!$stDesa) {
        fail('Gagal menyiapkan query desa/kelurahan');
    }
    $idKecInt = (int) $kec['id_kecamatan'];
    mysqli_stmt_bind_param($stDesa, 'i', $idKecInt);
    if (!mysqli_stmt_execute($stDesa)) {
        mysqli_stmt_close($stDesa);
        fail('Gagal membaca data desa/kelurahan');
    }
    $resDesa = mysqli_stmt_get_result($stDesa);
    $desa = [];
    $sumL = 0;
    $sumP = 0;
    $sumJ = 0;
    if ($resDesa) {
        while ($r = mysqli_fetch_assoc($resDesa)) {
            $l = (int) $r['laki_laki'];
            $p = (int) $r['perempuan'];
            $j = (int) $r['jumlah'];
            $sumL += $l;
            $sumP += $p;
            $sumJ += $j;
            $desa[] = [
                'id_desa_kelurahan' => (int) $r['id_desa_kelurahan'],
                'kode_wilayah' => $r['kode_wilayah'],
                'nama' => $r['nama'],
                'jenis' => $r['jenis'],
                'laki_laki' => $l,
                'perempuan' => $p,
                'jumlah' => $j,
            ];
        }
    }
    mysqli_stmt_close($stDesa);

    echo json_encode([
        'status' => true,
        'data' => [
            'kecamatan' => [
                'id_kecamatan' => (int) $kec['id_kecamatan'],
                'kode_kecamatan' => $kec['kode_kecamatan'],
                'nama_kecamatan' => $kec['nama_kecamatan'],
                'jumlah_desa_kelurahan' => count($desa),
                'laki_laki' => $sumL,
                'perempuan' => $sumP,
                'jumlah_penduduk' => $sumJ,
            ],
            'desa_kelurahan' => $desa,
        ],
        'errors' => $errors,
        'warnings' => $warnings,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

/*
|--------------------------------------------------------------------------
| MODE A : ringkasan kabupaten + seluruh kecamatan
|--------------------------------------------------------------------------
*/

$qKab = mysqli_query(
    $config,
    "SELECT COUNT(*) AS jml_desa,
            COALESCE(SUM(laki_laki), 0) AS l,
            COALESCE(SUM(perempuan), 0) AS p,
            COALESCE(SUM(jumlah), 0) AS j
     FROM tbl_desa_kelurahan WHERE aktif = 'Y'"
);
if (!$qKab) {
    fail('Gagal membaca ringkasan desa/kelurahan');
}
$kab = mysqli_fetch_assoc($qKab);

$qJmlKec = mysqli_query($config, "SELECT COUNT(*) AS c FROM tbl_kecamatan WHERE aktif = 'Y'");
if (!$qJmlKec) {
    fail('Gagal membaca data kecamatan');
}
$jmlKec = (int) mysqli_fetch_assoc($qJmlKec)['c'];

$qKec = mysqli_query(
    $config,
    "SELECT k.id_kecamatan, k.kode_kecamatan, k.nama_kecamatan,
            COUNT(d.id_desa_kelurahan) AS jml_desa,
            COALESCE(SUM(d.laki_laki), 0) AS l,
            COALESCE(SUM(d.perempuan), 0) AS p,
            COALESCE(SUM(d.jumlah), 0) AS j
     FROM tbl_kecamatan k
     LEFT JOIN tbl_desa_kelurahan d
       ON d.id_kecamatan = k.id_kecamatan AND d.aktif = 'Y'
     WHERE k.aktif = 'Y'
     GROUP BY k.id_kecamatan, k.kode_kecamatan, k.nama_kecamatan
     ORDER BY k.nama_kecamatan ASC"
);
if (!$qKec) {
    fail('Gagal membaca rincian kecamatan');
}
$kecamatan = [];
while ($r = mysqli_fetch_assoc($qKec)) {
    $kecamatan[] = [
        'id_kecamatan' => (int) $r['id_kecamatan'],
        'kode_kecamatan' => $r['kode_kecamatan'],
        'nama_kecamatan' => $r['nama_kecamatan'],
        'jumlah_desa_kelurahan' => (int) $r['jml_desa'],
        'laki_laki' => (int) $r['l'],
        'perempuan' => (int) $r['p'],
        'jumlah_penduduk' => (int) $r['j'],
    ];
}

echo json_encode([
    'status' => true,
    'data' => [
        'kabupaten' => [
            'jumlah_kecamatan' => $jmlKec,
            'jumlah_desa_kelurahan' => (int) $kab['jml_desa'],
            'laki_laki' => (int) $kab['l'],
            'perempuan' => (int) $kab['p'],
            'jumlah_penduduk' => (int) $kab['j'],
        ],
        'kecamatan' => $kecamatan,
    ],
    'errors' => $errors,
    'warnings' => $warnings,
], JSON_UNESCAPED_UNICODE);
