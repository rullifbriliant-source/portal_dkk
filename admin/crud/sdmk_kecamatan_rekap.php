<?php
require_once '../config.php';
requireLogin();
// Matching nama item: REUSE single source (juga dipakai sdmk.php STEP 4).
require_once __DIR__ . '/../lib/sdm_match.php';
$current_page = basename($_SERVER['PHP_SELF']);

// ==========================================================
// REKAP SDMK PER KECAMATAN (5 tahun terakhir dinamis)
// Tabel: tbl_sdmk_kecamatan_rekap (id_kecamatan x id_item x tahun x jumlah).
// Grain TANPA id_spesialis (cukup level item utama).
// BUKAN tbl_sdm_kecamatan (legacy, tidak disentuh file ini).
// 1 file import = 1 kecamatan (kecamatan wajib dipilih dulu).
// Item tak dikenal DITOLAK (tidak auto-insert ke master).
// ==========================================================
define('SDMK_REKAP_TAHUN_MIN', 1900);
define('SDMK_REKAP_TAHUN_MAX', 2100);

// Tabel mungkin belum ada (migration belum dijalankan).
$hasTable = false;
$chkTable = @mysqli_query($config, "SHOW TABLES LIKE 'tbl_sdmk_kecamatan_rekap'");
if ($chkTable && mysqli_num_rows($chkTable) > 0) {
    $hasTable = true;
}

// Helper validasi
function sdmk_rekap_valid_tahun($t) {
    $t = (int)$t;
    return ($t >= SDMK_REKAP_TAHUN_MIN && $t <= SDMK_REKAP_TAHUN_MAX) ? $t : null;
}
function sdmk_rekap_valid_jumlah($v) {
    if ($v === null || $v === '') return null;
    if (!preg_match('/^\d+$/', trim((string)$v))) return false;
    return (int)trim((string)$v);
}

// Master: kecamatan + item aktif (dropdown & peta matching)
$kecList = [];
$qKec = mysqli_query($config, "SELECT id_kecamatan, nama_kecamatan FROM tbl_kecamatan WHERE aktif='Y' ORDER BY nama_kecamatan");
while ($qKec && ($r = mysqli_fetch_assoc($qKec))) $kecList[] = $r;
$kecById = [];
foreach ($kecList as $k) $kecById[(int)$k['id_kecamatan']] = $k['nama_kecamatan'];

$itemList = [];   // master aktif terurut (untuk dropdown & baris matriks)
$itemById = [];
$mapNorm = [];    // normalized nama => id|[ids] (untuk matching import)
$existingNames = [];
$qItem = mysqli_query($config, "SELECT id, nama_item, kategori FROM tbl_sdm_items WHERE aktif='Y' ORDER BY FIELD(kategori,'Tenaga Kesehatan','Asisten Tenaga Kesehatan','Tenaga Penunjang'), urutan, nama_item");
while ($qItem && ($r = mysqli_fetch_assoc($qItem))) {
    $itemList[] = $r;
    $itemById[(int)$r['id']] = $r;
    $existingNames[] = $r['nama_item'];
    $n = normalizeNama($r['nama_item']);
    if (!isset($mapNorm[$n])) $mapNorm[$n] = (int)$r['id'];
    else {
        if (!is_array($mapNorm[$n])) $mapNorm[$n] = [$mapNorm[$n]];
        $mapNorm[$n][] = (int)$r['id'];
    }
}

// Kecamatan terpilih (filter tampilan & default form).
// Default = kecamatan PERTAMA (urutan nama) supaya matriks langsung
// tampil begitu halaman dibuka (tidak mulai dari kosong).
$selKec = isset($_GET['kec']) ? (int)$_GET['kec'] : 0;
if (!isset($kecById[$selKec])) {
    $selKec = !empty($kecList) ? (int)$kecList[0]['id_kecamatan'] : 0;
}

// ==========================================
// HANDLE ACTIONS (ADD, EDIT, DELETE, IMPORT)
// ==========================================
$msg = $_GET['msg'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $hasTable) {
    $action = $_POST['action'] ?? '';

    // TAMBAH SATU BARIS (UPSERT: sudah ada -> UPDATE angkanya).
    // Tahun mengikuti tab aktif (form tanpa field tahun) atau input tahun
    // sekali-saja (mode "+ Tahun Baru"). Redirect kembali ke tab tahun tsb.
    if ($action === 'add') {
        $kecId = (int)($_POST['id_kecamatan'] ?? 0);
        $itemId = (int)($_POST['id_item'] ?? 0);
        $tahun = sdmk_rekap_valid_tahun($_POST['tahun'] ?? '');
        $jumlah = sdmk_rekap_valid_jumlah($_POST['jumlah'] ?? '');
        if (!isset($kecById[$kecId]) || !isset($itemById[$itemId]) || $tahun === null || $jumlah === null || $jumlah === false) {
            header("Location: sdmk_kecamatan_rekap.php?kec=$kecId&msg=invalid");
            exit;
        }
        $stmt = $config->prepare("INSERT INTO tbl_sdmk_kecamatan_rekap (id_kecamatan, id_item, tahun, jumlah, aktif) VALUES (?, ?, ?, ?, 'Y') ON DUPLICATE KEY UPDATE jumlah=VALUES(jumlah), aktif='Y'");
        $stmt->bind_param("iiii", $kecId, $itemId, $tahun, $jumlah);
        $ok = $stmt->execute();
        $aff = $stmt->affected_rows;
        $stmt->close();
        $done = $ok ? ($aff === 1 ? 'added' : 'updated') : 'error';
        header("Location: sdmk_kecamatan_rekap.php?kec=$kecId&th=$tahun&msg=$done");
        exit;
    }

    // EDIT SATU BARIS (tolak bila bentrok dgn baris lain)
    if ($action === 'edit') {
        $id = (int)($_POST['id'] ?? 0);
        $kecId = (int)($_POST['id_kecamatan'] ?? 0);
        $itemId = (int)($_POST['id_item'] ?? 0);
        $tahun = sdmk_rekap_valid_tahun($_POST['tahun'] ?? '');
        $jumlah = sdmk_rekap_valid_jumlah($_POST['jumlah'] ?? '');
        $aktif = ($_POST['aktif'] ?? 'Y') === 'N' ? 'N' : 'Y';
        if ($id <= 0 || !isset($kecById[$kecId]) || !isset($itemById[$itemId]) || $tahun === null || $jumlah === null || $jumlah === false) {
            header("Location: sdmk_kecamatan_rekap.php?kec=$kecId&msg=invalid");
            exit;
        }
        $dup = mysqli_query($config, "SELECT id FROM tbl_sdmk_kecamatan_rekap WHERE id_kecamatan=$kecId AND id_item=$itemId AND tahun=$tahun AND id<>$id LIMIT 1");
        if ($dup && mysqli_fetch_assoc($dup)) {
            header("Location: sdmk_kecamatan_rekap.php?kec=$kecId&th=$tahun&msg=exists");
            exit;
        }
        $stmt = $config->prepare("UPDATE tbl_sdmk_kecamatan_rekap SET id_kecamatan=?, id_item=?, tahun=?, jumlah=?, aktif=? WHERE id=?");
        $stmt->bind_param("iiiisi", $kecId, $itemId, $tahun, $jumlah, $aktif, $id);
        $ok = $stmt->execute();
        $stmt->close();
        header("Location: sdmk_kecamatan_rekap.php?kec=$kecId&th=$tahun&msg=" . ($ok ? 'updated' : 'error'));
        exit;
    }

    // HAPUS (SOFT DELETE) — kembali ke tab tahun yg sedang dibuka
    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $kecId = (int)($_POST['id_kecamatan'] ?? $selKec);
        $thBack = sdmk_rekap_valid_tahun($_POST['th'] ?? '');
        if ($id > 0) {
            mysqli_query($config, "UPDATE tbl_sdmk_kecamatan_rekap SET aktif='N' WHERE id=$id");
        }
        $thQs = $thBack !== null ? "&th=$thBack" : '';
        header("Location: sdmk_kecamatan_rekap.php?kec=$kecId$thQs&msg=deleted");
        exit;
    }

    // IMPORT EXCEL MATRIKS — 1 file = 1 kecamatan (wajib pilih dulu):
    //   Kolom A = "Jenis SDM", kolom berikutnya header tahun.
    //   Matching nama REUSE lib (exact normalized; ambigu -> tolak).
    //   Item tak dikenal DITOLAK (tanpa auto-insert ke master).
    //   Upsert per (kecamatan, item, tahun). Sel kosong = dilewati.
    if ($action === 'import_excel') {
        $kecId = (int)($_POST['id_kecamatan'] ?? 0);
        if (!isset($kecById[$kecId])) {
            header("Location: sdmk_kecamatan_rekap.php?msg=import_no_kec");
            exit;
        }
        $autoload = __DIR__ . '/../../vendor/autoload.php';
        if (!file_exists($autoload)) {
            header("Location: sdmk_kecamatan_rekap.php?kec=$kecId&msg=excel_no_vendor");
            exit;
        }
        require_once $autoload;
        if (!isset($_FILES['excel_file']) || $_FILES['excel_file']['error'] !== UPLOAD_ERR_OK) {
            header("Location: sdmk_kecamatan_rekap.php?kec=$kecId&msg=import_no_file");
            exit;
        }
        $ext = strtolower(pathinfo($_FILES['excel_file']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['xlsx', 'xls', 'csv'], true)) {
            header("Location: sdmk_kecamatan_rekap.php?kec=$kecId&msg=import_invalid");
            exit;
        }
        $added = 0; $updated = 0; $skipped = 0; $failed = 0;
        $failedRows = []; $similarRows = [];
        $failRow = function($rNum, $jenisText, $reason) use (&$failedRows, &$failed) {
            $failedRows[] = ['row' => $rNum, 'jenis' => $jenisText, 'reason' => $reason];
            $failed++;
        };
        try {
            $reader = $ext === 'csv'
                ? new \PhpOffice\PhpSpreadsheet\Reader\Csv()
                : ($ext === 'xls'
                    ? new \PhpOffice\PhpSpreadsheet\Reader\Xls()
                    : new \PhpOffice\PhpSpreadsheet\Reader\Xlsx());
            $reader->setReadDataOnly(true);
            $ss = $reader->load($_FILES['excel_file']['tmp_name']);
            $rows = $ss->getActiveSheet()->toArray(null, true, true, true);
            // Deteksi baris header: kolom A berisi "Jenis SDM"
            $headerRow = null;
            foreach ($rows as $rNum => $row) {
                if (strcasecmp(trim((string)($row['A'] ?? '')), 'Jenis SDM') === 0) { $headerRow = (int)$rNum; break; }
            }
            if ($headerRow === null) {
                header("Location: sdmk_kecamatan_rekap.php?kec=$kecId&msg=import_invalid");
                exit;
            }
            // Petakan kolom tahun dari header
            $yearCols = [];
            foreach ($rows[$headerRow] as $col => $val) {
                if ($col === 'A') continue;
                $t = sdmk_rekap_valid_tahun(trim((string)$val));
                if ($t !== null) $yearCols[$col] = $t;
            }
            if (empty($yearCols)) {
                header("Location: sdmk_kecamatan_rekap.php?kec=$kecId&msg=import_invalid");
                exit;
            }
            $stmt = mysqli_prepare($config, "INSERT INTO tbl_sdmk_kecamatan_rekap (id_kecamatan, id_item, tahun, jumlah, aktif) VALUES (?, ?, ?, ?, 'Y') ON DUPLICATE KEY UPDATE jumlah=VALUES(jumlah), aktif='Y'");
            if (!$stmt) {
                header("Location: sdmk_kecamatan_rekap.php?kec=$kecId&msg=import_error");
                exit;
            }
            $config->begin_transaction();
            foreach ($rows as $rNum => $row) {
                if ((int)$rNum <= $headerRow) continue;
                $nama = trim((string)($row['A'] ?? ''));
                if ($nama === '') continue;
                $hit = sdm_match_item($nama, $mapNorm);
                if ($hit['status'] === 'ambigu') {
                    $failRow($rNum, $nama, "Jenis SDM ambigu (cocok dengan lebih dari satu item master). Perbaiki nama di file agar eksak.");
                    continue;
                }
                if ($hit['status'] !== 'ok') {
                    $reason = "Jenis SDM '$nama' tidak ditemukan di Master, tambahkan dulu lewat menu SDMK utama.";
                    $sug = sdm_suggest_similar($nama, $existingNames, 75);
                    if ($sug !== null) {
                        $reason .= " (mungkin maksud '{$sug['name']}' {$sug['score']}%?)";
                        $similarRows[] = ['file' => $nama, 'existing' => $sug['name'], 'score' => $sug['score']];
                    }
                    $failRow($rNum, $nama, $reason);
                    continue;
                }
                $pid = (int)$hit['id'];
                foreach ($yearCols as $col => $tahun) {
                    $raw = trim((string)($row[$col] ?? ''));
                    if ($raw === '') continue; // kosong = lewati, bukan 0
                    $jumlah = sdmk_rekap_valid_jumlah($raw);
                    if ($jumlah === false || $jumlah === null) { $skipped++; continue; }
                    mysqli_stmt_bind_param($stmt, 'iiii', $kecId, $pid, $tahun, $jumlah);
                    if (mysqli_stmt_execute($stmt)) {
                        if (mysqli_stmt_affected_rows($stmt) === 1) $added++;
                        else $updated++;
                    } else {
                        $skipped++;
                    }
                }
            }
            mysqli_stmt_close($stmt);
            $config->commit();
        } catch (Exception $e) {
            $config->rollback();
            header("Location: sdmk_kecamatan_rekap.php?kec=$kecId&msg=import_error");
            exit;
        }
        $_SESSION['sdmk_rekap_import'] = [
            'kec_nama' => $kecById[$kecId],
            'added' => $added, 'updated' => $updated,
            'skipped' => $skipped, 'failed' => $failed,
            'failed_rows' => $failedRows, 'similar' => $similarRows,
        ];
        header("Location: sdmk_kecamatan_rekap.php?kec=$kecId&msg=import_done");
        exit;
    }
}

// ==========================================
// EXCEL TEMPLATE / EXPORT (download, read-only)
// Template: kolom A = daftar Jenis SDM master + header 5 tahun
// kalender terakhir. Export: matriks kecamatan terpilih.
// ==========================================
$excelDl = $_GET['excel'] ?? '';
if (in_array($excelDl, ['template', 'export'], true)) {
    $autoload = __DIR__ . '/../../vendor/autoload.php';
    if (!file_exists($autoload)) {
        header("Location: sdmk_kecamatan_rekap.php?msg=excel_no_vendor");
        exit;
    }
    require_once $autoload;
    $curY = (int)date('Y');
    $tplYears = range($curY - 4, $curY);
    $headers = array_merge(['Jenis SDM'], $tplYears);
    $ss = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $ss->getActiveSheet();
    $sheet->setTitle('Rekap SDMK Kecamatan');
    foreach ($headers as $i => $h) {
        $sheet->setCellValueByColumnAndRow($i + 1, 1, $h);
    }
    $sheet->getStyle('A1:F1')->getFont()->setBold(true);
    $sheet->getStyle('A1:F1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('BDD7EE');
    $sheet->getColumnDimension('A')->setWidth(38);
    foreach (['B', 'C', 'D', 'E', 'F'] as $c) { $sheet->getColumnDimension($c)->setWidth(12); }
    $sheet->freezePane('A2');
    $rowIdx = 2;
    if ($excelDl === 'export' && $hasTable && $selKec) {
        $qExp = mysqli_query($config, "SELECT si.nama_item, r.tahun, r.jumlah FROM tbl_sdmk_kecamatan_rekap r JOIN tbl_sdm_items si ON si.id=r.id_item WHERE r.id_kecamatan=$selKec AND r.aktif='Y' ORDER BY si.urutan, si.nama_item, r.tahun");
        $mat = []; $yrSet = [];
        while ($r = mysqli_fetch_assoc($qExp)) { $mat[$r['nama_item']][(int)$r['tahun']] = (int)$r['jumlah']; $yrSet[(int)$r['tahun']] = true; }
        $expYears = array_keys($yrSet); sort($expYears);
        if (!empty($expYears)) {
            foreach (['B','C','D','E','F','G','H','I','J','K','L'] as $ci => $c) {
                if (!isset($expYears[$ci])) break;
                $sheet->setCellValueByColumnAndRow($ci + 2, 1, $expYears[$ci]);
            }
        }
        foreach ($itemList as $it) {
            $nm = $it['nama_item'];
            if (!isset($mat[$nm])) continue;
            $sheet->setCellValueByColumnAndRow(1, $rowIdx, $nm);
            $cols = !empty($expYears) ? $expYears : $tplYears;
            foreach ($cols as $i => $t) {
                if (isset($mat[$nm][$t])) { $sheet->setCellValueByColumnAndRow($i + 2, $rowIdx, $mat[$nm][$t]); }
            }
            $rowIdx++;
        }
        $filename = 'Export_Rekap_SDMK_' . preg_replace('/[^A-Za-z0-9]+/', '_', ($kecById[$selKec] ?? $selKec)) . '_' . date('Ymd') . '.xlsx';
    } else {
        foreach ($itemList as $it) {
            $sheet->setCellValueByColumnAndRow(1, $rowIdx, $it['nama_item']);
            $rowIdx++;
        }
        $filename = 'Template_Rekap_SDMK_Kecamatan.xlsx';
    }
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($ss);
    $writer->save('php://output');
    exit;
}

// ==========================================
// DATA RETRIEVAL (matriks item x tahun, per kecamatan terpilih)
// ==========================================
$search = isset($_GET['search']) && $_GET['search'] !== '' ? mysqli_real_escape_string($config, trim($_GET['search'])) : null;

$tahunList = [];  // tahun yg ada data di kecamatan ini (untuk tab)
$matriks = [];    // [nama_item][tahun] = ['id'=>..,'jumlah'=>..] (1 tahun aktif)
$statJenis = 0; $statBaris = 0; $statKec = 0;
if ($hasTable && $selKec) {
    $qT = mysqli_query($config, "SELECT DISTINCT tahun FROM tbl_sdmk_kecamatan_rekap WHERE id_kecamatan=$selKec AND aktif='Y' ORDER BY tahun ASC");
    while ($qT && ($r = mysqli_fetch_assoc($qT))) $tahunList[] = (int)$r['tahun'];
}

// TAB TAHUN AKTIF: ?th=YYYY | ?th=new | default = tahun terbaru berdata.
// $tahunAktif = int | null (null = mode "+ Tahun Baru": form memunculkan
// input tahun sekali-saja; matriks 1 kolom tanpa data).
$thParam = $_GET['th'] ?? '';
if ($thParam === 'new' || empty($tahunList)) {
    $tahunAktif = null;
} elseif (ctype_digit((string)$thParam) && in_array((int)$thParam, $tahunList, true)) {
    $tahunAktif = (int)$thParam;
} else {
    $tahunAktif = end($tahunList); // terbaru
}

if ($hasTable && $selKec) {
    $whereSearch = $search ? "AND si.nama_item LIKE '%$search%'" : '';
    $whereTahun = $tahunAktif !== null ? "AND r.tahun=$tahunAktif" : 'AND 1=0';
    $qAll = mysqli_query($config, "SELECT r.id, r.tahun, r.jumlah, si.nama_item FROM tbl_sdmk_kecamatan_rekap r JOIN tbl_sdm_items si ON si.id=r.id_item WHERE r.id_kecamatan=$selKec AND r.aktif='Y' $whereTahun $whereSearch ORDER BY si.urutan, si.nama_item");
    while ($qAll && ($r = mysqli_fetch_assoc($qAll))) {
        $matriks[$r['nama_item']][(int)$r['tahun']] = ['id' => (int)$r['id'], 'jumlah' => (int)$r['jumlah']];
    }
    $statJenis = count($matriks);
    $qCount = mysqli_query($config, "SELECT COUNT(*) AS c FROM tbl_sdmk_kecamatan_rekap WHERE id_kecamatan=$selKec AND aktif='Y'");
    if ($qCount) { $statBaris = (int)mysqli_fetch_assoc($qCount)['c']; }
    $qKec = mysqli_query($config, "SELECT COUNT(DISTINCT id_kecamatan) AS c FROM tbl_sdmk_kecamatan_rekap WHERE aktif='Y'");
    if ($qKec) { $statKec = (int)mysqli_fetch_assoc($qKec)['c']; }
}

$importResult = $_SESSION['sdmk_rekap_import'] ?? null;
if ($msg === 'import_done' && $importResult) { unset($_SESSION['sdmk_rekap_import']); }
else { $importResult = null; }

$username = $_SESSION['admin_username'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekap SDMK per Kecamatan - Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Poppins',sans-serif; background:#061426; min-height:100vh; display:flex; color:#fff; }

        /* SIDEBAR (pola sama seperti CRUD lain) */
        .sidebar { width:260px; min-height:100vh; background:rgba(255,255,255,0.04); backdrop-filter:blur(12px); border-right:1px solid rgba(255,255,255,0.06); padding:30px 20px; flex-shrink:0; position:sticky; top:0; height:100vh; overflow-y:auto; scrollbar-width:none; -ms-overflow-style:none; }
        /* Sembunyikan scrollbar, sidebar tetap bisa scroll */
        .sidebar::-webkit-scrollbar { display:none; width:0; height:0; }
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
        .sidebar-menu .menu-group {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: rgba(255,255,255,0.3);
            padding: 14px 16px 6px;
        }
        .sidebar-menu .logout { margin-top:30px; border-top:1px solid rgba(255,255,255,0.06); padding-top:20px; }
        .sidebar-menu .logout a { color:rgba(255,82,82,0.7); }
        .sidebar-menu .logout a:hover { background:rgba(255,82,82,0.12); color:#ff6b6b; }

        .main-content { flex:1; padding:30px 40px; }
        .page-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; }
        .page-header h1 { color:#fff; font-size:28px; font-weight:700; }
        .page-header p { color:#87e3ff; font-size:14px; margin-top:4px; }
        .page-header .back-link { color:#87e3ff; text-decoration:none; font-size:14px; display:flex; align-items:center; gap:8px; transition:0.3s; }
        .page-header .back-link:hover { color:#00d4ff; }

        .alert { padding:12px 18px; border-radius:12px; margin-bottom:18px; font-size:14px; }
        .alert-success { background:rgba(76,175,80,0.12); border:1px solid rgba(76,175,80,0.25); color:#81c784; }
        .alert-warning { background:rgba(255,193,7,0.12); border:1px solid rgba(255,193,7,0.25); color:#ffd54f; }

        /* STATS BAR */
        .stats-grid { display:grid; grid-template-columns:repeat(4, 1fr); gap:16px; margin-bottom:24px; }
        .stat-card { background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.06); border-radius:16px; padding:18px 20px; display:flex; align-items:center; gap:16px; }
        .stat-icon { width:44px; height:44px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:20px; }
        .stat-info b { display:block; font-size:22px; color:#fff; }
        .stat-info span { font-size:12px; color:rgba(255,255,255,0.5); }

        /* CARD + FORM */
        .card { background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.06); border-radius:18px; padding:24px; margin-bottom:24px; }
        .card h3 { color:#fff; font-size:17px; margin-bottom:18px; display:flex; align-items:center; gap:10px; }
        .form-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(220px,1fr)); gap:16px; }
        .form-group label { display:block; font-size:12px; color:#87e3ff; margin-bottom:6px; font-weight:600; }
        .form-group input, .form-group select { width:100%; padding:10px 14px; border-radius:10px; border:1px solid rgba(255,255,255,0.12); background:rgba(255,255,255,0.05); color:#fff; font-size:14px; font-family:'Poppins',sans-serif; }
        .form-group select option, .form-group select optgroup { background:#0b2343; }
        .form-actions { display:flex; gap:10px; flex-wrap:wrap; margin-top:18px; }
        .btn-primary { padding:10px 24px; border-radius:10px; border:none; background:linear-gradient(135deg,#00d4ff,#0088cc); color:#fff; font-weight:600; cursor:pointer; transition:0.3s; display:inline-flex; align-items:center; gap:8px; font-size:14px; text-decoration:none; font-family:'Poppins',sans-serif; }
        .btn-primary:hover { transform:translateY(-2px); box-shadow:0 8px 25px rgba(0,212,255,0.2); }
        .btn-excel-import { background:linear-gradient(135deg,#FF9800,#EF6C00); }
        .btn-excel-export { background:linear-gradient(135deg,#4CAF50,#2E7D32); }
        .btn-excel-template { background:rgba(255,255,255,0.08); border:1px solid rgba(255,255,255,0.15); }

        /* TOOLBAR + TABLE */
        .toolbar { display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; margin-bottom:18px; }
        .toolbar h3 { margin-bottom:0; }
        /* TAB TAHUN (1 tab per tahun berdata + tab "+ Tahun Baru") */
        .year-tabs { display:flex; gap:8px; flex-wrap:wrap; margin-bottom:18px; }
        .year-tab { padding:9px 18px; border-radius:10px; border:1px solid rgba(255,255,255,0.12); background:rgba(255,255,255,0.05); color:rgba(255,255,255,0.65); text-decoration:none; font-size:13px; font-weight:600; font-family:'Poppins',sans-serif; transition:0.25s; display:inline-flex; align-items:center; gap:8px; }
        .year-tab:hover { background:rgba(0,212,255,0.12); color:#fff; }
        .year-tab.active { background:rgba(0,212,255,0.15); border-color:rgba(0,212,255,0.4); color:#00d4ff; }
        .year-tab.new-tab { border-style:dashed; }
        .year-tab.new-tab.active { background:rgba(255,193,7,0.12); border-color:rgba(255,193,7,0.4); color:#ffd54f; }
        .filters-wrap { display:flex; gap:8px; align-items:center; }
        .filters-wrap input, .filters-wrap select { padding:9px 14px; border-radius:10px; border:1px solid rgba(255,255,255,0.12); background:rgba(255,255,255,0.05); color:#fff; font-size:13px; font-family:'Poppins',sans-serif; }
        .filters-wrap select option { background:#0b2343; }
        .btn-icon { padding:9px 12px; border-radius:10px; border:1px solid rgba(0,212,255,0.3); background:rgba(0,212,255,0.1); color:#00d4ff; cursor:pointer; font-size:13px; text-decoration:none; display:inline-flex; align-items:center; gap:6px; }
        .btn-icon:hover { background:rgba(0,212,255,0.2); }
        .btn-danger { border-color:rgba(255,82,82,0.3); background:rgba(255,82,82,0.1); color:#ff8a80; }
        table { width:100%; border-collapse:collapse; }
        th, td { padding:12px 14px; text-align:left; font-size:13px; border-bottom:1px solid rgba(255,255,255,0.06); }
        th { color:#87e3ff; font-weight:600; font-size:12px; text-transform:uppercase; letter-spacing:0.5px; }
        td { color:rgba(255,255,255,0.85); }
        td.num { text-align:right; font-variant-numeric:tabular-nums; }
        th.num { text-align:right; }
        .cell-empty { color:rgba(255,255,255,0.25); }

        /* MODAL */
        .modal-box { background:#0b2343; border:1px solid rgba(255,255,255,0.1); border-radius:18px; padding:28px; max-width:520px; width:92%; }
        .modal-box h2 { color:#fff; font-size:19px; margin-bottom:18px; display:flex; align-items:center; gap:10px; }
        #editModal .modal-box, #importModal .modal-box { scrollbar-width:none; -ms-overflow-style:none; }
        #editModal .modal-box::-webkit-scrollbar, #importModal .modal-box::-webkit-scrollbar { display:none; }
        .btn-secondary { padding:10px 20px; border-radius:10px; border:1px solid rgba(255,255,255,0.15); background:transparent; color:#fff; cursor:pointer; font-size:14px; font-family:'Poppins',sans-serif; }

        /* LAPORAN IMPORT (gaya ringkas STEP 6 SDMK utama) */
        .rep-table { width:100%; border-collapse:collapse; font-size:12px; }
        .rep-table th { text-align:left; padding:6px; border-bottom:1px solid rgba(255,255,255,0.15); }
        .rep-table td { padding:6px; border-bottom:1px solid rgba(255,255,255,0.06); }

        /* HEADER GRUP KATEGORI (pola sama seperti rekap SDMK per fasyankes) */
        .kategori-row { background:#123B63; color:#fff; font-weight:800; letter-spacing:.3px; }
        .kategori-row td { padding:11px 12px; border-bottom:1px solid rgba(255,255,255,.08); color:#fff; box-shadow:inset 4px 0 0 #00d4ff; }
        /* Ikon pena abu-abu untuk sel kosong (buka modal isi, pola sama dgn edit) */
        .btn-fill { border-color:rgba(255,255,255,0.15); background:rgba(255,255,255,0.06); color:rgba(255,255,255,0.45); }

        @media (max-width:1024px) { .stats-grid { grid-template-columns:repeat(2,1fr); } }
        @media (max-width:768px) { .sidebar{display:none;} .main-content{padding:20px;} .form-grid{grid-template-columns:1fr;} .stats-grid{grid-template-columns:1fr;} }
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
        <li class="menu-group">Utama</li>
        <li><a href="../index.php"><i class="fas fa-chart-pie"></i> Dashboard</a></li>
        <li class="menu-group">Fasyankes</li>
        <li><a href="fasyankes.php"><i class="fas fa-hospital"></i> Fasyankes</a></li>
        <li><a href="faskes_rekap.php"><i class="fas fa-table"></i> Rekap Fasyankes</a></li>
        <li class="menu-group">SDM Kesehatan</li>
        <li><a href="sdmk.php"><i class="fas fa-hospital-user"></i> SDMK</a></li>
        <li><a href="sdmk_kecamatan_rekap.php" class="active"><i class="fas fa-chart-bar"></i> Rekap SDMK</a></li>
        <li><a href="spesialis.php"><i class="fas fa-user-doctor"></i> Spesialis Dokter</a></li>
        <li class="menu-group">Wilayah &amp; Data Lain</li>
        <li><a href="kecamatan.php"><i class="fas fa-map"></i> Kecamatan</a></li>
        <li><a href="penyakit.php"><i class="fas fa-disease"></i> Penyakit</a></li>
        <li class="menu-group">SPM</li>
        <li><a href="spm.php"><i class="fas fa-chart-pie"></i> SPM Target</a></li>
        <li><a href="spm_realisasi.php"><i class="fas fa-chart-line"></i> SPM Realisasi</a></li>
        <li class="menu-group">Lainnya</li>
        <li><a href="portal_info.php"><i class="fas fa-circle-info"></i> Informasi Portal</a></li>
        <li class="logout"><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
    </ul>
</div>

<!-- MAIN CONTENT -->
<div class="main-content">
    <div class="page-header">
        <div>
            <h1>Rekap SDMK per Kecamatan</h1>
            <p>Agregat <?= htmlspecialchars($kecById[$selKec] ?? '-') ?> per tahun × jenis SDM (terpisah dari data individual per faskes).</p>
        </div>
        <a href="../index.php" class="back-link"><i class="fas fa-arrow-left"></i> Kembali</a>
    </div>

    <?php if ($msg === 'added'): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle"></i> Data rekap berhasil ditambahkan!</div>
    <?php elseif ($msg === 'updated'): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle"></i> Data rekap berhasil diperbarui (upsert: kombinasi sudah ada, angka ditimpa)! </div>
    <?php elseif ($msg === 'deleted'): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle"></i> Data rekap berhasil dihapus!</div>
    <?php elseif ($msg === 'exists'): ?>
        <div class="alert alert-warning"><i class="fas fa-exclamation-triangle"></i> Kombinasi kecamatan × jenis SDM × tahun tersebut sudah ada di baris lain. Ubah salah satunya dulu.</div>
    <?php elseif ($msg === 'invalid'): ?>
        <div class="alert alert-warning"><i class="fas fa-exclamation-triangle"></i> Input tidak valid. Pastikan kecamatan &amp; jenis SDM dipilih, tahun 1900–2100, jumlah angka ≥ 0.</div>
    <?php elseif ($msg === 'error'): ?>
        <div class="alert alert-warning"><i class="fas fa-exclamation-circle"></i> Gagal menyimpan. Silakan coba lagi.</div>
    <?php elseif ($msg === 'import_no_kec'): ?>
        <div class="alert alert-warning"><i class="fas fa-exclamation-triangle"></i> Pilih Kecamatan dulu sebelum upload file import (1 file = 1 kecamatan).</div>
    <?php elseif (in_array($msg, ['import_no_file', 'import_invalid', 'import_error'], true)): ?>
        <div class="alert alert-warning"><i class="fas fa-exclamation-circle"></i> Import Excel gagal. Pastikan file .xlsx/.xls/.csv sesuai Template (kolom A = "Jenis SDM", header kolom = tahun) dan Kecamatan sudah dipilih.</div>
    <?php elseif ($msg === 'excel_no_vendor'): ?>
        <div class="alert alert-warning"><i class="fas fa-exclamation-triangle"></i> Library Excel (PhpSpreadsheet) belum terinstal. Jalankan <code>composer install</code> terlebih dahulu.</div>
    <?php endif; ?>

    <?php if (!$hasTable): ?>
        <div class="alert alert-warning"><i class="fas fa-database"></i> Tabel <code>tbl_sdmk_kecamatan_rekap</code> belum ada — migration <code>database/migrations/20260915_sdmk_kecamatan_rekap.sql</code> belum dijalankan. CRUD aktif setelah migration dijalankan.</div>
    <?php endif; ?>

    <?php if ($importResult): ?>
    <div class="card" style="border-color:rgba(0,212,255,0.25)">
        <h3><i class="fas fa-file-excel" style="color:#4CAF50"></i> Hasil Import — <?= htmlspecialchars($importResult['kec_nama']) ?></h3>
        <p style="font-size:15px">Ditambah: <strong style="color:#81c784"><?= (int)$importResult['added'] ?></strong> sel &nbsp;|&nbsp; Diperbarui: <strong style="color:#81c784"><?= (int)$importResult['updated'] ?></strong> sel &nbsp;|&nbsp; Dilewati: <strong><?= (int)$importResult['skipped'] ?></strong> sel &nbsp;|&nbsp; Gagal: <strong style="color:<?= ((int)$importResult['failed']) > 0 ? '#ff8a80' : '#81c784' ?>"><?= (int)$importResult['failed'] ?></strong> baris</p>
        <?php $fr = $importResult['failed_rows'] ?? []; ?>
        <h4 style="margin:14px 0 8px;font-size:14px;color:#84e7ff">Baris gagal (<?= count($fr) ?>)</h4>
        <?php if (empty($fr)): ?><p style="font-size:13px;color:rgba(255,255,255,0.5)">Tidak ada.</p>
        <?php else: ?><details open style="font-size:13px"><summary style="cursor:pointer;color:#87e8ff">Tampilkan daftar</summary><div style="max-height:220px;overflow:auto;margin-top:8px">
        <table class="rep-table">
        <tr><th style="text-align:right;">Baris Excel</th><th>Isi kolom Jenis SDM</th><th>Alasan gagal</th></tr>
        <?php foreach ($fr as $f): ?><tr><td style="text-align:right;"><?= (int)$f['row'] ?></td><td><?= htmlspecialchars($f['jenis']) ?></td><td><?= htmlspecialchars($f['reason']) ?></td></tr><?php endforeach; ?>
        </table></div></details><?php endif; ?>
        <?php $sm = $importResult['similar'] ?? []; ?>
        <?php if (!empty($sm)): ?>
        <h4 style="margin:14px 0 8px;font-size:14px;color:#84e7ff">Saran kemiripan nama (<?= count($sm) ?>) — baris tetap DITOLAK, tidak auto-insert</h4>
        <details open style="font-size:13px"><summary style="cursor:pointer;color:#87e8ff">Tampilkan daftar</summary><div style="max-height:220px;overflow:auto;margin-top:8px">
        <table class="rep-table">
        <tr><th>Nama di file</th><th>Mirip dengan master</th><th style="text-align:right;">Skor</th></tr>
        <?php foreach ($sm as $s): ?><tr><td><?= htmlspecialchars($s['file']) ?></td><td><?= htmlspecialchars($s['existing']) ?></td><td style="text-align:right;"><?= (int)$s['score'] ?>%</td></tr><?php endforeach; ?>
        </table></div></details>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- STATS -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon" style="background:rgba(0,212,255,0.15);color:#00d4ff;"><i class="fas fa-list"></i></div>
            <div class="stat-info"><b><?= number_format($statJenis) ?></b><span>Jenis Terisi (kec ini)</span></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background:rgba(76,175,80,0.15);color:#81c784;"><i class="fas fa-table"></i></div>
            <div class="stat-info"><b><?= number_format($statBaris) ?></b><span>Baris Data Aktif (kec ini)</span></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background:rgba(255,193,7,0.15);color:#ffd54f;"><i class="fas fa-calendar-alt"></i></div>
            <div class="stat-info"><b><?= count($tahunList) ?></b><span>Tahun Terisi (kec ini)</span></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background:rgba(156,39,176,0.15);color:#ce93d8;"><i class="fas fa-map"></i></div>
            <div class="stat-info"><b><?= (int)$statKec ?>/<?= count($kecList) ?></b><span>Kecamatan Berdata</span></div>
        </div>
    </div>

    <!-- FORM TAMBAH (tahun mengikuti tab aktif; input tahun hanya di mode "+ Tahun Baru") -->
    <div class="card">
        <h3><i class="fas fa-plus-circle" style="color:#00d4ff;"></i>Tambah Data Rekap<?= $tahunAktif !== null ? ' — ' . htmlspecialchars($kecById[$selKec] ?? '') . ' · ' . $tahunAktif : '' ?></h3>
        <form method="POST">
            <input type="hidden" name="action" value="add">
            <?php if ($tahunAktif !== null): ?>
                <input type="hidden" name="tahun" value="<?= $tahunAktif ?>">
            <?php endif; ?>
            <div class="form-grid">
                <div class="form-group">
                    <label>Kecamatan</label>
                    <select name="id_kecamatan" required>
                        <?php foreach ($kecList as $k): ?>
                            <option value="<?= (int)$k['id_kecamatan'] ?>" <?= $selKec === (int)$k['id_kecamatan'] ? 'selected' : '' ?>><?= htmlspecialchars($k['nama_kecamatan']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Jenis SDM</label>
                    <select name="id_item" required>
                        <option value="">-- Pilih Jenis SDM --</option>
                        <?php $lastKat = null; foreach ($itemList as $it): ?>
                            <?php if ($it['kategori'] !== $lastKat): $lastKat = $it['kategori']; ?>
                                <?php if ($it !== $itemList[0]): ?></optgroup><?php endif; ?>
                                <optgroup label="<?= htmlspecialchars($lastKat) ?>">
                            <?php endif; ?>
                            <option value="<?= (int)$it['id'] ?>"><?= htmlspecialchars($it['nama_item']) ?></option>
                        <?php endforeach; ?>
                        <?php if (!empty($itemList)): ?></optgroup><?php endif; ?>
                    </select>
                </div>
                <?php if ($tahunAktif === null): ?>
                <div class="form-group">
                    <label>Tahun (baru — sekali saja, membuka tab baru)</label>
                    <input type="number" name="tahun" min="1900" max="2100" value="<?= (int)date('Y') ?>" required>
                </div>
                <?php endif; ?>
                <div class="form-group">
                    <label>Jumlah</label>
                    <input type="number" name="jumlah" min="0" step="1" required placeholder="Contoh: 12">
                </div>
            </div>
            <?php if ($tahunAktif !== null): ?>
                <p style="font-size:12px;color:rgba(255,255,255,0.45);margin-top:10px;">Menyimpan untuk <strong style="color:#87e3ff;"><?= htmlspecialchars($kecById[$selKec] ?? '') ?> · <?= $tahunAktif ?></strong> (mengikuti tab aktif). Kombinasi yang sudah ada akan di-UPDATE angkanya (upsert), bukan ditolak.</p>
            <?php else: ?>
                <p style="font-size:12px;color:rgba(255,255,255,0.45);margin-top:10px;">Belum ada tab tahun — simpan 1 baris untuk membuka tab tahun baru. Kombinasi yang sudah ada akan di-UPDATE angkanya (upsert).</p>
            <?php endif; ?>
            <div class="form-actions">
                <button type="submit" class="btn-primary" <?= $hasTable ? '' : 'disabled' ?>><i class="fas fa-save"></i> Simpan</button>
                <button type="button" class="btn-primary btn-excel-import" onclick="document.getElementById('importModal').style.display='flex'"><i class="fas fa-file-import"></i> Import Excel</button>
                <a href="sdmk_kecamatan_rekap.php?excel=export&kec=<?= $selKec ?>" class="btn-primary btn-excel-export"><i class="fas fa-file-export"></i> Export Excel</a>
                <a href="sdmk_kecamatan_rekap.php?excel=template" class="btn-primary btn-excel-template"><i class="fas fa-file-excel"></i> Template Excel</a>
            </div>
        </form>
    </div>

    <!-- TAB TAHUN + TABEL 1 KOLOM (1 tab = 1 tahun) -->
    <div class="card">
        <div class="toolbar">
            <h3><i class="fas fa-table" style="color:#00d4ff;"></i>Matriks Rekap — <?= htmlspecialchars($kecById[$selKec] ?? '-') ?></h3>
            <form method="GET" class="filters-wrap">
                <select name="kec" onchange="this.form.submit()">
                    <?php foreach ($kecList as $k): ?>
                        <option value="<?= (int)$k['id_kecamatan'] ?>" <?= $selKec === (int)$k['id_kecamatan'] ? 'selected' : '' ?>><?= htmlspecialchars($k['nama_kecamatan']) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if ($tahunAktif !== null): ?>
                    <input type="hidden" name="th" value="<?= $tahunAktif ?>">
                <?php else: ?>
                    <input type="hidden" name="th" value="new">
                <?php endif; ?>
                <input type="text" name="search" placeholder="Cari jenis SDM..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                <button type="submit" class="btn-icon"><i class="fas fa-search"></i></button>
                <?php if ($search): ?>
                    <a href="sdmk_kecamatan_rekap.php?kec=<?= $selKec ?><?= $tahunAktif !== null ? '&th=' . $tahunAktif : '&th=new' ?>" class="btn-icon btn-danger" title="Reset Filter"><i class="fas fa-times"></i></a>
                <?php endif; ?>
            </form>
        </div>

        <?php $searchQs = ($search && isset($_GET['search']) && $_GET['search'] !== '') ? '&search=' . urlencode($_GET['search']) : ''; ?>
        <div class="year-tabs">
            <?php foreach ($tahunList as $t): ?>
                <a class="year-tab <?= $tahunAktif === $t ? 'active' : '' ?>" href="sdmk_kecamatan_rekap.php?kec=<?= $selKec ?>&th=<?= $t ?><?= $searchQs ?>"><i class="fas fa-calendar-alt"></i><?= $t ?></a>
            <?php endforeach; ?>
            <a class="year-tab new-tab <?= $tahunAktif === null ? 'active' : '' ?>" href="sdmk_kecamatan_rekap.php?kec=<?= $selKec ?>&th=new<?= $searchQs ?>"><i class="fas fa-plus"></i> Tahun Baru</a>
        </div>

        <div style="overflow-x:auto;">
        <table>
            <thead>
                <tr>
                    <th>Jenis SDM</th>
                    <th class="num"><?= $tahunAktif !== null ? $tahunAktif : 'Jumlah' ?></th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Baris dikelompokkan per kategori dgn header grup (pola rekap
                // SDMK per fasyankes) — tanpa subtitle kategori per baris.
                // Hanya 1 kolom angka: tahun tab aktif (atau kosong di mode baru).
                $kategoriOrder = ['Tenaga Kesehatan', 'Asisten Tenaga Kesehatan', 'Tenaga Penunjang'];
                $kategoriLabel = ['Tenaga Kesehatan' => 'A. Tenaga Kesehatan', 'Asisten Tenaga Kesehatan' => 'B. Asisten Tenaga Kesehatan', 'Tenaga Penunjang' => 'C. Tenaga Penunjang'];
                $searchText = $search ? trim($_GET['search'] ?? '') : '';
                $grouped = [];
                foreach ($itemList as $it) {
                    if ($searchText !== '' && stripos($it['nama_item'], $searchText) === false) continue;
                    $grouped[$it['kategori']][] = $it;
                }
                $t = $tahunAktif; // int|null
                $colspan = 3;
                $hasRows = false;
                foreach ($kategoriOrder as $kat) { if (!empty($grouped[$kat])) { $hasRows = true; break; } }
                ?>
                <?php if (!$hasRows): ?>
                <tr>
                    <td colspan="<?= $colspan ?>" style="text-align:center;color:rgba(255,255,255,0.4);padding:28px;">
                        <i class="fas fa-inbox"></i> Belum ada master Jenis SDM atau tidak cocok dengan pencarian.
                    </td>
                </tr>
                <?php endif; ?>
                <?php if ($tahunAktif === null): ?>
                <tr>
                    <td colspan="<?= $colspan ?>" style="text-align:center;color:rgba(255,255,255,0.5);padding:16px;font-size:13px;">
                        <i class="fas fa-info-circle" style="color:#00d4ff;"></i> Mode tahun baru — isi form di atas (1 baris) untuk membuka tab tahun, atau klik ikon pena di baris mana pun lalu isi tahunnya di modal.
                    </td>
                </tr>
                <?php endif; ?>
                <?php foreach ($kategoriOrder as $kat): ?>
                <?php if (empty($grouped[$kat])) continue; ?>
                <tr class="kategori-row"><td colspan="<?= $colspan ?>"><?= htmlspecialchars($kategoriLabel[$kat]) ?></td></tr>
                <?php foreach ($grouped[$kat] as $it):
                    $nm = $it['nama_item'];
                    $cell = ($t !== null && isset($matriks[$nm][$t])) ? $matriks[$nm][$t] : null;
                ?>
                <tr>
                    <td><strong><?= htmlspecialchars($nm) ?></strong></td>
                    <td class="num">
                        <?php if ($cell !== null): ?>
                            <?= number_format($cell['jumlah']) ?>
                            <button class="btn-icon edit-btn" style="padding:4px 8px;font-size:11px;margin-left:6px;"
                                data-id="<?= $cell['id'] ?>"
                                data-kec="<?= $selKec ?>"
                                data-item="<?= (int)$it['id'] ?>"
                                data-tahun="<?= $t ?>"
                                data-jumlah="<?= $cell['jumlah'] ?>"
                                title="Edit <?= htmlspecialchars($nm) ?> <?= $t ?>"><i class="fas fa-pen"></i></button>
                            <form method="POST" style="display:inline-block;" onsubmit="return confirm('Yakin hapus data <?= htmlspecialchars(addslashes($nm)) ?> (<?= htmlspecialchars($kecById[$selKec] ?? '') ?>, <?= $t ?>)?')">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $cell['id'] ?>">
                                <input type="hidden" name="id_kecamatan" value="<?= $selKec ?>">
                                <input type="hidden" name="th" value="<?= $t ?>">
                                <button type="submit" class="btn-icon btn-danger" style="padding:4px 8px;font-size:11px;" title="Hapus"><i class="fas fa-trash"></i></button>
                            </form>
                        <?php else: ?>
                            <span class="cell-empty">—</span><button class="btn-icon btn-fill fill-btn" style="padding:4px 8px;font-size:11px;margin-left:6px;"
                                data-kec="<?= $selKec ?>"
                                data-item="<?= (int)$it['id'] ?>"
                                data-tahun="<?= $t !== null ? $t : '' ?>"
                                title="Isi <?= htmlspecialchars($nm) ?><?= $t !== null ? ' ' . $t : '' ?>"><i class="fas fa-pen"></i></button>
                        <?php endif; ?>
                    </td>
                    <td><span style="color:rgba(255,255,255,0.3);font-size:12px;">per sel</span></td>
                </tr>
                <?php endforeach; ?>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>

<!-- MODAL EDIT -->
<div id="editModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.65);backdrop-filter:blur(6px);z-index:999;justify-content:center;align-items:center;">
    <div class="modal-box">
        <h2 id="modalTitle"><i class="fas fa-pen" style="color:#00d4ff;"></i> Edit Data Rekap</h2>
        <form method="POST">
            <input type="hidden" name="action" value="edit" id="formAction">
            <input type="hidden" name="id" id="eId">
            <div class="form-grid" style="grid-template-columns:1fr;">
                <div class="form-group">
                    <label>Kecamatan</label>
                    <select name="id_kecamatan" id="eKec" required>
                        <?php foreach ($kecList as $k): ?>
                            <option value="<?= (int)$k['id_kecamatan'] ?>"><?= htmlspecialchars($k['nama_kecamatan']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Jenis SDM</label>
                    <select name="id_item" id="eItem" required>
                        <?php foreach ($itemList as $it): ?>
                            <option value="<?= (int)$it['id'] ?>"><?= htmlspecialchars($it['nama_item']) ?> (<?= htmlspecialchars($it['kategori']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Tahun</label>
                    <input type="number" name="tahun" id="eTahun" min="1900" max="2100" required>
                </div>
                <div class="form-group">
                    <label>Jumlah</label>
                    <input type="number" name="jumlah" id="eJumlah" min="0" step="1" required>
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="aktif" id="eAktif">
                        <option value="Y">Aktif</option>
                        <option value="N">Nonaktif</option>
                    </select>
                </div>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn-primary"><i class="fas fa-save"></i> Simpan Perubahan</button>
                <button type="button" class="btn-secondary" onclick="document.getElementById('editModal').style.display='none'">Batal</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL IMPORT -->
<div id="importModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.65);backdrop-filter:blur(6px);z-index:999;justify-content:center;align-items:center;">
    <div class="modal-box">
        <h2><i class="fas fa-file-import" style="color:#00d4ff;"></i> Import Excel Rekap</h2>
        <form method="POST" enctype="multipart/form-data">
            <p style="font-size:13px;color:rgba(255,255,255,0.6);margin-bottom:14px;line-height:1.7;">
                Unduh <a href="sdmk_kecamatan_rekap.php?excel=template" style="color:#00d4ff;">Template Excel</a> terlebih dahulu.
                Format: kolom A = "Jenis SDM", kolom berikutnya header tahun. 1 file = 1 kecamatan.
                Nama yang tidak ada di Master DITOLAK (tidak auto-insert).
            </p>
            <input type="hidden" name="action" value="import_excel">
            <div class="form-group" style="margin-bottom:16px;">
                <label>Kecamatan (wajib — 1 file untuk kecamatan ini)</label>
                <select name="id_kecamatan" required>
                    <?php foreach ($kecList as $k): ?>
                        <option value="<?= (int)$k['id_kecamatan'] ?>" <?= $selKec === (int)$k['id_kecamatan'] ? 'selected' : '' ?>><?= htmlspecialchars($k['nama_kecamatan']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group" style="margin-bottom:16px;">
                <label>File Excel (.xlsx / .xls / .csv, maks. 5MB)</label>
                <input type="file" name="excel_file" accept=".xlsx,.xls,.csv" required>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn-primary"><i class="fas fa-upload"></i> Import</button>
                <button type="button" class="btn-secondary" onclick="document.getElementById('importModal').style.display='none'">Batal</button>
            </div>
        </form>
    </div>
</div>

<script>
// Satu modal untuk EDIT (sel berisi) maupun ISI (sel kosong) — pola sama.
// Mode edit: action=edit + id terisi. Mode isi: action=add + id kosong.
function openRekapModal(mode, btn) {
    document.getElementById('formAction').value = (mode === 'add') ? 'add' : 'edit';
    document.getElementById('modalTitle').innerHTML = (mode === 'add'
        ? '<i class="fas fa-plus-circle" style="color:#00d4ff;"></i> Isi Data Rekap'
        : '<i class="fas fa-pen" style="color:#00d4ff;"></i> Edit Data Rekap');
    document.getElementById('eId').value = btn.dataset.id || '';
    document.getElementById('eKec').value = btn.dataset.kec || '';
    document.getElementById('eItem').value = btn.dataset.item || '';
    document.getElementById('eTahun').value = btn.dataset.tahun || '';
    document.getElementById('eJumlah').value = btn.dataset.jumlah || '';
    document.getElementById('eAktif').value = 'Y';
    document.getElementById('editModal').style.display = 'flex';
}
document.querySelectorAll('.edit-btn').forEach(function(btn) {
    btn.addEventListener('click', function() { openRekapModal('edit', btn); });
});
document.querySelectorAll('.fill-btn').forEach(function(btn) {
    btn.addEventListener('click', function() { openRekapModal('add', btn); });
});
document.getElementById('editModal').onclick = function(e) {
    if (e.target === this) this.style.display = 'none';
};
document.getElementById('importModal').onclick = function(e) {
    if (e.target === this) this.style.display = 'none';
};
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        document.getElementById('editModal').style.display = 'none';
        document.getElementById('importModal').style.display = 'none';
    }
});
</script>
<script>window.PORTAL_NOTIFY_OK=["added","updated","deleted","import_done"];</script>
</body>
</html>
