<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header("Content-Type: application/json; charset=utf-8");
require_once __DIR__ . "/../config/database.php";

/*
|--------------------------------------------------------------------------
| REKAP SDMK — PER KECAMATAN & KABUPATEN, 5 TAHUN TERBARU (READ-ONLY)
|--------------------------------------------------------------------------
| Sumber: tbl_sdmk_kecamatan_rekap (grain id_kecamatan x id_item x tahun).
| Terpisah dari tbl_sdm_kecamatan (legacy) dan tbl_sdm_faskes.
| Hanya SELECT aktif='Y'. Tidak ada INSERT/UPDATE/DELETE.
| Mode kecamatan  : ?id_kecamatan=X (wajib) -> angka per kecamatan.
| Mode kabupaten  : ?scope=kabupaten -> SUM lintas kecamatan per
|                   (tahun x item). Tanpa tabel/fitur baru.
| Kolom tahun = DISTINCT 5 tahun terbaru yg punya data aktif (dinamis,
| ASC untuk tampilan kolom). Tabel kosong -> status true, data [].
| Response: { status, scope: kecamatan|kabupaten,
|             kecamatan: {id_kecamatan, nama_kecamatan},
|             tahun_min, tahun_max, data: [{tahun, items:
|             [{id_item, nama_item, jumlah}]}], total }
|   (mode kabupaten: id_kecamatan null, nama "Kabupaten Sukoharjo")
|--------------------------------------------------------------------------
*/

$scope = (isset($_GET['scope']) && $_GET['scope'] === 'kabupaten') ? 'kabupaten' : 'kecamatan';

$check = @mysqli_query($config, "SHOW TABLES LIKE 'tbl_sdmk_kecamatan_rekap'");
if (!$check || mysqli_num_rows($check) === 0) {
    echo json_encode([
        "status" => true,
        "scope" => $scope,
        "kecamatan" => $scope === 'kabupaten'
            ? ["id_kecamatan" => null, "nama_kecamatan" => "Kabupaten Sukoharjo"]
            : ["id_kecamatan" => 0, "nama_kecamatan" => null],
        "tahun_min" => null,
        "tahun_max" => null,
        "data" => [],
        "total" => 0,
        "note" => "migration belum dijalankan"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($scope === 'kabupaten') {
    $kecInfo = ["id_kecamatan" => null, "nama_kecamatan" => "Kabupaten Sukoharjo"];
    $whereKec = "1=1";
} else {
    $id_kecamatan = isset($_GET['id_kecamatan']) ? (int)$_GET['id_kecamatan'] : 0;
    if ($id_kecamatan <= 0) {
        echo json_encode([
            "status" => false,
            "message" => "Parameter id_kecamatan wajib diisi"
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $kecStmt = $config->prepare("SELECT id_kecamatan, nama_kecamatan FROM tbl_kecamatan WHERE id_kecamatan=? AND aktif='Y' LIMIT 1");
    $kecStmt->bind_param("i", $id_kecamatan);
    $kecStmt->execute();
    $kecRow = $kecStmt->get_result()->fetch_assoc();
    $kecStmt->close();
    if (!$kecRow) {
        echo json_encode([
            "status" => false,
            "message" => "Kecamatan tidak ditemukan"
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $kecInfo = ["id_kecamatan" => (int)$kecRow['id_kecamatan'], "nama_kecamatan" => $kecRow['nama_kecamatan']];
    $whereKec = "r.id_kecamatan = $id_kecamatan";
}

// 5 tahun terbaru yg punya data aktif (dinamis; kabupaten = lintas kecamatan)
$tahunRes = mysqli_query(
    $config,
    "SELECT DISTINCT tahun FROM tbl_sdmk_kecamatan_rekap r
     WHERE $whereKec AND r.aktif='Y' ORDER BY tahun DESC LIMIT 5"
);
$tahunList = [];
while ($tahunRes && ($r = $tahunRes->fetch_assoc())) {
    $tahunList[] = (int)$r['tahun'];
}
sort($tahunList);

if (empty($tahunList)) {
    echo json_encode([
        "status" => true,
        "scope" => $scope,
        "kecamatan" => $kecInfo,
        "tahun_min" => null,
        "tahun_max" => null,
        "data" => [],
        "total" => 0
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$minTahun = $tahunList[0];
$maxTahun = $tahunList[count($tahunList) - 1];

$query = mysqli_query(
    $config,
    "SELECT r.tahun, r.id_item, si.nama_item, SUM(r.jumlah) AS jumlah
     FROM tbl_sdmk_kecamatan_rekap r
     JOIN tbl_sdm_items si ON si.id = r.id_item
     WHERE $whereKec AND r.aktif = 'Y'
       AND r.tahun >= $minTahun AND r.tahun <= $maxTahun
     GROUP BY r.tahun, r.id_item, si.nama_item, si.urutan
     ORDER BY r.tahun ASC, si.urutan ASC, si.nama_item ASC"
);

if (!$query) {
    echo json_encode([
        "status" => false,
        "message" => "Gagal membaca data rekap"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$byYear = [];
$total = 0;
while ($row = mysqli_fetch_assoc($query)) {
    $t = (int)$row['tahun'];
    if (!isset($byYear[$t])) {
        $byYear[$t] = ["tahun" => $t, "items" => []];
    }
    $byYear[$t]["items"][] = [
        "id_item" => (int)$row['id_item'],
        "nama_item" => $row['nama_item'],
        "jumlah" => (int)$row['jumlah']
    ];
    $total++;
}

echo json_encode([
    "status" => true,
    "scope" => $scope,
    "kecamatan" => $kecInfo,
    "tahun_min" => $minTahun,
    "tahun_max" => $maxTahun,
    "data" => array_values($byYear),
    "total" => $total
], JSON_UNESCAPED_UNICODE);
