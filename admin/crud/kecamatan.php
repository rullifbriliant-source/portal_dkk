<?php
require_once '../config.php';
requireLogin();
$current_page = basename($_SERVER['PHP_SELF']);

/**
 * Validasi server-side untuk Desa/Kelurahan (tbl_desa_kelurahan).
 * Return '' bila valid, atau kode error: kecamatan, kode_wajib,
 * kode_format, kode_duplikat, nama, jenis, l, p, db.
 * $excludeId > 0 = mode edit (kode_wilayah immutable, tidak divalidasi).
 * Keterbatasan yang dilaporkan: prefix Kemendagri (XX) tidak divalidasi
 * silang terhadap kecamatan karena belum ada mapping resmi
 * kode Kemendagri <-> id_kecamatan di database/project.
 */
function desa_validate($config, $post, $excludeId = 0) {
    $idKecRaw = trim((string)($post['id_kecamatan'] ?? ''));
    if ($idKecRaw === '' || !ctype_digit($idKecRaw)) return 'kecamatan';
    $idKec = (int)$idKecRaw;
    $st = mysqli_prepare($config, "SELECT id_kecamatan FROM tbl_kecamatan WHERE id_kecamatan=? AND aktif='Y' LIMIT 1");
    if (!$st) return 'db';
    mysqli_stmt_bind_param($st, 'i', $idKec);
    mysqli_stmt_execute($st);
    $r = mysqli_stmt_get_result($st);
    $ok = $r && mysqli_num_rows($r) > 0;
    mysqli_stmt_close($st);
    if (!$ok) return 'kecamatan';

    if ($excludeId === 0) {
        $kode = trim((string)($post['kode_wilayah'] ?? ''));
        if ($kode === '') return 'kode_wajib';
        if (strlen($kode) > 20) return 'kode_format';
        if (!preg_match('/^\d{2}\.\d{2}\.\d{2}\.\d{4}$/', $kode)) return 'kode_format';
        $st = mysqli_prepare($config, "SELECT id_desa_kelurahan FROM tbl_desa_kelurahan WHERE kode_wilayah=? LIMIT 1");
        if (!$st) return 'db';
        mysqli_stmt_bind_param($st, 's', $kode);
        mysqli_stmt_execute($st);
        $r = mysqli_stmt_get_result($st);
        $dup = $r && mysqli_num_rows($r) > 0;
        mysqli_stmt_close($st);
        if ($dup) return 'kode_duplikat';
    }

    $nama = trim((string)($post['nama'] ?? ''));
    if ($nama === '' || mb_strlen($nama) > 100) return 'nama';

    if (!in_array($post['jenis'] ?? '', ['Desa', 'Kelurahan'], true)) return 'jenis';

    foreach (['laki_laki' => 'l', 'perempuan' => 'p'] as $field => $code) {
        $v = trim((string)($post[$field] ?? ''));
        if ($v === '' || !preg_match('/^\d+$/', $v)) return $code;
    }
    return '';
}

function desa_back($fkec, $fstatus, $msg, $err = '') {
    $url = 'kecamatan.php?fkec=' . (int)$fkec . '&fstatus=' . urlencode($fstatus) . '&msg=' . $msg;
    if ($err !== '') $url .= '&err=' . urlencode($err);
    header('Location: ' . $url);
    exit;
}

// ================= EXPORT / TEMPLATE EXCEL (GET, output biner) =================
// Pola mengikuti fasyankes (?excel=template/export): dieksekusi sebelum output
// HTML apa pun. Export = data aktif saja, order kode_wilayah; template = header saja.
if (isset($_GET['desa_excel']) && in_array($_GET['desa_excel'], ['export', 'template'], true)) {
    $desaExcelMode = $_GET['desa_excel'];
    $expKec = (isset($_GET['fkec']) && ctype_digit((string)$_GET['fkec'])) ? (int)$_GET['fkec'] : 0;
    $autoloadExp = __DIR__ . '/../../vendor/autoload.php';
    if (!file_exists($autoloadExp)) {
        http_response_code(500);
        exit('Library Excel belum tersedia di server.');
    }
    require_once $autoloadExp;
    $rowsExp = [];
    if ($expKec > 0) {
        $stExp = mysqli_prepare($config, "SELECT kode_wilayah, nama, jenis, laki_laki, perempuan, jumlah FROM tbl_desa_kelurahan WHERE aktif='Y' AND id_kecamatan=? ORDER BY kode_wilayah ASC");
        if ($stExp) {
            mysqli_stmt_bind_param($stExp, 'i', $expKec);
            mysqli_stmt_execute($stExp);
            $resExp = mysqli_stmt_get_result($stExp);
            if ($resExp) while ($rExp = mysqli_fetch_assoc($resExp)) $rowsExp[] = $rExp;
            mysqli_stmt_close($stExp);
        }
    } else {
        $qExp = mysqli_query($config, "SELECT kode_wilayah, nama, jenis, laki_laki, perempuan, jumlah FROM tbl_desa_kelurahan WHERE aktif='Y' ORDER BY kode_wilayah ASC");
        if ($qExp) while ($rExp = mysqli_fetch_assoc($qExp)) $rowsExp[] = $rExp;
    }
    $ssExp = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $shExp = $ssExp->getActiveSheet();
    $shExp->setTitle('Data Asli');
    $shExp->fromArray([['KODE', 'WILAYAH', 'JENIS', 'L', 'P', 'JML']], null, 'A1');
    if ($desaExcelMode === 'export') {
        $rExp = 2;
        foreach ($rowsExp as $rowExp) {
            $shExp->fromArray([[$rowExp['kode_wilayah'], $rowExp['nama'], $rowExp['jenis'], (int)$rowExp['laki_laki'], (int)$rowExp['perempuan'], (int)$rowExp['jumlah']]], null, 'A' . $rExp);
            $rExp++;
        }
    }
    $shExp->getStyle('A1:F1')->getFont()->setBold(true);
    $shExp->getStyle('A1:F1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('BDD7EE');
    foreach (['A' => 18, 'B' => 28, 'C' => 14, 'D' => 12, 'E' => 12, 'F' => 14] as $colExp => $wExp) $shExp->getColumnDimension($colExp)->setWidth($wExp);
    $shExp->freezePane('A2');
    $fnameExp = $desaExcelMode === 'export' ? 'Export_Desa_Kelurahan_' . date('Ymd_His') . '.xlsx' : 'Template_Desa_Kelurahan.xlsx';
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $fnameExp . '"');
    header('Cache-Control: max-age=0');
    (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($ssExp))->save('php://output');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $nama = mysqli_real_escape_string($config, trim($_POST['nama_kecamatan'] ?? ''));
        $kode = mysqli_real_escape_string($config, trim($_POST['kode_kecamatan'] ?? ''));
        $penduduk = (int)($_POST['jumlah_penduduk'] ?? 0);
        $desa = (int)($_POST['jumlah_desa'] ?? 0);
        $posyandu = (int)($_POST['jumlah_posyandu'] ?? 0);

        if (!empty($nama) && !empty($kode)) {
            $sql = "INSERT INTO tbl_kecamatan 
                    (nama_kecamatan, kode_kecamatan, jumlah_penduduk, jumlah_desa, jumlah_posyandu, aktif) 
                    VALUES ('$nama', '$kode', $penduduk, $desa, $posyandu, 'Y')";
            mysqli_query($config, $sql);
        }
        header('Location: kecamatan.php?msg=added');
        exit;
    }

    if ($action === 'edit') {
        $id = (int)$_POST['id'];
        
        // ===== PENGAMANAN: NAMA KECAMATAN DIKUNCI AGAR SESUAI PETA SVG =====
        // jumlah_penduduk/jumlah_desa SENGAJA tidak diupdate: source of truth
        // adalah tbl_desa_kelurahan (ditampilkan sebagai penduduk_resmi/desa_resmi).
        $kode = mysqli_real_escape_string($config, trim($_POST['kode_kecamatan'] ?? ''));
        $posyandu = (int)($_POST['jumlah_posyandu'] ?? 0);

        $sql = "UPDATE tbl_kecamatan SET
                kode_kecamatan='$kode',
                jumlah_posyandu=$posyandu
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

    // ================= DESA/KELURAHAN (tbl_desa_kelurahan) =================
    // Business key: kode_wilayah (immutable). JML = generated column (L+P),
    // tidak pernah di-INSERT/UPDATE manual. Agregat tbl_kecamatan TIDAK diubah.

    if ($action === 'desa_add') {
        $fkecBack = ctype_digit((string)($_POST['id_kecamatan'] ?? '')) ? (int)$_POST['id_kecamatan'] : 0;
        $err = desa_validate($config, $_POST, 0);
        if ($err !== '') desa_back($fkecBack, 'Y', 'desa_error', $err);
        $idKec = (int)$_POST['id_kecamatan'];
        $kode = trim($_POST['kode_wilayah']);
        $nama = trim($_POST['nama']);
        $jenis = $_POST['jenis'];
        $l = (int)$_POST['laki_laki'];
        $p = (int)$_POST['perempuan'];
        $st = mysqli_prepare($config, "INSERT INTO tbl_desa_kelurahan (id_kecamatan, kode_wilayah, nama, jenis, laki_laki, perempuan, aktif) VALUES (?, ?, ?, ?, ?, ?, 'Y')");
        if (!$st) desa_back($idKec, 'Y', 'desa_error', 'db');
        mysqli_stmt_bind_param($st, 'isssii', $idKec, $kode, $nama, $jenis, $l, $p);
        if (!mysqli_stmt_execute($st)) {
            $code = mysqli_stmt_errno($st) === 1062 ? 'kode_duplikat' : 'db';
            mysqli_stmt_close($st);
            desa_back($idKec, 'Y', 'desa_error', $code);
        }
        mysqli_stmt_close($st);
        desa_back($idKec, 'Y', 'desa_added');
    }

    if ($action === 'desa_edit') {
        $id = (int)($_POST['id'] ?? 0);
        $fkecBack = ctype_digit((string)($_POST['id_kecamatan'] ?? '')) ? (int)$_POST['id_kecamatan'] : 0;
        $fstatusBack = in_array($_POST['fstatus_back'] ?? '', ['Y', 'N', 'all'], true) ? $_POST['fstatus_back'] : 'Y';
        $err = desa_validate($config, $_POST, $id);
        if ($id <= 0) $err = 'notfound';
        if ($err === '') {
            $stCek = mysqli_prepare($config, "SELECT id_desa_kelurahan FROM tbl_desa_kelurahan WHERE id_desa_kelurahan=? LIMIT 1");
            if ($stCek) {
                mysqli_stmt_bind_param($stCek, 'i', $id);
                mysqli_stmt_execute($stCek);
                $rCek = mysqli_stmt_get_result($stCek);
                if (!$rCek || mysqli_num_rows($rCek) === 0) $err = 'notfound';
                mysqli_stmt_close($stCek);
            } else {
                $err = 'db';
            }
        }
        if ($err !== '') desa_back($fkecBack, $fstatusBack, 'desa_error', $err);
        // id_kecamatan + kode_wilayah SENGAJA tidak diupdate (relasi & business key dikunci)
        $nama = trim($_POST['nama']);
        $jenis = $_POST['jenis'];
        $l = (int)$_POST['laki_laki'];
        $p = (int)$_POST['perempuan'];
        $st = mysqli_prepare($config, "UPDATE tbl_desa_kelurahan SET nama=?, jenis=?, laki_laki=?, perempuan=? WHERE id_desa_kelurahan=?");
        if (!$st) desa_back($fkecBack, $fstatusBack, 'desa_error', 'db');
        mysqli_stmt_bind_param($st, 'ssiii', $nama, $jenis, $l, $p, $id);
        if (!mysqli_stmt_execute($st) || mysqli_stmt_affected_rows($st) < 0) {
            mysqli_stmt_close($st);
            desa_back($fkecBack, $fstatusBack, 'desa_error', 'db');
        }
        mysqli_stmt_close($st);
        desa_back($fkecBack, $fstatusBack, 'desa_updated');
    }

    if ($action === 'desa_delete') {
        $id = (int)($_POST['id'] ?? 0);
        $fkecBack = ctype_digit((string)($_POST['fkec_back'] ?? '')) ? (int)$_POST['fkec_back'] : 0;
        $fstatusBack = in_array($_POST['fstatus_back'] ?? '', ['Y', 'N', 'all'], true) ? $_POST['fstatus_back'] : 'Y';
        if ($id > 0) {
            $st = mysqli_prepare($config, "UPDATE tbl_desa_kelurahan SET aktif='N' WHERE id_desa_kelurahan=?");
            if ($st) {
                mysqli_stmt_bind_param($st, 'i', $id);
                mysqli_stmt_execute($st);
                mysqli_stmt_close($st);
            }
        }
        desa_back($fkecBack, $fstatusBack, 'desa_deleted');
    }

    if ($action === 'desa_restore') {
        $id = (int)($_POST['id'] ?? 0);
        $fkecBack = ctype_digit((string)($_POST['fkec_back'] ?? '')) ? (int)$_POST['fkec_back'] : 0;
        $fstatusBack = in_array($_POST['fstatus_back'] ?? '', ['Y', 'N', 'all'], true) ? $_POST['fstatus_back'] : 'Y';
        if ($id > 0) {
            $st = mysqli_prepare($config, "UPDATE tbl_desa_kelurahan SET aktif='Y' WHERE id_desa_kelurahan=?");
            if ($st) {
                mysqli_stmt_bind_param($st, 'i', $id);
                mysqli_stmt_execute($st);
                mysqli_stmt_close($st);
            }
        }
        desa_back($fkecBack, $fstatusBack, 'desa_restored');
    }

    // ================= IMPORT EXCEL RESMI (sheet "Data Asli") =================
    // Sumber: Dukcapil Semester I 2026 (KODE|WILAYAH|L|P|JML). Hanya baris desa
    // (33.11.XX.YYYY) yang diimport; baris kabupaten/kecamatan dilewati.
    // Mapping XX -> id_kecamatan via NAMA (UPPER) dari tbl_kecamatan aktif.
    // Upsert by kode_wilayah dalam SATU transaction (gagal -> rollback).

    if ($action === 'desa_import') {
        $impErr = function ($code, $errors = []) {
            $_SESSION['desa_import_errors'] = array_slice($errors, 0, 20);
            $_SESSION['desa_import_errcount'] = count($errors);
            header('Location: kecamatan.php?msg=desa_error&err=' . $code);
            exit;
        };

        $f = $_FILES['file_excel'] ?? null;
        if (!$f || ($f['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) $impErr('import_file');
        if (($f['size'] ?? 0) > 10 * 1024 * 1024) $impErr('import_size');
        $ext = strtolower(pathinfo($f['name'] ?? '', PATHINFO_EXTENSION));
        if (!in_array($ext, ['xlsx', 'xls', 'csv'], true)) $impErr('import_ext');
        $autoload = __DIR__ . '/../../vendor/autoload.php';
        if (!file_exists($autoload)) $impErr('import_novendor');
        require_once $autoload;

        try {
            if ($ext === 'csv') $reader = new \PhpOffice\PhpSpreadsheet\Reader\Csv();
            elseif ($ext === 'xls') $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xls();
            else $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
            $reader->setReadDataOnly(true);
            $wb = $reader->load($f['tmp_name']);
        } catch (Throwable $e) {
            $impErr('import_read');
        }
        $sheet = $wb->getSheetByName('Data Asli');
        if (!$sheet) $impErr('import_sheet');
        $rows = $sheet->toArray(null, true, true, false);
        // Header resmi: KODE|WILAYAH|L|P|JML. Varian template: + kolom JENIS
        // (KODE|WILAYAH|JENIS|L|P|JML) agar file template round-trip ke import.
        $head = array_map(function ($v) { return mb_strtolower(trim((string)$v), 'UTF-8'); }, $rows[0] ?? []);
        if (array_slice($head, 0, 5) === ['kode', 'wilayah', 'l', 'p', 'jml']) {
            $cNama = 1;
            $cJenis = -1;
            $cL = 2;
            $cP = 3;
            $cJ = 4;
        } elseif (array_slice($head, 0, 6) === ['kode', 'wilayah', 'jenis', 'l', 'p', 'jml']) {
            $cNama = 1;
            $cJenis = 2;
            $cL = 3;
            $cP = 4;
            $cJ = 5;
        } else {
            $impErr('import_header');
        }

        $kecRows = [];
        $desaRows = [];
        $seenKode = [];
        $errors = [];
        foreach (array_slice($rows, 1) as $i => $row) {
            $no = $i + 2;
            $kode = trim((string)($row[0] ?? ''));
            if ($kode === '') continue;
            $dots = substr_count($kode, '.');
            if ($dots === 2) {
                $kecRows[$kode] = trim((string)($row[1] ?? ''));
                continue;
            }
            if ($dots !== 3) continue; // baris kabupaten / format tak dikenal dilewati
            if (!preg_match('/^\d{2}\.\d{2}\.\d{2}\.\d{4}$/', $kode)) {
                $errors[] = "Baris $no: format kode '$kode' tidak valid.";
                continue;
            }
            if (isset($seenKode[$kode])) {
                $errors[] = "Baris $no: kode duplikat '$kode' dalam file.";
                continue;
            }
            $seenKode[$kode] = true;
            $nama = trim((string)($row[$cNama] ?? ''));
            if ($nama === '' || mb_strlen($nama) > 100) {
                $errors[] = "Baris $no ($kode): nama wajib diisi (maks 100).";
                continue;
            }
            $seg = explode('.', $kode);
            if ($seg[3][0] === '1') $jenisHitung = 'Kelurahan';
            elseif ($seg[3][0] === '2') $jenisHitung = 'Desa';
            else {
                $errors[] = "Baris $no ($kode): segmen desa tidak diawali 1/2.";
                continue;
            }
            if ($cJenis >= 0) {
                $jenisFile = trim((string)($row[$cJenis] ?? ''));
                if ($jenisFile !== $jenisHitung) {
                    $errors[] = "Baris $no ($kode): JENIS file ('$jenisFile') tidak sesuai kode ('$jenisHitung').";
                    continue;
                }
            }
            $jenis = $jenisHitung;
            $okNum = true;
            foreach (['L' => $cL, 'P' => $cP, 'JML' => $cJ] as $lbl => $c) {
                $v = $row[$c] ?? null;
                if (!is_numeric($v) || floor((float)$v) != (float)$v || (float)$v < 0) {
                    $errors[] = "Baris $no ($kode): kolom $lbl tidak valid.";
                    $okNum = false;
                    break;
                }
            }
            if (!$okNum) continue;
            $l = (int)$row[$cL];
            $p = (int)$row[$cP];
            $j = (int)$row[$cJ];
            if ($l + $p !== $j) {
                $errors[] = "Baris $no ($kode): JML Excel ($j) != L+P (" . ($l + $p) . ").";
                continue;
            }
            $desaRows[] = ['kode' => $kode, 'nama' => $nama, 'jenis' => $jenis, 'l' => $l, 'p' => $p, 'xx' => $seg[0] . '.' . $seg[1] . '.' . $seg[2]];
        }

        // Mapping XX -> id_kecamatan via NAMA (bukan tebakan posisi)
        $nameMap = [];
        $qMap = mysqli_query($config, "SELECT id_kecamatan, nama_kecamatan FROM tbl_kecamatan WHERE aktif='Y'");
        if ($qMap) while ($r = mysqli_fetch_assoc($qMap)) $nameMap[mb_strtoupper(trim($r['nama_kecamatan']), 'UTF-8')] = (int)$r['id_kecamatan'];
        $xxMap = [];
        foreach ($kecRows as $xxKode => $xxNama) {
            $up = mb_strtoupper($xxNama, 'UTF-8');
            if (!isset($nameMap[$up])) {
                $errors[] = "Kecamatan Excel '$xxKode=$xxNama' tidak cocok dengan tbl_kecamatan.";
            } else {
                $xxMap[$xxKode] = $nameMap[$up];
            }
        }
        if (!empty($errors)) $impErr('import_nomap', $errors);
        foreach ($desaRows as $d) {
            if (!isset($xxMap[$d['xx']])) $errors[] = "Baris {$d['kode']}: induk kecamatan '{$d['xx']}' tidak terpetakan.";
        }
        if (count($desaRows) !== 167) $errors[] = "Jumlah baris desa valid " . count($desaRows) . ", harus 167.";
        if (!empty($errors)) $impErr('import_count', $errors);

        $stFind = mysqli_prepare($config, "SELECT id_desa_kelurahan, id_kecamatan, nama, jenis, laki_laki, perempuan, aktif FROM tbl_desa_kelurahan WHERE kode_wilayah=? LIMIT 1");
        $stIns = mysqli_prepare($config, "INSERT INTO tbl_desa_kelurahan (id_kecamatan, kode_wilayah, nama, jenis, laki_laki, perempuan, aktif) VALUES (?, ?, ?, ?, ?, ?, 'Y')");
        $stUpd = mysqli_prepare($config, "UPDATE tbl_desa_kelurahan SET id_kecamatan=?, nama=?, jenis=?, laki_laki=?, perempuan=?, aktif='Y' WHERE id_desa_kelurahan=?");
        if (!$stFind || !$stIns || !$stUpd) $impErr('import_error', ['Gagal menyiapkan query database.']);

        $ins = 0;
        $upd = 0;
        $same = 0;
        mysqli_begin_transaction($config);
        try {
            foreach ($desaRows as $d) {
                $idKec = $xxMap[$d['xx']];
                mysqli_stmt_bind_param($stFind, 's', $d['kode']);
                if (!mysqli_stmt_execute($stFind)) throw new Exception("Gagal mencocokkan {$d['kode']}.");
                $res = mysqli_stmt_get_result($stFind);
                $ex = $res ? mysqli_fetch_assoc($res) : null;
                if (!$ex) {
                    mysqli_stmt_bind_param($stIns, 'isssii', $idKec, $d['kode'], $d['nama'], $d['jenis'], $d['l'], $d['p']);
                    if (!mysqli_stmt_execute($stIns)) {
                        if (mysqli_stmt_errno($stIns) === 1062) throw new Exception("Kode duplikat {$d['kode']} (tertabrak UNIQUE).");
                        throw new Exception("Gagal insert {$d['kode']}.");
                    }
                    $ins++;
                } elseif ((int)$ex['id_kecamatan'] === $idKec && $ex['nama'] === $d['nama'] && $ex['jenis'] === $d['jenis'] && (int)$ex['laki_laki'] === $d['l'] && (int)$ex['perempuan'] === $d['p'] && $ex['aktif'] === 'Y') {
                    $same++;
                } else {
                    $eid = (int)$ex['id_desa_kelurahan'];
                    mysqli_stmt_bind_param($stUpd, 'issiii', $idKec, $d['nama'], $d['jenis'], $d['l'], $d['p'], $eid);
                    if (!mysqli_stmt_execute($stUpd)) throw new Exception("Gagal update {$d['kode']}.");
                    $upd++;
                }
            }
            mysqli_commit($config);
        } catch (Throwable $e) {
            mysqli_rollback($config);
            foreach ([$stFind, $stIns, $stUpd] as $st) if ($st) mysqli_stmt_close($st);
            $impErr('import_error', [$e->getMessage() . ' (transaksi di-rollback, tidak ada data berubah).']);
        }
        foreach ([$stFind, $stIns, $stUpd] as $st) if ($st) mysqli_stmt_close($st);
        $qAktif = mysqli_query($config, "SELECT COUNT(*) c FROM tbl_desa_kelurahan WHERE aktif='Y'");
        $aktif = $qAktif ? (int)mysqli_fetch_assoc($qAktif)['c'] : 0;
        $_SESSION['desa_import_errors'] = [];
        $_SESSION['desa_import_errcount'] = 0;
        header('Location: kecamatan.php?msg=desa_imported&ins=' . $ins . '&upd=' . $upd . '&same=' . $same . '&kec=' . count($xxMap) . '&total=' . count($desaRows) . '&aktif=' . $aktif);
        exit;
    }
}

// Ambil data kecamatan dan hitung jumlah faskes riil per kecamatan.
// Jumlah Penduduk + Desa/Kelurahan dibaca dari tbl_desa_kelurahan
// (source of truth); kolom agregat lama tbl_kecamatan tidak dipakai tampil.
$sql = "SELECT k.*,
        (SELECT COUNT(*) FROM tbl_faskes f
         WHERE (f.id_kecamatan = k.id_kecamatan OR LOWER(f.kecamatan) = LOWER(k.nama_kecamatan))
         AND f.aktif = 'Y') AS total_faskes,
        (SELECT COUNT(*) FROM tbl_desa_kelurahan d
         WHERE d.id_kecamatan = k.id_kecamatan AND d.aktif = 'Y') AS desa_resmi,
        (SELECT COALESCE(SUM(d.jumlah), 0) FROM tbl_desa_kelurahan d
         WHERE d.id_kecamatan = k.id_kecamatan AND d.aktif = 'Y') AS penduduk_resmi
        FROM tbl_kecamatan k
        WHERE k.aktif = 'Y'
        ORDER BY k.nama_kecamatan";
$data = mysqli_query($config, $sql);
$username = $_SESSION['admin_username'] ?? 'Admin';

// ================= FILTER + LIST DESA/KELURAHAN =================
$fkec = (isset($_GET['fkec']) && ctype_digit((string)$_GET['fkec'])) ? (int)$_GET['fkec'] : 0;
$fstatus = $_GET['fstatus'] ?? 'Y';
if (!in_array($fstatus, ['Y', 'N', 'all'], true)) $fstatus = 'Y';

$kecList = [];
$qKecList = mysqli_query($config, "SELECT id_kecamatan, nama_kecamatan FROM tbl_kecamatan WHERE aktif='Y' ORDER BY nama_kecamatan");
if ($qKecList) while ($r = mysqli_fetch_assoc($qKecList)) $kecList[] = $r;
$kecNama = '';
foreach ($kecList as $k) {
    if ((int)$k['id_kecamatan'] === $fkec) { $kecNama = $k['nama_kecamatan']; break; }
}
if ($fkec > 0 && $kecNama === '') $fkec = 0; // kecamatan tidak dikenal/di-nonaktif -> reset filter

$desaRows = [];
$subDesa = ['jml' => 0, 'l' => 0, 'p' => 0, 'j' => 0];
if ($fkec > 0) {
    if ($fstatus === 'all') {
        $st = mysqli_prepare($config, "SELECT id_desa_kelurahan, kode_wilayah, nama, jenis, laki_laki, perempuan, jumlah, aktif FROM tbl_desa_kelurahan WHERE id_kecamatan=? ORDER BY kode_wilayah ASC, nama ASC");
        if ($st) {
            mysqli_stmt_bind_param($st, 'i', $fkec);
            mysqli_stmt_execute($st);
            $res = mysqli_stmt_get_result($st);
            if ($res) while ($r = mysqli_fetch_assoc($res)) $desaRows[] = $r;
            mysqli_stmt_close($st);
        }
    } else {
        $st = mysqli_prepare($config, "SELECT id_desa_kelurahan, kode_wilayah, nama, jenis, laki_laki, perempuan, jumlah, aktif FROM tbl_desa_kelurahan WHERE id_kecamatan=? AND aktif=? ORDER BY kode_wilayah ASC, nama ASC");
        if ($st) {
            mysqli_stmt_bind_param($st, 'is', $fkec, $fstatus);
            mysqli_stmt_execute($st);
            $res = mysqli_stmt_get_result($st);
            if ($res) while ($r = mysqli_fetch_assoc($res)) $desaRows[] = $r;
            mysqli_stmt_close($st);
        }
    }
    // Subtotal selalu dari data AKTIF (nonaktif tidak ikut hitung)
    $st = mysqli_prepare($config, "SELECT COUNT(*) c, COALESCE(SUM(laki_laki),0) l, COALESCE(SUM(perempuan),0) p, COALESCE(SUM(jumlah),0) j FROM tbl_desa_kelurahan WHERE id_kecamatan=? AND aktif='Y'");
    if ($st) {
        mysqli_stmt_bind_param($st, 'i', $fkec);
        mysqli_stmt_execute($st);
        $res = mysqli_stmt_get_result($st);
        if ($res && ($s = mysqli_fetch_assoc($res))) {
            $subDesa = ['jml' => (int)$s['c'], 'l' => (int)$s['l'], 'p' => (int)$s['p'], 'j' => (int)$s['j']];
        }
        mysqli_stmt_close($st);
    }
}

// Pesan khusus Desa/Kelurahan (pola ?msg= halaman existing)
$desaMsgOk = [
    'desa_added' => 'Data desa/kelurahan berhasil ditambahkan.',
    'desa_updated' => 'Data desa/kelurahan berhasil diperbarui.',
    'desa_deleted' => 'Data desa/kelurahan dinonaktifkan (soft delete).',
    'desa_restored' => 'Data desa/kelurahan diaktifkan kembali.',
];
$desaMsgErr = [
    'kecamatan' => 'Kecamatan wajib dipilih.',
    'kode_wajib' => 'Kode wilayah wajib diisi.',
    'kode_format' => 'Format kode wilayah tidak valid (contoh: 33.11.01.2001).',
    'kode_duplikat' => 'Kode wilayah sudah digunakan.',
    'nama' => 'Nama desa/kelurahan wajib diisi (maks 100 karakter).',
    'jenis' => 'Jenis harus Desa atau Kelurahan.',
    'l' => 'Laki-laki harus berupa angka >= 0.',
    'p' => 'Perempuan harus berupa angka >= 0.',
    'notfound' => 'Data tidak ditemukan.',
    'db' => 'Operasi database gagal. Silakan coba lagi.',
    'import_file' => 'File tidak ter-upload. Pilih file Excel yang valid.',
    'import_size' => 'Ukuran file melebihi 10 MB.',
    'import_ext' => 'Format file ditolak. Gunakan .xlsx, .xls, atau .csv.',
    'import_novendor' => 'Library Excel belum tersedia di server.',
    'import_read' => 'Gagal membaca file Excel.',
    'import_sheet' => 'Sheet "Data Asli" tidak ditemukan dalam file.',
    'import_header' => 'Header tidak sesuai (harus: KODE | WILAYAH | L | P | JML).',
    'import_nomap' => 'Mapping kecamatan gagal. Detail di bawah.',
    'import_count' => 'Jumlah data tidak memenuhi syarat import. Detail di bawah.',
    'import_error' => 'Import gagal. Detail di bawah.',
];
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

        .form-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(200px,1fr)); gap:16px; }
        .form-grid input { padding:10px 14px; border-radius:10px; border:1px solid rgba(255,255,255,0.1); background:rgba(255,255,255,0.06); color:#fff; font-size:14px; font-family:'Poppins',sans-serif; width:100%; margin-top:4px; }
        .form-grid input:focus { outline:none; border-color:#00d4ff; }
        
        .form-group { display:flex; flex-direction:column; }
        .form-group label { color:#87e3ff; font-size:12px; font-weight:600; margin-bottom:4px; }

        .btn-primary { padding:10px 24px; border-radius:10px; border:none; background:linear-gradient(135deg,#00d4ff,#0088cc); color:#fff; font-weight:600; cursor:pointer; transition:0.3s; }
        .btn-primary:hover { transform:translateY(-2px); box-shadow:0 8px 25px rgba(0,212,255,0.25); }

        table { width:100%; border-collapse:collapse; }
        table th { text-align:left; padding:12px 10px; color:#87e3ff; font-weight:600; font-size:13px; border-bottom:2px solid rgba(255,255,255,0.08); }
        table td { padding:12px 10px; border-bottom:1px solid rgba(255,255,255,0.05); font-size:14px; }
        table td:last-child { text-align:right; }
        .badge-faskes { display:inline-block; padding:3px 10px; border-radius:12px; font-size:12px; font-weight:600; background:rgba(0,212,255,0.15); color:#72e8ff; border:1px solid rgba(0,212,255,0.3); }
        .btn-icon { padding:6px 14px; border-radius:8px; border:none; background:rgba(0,212,255,0.15); color:#00d4ff; cursor:pointer; font-size:13px; font-weight:600; transition:0.3s; text-decoration:none; display:inline-block; }
        .btn-icon:hover { background:rgba(0,212,255,0.3); }
        .btn-danger { background:rgba(255,82,82,0.15); color:#ff6b6b; }
        .btn-danger:hover { background:rgba(255,82,82,0.3); }

        .alert { padding:14px 20px; border-radius:12px; margin-bottom:20px; display:flex; align-items:center; gap:12px; font-size:14px; font-weight:500; }
        .alert-success { background:rgba(0,212,255,0.12); border:1px solid rgba(0,212,255,0.2); color:#72e8ff; }
        .alert-error { background:rgba(255,82,82,0.12); border:1px solid rgba(255,82,82,0.25); color:#ff8a80; }
        .form-grid select { padding:10px 14px; border-radius:10px; border:1px solid rgba(255,255,255,0.1); background:rgba(255,255,255,0.06); color:#fff; font-size:14px; font-family:'Poppins',sans-serif; width:100%; margin-top:4px; }
        .form-grid select option { color:#111; }
        .filter-bar { display:flex; gap:10px; flex-wrap:wrap; align-items:end; margin-bottom:16px; }
        .filter-bar .form-group { min-width:200px; }
        .filter-bar select { padding:9px 13px; border-radius:10px; border:1px solid rgba(255,255,255,0.1); background:rgba(255,255,255,0.06); color:#fff; font-size:13px; font-family:'Poppins',sans-serif; }
        .filter-bar select option { color:#111; }
        .subtotal { display:flex; gap:22px; flex-wrap:wrap; margin-top:16px; padding:14px 18px; border-radius:12px; background:rgba(0,212,255,0.07); border:1px solid rgba(0,212,255,0.18); font-size:13px; }
        .subtotal b { color:#72e8ff; }
        .badge-status { display:inline-block; padding:3px 10px; border-radius:12px; font-size:11px; font-weight:600; }
        .badge-aktif { background:rgba(76,175,80,0.15); color:#81c784; border:1px solid rgba(76,175,80,0.3); }
        .badge-nonaktif { background:rgba(255,82,82,0.15); color:#ff8a80; border:1px solid rgba(255,82,82,0.3); }
        .btn-success { background:rgba(76,175,80,0.15); color:#81c784; }
        .btn-success:hover { background:rgba(76,175,80,0.3); }

        #editModal { display:none; position:fixed; inset:0; background:rgba(0,0,0,0.6); backdrop-filter:blur(4px); z-index:999; justify-content:center; align-items:center; }
        .modal-box { background:#0b223c; padding:35px; border-radius:24px; max-width:550px; width:95%; border:1px solid rgba(255,255,255,0.1); box-shadow:0 30px 60px rgba(0,0,0,0.5); }
        .modal-box h2 { color:#84e7ff; margin-bottom:20px; }
        .modal-box .form-grid { grid-template-columns:1fr 1fr; }
        .modal-actions { display:flex; gap:12px; margin-top:24px; }
        .modal-actions .btn-secondary { padding:10px 20px; border-radius:10px; border:1px solid rgba(255,255,255,0.1); background:transparent; color:rgba(255,255,255,0.6); cursor:pointer; transition:0.3s; }
        .modal-actions .btn-secondary:hover { background:rgba(255,255,255,0.05); color:#fff; }

        .readonly-input {
            background: rgba(255,255,255,0.03) !important;
            cursor: not-allowed !important;
            opacity: 0.7 !important;
        }

        .info-hint { color:rgba(255,255,255,0.4); font-size:11px; margin-top:4px; }

        @media (max-width:768px) { .sidebar{display:none;} .main-content{padding:20px;} .form-grid{grid-template-columns:1fr;} .modal-box .form-grid{grid-template-columns:1fr;} }
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
        <li><a href="sdmk.php"><i class="fas fa-hospital-user"></i> SDMK</a></li>
        <li><a href="kecamatan.php" class="active"><i class="fas fa-map"></i> Kecamatan</a></li>
        <li><a href="penyakit.php"><i class="fas fa-disease"></i> Penyakit</a></li>
        <li><a href="spm.php"><i class="fas fa-chart-pie"></i> SPM Target</a></li>
        <li><a href="spm_realisasi.php"><i class="fas fa-chart-line"></i> SPM Realisasi</a></li>
        <li><a href="portal_info.php"><i class="fas fa-circle-info"></i> Informasi Portal</a></li>
        <li class="logout"><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
    </ul>
</div>

<div class="main-content">
    <div class="page-header">
        <div>
            <h1>Kelola Kecamatan</h1>
            <p>Data dasar kecamatan (jumlah penduduk dan desa/kelurahan). Jumlah fasyankes dihitung otomatis dari database Fasyankes.</p>
        </div>
        <a href="../index.php" class="back-link"><i class="fas fa-arrow-left"></i> Kembali</a>
    </div>

    <?php
    $msgCode = $_GET['msg'] ?? '';
    if (isset($desaMsgOk[$msgCode])):
    ?>
        <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($desaMsgOk[$msgCode]) ?></div>
    <?php elseif ($msgCode === 'desa_imported'): ?>
        <div class="alert alert-success"><i class="fas fa-file-import"></i>
            <span><b>Import Desa/Kelurahan berhasil</b> — Total Excel: <?= (int)($_GET['total'] ?? 0) ?> |
            Kecamatan: <?= (int)($_GET['kec'] ?? 0) ?> |
            Baru: <?= (int)($_GET['ins'] ?? 0) ?> |
            Update: <?= (int)($_GET['upd'] ?? 0) ?> |
            Sama: <?= (int)($_GET['same'] ?? 0) ?> |
            Aktif: <?= (int)($_GET['aktif'] ?? 0) ?></span>
        </div>
    <?php elseif ($msgCode === 'desa_error'): ?>
        <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($desaMsgErr[$_GET['err'] ?? ''] ?? $desaMsgErr['db']) ?></div>
        <?php
        $impErrors = $_SESSION['desa_import_errors'] ?? [];
        $impErrCount = (int)($_SESSION['desa_import_errcount'] ?? 0);
        unset($_SESSION['desa_import_errors'], $_SESSION['desa_import_errcount']);
        if (!empty($impErrors)):
        ?>
        <div class="alert alert-error" style="display:block;">
            <b>Detail (<?= $impErrCount ?>):</b>
            <ul style="margin:6px 0 0 18px;">
                <?php foreach ($impErrors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
                <?php if ($impErrCount > count($impErrors)): ?><li>... dan <?= $impErrCount - count($impErrors) ?> lainnya.</li><?php endif; ?>
            </ul>
        </div>
        <?php endif; ?>
    <?php elseif ($msgCode !== ''): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle"></i> Data berhasil disimpan!</div>
    <?php endif; ?>

    <div class="card">
        <h3><i class="fas fa-list" style="color:#00d4ff;margin-right:10px;"></i>Daftar Kecamatan</h3>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Nama Kecamatan</th>
                    <th>Kode</th>
                    <th>Jumlah Penduduk</th>
                    <th>Desa / Kelurahan</th>
                    <th>Posyandu</th>
                    <th>Total Fasyankes (Riil)</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php $i=1; while ($row = mysqli_fetch_assoc($data)): ?>
                <tr>
                    <td><?= $i++ ?></td>
                    <td><strong><?= htmlspecialchars($row['nama_kecamatan']) ?></strong></td>
                    <td><?= htmlspecialchars($row['kode_kecamatan']) ?></td>
                    <td><?= number_format((float)($row['penduduk_resmi'] ?? 0)) ?> Jiwa</td>
                    <td><?= number_format((int)($row['desa_resmi'] ?? 0)) ?></td>
                    <td><?= number_format((int)($row['jumlah_posyandu'] ?? 0)) ?> Posyandu</td>
                    <td>
                        <span class="badge-faskes"><i class="fas fa-hospital-user"></i> <?= (int)$row['total_faskes'] ?> Faskes</span>
                    </td>
                    <td>
                        <button class="btn-icon edit-btn" 
                            data-id="<?= $row['id_kecamatan'] ?>"
                            data-nama="<?= htmlspecialchars($row['nama_kecamatan']) ?>"
                            data-kode="<?= htmlspecialchars($row['kode_kecamatan']) ?>"
                            data-penduduk="<?= (int)($row['penduduk_resmi'] ?? 0) ?>"
                            data-desa="<?= (int)($row['desa_resmi'] ?? 0) ?>"
                            data-posyandu="<?= (int)$row['jumlah_posyandu'] ?>">
                            <i class="fas fa-pen"></i> Edit
                        </button>
                        <form method="POST" style="display:inline-block;" onsubmit="return confirm('Yakin hapus data kecamatan ini?')">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $row['id_kecamatan'] ?>">
                            <button type="submit" class="btn-icon btn-danger"><i class="fas fa-trash"></i></button>
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
                <?php if (mysqli_num_rows($data) == 0): ?>
                <tr><td colspan="7" style="text-align:center;color:rgba(255,255,255,0.3);padding:20px;">Belum ada data kecamatan</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- ===== DESA / KELURAHAN + PENDUDUK (tbl_desa_kelurahan) ===== -->
    <div class="card">
        <h3><i class="fas fa-house-user" style="color:#00d4ff;margin-right:10px;"></i>Desa / Kelurahan + Penduduk</h3>
        <form method="GET" class="filter-bar">
            <div class="form-group">
                <label style="color:#87e3ff;font-size:12px;font-weight:600;">Kecamatan</label>
                <select name="fkec" onchange="this.form.submit()">
                    <option value="0">— Pilih kecamatan —</option>
                    <?php foreach ($kecList as $k): ?>
                    <option value="<?= (int)$k['id_kecamatan'] ?>" <?= $fkec === (int)$k['id_kecamatan'] ? 'selected' : '' ?>><?= htmlspecialchars($k['nama_kecamatan']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label style="color:#87e3ff;font-size:12px;font-weight:600;">Status</label>
                <select name="fstatus" onchange="this.form.submit()">
                    <option value="Y" <?= $fstatus === 'Y' ? 'selected' : '' ?>>Aktif</option>
                    <option value="N" <?= $fstatus === 'N' ? 'selected' : '' ?>>Nonaktif</option>
                    <option value="all" <?= $fstatus === 'all' ? 'selected' : '' ?>>Semua</option>
                </select>
            </div>
        </form>
        <div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:6px;">
            <a class="btn-icon" style="text-decoration:none;" href="kecamatan.php?desa_excel=export<?= $fkec > 0 ? '&fkec=' . $fkec : '' ?>"><i class="fas fa-file-export"></i> Export Excel<?= $fkec > 0 ? ' (' . htmlspecialchars($kecNama) . ')' : ' (Semua)' ?></a>
            <a class="btn-icon" style="text-decoration:none;" href="kecamatan.php?desa_excel=template"><i class="fas fa-file-download"></i> Template Excel</a>
        </div>

        <?php if ($fkec === 0): ?>
            <p style="color:rgba(255,255,255,0.4);font-size:13px;padding:12px 0;">Silakan pilih kecamatan untuk melihat dan mengelola Desa/Kelurahan.</p>
        <?php else: ?>
        <div style="overflow-x:auto;">
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Kode Wilayah</th>
                    <th>Desa / Kelurahan</th>
                    <th>Jenis</th>
                    <th style="text-align:right;">L</th>
                    <th style="text-align:right;">P</th>
                    <th style="text-align:right;">JML</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php $no = 1; foreach ($desaRows as $d): ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td><?= htmlspecialchars($d['kode_wilayah']) ?></td>
                    <td><strong><?= htmlspecialchars($d['nama']) ?></strong></td>
                    <td><?= htmlspecialchars($d['jenis']) ?></td>
                    <td style="text-align:right;"><?= number_format((int)$d['laki_laki']) ?></td>
                    <td style="text-align:right;"><?= number_format((int)$d['perempuan']) ?></td>
                    <td style="text-align:right;"><b><?= number_format((int)$d['jumlah']) ?></b></td>
                    <td>
                        <?php if ($d['aktif'] === 'Y'): ?>
                            <span class="badge-status badge-aktif">Aktif</span>
                        <?php else: ?>
                            <span class="badge-status badge-nonaktif">Nonaktif</span>
                        <?php endif; ?>
                    </td>
                    <td style="white-space:nowrap;">
                        <button class="btn-icon desa-edit-btn"
                            data-id="<?= (int)$d['id_desa_kelurahan'] ?>"
                            data-kode="<?= htmlspecialchars($d['kode_wilayah']) ?>"
                            data-nama="<?= htmlspecialchars($d['nama']) ?>"
                            data-jenis="<?= htmlspecialchars($d['jenis']) ?>"
                            data-l="<?= (int)$d['laki_laki'] ?>"
                            data-p="<?= (int)$d['perempuan'] ?>">
                            <i class="fas fa-pen"></i> Edit
                        </button>
                        <?php if ($d['aktif'] === 'Y'): ?>
                        <form method="POST" style="display:inline-block;" onsubmit="return confirm('Nonaktifkan data ini? (soft delete)')">
                            <input type="hidden" name="action" value="desa_delete">
                            <input type="hidden" name="id" value="<?= (int)$d['id_desa_kelurahan'] ?>">
                            <input type="hidden" name="fkec_back" value="<?= $fkec ?>">
                            <input type="hidden" name="fstatus_back" value="<?= htmlspecialchars($fstatus) ?>">
                            <button type="submit" class="btn-icon btn-danger"><i class="fas fa-trash"></i></button>
                        </form>
                        <?php else: ?>
                        <form method="POST" style="display:inline-block;">
                            <input type="hidden" name="action" value="desa_restore">
                            <input type="hidden" name="id" value="<?= (int)$d['id_desa_kelurahan'] ?>">
                            <input type="hidden" name="fkec_back" value="<?= $fkec ?>">
                            <input type="hidden" name="fstatus_back" value="<?= htmlspecialchars($fstatus) ?>">
                            <button type="submit" class="btn-icon btn-success"><i class="fas fa-undo"></i> Aktifkan</button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($desaRows)): ?>
                <tr><td colspan="9" style="text-align:center;color:rgba(255,255,255,0.3);padding:20px;">Belum ada data desa/kelurahan pada filter ini.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
        </div>
        <div class="subtotal">
            <span>TOTAL <?= htmlspecialchars(mb_strtoupper($kecNama)) ?> (data aktif)</span>
            <span>Desa/Kelurahan: <b><?= number_format($subDesa['jml']) ?></b></span>
            <span>Laki-laki: <b><?= number_format($subDesa['l']) ?></b></span>
            <span>Perempuan: <b><?= number_format($subDesa['p']) ?></b></span>
            <span>Penduduk: <b><?= number_format($subDesa['j']) ?></b></span>
        </div>
        <?php endif; ?>
    </div>

    <!-- ===== TAMBAH DESA / KELURAHAN ===== -->
    <div class="card">
        <h3><i class="fas fa-plus-circle" style="color:#00d4ff;margin-right:10px;"></i>Tambah Desa / Kelurahan</h3>
        <form method="POST">
            <input type="hidden" name="action" value="desa_add">
            <div class="form-grid">
                <div class="form-group">
                    <label>Kecamatan</label>
                    <select name="id_kecamatan" required>
                        <option value="">— Pilih —</option>
                        <?php foreach ($kecList as $k): ?>
                        <option value="<?= (int)$k['id_kecamatan'] ?>" <?= $fkec === (int)$k['id_kecamatan'] ? 'selected' : '' ?>><?= htmlspecialchars($k['nama_kecamatan']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Kode Wilayah (cth: 33.11.01.2001)</label>
                    <input type="text" name="kode_wilayah" required maxlength="20" placeholder="33.11.XX.YYYY">
                    <span class="info-hint">Kode Kemendagri apa adanya. Tidak bisa diubah setelah disimpan.</span>
                </div>
                <div class="form-group">
                    <label>Nama Desa / Kelurahan</label>
                    <input type="text" name="nama" required maxlength="100">
                </div>
                <div class="form-group">
                    <label>Jenis</label>
                    <select name="jenis" required>
                        <option value="Desa">Desa</option>
                        <option value="Kelurahan">Kelurahan</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Laki-laki</label>
                    <input type="number" name="laki_laki" min="0" step="1" value="0" required>
                </div>
                <div class="form-group">
                    <label>Perempuan</label>
                    <input type="number" name="perempuan" min="0" step="1" value="0" required>
                    <span class="info-hint">JML dihitung otomatis (L + P).</span>
                </div>
            </div>
            <div class="modal-actions" style="margin-top:16px;">
                <button type="submit" class="btn-primary"><i class="fas fa-save"></i> Simpan Desa / Kelurahan</button>
            </div>
        </form>
    </div>

    <!-- ===== IMPORT EXCEL RESMI ===== -->
    <div class="card">
        <h3><i class="fas fa-file-import" style="color:#00d4ff;margin-right:10px;"></i>Import Excel Resmi (Dukcapil)</h3>
        <form method="POST" enctype="multipart/form-data" style="display:flex;gap:10px;flex-wrap:wrap;align-items:end;">
            <input type="hidden" name="action" value="desa_import">
            <div class="form-group" style="min-width:260px;">
                <label style="color:#87e3ff;font-size:12px;font-weight:600;">File Excel (.xlsx/.xls/.csv, maks 10 MB)</label>
                <input type="file" name="file_excel" accept=".xlsx,.xls,.csv" required style="padding:10px 14px;border-radius:10px;border:1px solid rgba(255,255,255,0.1);background:rgba(255,255,255,0.06);color:#fff;font-size:13px;font-family:'Poppins',sans-serif;">
            </div>
            <button type="submit" class="btn-primary" onclick="return confirm('Import file Excel resmi? Baris desa yang sama (kode wilayah) akan di-UPDATE, baris baru di-INSERT dalam satu transaksi.')"><i class="fas fa-file-import"></i> Import</button>
        </form>
        <p class="info-hint" style="margin-top:10px;">Sumber: sheet "Data Asli" (KODE | WILAYAH | L | P | JML). Hanya kode 33.11.XX.YYYY yang diproses (harus 167 baris valid); JML wajib = L+P; mapping kecamatan via nama. Kode yang sudah ada di database akan diperbarui + diaktifkan kembali, bukan diduplikat.</p>
    </div>
</div>

<!-- MODAL EDIT DATA DASAR KECAMATAN -->
<div id="editModal">
    <div class="modal-box">
        <h2 id="modalTitle"><i class="fas fa-pen" style="color:#00d4ff;"></i> Edit Data Kecamatan</h2>
        <form method="POST" id="editForm">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="editId">
            <div class="form-grid">
                <div class="form-group" style="grid-column: 1 / -1;">
                    <label>Nama Kecamatan (Terkunci Sesuai Peta)</label>
                    <input type="text" name="nama_kecamatan" id="editNama" required readonly class="readonly-input">
                    <span class="info-hint">Nama kecamatan terkunci agar sinkron dengan peta interaktif SVG.</span>
                </div>
                <div class="form-group">
                    <label>Kode Kecamatan</label>
                    <input type="text" name="kode_kecamatan" id="editKode" required>
                </div>
                <div class="form-group">
                    <label>Jumlah Penduduk (Otomatis dari Desa/Kelurahan)</label>
                    <input type="number" name="jumlah_penduduk" id="editPenduduk" min="0" readonly class="readonly-input">
                    <span class="info-hint">Dihitung otomatis (SUM Desa/Kelurahan). Tidak dapat diubah manual.</span>
                </div>
                <div class="form-group" style="grid-column: 1 / -1;">
                    <label>Jumlah Desa / Kelurahan (Otomatis)</label>
                    <input type="number" name="jumlah_desa" id="editDesa" min="0" readonly class="readonly-input">
                    <span class="info-hint">Dihitung otomatis (COUNT Desa/Kelurahan aktif). Tidak dapat diubah manual.</span>
                </div>
                <div class="form-group" style="grid-column: 1 / -1;">
                    <label>Jumlah Posyandu</label>
                    <input type="number" name="jumlah_posyandu" id="editPosyandu" min="0" required>
                </div>
            </div>
            <div class="modal-actions">
                <button type="submit" class="btn-primary"><i class="fas fa-save"></i> Simpan Perubahan</button>
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
        document.getElementById('editPosyandu').value = this.dataset.posyandu;

        document.getElementById('modalTitle').innerHTML = '<i class="fas fa-pen" style="color:#00d4ff;"></i> Edit Kecamatan: ' + this.dataset.nama;
        document.getElementById('editModal').style.display = 'flex';
    };
});

document.getElementById('editModal').onclick = function(e) {
    if (e.target === this) this.style.display = 'none';
};
</script>

<!-- MODAL EDIT DESA / KELURAHAN (kode wilayah + kecamatan dikunci) -->
<div id="desaEditModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.6); z-index:999; justify-content:center; align-items:center;">
    <div class="modal-box">
        <h2 id="desaModalTitle"><i class="fas fa-pen" style="color:#00d4ff;"></i> Edit Desa / Kelurahan</h2>
        <form method="POST" id="desaEditForm">
            <input type="hidden" name="action" value="desa_edit">
            <input type="hidden" name="id" id="desaEditId">
            <input type="hidden" name="id_kecamatan" id="desaEditKec">
            <input type="hidden" name="fstatus_back" value="<?= htmlspecialchars($fstatus) ?>">
            <div class="form-grid">
                <div class="form-group">
                    <label>Kode Wilayah (Terkunci)</label>
                    <input type="text" id="desaEditKode" readonly class="readonly-input">
                    <span class="info-hint">Business key tidak dapat diubah.</span>
                </div>
                <div class="form-group">
                    <label>Nama Desa / Kelurahan</label>
                    <input type="text" name="nama" id="desaEditNama" required maxlength="100">
                </div>
                <div class="form-group">
                    <label>Jenis</label>
                    <select name="jenis" id="desaEditJenis" required>
                        <option value="Desa">Desa</option>
                        <option value="Kelurahan">Kelurahan</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Kecamatan (Terkunci)</label>
                    <input type="text" id="desaEditKecNama" readonly class="readonly-input">
                </div>
                <div class="form-group">
                    <label>Laki-laki</label>
                    <input type="number" name="laki_laki" id="desaEditL" min="0" step="1" required>
                </div>
                <div class="form-group">
                    <label>Perempuan</label>
                    <input type="number" name="perempuan" id="desaEditP" min="0" step="1" required>
                </div>
            </div>
            <div class="modal-actions">
                <button type="submit" class="btn-primary"><i class="fas fa-save"></i> Simpan Perubahan</button>
                <button type="button" class="btn-secondary" onclick="document.getElementById('desaEditModal').style.display='none'">Batal</button>
            </div>
        </form>
    </div>
</div>

<script>
document.querySelectorAll('.desa-edit-btn').forEach(function(btn) {
    btn.onclick = function() {
        document.getElementById('desaEditId').value = this.dataset.id;
        document.getElementById('desaEditKec').value = "<?= $fkec ?>";
        document.getElementById('desaEditKode').value = this.dataset.kode;
        document.getElementById('desaEditNama').value = this.dataset.nama;
        document.getElementById('desaEditJenis').value = this.dataset.jenis;
        document.getElementById('desaEditKecNama').value = "<?= htmlspecialchars($kecNama) ?>";
        document.getElementById('desaEditL').value = this.dataset.l;
        document.getElementById('desaEditP').value = this.dataset.p;
        document.getElementById('desaModalTitle').innerHTML = '<i class="fas fa-pen" style="color:#00d4ff;"></i> Edit: ' + this.dataset.kode;
        document.getElementById('desaEditModal').style.display = 'flex';
    };
});

document.getElementById('desaEditModal').onclick = function(e) {
    if (e.target === this) this.style.display = 'none';
};
</script>
<!-- Notify tab Portal setelah mutasi kecamatan/desa berhasil (?msg sukses) -->
<script>window.PORTAL_NOTIFY_OK=["added","updated","deleted","desa_added","desa_updated","desa_deleted","desa_restored","desa_imported"];</script>
<script src="../assetsadmin/portal-notify.js"></script>

</body>
</html>