<?php
/**
 * SpmLib — shared helper SPM (Standar Pelayanan Minimal).
 *
 * Dipakai oleh:
 * - api/get_spm.php (public read-only)
 * - admin/crud/spm.php, spm_export.php, spm_template.php, spm_sasaran.php
 *
 * IDENTITAS DATA (business key):
 *   bkey = md5(periode | tahun | layanan | sub_no | indikator | satuan | sasaran)
 *   dengan masing-masing komponen dinormalisasi (trim, collapse whitespace,
 *   lowercase). Normalisasi HANYA untuk pencocokan; nilai asli tetap disimpan
 *   dan ditampilkan apa adanya.
 *   Kolom bkey di tbl_spm dilindungi UNIQUE KEY uq_spm_bkey sehingga
 *   duplikat tidak mungkin terbentuk di level database.
 *
 * HIERARKI: baris parent (sub_no kosong, cth "Jumlah yang Harus Dilayani")
 *   di-relasikan ke baris anak via parent_id. Satu baris SPM -> N target
 *   kecamatan di tbl_spm_target (UNIQUE id_spm+id_kecamatan).
 *
 * Tidak ada session/auth di file ini. Auth ditangani masing-masing halaman admin.
 */

class SpmLib
{
    /** Urutan kanonik 12 kecamatan (sesuai spreadsheet sumber). */
    const KEC_ORDER = [
        'WERU', 'BULU', 'TAWANGSARI', 'SUKOHARJO', 'NGUTER', 'BENDOSARI',
        'POLOKARTO', 'MOJOLABAN', 'GROGOL', 'BAKI', 'GATAK', 'KARTASURA',
    ];

    /** Periode/sheet yang didukung. */
    const PERIODS = ['Perubahan Target 2026', '2026', '2027'];

    /**
     * Map NAMA_KECAMATAN_UPPER => id_kecamatan dari tbl_kecamatan existing.
     * $db = koneksi mysqli ($config).
     */
    public static function kecMap($db)
    {
        $map = [];
        $q = mysqli_query($db, "SELECT id_kecamatan, nama_kecamatan FROM tbl_kecamatan WHERE aktif='Y'");
        if ($q) {
            while ($r = mysqli_fetch_assoc($q)) {
                $map[mb_strtoupper(trim($r['nama_kecamatan']), 'UTF-8')] = (int)$r['id_kecamatan'];
            }
        }
        return $map;
    }

    /** Daftar periode yang ada di DB (union dengan PERIODS agar tab selalu tampil). */
    public static function periods($db)
    {
        $found = [];
        $q = mysqli_query($db, "SELECT DISTINCT periode FROM tbl_spm WHERE aktif='Y'");
        if ($q) {
            while ($r = mysqli_fetch_assoc($q)) {
                $found[] = $r['periode'];
            }
        }
        $all = array_unique(array_merge(self::PERIODS, $found));
        usort($all, function ($a, $b) {
            $oa = array_search($a, self::PERIODS);
            $ob = array_search($b, self::PERIODS);
            $oa = $oa === false ? 99 : $oa;
            $ob = $ob === false ? 99 : $ob;
            if ($oa === $ob) return strcmp($a, $b);
            return $oa - $ob;
        });
        return $all;
    }

    /** Normalisasi nama kecamatan untuk pencocokan (case-insensitive, trim). */
    public static function normKec($s)
    {
        return mb_strtoupper(trim((string)$s), 'UTF-8');
    }

    /**
     * Normalisasi teks untuk PENCOCOKAN business key saja.
     * - trim, collapse semua whitespace/newline jadi 1 spasi, lowercase.
     * Nilai asli TIDAK diubah; hanya kunci pencocokan yang dinormalisasi.
     */
    public static function normKey($s)
    {
        $s = trim((string)$s);
        $s = preg_replace('/\s+/u', ' ', $s);
        return mb_strtolower($s, 'UTF-8');
    }

    /**
     * Business key: identitas unik satu baris SPM.
     * Kombinasi: periode(sheet) + tahun + layanan + sub_no + indikator + satuan + sasaran.
     * Nama kecamatan SENGAJA tidak masuk key (1 SPM -> 12 target di tabel relasi).
     */
    public static function bkey($periode, $tahun, $layanan, $subNo, $indikator, $satuan, $sasaran)
    {
        return md5(implode("\x1F", [
            self::normKey($periode),
            (string)(int)$tahun,
            self::normKey($layanan),
            self::normKey($subNo),
            self::normKey($indikator),
            self::normKey($satuan),
            self::normKey($sasaran),
        ]));
    }

    /**
     * Parse angka dari sel Excel/POST.
     * Mendukung format Indonesia ("1.234,56"), Inggris ("1,234.56"), dan angka biasa.
     * Return float, atau 0.0 bila kosong. Return null hanya bila $allowNull dan kosong.
     */
    public static function parseNumber($v, $allowNull = false)
    {
        if ($v === null) return $allowNull ? null : 0.0;
        if (is_int($v) || is_float($v)) return (float)$v;
        $s = trim((string)$v);
        if ($s === '' || $s === '-') return $allowNull ? null : 0.0;
        // buang karakter non angka/koma/titik/minus (mis. "org", "%", spasi)
        $s = preg_replace('/[^0-9,\.\-]/', '', $s);
        if ($s === '' || $s === '-' || $s === '.' || $s === ',') {
            return $allowNull ? null : 0.0;
        }
        $hasComma = strpos($s, ',') !== false;
        $hasDot = strpos($s, '.') !== false;
        if ($hasComma && $hasDot) {
            // pemisah desimal = yang paling kanan
            if (strrpos($s, ',') > strrpos($s, '.')) {
                $s = str_replace('.', '', $s);
                $s = str_replace(',', '.', $s);
            } else {
                $s = str_replace(',', '', $s);
            }
        } elseif ($hasComma) {
            // "1,5" => desimal; "1,000" (3 digit) => ribuan. Heuristik: koma + tepat 3 digit di akhir => ribuan
            if (preg_match('/,\d{3}$/', $s)) {
                $s = str_replace(',', '', $s);
            } else {
                $s = str_replace(',', '.', $s);
            }
        } elseif ($hasDot) {
            // "1.000" (tepat 3 digit) => ribuan; "1.5" => desimal
            if (preg_match('/\.\d{3}(\.|$)/', $s) && !preg_match('/\.\d{1,2}$/', $s)) {
                $s = str_replace('.', '', $s);
            } elseif (substr_count($s, '.') > 1) {
                $s = str_replace('.', '', $s);
            }
        }
        if (!is_numeric($s)) return $allowNull ? null : 0.0;
        return (float)$s;
    }

    /**
     * Cek apakah sel target kecamatan berisi nilai yang benar-benar tidak valid
     * (teks tanpa digit sama sekali, cth "N/A", "belum ada"). Sel kosong = valid (0).
     */
    public static function targetCellError($raw)
    {
        if ($raw === null) return false;
        $s = trim((string)$raw);
        if ($s === '' || $s === '-') return false;
        return preg_match('/[0-9]/', $s) !== 1;
    }

    /** Format angka untuk tampilan: buang desimal bila bulat. */
    public static function fmt($v)
    {
        if ($v === null) return '';
        $f = (float)$v;
        if (abs($f - round($f)) < 0.0001) {
            return number_format(round($f), 0, ',', '.');
        }
        return number_format($f, 2, ',', '.');
    }

    /**
     * Ambil seluruh baris SPM satu periode beserta target 12 kecamatan.
     * Return array rows: [id, jenis_layanan, sub_no, indikator, satuan, sasaran, tahun, periode,
     *   urutan, total_manual, targets=>[KEC=>float], total=>float]
     * TOTAL = total_manual bila diisi, selain itu SUM 12 kecamatan (mengikuti spreadsheet).
     */
    public static function fetchData($db, $periode)
    {
        $kecMap = self::kecMap($db);
        // id_kecamatan berdasarkan urutan kanonik (yang tidak ketemu di-skip)
        $orderedIds = [];
        $orderedNames = [];
        foreach (self::KEC_ORDER as $kn) {
            if (isset($kecMap[$kn])) {
                $orderedIds[] = $kecMap[$kn];
                $orderedNames[] = $kn;
            }
        }
        $p = mysqli_real_escape_string($db, $periode);
        $rows = [];
        $q = mysqli_query($db, "SELECT * FROM tbl_spm WHERE periode='$p' AND aktif='Y' ORDER BY urutan ASC, id ASC");
        if (!$q) return ['kecamatan' => $orderedNames, 'rows' => []];
        $ids = [];
        while ($r = mysqli_fetch_assoc($q)) {
            $r['targets'] = [];
            foreach ($orderedNames as $kn) $r['targets'][$kn] = null;
            $rows[(int)$r['id']] = $r;
            $ids[] = (int)$r['id'];
        }
        if (!empty($ids)) {
            $idList = implode(',', $ids);
            $qt = mysqli_query($db, "SELECT t.id_spm, t.id_kecamatan, t.target, k.nama_kecamatan
                FROM tbl_spm_target t JOIN tbl_kecamatan k ON k.id_kecamatan=t.id_kecamatan
                WHERE t.id_spm IN ($idList) AND t.aktif='Y'");
            if ($qt) {
                while ($t = mysqli_fetch_assoc($qt)) {
                    $sid = (int)$t['id_spm'];
                    if (!isset($rows[$sid])) continue;
                    $kn = self::normKec($t['nama_kecamatan']);
                    if (array_key_exists($kn, $rows[$sid]['targets'])) {
                        $rows[$sid]['targets'][$kn] = (float)$t['target'];
                    }
                }
            }
        }
        foreach ($rows as &$r) {
            $sum = array_sum(array_map(function ($v) { return $v === null || $v === '' ? 0 : (float)$v; }, $r['targets']));
            $r['total'] = ($r['total_manual'] !== null && $r['total_manual'] !== '')
                ? (float)$r['total_manual']
                : $sum;
            $r['total_hitung'] = $sum;
        }
        unset($r);
        return ['kecamatan' => $orderedNames, 'rows' => array_values($rows)];
    }

    /** Hitung jumlah baris aktif per periode (untuk badge/tab). */
    public static function counts($db)
    {
        $out = [];
        $q = mysqli_query($db, "SELECT periode, COUNT(*) c FROM tbl_spm WHERE aktif='Y' GROUP BY periode");
        if ($q) {
            while ($r = mysqli_fetch_assoc($q)) $out[$r['periode']] = (int)$r['c'];
        }
        return $out;
    }

    /** Tahun default dari nama periode. */
    public static function yearOf($periode)
    {
        if (preg_match('/(19|20)\d{2}/', (string)$periode, $m)) return (int)$m[0];
        return (int)date('Y');
    }

    /** Pastikan tabel sasaran master ada + seed dari nilai distinct. Return array sasaran. */
    public static function ensureSasaranTable($db)
    {
        mysqli_query($db, "CREATE TABLE IF NOT EXISTS tbl_spm_sasaran (
            id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
            nama VARCHAR(255) NOT NULL,
            aktif ENUM('Y','N') NOT NULL DEFAULT 'Y',
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_spm_sasaran_nama (nama)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        // seed dari distinct sasaran yang sudah ada di tbl_spm
        $q = mysqli_query($db, "SELECT DISTINCT sasaran FROM tbl_spm WHERE sasaran<>''");
        if ($q) {
            $stmt = mysqli_prepare($db, "INSERT IGNORE INTO tbl_spm_sasaran (nama, aktif) VALUES (?, 'Y')");
            if ($stmt) {
                while ($r = mysqli_fetch_assoc($q)) {
                    mysqli_stmt_bind_param($stmt, 's', $r['sasaran']);
                    mysqli_stmt_execute($stmt);
                }
                mysqli_stmt_close($stmt);
            }
        }
    }

    /** Daftar sasaran aktif untuk datalist/select. */
    public static function sasaranList($db)
    {
        self::ensureSasaranTable($db);
        $out = [];
        $q = mysqli_query($db, "SELECT id, nama FROM tbl_spm_sasaran WHERE aktif='Y' ORDER BY nama");
        if ($q) {
            while ($r = mysqli_fetch_assoc($q)) $out[] = $r;
        }
        return $out;
    }

    // =====================================================================
    // DETEKSI KOLOM EXCEL
    // =====================================================================

    /**
     * Deteksi baris header + pemetaan kolom pada satu worksheet.
     * Prioritas klasifikasi header (agar kolom "Sub No" tidak dikira indikator):
     *   kecamatan (nama persis) > total > satuan > sasaran > layanan > sub_no > indikator.
     * Dilengkapi VALIDASI isi: bila kolom indikator terdeteksi berisi angka-angka
     * pendek (4279 dst) sementara ada kolom teks lain, otomatis dikoreksi.
     * Return ['headerRow'=>int, 'colKec'=>[colIdx=>KEC], 'colTotal'=>, 'colSatuan'=>,
     *   'colSasaran'=>, 'colIndikator'=>, 'colLayanan'=>, 'colSubno'=>, 'warnings'=>[]]
     */
    public static function detectColumns($sheet, $kecMap)
    {
        $res = [
            'headerRow' => 0, 'colKec' => [], 'colTotal' => 0, 'colSatuan' => 0,
            'colSasaran' => 0, 'colIndikator' => 0, 'colLayanan' => 0, 'colSubno' => 0,
            'colTahun' => 0, 'runningNo' => 0, 'warnings' => [],
        ];
        $maxRow = $sheet->getHighestRow();
        $maxColIdx = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($sheet->getHighestColumn());
        $scanMax = min(25, $maxRow);

        $bestRow = 0; $bestHits = [];
        $bestMeta = [];
        for ($r = 1; $r <= $scanMax; $r++) {
            $hits = [];
            $meta = ['total' => 0, 'satuan' => 0, 'sasaran' => 0, 'ind' => 0, 'lay' => 0, 'sub' => 0];
            for ($c = 1; $c <= $maxColIdx; $c++) {
                $raw = trim((string)$sheet->getCellByColumnAndRow($c, $r)->getCalculatedValue());
                if ($raw === '') continue;
                $up = self::normKec($raw);
                if (isset($kecMap[$up]) && in_array($up, self::KEC_ORDER, true)) {
                    $hits[$c] = $up;
                    continue;
                }
                $low = mb_strtolower($up, 'UTF-8');
                if (strpos($low, 'total') !== false) { if (!$meta['total']) $meta['total'] = $c; }
                elseif (strpos($low, 'satuan') !== false) { if (!$meta['satuan']) $meta['satuan'] = $c; }
                elseif (strpos($low, 'sasaran') !== false) { if (!$meta['sasaran']) $meta['sasaran'] = $c; }
                elseif (preg_match('/\btahun\b/u', $low)) { if (!$meta['thn']) $meta['thn'] = $c; }
                elseif (strpos($low, 'layanan') !== false || (strpos($low, 'jenis') !== false && strpos($low, 'kelamin') === false)) { if (!$meta['lay']) $meta['lay'] = $c; }
                elseif (strpos($low, 'indikator') !== false || strpos($low, 'uraian') !== false || strpos($low, 'keterangan') !== false || strpos($low, 'parameter') !== false) { if (!$meta['ind']) $meta['ind'] = $c; }
                // Sub-No: HANYA varian yang menyebut 'sub' (cth 'Sub-No', 'Sub No').
                // Header 'No'/'Nomor' telanjang adalah nomor urut baris -> dicatat
                // terpisah (runningNo) dan TIDAK dipakai sebagai sub-no.
                elseif ((strpos($low, 'sub') !== false && strpos($low, 'indikator') === false) || $low === 'subno' || $low === 'sub-no.') { if (!$meta['sub']) $meta['sub'] = $c; }
                elseif ($low === 'no' || $low === 'no.' || $low === 'nomor' || $low === 'number') { if (empty($meta['runningNo'])) $meta['runningNo'] = $c; }
            }
            if (count($hits) > count($bestHits)) {
                $bestHits = $hits; $bestRow = $r; $bestMeta = $meta;
            }
            if (count($hits) >= 12) break;
        }

        if (count($bestHits) >= 6) {
            $res['headerRow'] = $bestRow;
            $res['colKec'] = $bestHits;
            $res['colTotal'] = $bestMeta['total'];
            $res['colSatuan'] = $bestMeta['satuan'];
            $res['colSasaran'] = $bestMeta['sasaran'];
            $res['colIndikator'] = $bestMeta['ind'];
            $res['colLayanan'] = $bestMeta['lay'];
            $res['colSubno'] = $bestMeta['sub'];
            $res['colTahun'] = $bestMeta['thn'] ?? 0;
            $res['runningNo'] = $bestMeta['runningNo'] ?? 0;
        } else {
            // Fallback layout template/export internal:
            // A=No, B=Jenis Layanan, C=Sub-No, D=Indikator, E=Satuan, F=Sasaran, G..=kecamatan, terakhir=TOTAL
            for ($r = 1; $r <= $scanMax; $r++) {
                $line = '';
                for ($c = 1; $c <= min(8, $maxColIdx); $c++) {
                    $line .= ' ' . mb_strtolower(trim((string)$sheet->getCellByColumnAndRow($c, $r)->getCalculatedValue()), 'UTF-8');
                }
                if (strpos($line, 'layanan') !== false && strpos($line, 'indikator') !== false) {
                    $res['headerRow'] = $r;
                    break;
                }
            }
            if ($res['headerRow'] === 0) {
                if ($maxColIdx < 6) {
                    $res['warnings'][] = 'struktur tidak dikenali (kolom < 6 dan tidak ada baris header kecamatan).';
                    return $res;
                }
                $res['headerRow'] = 3;
            }
            $res['colLayanan'] = 2; $res['colSubno'] = 3; $res['colIndikator'] = 4;
            $res['colSatuan'] = 5; $res['colSasaran'] = 6;
            $ci = 7;
            foreach (self::KEC_ORDER as $kn) {
                if ($ci > $maxColIdx) break;
                $res['colKec'][$ci] = $kn;
                $ci++;
            }
            if ($ci <= $maxColIdx) $res['colTotal'] = $ci;
            $res['warnings'][] = 'header kecamatan tidak terdeteksi penuh; memakai layout kolom standar (B=Layanan, C=Sub-No, D=Indikator, E=Satuan, F=Sasaran, G..=kecamatan).';
        }

        // Label meta (satuan/sasaran/indikator/dll) kadang berada di baris
        // TEPAT DI ATAS baris kecamatan (header bertingkat / merged cells).
        // Dijalankan SEBELUM fallback agar label asli mengalahkan tebakan posisi.
        // 'No'/'Nomor' telanjang = nomor urut -> diabaikan (bukan sub-no).
        foreach ([$res['headerRow'] - 2, $res['headerRow'] - 1] as $rr) {
            if ($rr < 1) continue;
            for ($c = 1; $c <= $maxColIdx; $c++) {
                if (isset($res['colKec'][$c])) continue;
                $raw = trim((string)$sheet->getCellByColumnAndRow($c, $rr)->getCalculatedValue());
                if ($raw === '') continue;
                $low = mb_strtolower(self::normKec($raw), 'UTF-8');
                if (!$res['colTotal'] && strpos($low, 'total') !== false) { $res['colTotal'] = $c; continue; }
                if (!$res['colSatuan'] && strpos($low, 'satuan') !== false) { $res['colSatuan'] = $c; continue; }
                if (!$res['colSasaran'] && strpos($low, 'sasaran') !== false) { $res['colSasaran'] = $c; continue; }
                if (!$res['colLayanan'] && (strpos($low, 'layanan') !== false || (strpos($low, 'jenis') !== false && strpos($low, 'kelamin') === false))) { $res['colLayanan'] = $c; continue; }
                if (!$res['colIndikator'] && (strpos($low, 'indikator') !== false || strpos($low, 'uraian') !== false || strpos($low, 'keterangan') !== false)) { $res['colIndikator'] = $c; continue; }
                if (!$res['colSubno'] && ((strpos($low, 'sub') !== false && strpos($low, 'indikator') === false) || $low === 'subno')) { $res['colSubno'] = $c; continue; }
                if (empty($res['runningNo']) && ($low === 'no' || $low === 'no.' || $low === 'nomor' || $low === 'number')) { $res['runningNo'] = $c; continue; }
            }
        }

        if (!$res['colIndikator']) $res['colIndikator'] = 3;
        if (!$res['colLayanan']) $res['colLayanan'] = ($res['colIndikator'] > 1) ? $res['colIndikator'] - 1 : 1;

        // ---- VALIDASI ISI: pastikan kolom indikator benar-benar berisi teks ----
        // (mencegah bug lama: kolom "Sub No" berisi 1,2,3 terbaca sebagai indikator)
        $samples = [];
        for ($r = $res['headerRow'] + 1; $r <= min($res['headerRow'] + 40, $maxRow); $r++) {
            $v = trim((string)$sheet->getCellByColumnAndRow($res['colIndikator'], $r)->getCalculatedValue());
            if ($v !== '') $samples[] = $v;
            if (count($samples) >= 25) break;
        }
        if (!empty($samples)) {
            $numShort = 0;
            foreach ($samples as $s) {
                if (is_numeric($s) && strlen($s) <= 4) $numShort++;
            }
            if ($numShort / count($samples) >= 0.6) {
                // cari kolom teks alternatif (kolom non-kecamatan/non-total dengan teks terpanjang)
                $skip = array_merge(array_keys($res['colKec']), [$res['colTotal'], $res['colSatuan'], $res['colSasaran'], $res['colLayanan'], $res['colSubno']]);
                $bestAlt = 0; $bestLen = 0;
                for ($c = 1; $c <= $maxColIdx; $c++) {
                    if (in_array($c, $skip, true) || $c === $res['colIndikator']) continue;
                    $len = 0; $n = 0; $num = 0;
                    for ($r = $res['headerRow'] + 1; $r <= min($res['headerRow'] + 40, $maxRow); $r++) {
                        $v = trim((string)$sheet->getCellByColumnAndRow($c, $r)->getCalculatedValue());
                        if ($v === '') continue;
                        $n++; $len += mb_strlen($v);
                        if (is_numeric($v) && strlen($v) <= 4) $num++;
                    }
                    if ($n >= 3 && $num / $n < 0.5 && ($len / $n) > $bestLen) {
                        $bestLen = $len / $n; $bestAlt = $c;
                    }
                }
                if ($bestAlt && $bestLen > 8) {
                    $res['warnings'][] = "kolom indikator dikoreksi (kolom {$res['colIndikator']} berisi penomoran, dipindah ke Sub-No; indikator memakai kolom $bestAlt).";
                    if (!$res['colSubno']) $res['colSubno'] = $res['colIndikator'];
                    $res['colIndikator'] = $bestAlt;
                    // satuan/sasaran mungkin ikut bergeser: cari ulang di kanan indikator
                    if (!$res['colSatuan'] || !$res['colSasaran']) {
                        for ($c = $bestAlt + 1; $c <= min($bestAlt + 4, $maxColIdx); $c++) {
                            if (in_array($c, array_keys($res['colKec']), true)) continue;
                            // heuristik isi: satuan umumnya kata pendek berulang, sasaran juga
                            if (!$res['colSatuan']) { $res['colSatuan'] = $c; continue; }
                            if (!$res['colSasaran']) { $res['colSasaran'] = $c; break; }
                        }
                    }
                } else {
                    $res['warnings'][] = 'kolom indikator terdeteksi berisi penomoran; pastikan file memakai kolom Indikator teks.';
                }
            }
        }

        // Cegah duplikasi satuan/sasaran ke kolom yang sama (Excel SPM tidak punya kolom Sasaran terpisah)
        if ($res['colSasaran'] && $res['colSatuan'] && $res['colSasaran'] === $res['colSatuan']) {
            $res['colSasaran'] = 0;
        }

        // ---- VALIDASI SUB-NO vs NOMOR URUT (dijalankan SETELAH koreksi indikator agar tidak menimpa colSubno yang baru dikoreksi) ----
        $colVals = function ($c, $limit = 80) use ($sheet, $res, $maxRow) {
            $out = [];
            for ($r = $res['headerRow'] + 1; $r <= min($res['headerRow'] + $limit, $maxRow); $r++) {
                $out[] = trim((string)$sheet->getCellByColumnAndRow($c, $r)->getCalculatedValue());
            }
            return $out;
        };
        $isStrictSeq = function ($vals) {
            $nums = [];
            foreach ($vals as $v) {
                if ($v === '') return false;
                if (!is_numeric($v)) return false;
                $nums[] = (int)$v;
            }
            if (count($nums) < 5) return false;
            for ($i = 0; $i < count($nums); $i++) {
                if ($nums[$i] !== $i + 1) return false;
            }
            return true;
        };
        if ($res['colSubno'] && $isStrictSeq($colVals($res['colSubno']))) {
            $res['warnings'][] = "kolom {$res['colSubno']} berisi nomor urut (1,2,3..), bukan Sub-No; diabaikan.";
            $res['colSubno'] = 0;
        }
        if (!$res['colSubno'] && !empty($res['runningNo'])) {
            $vals = $colVals($res['runningNo']);
            $hasBlank = in_array('', $vals, true);
            if ($hasBlank && !$isStrictSeq($vals)) {
                $res['colSubno'] = $res['runningNo'];
            }
        }

        return $res;
    }

    // =====================================================================
    // IMPORT EXCEL — UPSERT/SYNC BERDASARKAN BUSINESS KEY
    // =====================================================================

    /**
     * Import satu worksheet ke DB sebagai UPSERT berdasarkan bkey.
     * - Sudah ada (cocok bkey, termasuk yang soft-delete) -> UPDATE + revive.
     * - Belum ada -> INSERT.
     * - Target kecamatan: UPSERT per (id_spm, id_kecamatan).
     * - Sasaran: INSERT IGNORE (unik nama).
     * - Urutan (urutan) = posisi baris data di Excel.
     * - Satu sheet = satu transaksi (gagal -> rollback).
     *
     * $opts: ['fullSync' => bool] — bila true, baris DB pada periode ini yang
     *   tidak ada di file dinonaktifkan (soft-delete). Default false (upsert biasa).
     * Return statistik lengkap.
     */
    public static function importSheet($db, $sheet, $periode, $tahun, $opts = [])
    {
        $fullSync = !empty($opts['fullSync']);
        $sheetName = $sheet->getTitle();
        $stat = [
            'sheet' => $sheetName, 'periode' => $periode,
            'excel_rows' => 0, 'inserted' => 0, 'updated' => 0, 'revived' => 0,
            'skipped_empty' => 0, 'failed' => 0, 'errors' => [],
            'targets_ins' => 0, 'targets_upd' => 0, 'sasaran_new' => 0,
            'warnings' => [], 'synced_off' => 0,
        ];
        $kecMap = self::kecMap($db);
        $det = self::detectColumns($sheet, $kecMap);
        $stat['warnings'] = $det['warnings'];
        if ($det['headerRow'] === 0 || empty($det['colKec'])) {
            $stat['failed']++;
            $stat['errors'][] = "Sheet '$sheetName': " . implode(' ', $det['warnings']);
            return $stat;
        }
        foreach ($det['warnings'] as $w) {
            if (count($stat['errors']) < 30) $stat['errors'][] = "Sheet '$sheetName' peringatan: $w";
        }
        $foundKec = array_values($det['colKec']);
        $missingKec = array_diff(self::KEC_ORDER, $foundKec);
        if (!empty($missingKec)) {
            $stat['warnings'][] = 'kolom kecamatan tidak ditemukan: ' . implode(', ', $missingKec) . ' (targetnya dilewati).';
        }

        $colKec = $det['colKec'];
        $colTotal = $det['colTotal']; $colSatuan = $det['colSatuan']; $colSasaran = $det['colSasaran'];
        $colIndikator = $det['colIndikator']; $colLayanan = $det['colLayanan']; $colSubno = $det['colSubno'];

        $maxRow = $sheet->getHighestRow();
        $tahun = (int)$tahun;
        $pEsc = mysqli_real_escape_string($db, $periode);

        // prepared statements
        $stFind = mysqli_prepare($db, "SELECT id, aktif FROM tbl_spm WHERE bkey=? LIMIT 1");
        $stIns = mysqli_prepare($db, "INSERT INTO tbl_spm
            (jenis_layanan, indikator, sub_no, parent_id, satuan, sasaran, tahun, periode, urutan, total_manual, bkey, aktif)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,'Y')");
        // jenis: s,s,s,i,s,s,i,s,i,d,s  (11 param)
        $stUpd = mysqli_prepare($db, "UPDATE tbl_spm SET jenis_layanan=?, indikator=?, sub_no=?, parent_id=?,
            satuan=?, sasaran=?, tahun=?, urutan=?, total_manual=?, bkey=?, aktif='Y', updated_at=NOW() WHERE id=?");
        // jenis: s,s,s,i,s,s,i,i,d,s,i  (11 param)
        $stTgt = mysqli_prepare($db, "INSERT INTO tbl_spm_target (id_spm, id_kecamatan, target, aktif)
            VALUES (?,?,?,'Y') ON DUPLICATE KEY UPDATE target=VALUES(target), aktif='Y', updated_at=NOW()");
        $stDelTgt = mysqli_prepare($db, "DELETE FROM tbl_spm_target WHERE id_spm=? AND id_kecamatan=?");
        $stSas = mysqli_prepare($db, "INSERT IGNORE INTO tbl_spm_sasaran (nama, aktif) VALUES (?,'Y')");
        if (!$stFind || !$stIns || !$stUpd || !$stTgt || !$stSas || !$stDelTgt) {
            $stat['failed']++;
            $stat['errors'][] = "Sheet '$sheetName': gagal menyiapkan query (" . mysqli_error($db) . ").";
            return $stat;
        }

        $lastLayanan = '';
        $lastParentId = null;
        $lastParentLay = null;
        $urutan = 0;
        $seenBkeys = [];

        mysqli_begin_transaction($db);
        try {
            for ($r = $det['headerRow'] + 1; $r <= $maxRow; $r++) {
                $get = function ($c) use ($sheet, $r) {
                    if (!$c) return '';
                    return trim((string)$sheet->getCellByColumnAndRow($c, $r)->getCalculatedValue());
                };
                $getRaw = function ($c) use ($sheet, $r) {
                    if (!$c) return null;
                    return $sheet->getCellByColumnAndRow($c, $r)->getCalculatedValue();
                };
                $layanan = $get($colLayanan);
                $subNo = $colSubno ? $get($colSubno) : '';
                $indikator = $get($colIndikator);
                $satuan = $colSatuan ? $get($colSatuan) : '';
                $sasaran = $colSasaran ? $get($colSasaran) : '';

                // Handle parent row merged cell: C:D merged -> bullet di C (subNo), indikator di D kosong.
                // Jika indikator kosong tetapi subNo berisi teks indikator (• / Jumlah yang Harus Dilayani), tukarkan.
                if ($indikator === '' && $subNo !== '') {
                    $subLow = mb_strtolower($subNo, 'UTF-8');
                    $isParentText = (strpos($subNo, '•') !== false)
                        || strpos($subLow, 'jumlah yang harus dilayani') !== false
                        || strpos($subLow, 'jumlah yang harus') !== false
                        || mb_strlen($subNo, 'UTF-8') > 12;
                    // subNo panjang >12 kemungkinan indikator teks, bukan nomor 1-2 digit
                    // Pastikan bukan nomor murni: indikator parent biasanya >10 karakter
                    if ($isParentText && !is_numeric($subNo)) {
                        $indikator = $subNo;
                        $subNo = '';
                    }
                }

                // baris kosong total -> lewati (bukan error)
                $hasTarget = false;
                foreach ($colKec as $c => $kn) {
                    if (trim((string)$getRaw($c)) !== '') { $hasTarget = true; break; }
                }
                if ($layanan === '' && $subNo === '' && $indikator === '' && !$hasTarget) {
                    $stat['skipped_empty']++;
                    continue;
                }
                // carry-forward layanan (merged cell): nilai hanya di baris pertama
                if ($layanan !== '') $lastLayanan = $layanan;
                elseif ($lastLayanan !== '') $layanan = $lastLayanan;

                // tanpa indikator = baris seksi/judul, bukan data -> lewati
                if ($indikator === '') {
                    $stat['skipped_empty']++;
                    continue;
                }
                if ($layanan === '') {
                    $stat['failed']++;
                    if (count($stat['errors']) < 30) $stat['errors'][] = "Sheet '$sheetName' baris $r: jenis layanan kosong.";
                    continue;
                }
                $stat['excel_rows']++;

                // batas panjang kolom DB (nilai asli hanya dipotong, tidak diubah isinya)
                $layanan = mb_substr($layanan, 0, 255);
                $subNo = mb_substr($subNo, 0, 10);
                $indikator = mb_substr($indikator, 0, 500);
                $satuan = mb_substr($satuan, 0, 50);
                $sasaran = mb_substr($sasaran, 0, 255);

                // validasi target per kecamatan
                $targets = [];
                $rowFailed = false;
                foreach ($colKec as $c => $kn) {
                    $raw = $getRaw($c);
                    if (self::targetCellError($raw)) {
                        $stat['failed']++;
                        if (count($stat['errors']) < 30) {
                            $stat['errors'][] = "Sheet '$sheetName' baris $r: target $kn tidak valid ('" . mb_substr(trim((string)$raw), 0, 30) . "'). Baris dilewati.";
                        }
                        $rowFailed = true;
                        break;
                    }
                    $trimRaw = trim((string)$raw);
                    if ($trimRaw === '' || $trimRaw === '-') {
                        $targets[$kn] = null;
                    } else {
                        $targets[$kn] = self::parseNumber($raw);
                    }
                }
                if ($rowFailed) continue;

                $sum = array_sum(array_map(function ($v) { return $v === null || $v === '' ? 0 : (float)$v; }, $targets));
                $totalManual = null;
                if ($colTotal) {
                    $rawT = $getRaw($colTotal);
                    if (trim((string)$rawT) !== '') {
                        if (self::targetCellError($rawT)) {
                            $stat['failed']++;
                            if (count($stat['errors']) < 30) $stat['errors'][] = "Sheet '$sheetName' baris $r: kolom TOTAL tidak valid. Baris dilewati.";
                            continue;
                        }
                        $tVal = self::parseNumber($rawT);
                        if (abs($tVal - $sum) > 0.01) $totalManual = $tVal;
                    }
                }
                $urutan++;
                $bkey = self::bkey($periode, $tahun, $layanan, $subNo, $indikator, $satuan, $sasaran);
                $seenBkeys[] = $bkey;

                // parent: baris tanpa sub_no = parent; anak menunjuk parent terakhir se-layanan
                $isParent = ($subNo === '' || $subNo === '-');
                $parentId = null;
                if (!$isParent && $lastParentId && $layanan === $lastParentLay) {
                    $parentId = $lastParentId;
                }

                mysqli_stmt_bind_param($stFind, 's', $bkey);
                if (!mysqli_stmt_execute($stFind)) {
                    throw new Exception("baris $r: query pencocokan gagal (" . mysqli_stmt_error($stFind) . ").");
                }
                $resFind = mysqli_stmt_get_result($stFind);
                $found = $resFind ? mysqli_fetch_assoc($resFind) : null;

                if ($found) {
                    $id = (int)$found['id'];
                    $wasN = ($found['aktif'] !== 'Y');
                    // tabrakan key BARU dengan baris LAIN (koreksi teks duplikat di file):
                    // tolak baris ini sebagai data-error, lanjutkan baris lain (tanpa rollback).
                    $qCol = mysqli_query($db, "SELECT id FROM tbl_spm WHERE bkey='" . mysqli_real_escape_string($db, $bkey) . "' AND id<>$id LIMIT 1");
                    if ($qCol && mysqli_num_rows($qCol) > 0) {
                        $stat['failed']++;
                        if (count($stat['errors']) < 30) $stat['errors'][] = "Sheet '$sheetName' baris $r: hasil koreksi teks menabrak identitas baris lain (duplikat). Baris dilewati.";
                        continue;
                    }
                    $tmParam = $totalManual === null ? null : (float)$totalManual;
                    // parent NULL handling: bind_param butuh nilai; pakai string/int + NULL didukung dengan 'i' + null
                    mysqli_stmt_bind_param($stUpd, 'sssissiidsi',
                        $layanan, $indikator, $subNo, $parentId, $satuan, $sasaran, $tahun, $urutan, $tmParam, $bkey, $id);
                    if (!mysqli_stmt_execute($stUpd)) {
                        throw new Exception("baris $r: gagal update (" . mysqli_stmt_error($stUpd) . ").");
                    }
                    $stat['updated']++;
                    if ($wasN) $stat['revived']++;
                } else {
                    $tmParam = $totalManual === null ? null : (float)$totalManual;
                    mysqli_stmt_bind_param($stIns, 'sssissisids',
                        $layanan, $indikator, $subNo, $parentId, $satuan, $sasaran, $tahun, $periode, $urutan, $tmParam, $bkey);
                    if (!mysqli_stmt_execute($stIns)) {
                        // 1062 = balapan/unik: baca ulang lalu update
                        if (mysqli_stmt_errno($stIns) == 1062) {
                            mysqli_stmt_bind_param($stFind, 's', $bkey);
                            mysqli_stmt_execute($stFind);
                            $res2 = mysqli_stmt_get_result($stFind);
                            $f2 = $res2 ? mysqli_fetch_assoc($res2) : null;
                            if ($f2) {
                                $id = (int)$f2['id'];
                                mysqli_stmt_bind_param($stUpd, 'sssissiidsi',
                                    $layanan, $indikator, $subNo, $parentId, $satuan, $sasaran, $tahun, $urutan, $tmParam, $bkey, $id);
                                mysqli_stmt_execute($stUpd);
                                $stat['updated']++;
                            } else {
                                throw new Exception("baris $r: duplikat key tanpa baris pembanding.");
                            }
                        } else {
                            throw new Exception("baris $r: gagal insert (" . mysqli_stmt_error($stIns) . ").");
                        }
                    } else {
                        $id = (int)mysqli_insert_id($db);
                        $stat['inserted']++;
                    }
                }

                if ($isParent) {
                    $lastParentId = $id;
                    $lastParentLay = $layanan;
                }

                // UPSERT target kecamatan: (id_spm, id_kecamatan) sudah UNIQUE; kosong -> hapus baris target
                foreach ($targets as $kn => $val) {
                    if (!isset($kecMap[$kn])) continue;
                    $kid = (int)$kecMap[$kn];
                    if ($val === null) {
                        mysqli_stmt_bind_param($stDelTgt, 'ii', $id, $kid);
                        mysqli_stmt_execute($stDelTgt);
                        continue;
                    }
                    $v = (float)$val;
                    mysqli_stmt_bind_param($stTgt, 'iid', $id, $kid, $v);
                    if (!mysqli_stmt_execute($stTgt)) {
                        throw new Exception("baris $r ($kn): gagal simpan target (" . mysqli_stmt_error($stTgt) . ").");
                    }
                    if (mysqli_stmt_affected_rows($stTgt) == 1) $stat['targets_ins']++;
                    else $stat['targets_upd']++;
                }
                // sasaran master (unik nama)
                if ($sasaran !== '') {
                    mysqli_stmt_bind_param($stSas, 's', $sasaran);
                    mysqli_stmt_execute($stSas);
                    if (mysqli_stmt_affected_rows($stSas) == 1) $stat['sasaran_new']++;
                }
            }

            // FULL SYNC opsional: nonaktifkan baris periode ini yang tak ada di file
            if ($fullSync) {
                if (empty($seenBkeys)) {
                    throw new Exception("mode Full Sync dibatalkan: tidak ada baris valid di file (menolak menonaktifkan semua data).");
                }
                $seenEsc = array_map(function ($b) use ($db) { return "'" . mysqli_real_escape_string($db, $b) . "'"; }, array_unique($seenBkeys));
                $chunks = array_chunk($seenEsc, 500);
                $offIds = [];
                // kumpulkan id yang terlihat
                $seenIds = [];
                foreach ($chunks as $ch) {
                    $q = mysqli_query($db, "SELECT id FROM tbl_spm WHERE bkey IN (" . implode(',', $ch) . ")");
                    if (!$q) throw new Exception("Full Sync gagal membaca bkey (" . mysqli_error($db) . ").");
                    while ($rr = mysqli_fetch_assoc($q)) $seenIds[] = (int)$rr['id'];
                }
                $sql = "SELECT id FROM tbl_spm WHERE periode='$pEsc' AND aktif='Y'";
                if (!empty($seenIds)) $sql .= " AND id NOT IN (" . implode(',', $seenIds) . ")";
                $q = mysqli_query($db, $sql);
                if (!$q) throw new Exception("Full Sync gagal memindai periode (" . mysqli_error($db) . ").");
                while ($rr = mysqli_fetch_assoc($q)) $offIds[] = (int)$rr['id'];
                if (!empty($offIds)) {
                    $list = implode(',', $offIds);
                    if (!mysqli_query($db, "UPDATE tbl_spm SET aktif='N', updated_at=NOW() WHERE id IN ($list)")) {
                        throw new Exception("Full Sync gagal menonaktifkan (" . mysqli_error($db) . ").");
                    }
                    mysqli_query($db, "UPDATE tbl_spm_target SET aktif='N', updated_at=NOW() WHERE id_spm IN ($list)");
                    $stat['synced_off'] = count($offIds);
                }
            }

            mysqli_commit($db);
        } catch (Throwable $e) {
            mysqli_rollback($db);
            $stat['failed']++;
            if (count($stat['errors']) < 30) $stat['errors'][] = "Sheet '$sheetName': " . $e->getMessage() . " (transaksi di-rollback).";
        }
        foreach ([$stFind, $stIns, $stUpd, $stTgt, $stDelTgt, $stSas] as $st) {
            if ($st) mysqli_stmt_close($st);
        }
        return $stat;
    }

    // =====================================================================
    // EXPORT / TEMPLATE
    // =====================================================================

    /**
     * Bangun workbook PhpSpreadsheet dari data DB.
     * Layout: A=No, B=Jenis Layanan, C=Sub-No, D=Indikator, E=Satuan, F=Sasaran,
     *   G..=12 kecamatan, terakhir=TOTAL.
     * $mode = 'export' (isi data) | 'template' (target dikosongkan + contoh + baris kosong).
     */
    public static function buildWorkbook($db, $mode = 'export')
    {
        $ss = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $kecMap = self::kecMap($db);
        $periods = self::PERIODS;
        $first = true;

        $headerFill = 'BDD7EE';
        $titleFill = '1F4E78';

        foreach ($periods as $periode) {
            $data = self::fetchData($db, $periode);
            $kecs = $data['kecamatan'];
            if (empty($kecs)) $kecs = self::KEC_ORDER;

            if ($first) {
                $sheet = $ss->getActiveSheet();
                $first = false;
            } else {
                $sheet = $ss->createSheet();
            }
            $sheet->setTitle(mb_substr($periode, 0, 31));

            $nKec = count($kecs);
            // kolom: A=No, B=Layanan, C=Sub-No, D=Indikator, E=Satuan, F=Sasaran, G..=kecamatan, terakhir=TOTAL
            $colTotalIdx = 7 + $nKec; // 1-based
            $lastCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colTotalIdx);

            $tahun = self::yearOf($periode);
            $sheet->mergeCells("A1:{$lastCol}1");
            $sheet->setCellValue('A1', "REKAPITULASI TARGET SPM — {$periode} TAHUN {$tahun} (Dinas Kesehatan Kab. Sukoharjo)");
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(12)->getColor()->setRGB('FFFFFF');
            $sheet->getStyle('A1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB($titleFill);
            $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER)->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
            $sheet->getRowDimension(1)->setRowHeight(26);

            // header 2 baris: baris2 = grup, baris3 = nama kolom (kecamatan per kolom)
            foreach (['A', 'B', 'C', 'D', 'E', 'F'] as $cc) {
                $sheet->mergeCells("{$cc}2:{$cc}3");
            }
            $sheet->setCellValue('A2', 'No');
            $sheet->setCellValue('B2', 'Jenis Layanan');
            $sheet->setCellValue('C2', 'Sub-No');
            $sheet->setCellValue('D2', 'Indikator / Sub-Indikator');
            $sheet->setCellValue('E2', 'Satuan');
            $sheet->setCellValue('F2', 'Sasaran');
            $kecStartCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(7);
            $kecEndCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(6 + $nKec);
            $sheet->mergeCells("{$kecStartCol}2:{$kecEndCol}2");
            $sheet->setCellValue("{$kecStartCol}2", 'Target Per Kecamatan');
            $totCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colTotalIdx);
            $sheet->mergeCells("{$totCol}2:{$totCol}3");
            $sheet->setCellValue("{$totCol}2", 'TOTAL');
            $c = 7;
            foreach ($kecs as $kn) {
                $cl = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c);
                $sheet->setCellValue("{$cl}3", ucwords(mb_strtolower($kn, 'UTF-8')));
                $c++;
            }
            $hdr = "A2:{$lastCol}3";
            $sheet->getStyle($hdr)->getFont()->setBold(true);
            $sheet->getStyle($hdr)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB($headerFill);
            $sheet->getStyle($hdr)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER)->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER)->setWrapText(true);
            $sheet->getStyle($hdr)->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
            $sheet->getRowDimension(2)->setRowHeight(20);
            $sheet->getRowDimension(3)->setRowHeight(30);
            $sheet->freezePane('G4');

            $sheet->getColumnDimension('A')->setWidth(6);
            $sheet->getColumnDimension('B')->setWidth(34);
            $sheet->getColumnDimension('C')->setWidth(9);
            $sheet->getColumnDimension('D')->setWidth(46);
            $sheet->getColumnDimension('E')->setWidth(14);
            $sheet->getColumnDimension('F')->setWidth(22);
            for ($i = 7; $i <= $colTotalIdx; $i++) {
                $sheet->getColumnDimensionByColumn($i)->setWidth(13);
            }

            $rows = $data['rows'];
            if ($mode === 'template' && empty($rows)) {
                // fallback: ambil struktur dari periode lain agar template tetap berguna
                foreach ($periods as $p2) {
                    if ($p2 === $periode) continue;
                    $d2 = self::fetchData($db, $p2);
                    if (!empty($d2['rows'])) { $rows = array_slice($d2['rows'], 0, 3); break; }
                }
            }
            $r = 4;
            $no = 1;
            $exampleGiven = false;
            foreach ($rows as $row) {
                $limitRows = ($mode === 'template') ? 3 : null;
                if ($limitRows !== null && $no > $limitRows && $exampleGiven) break;
                $sheet->setCellValueByColumnAndRow(1, $r, $no);
                $sheet->setCellValueByColumnAndRow(2, $r, $row['jenis_layanan']);
                $sheet->setCellValueByColumnAndRow(3, $r, $row['sub_no'] ?? '');
                $sheet->setCellValueByColumnAndRow(4, $r, $row['indikator']);
                $sheet->setCellValueByColumnAndRow(5, $r, $row['satuan']);
                $sheet->setCellValueByColumnAndRow(6, $r, $row['sasaran']);
                $c = 7;
                foreach ($kecs as $kn) {
                    $val = ($mode === 'template') ? null : (float)($row['targets'][$kn] ?? 0);
                    if ($val !== null) $sheet->setCellValueByColumnAndRow($c, $r, $val);
                    $c++;
                }
                $tot = ($mode === 'template') ? null : (float)$row['total'];
                if ($tot !== null) {
                    // TOTAL sebagai formula SUM agar mengikuti spreadsheet (kecuali total manual)
                    if ($row['total_manual'] === null || $row['total_manual'] === '') {
                        $c1 = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(7);
                        $c2 = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(6 + $nKec);
                        $sheet->setCellValueByColumnAndRow($colTotalIdx, $r, "=SUM({$c1}{$r}:{$c2}{$r})");
                    } else {
                        $sheet->setCellValueByColumnAndRow($colTotalIdx, $r, $tot);
                    }
                }
                $range = "A{$r}:{$lastCol}{$r}";
                $sheet->getStyle($range)->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
                $sheet->getStyle($range)->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER)->setWrapText(true);
                $sheet->getStyle("A{$r}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("C{$r}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("E{$r}:F{$r}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("G{$r}:{$lastCol}{$r}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
                $sheet->getStyle("B{$r}:D{$r}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
                $sheet->getRowDimension($r)->setRowHeight(30);
                $r++;
                $no++;
                $exampleGiven = true;
            }
            if ($mode === 'template') {
                // baris kosong untuk diisi admin (20 baris)
                for ($i = 0; $i < 20; $i++) {
                    $range = "A{$r}:{$lastCol}{$r}";
                    $sheet->getStyle($range)->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
                    $sheet->getStyle($range)->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER)->setWrapText(true);
                    $sheet->getRowDimension($r)->setRowHeight(30);
                    $r++;
                }
                $nr = $r + 1;
                $sheet->setCellValue("A{$nr}", 'PETUNJUK: Isi kolom Jenis Layanan, Sub-No (kosongkan untuk baris induk/wilayah), Indikator, Satuan, Sasaran, dan target 12 kecamatan. Kolom TOTAL boleh dikosongkan (otomatis = jumlah kecamatan) atau diisi bila TOTAL pada spreadsheet bukan hasil penjumlahan. JANGAN menambah kolom "kecamatan" bernama TOTAL. Baris 4-6 adalah contoh. Upload kembali via Admin → Kelola SPM → Import Excel.');
                $sheet->mergeCells("A{$nr}:{$lastCol}{$nr}");
                $sheet->getStyle("A{$nr}")->getAlignment()->setWrapText(true);
            }
            $sheet->setAutoFilter("A3:{$lastCol}3");
        }
        $ss->setActiveSheetIndex(0);
        return $ss;
    }
}
