<?php
/**
 * API Public SPM — READ ONLY.
 * Tidak ada operasi tulis di endpoint ini.
 *
 * GET api/get_spm.php
 *   ?periode=2026            (default: periode pertama yang tersedia)
 *   ?action=periods          -> daftar periode + jumlah baris
 *   ?action=count            -> total baris aktif (untuk badge/card SPM)
 */
header("Content-Type: application/json; charset=utf-8");
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../App/Services/SpmLib.php";

$action = $_GET['action'] ?? 'data';

if ($action === 'periods') {
    echo json_encode([
        'status' => true,
        'periods' => SpmLib::periods($config),
        'counts' => SpmLib::counts($config),
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($action === 'count') {
    $q = mysqli_query($config, "SELECT COUNT(*) c FROM tbl_spm WHERE aktif='Y'");
    $c = $q ? (int)mysqli_fetch_assoc($q)['c'] : 0;
    echo json_encode(['status' => true, 'total' => $c]);
    exit;
}

$periods = SpmLib::periods($config);
$periode = trim($_GET['periode'] ?? '');
if ($periode === '' || !in_array($periode, $periods, true)) {
    $periode = $periods[0] ?? '2026';
}

$data = SpmLib::fetchData($config, $periode);

// Kelompokkan berurutan per jenis layanan (untuk rowspan tampilan spreadsheet)
$groups = [];
foreach ($data['rows'] as $row) {
    $groups[$row['jenis_layanan']][] = $row;
}

echo json_encode([
    'status' => true,
    'periode' => $periode,
    'periods' => $periods,
    'counts' => SpmLib::counts($config),
    'kecamatan' => $data['kecamatan'],
    'total_rows' => count($data['rows']),
    'groups' => $groups,
], JSON_UNESCAPED_UNICODE);
