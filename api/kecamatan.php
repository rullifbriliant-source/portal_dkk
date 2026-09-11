<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';

/*
|--------------------------------------------------------------------------
| MODE AGREGAT KABUPATEN (read-only)
| GET api/kecamatan.php?aggregate=kabupaten
| Dipakai card "Data Dasar" saat belum ada kecamatan dipilih.
| Shape response disamakan dengan mode kecamatan agar
| Dashboard.renderData() di frontend bisa dipakai ulang tanpa diubah.
| Source of truth (tidak hardcode, tidak dummy):
| - Penduduk + Desa/Kelurahan : tbl_desa_kelurahan (aktif='Y')
| - Puskesmas/Pustu/Klinik/RS : COUNT tbl_faskes (aktif='Y') per jenis
| - Posyandu                   : SUM tbl_kecamatan.jumlah_posyandu (aktif='Y')
|                                (sumber yang selama ini dipakai mode kecamatan;
|                                belum ada tabel posyandu tersendiri)
|--------------------------------------------------------------------------
*/

$aggregate = strtolower(trim($_GET['aggregate'] ?? ''));

if ($aggregate === 'kabupaten') {
    $kab = [
        'jml_kec' => 0,
        'jml_desa' => 0,
        'penduduk' => 0,
        'kk' => 0,
        'posyandu' => 0,
    ];

    $qKec = mysqli_query($config, "SELECT COUNT(*) AS c FROM tbl_kecamatan WHERE aktif = 'Y'");
    if ($qKec && ($r = mysqli_fetch_assoc($qKec))) {
        $kab['jml_kec'] = (int) $r['c'];
    }

    // Aturan data penduduk: SUM(laki_laki + perempuan) dari tbl_desa_kelurahan.
    // JANGAN pakai tbl_kecamatan.jumlah_penduduk/jumlah_desa untuk agregat.
    $qDesa = mysqli_query(
        $config,
        "SELECT COUNT(*) AS jml_desa,
                COALESCE(SUM(laki_laki + perempuan), 0) AS total_penduduk
         FROM tbl_desa_kelurahan WHERE aktif = 'Y'"
    );
    if ($qDesa && ($r = mysqli_fetch_assoc($qDesa))) {
        $kab['jml_desa'] = (int) $r['jml_desa'];
        $kab['penduduk'] = (int) $r['total_penduduk'];
    }

    $qFaskes = mysqli_query(
        $config,
        "SELECT
            SUM(CASE WHEN jenis = 'Puskesmas' THEN 1 ELSE 0 END) AS count_puskesmas,
            SUM(CASE WHEN jenis = 'Pustu' THEN 1 ELSE 0 END) AS count_pustu,
            SUM(CASE WHEN jenis = 'Klinik' THEN 1 ELSE 0 END) AS count_klinik,
            SUM(CASE WHEN jenis = 'Rumah Sakit' THEN 1 ELSE 0 END) AS count_rs
         FROM tbl_faskes WHERE aktif = 'Y'"
    );
    $faskesKab = $qFaskes ? mysqli_fetch_assoc($qFaskes) : [];
    $puskesmasKab = (int) ($faskesKab['count_puskesmas'] ?? 0);
    $pustuKab = (int) ($faskesKab['count_pustu'] ?? 0);
    $klinikKab = (int) ($faskesKab['count_klinik'] ?? 0);
    $rsKab = (int) ($faskesKab['count_rs'] ?? 0);

    $qPos = mysqli_query(
        $config,
        "SELECT COALESCE(SUM(jumlah_posyandu), 0) AS total_posyandu,
                COALESCE(SUM(jumlah_kk), 0) AS total_kk
         FROM tbl_kecamatan WHERE aktif = 'Y'"
    );
    if ($qPos && ($r = mysqli_fetch_assoc($qPos))) {
        $kab['posyandu'] = (int) $r['total_posyandu'];
        $kab['kk'] = (int) $r['total_kk'];
    }

    echo json_encode([
        'status' => true,
        'scope' => 'kabupaten',

        'nama' => $kab['jml_kec'] . ' Kecamatan',
        'jumlah_kecamatan' => $kab['jml_kec'],

        // DATA DASAR (agregat kabupaten)
        'penduduk' => $kab['penduduk'],
        'kk' => $kab['kk'],
        'desa' => $kab['jml_desa'],

        // FASYANKES (agregat kabupaten dari tbl_faskes)
        'puskesmas' => $puskesmasKab,
        'pustu' => $pustuKab,
        'klinik' => $klinikKab,
        'rumah_sakit' => $rsKab,

        // DATA TAMBAHAN
        'posyandu' => $kab['posyandu'],
        'rs' => $rsKab,
    ]);
    exit;
}

$id = $_GET['id'] ?? null;

if (!$id) {
    echo json_encode([
        'status' => false,
        'message' => 'ID kecamatan tidak diberikan'
    ]);
    exit;
}

// Bersihkan prefix kec_ jika ada
if (strpos($id, 'kec_') === 0) {
    $id = substr($id, 4);
}

$id = mysqli_real_escape_string($config, $id);

/*
|--------------------------------------------------------------------------
| AMBIL DATA DASAR KECAMATAN
|--------------------------------------------------------------------------
*/

$sql = "SELECT
            id_kecamatan,
            kode_kecamatan,
            nama_kecamatan,
            jumlah_penduduk,
            jumlah_kk,
            jumlah_desa,
            jumlah_posyandu,
            luas_wilayah,
            kepadatan
        FROM tbl_kecamatan
        WHERE (LOWER(nama_kecamatan) = LOWER('$id') OR id_kecamatan = '$id')
        AND aktif = 'Y'
        LIMIT 1";

$query = mysqli_query($config, $sql);

if (!$query) {
    echo json_encode([
        'status' => false,
        'message' => mysqli_error($config)
    ]);
    exit;
}

$row = mysqli_fetch_assoc($query);

if (!$row) {
    echo json_encode([
        'status' => false,
        'message' => "Kecamatan '$id' tidak ditemukan"
    ]);
    exit;
}

$idKec = (int)$row['id_kecamatan'];
$namaKec = mysqli_real_escape_string($config, $row['nama_kecamatan']);

/*
|--------------------------------------------------------------------------
| HITUNG JUMLAH FASILITAS KESEHATAN SECARA DINAMIS DARI tbl_faskes
|--------------------------------------------------------------------------
*/
$sqlFaskes = "SELECT 
    SUM(CASE WHEN jenis = 'Puskesmas' THEN 1 ELSE 0 END) AS count_puskesmas,
    SUM(CASE WHEN jenis = 'Pustu' THEN 1 ELSE 0 END) AS count_pustu,
    SUM(CASE WHEN jenis = 'Klinik' THEN 1 ELSE 0 END) AS count_klinik,
    SUM(CASE WHEN jenis = 'Rumah Sakit' THEN 1 ELSE 0 END) AS count_rs
FROM tbl_faskes 
WHERE (id_kecamatan = $idKec OR LOWER(kecamatan) = LOWER('$namaKec')) 
AND aktif = 'Y'";

$queryFaskes = mysqli_query($config, $sqlFaskes);
$faskesCount = mysqli_fetch_assoc($queryFaskes);

$puskesmas = (int)($faskesCount['count_puskesmas'] ?? 0);
$pustu = (int)($faskesCount['count_pustu'] ?? 0);
$klinik = (int)($faskesCount['count_klinik'] ?? 0);
$rumah_sakit = (int)($faskesCount['count_rs'] ?? 0);

// ============================================================
// RESPONSE
// ============================================================

echo json_encode([
    'status' => true,

    'id_kecamatan' => $idKec,
    'kode_kecamatan' => $row['kode_kecamatan'],
    'nama' => $row['nama_kecamatan'],

    // DATA DASAR
    'penduduk' => (int) $row['jumlah_penduduk'],
    'kk' => (int) $row['jumlah_kk'],
    'desa' => (int) $row['jumlah_desa'],

    // FASYANKES (DIHITUNG DARI DATA RIIL tbl_faskes)
    'puskesmas' => $puskesmas,
    'pustu' => $pustu,
    'klinik' => $klinik,
    'rumah_sakit' => $rumah_sakit,

    // DATA TAMBAHAN
    'posyandu' => (int) $row['jumlah_posyandu'],
    'rs' => $rumah_sakit,
    'luas' => (float) $row['luas_wilayah'],
    'kepadatan' => (int) $row['kepadatan']
]);