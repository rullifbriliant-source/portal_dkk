<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header("Content-Type: application/json; charset=utf-8");
require_once "../config/database.php";

// ============================================================
// PARAMETER: kecamatan (nama/id), faskes (id_faskes opsional)
// Backward compatible: ?kecamatan=Baki tetap return data lama (data:[...])
// Baru: hierarki kecamatan → fasyankes → total_per_jenis + faskes[]
// ============================================================
$kecamatan = isset($_GET['kecamatan']) ? trim($_GET['kecamatan']) : '';
$faskesParam = isset($_GET['faskes']) ? trim($_GET['faskes']) : (isset($_GET['id_faskes']) ? trim($_GET['id_faskes']) : '');

$items = [];
$id_kecamatan = null;
$nama_kecamatan = null;

// Deteksi kolom spesialis (jika migrasi sudah dijalankan)
$hasSpesialisCol = false;
$hasSpesialisKecCol = false;
$chkSp = @mysqli_query($config, "SHOW COLUMNS FROM tbl_sdm_faskes LIKE 'id_spesialis'");
if ($chkSp && mysqli_num_rows($chkSp) > 0) $hasSpesialisCol = true;
$chkSp2 = @mysqli_query($config, "SHOW COLUMNS FROM tbl_sdm_kecamatan LIKE 'id_spesialis'");
if ($chkSp2 && mysqli_num_rows($chkSp2) > 0) $hasSpesialisKecCol = true;
$hasSpTable = false;
$chkTbl = @mysqli_query($config, "SHOW TABLES LIKE 'tbl_spesialis'");
if ($chkTbl && mysqli_num_rows($chkTbl) > 0) $hasSpTable = true;

// Deteksi kolom scope (pemisah struktur item per jenis faskes)
$hasScopeCol = false;
$chkScope = @mysqli_query($config, "SHOW COLUMNS FROM tbl_sdm_items LIKE 'scope'");
if ($chkScope && mysqli_num_rows($chkScope) > 0) $hasScopeCol = true;
function sdm_scope_for_jenis($jenis){
    $j = strtolower(trim((string)$jenis));
    if (strpos($j,'rumah sakit')!==false || $j==='rs' || strpos($j,'rsud')!==false) return 'rs';
    if (strpos($j,'puskesmas')!==false) return 'puskesmas';
    return 'lainnya';
}
// Peta include_in_total (aturan total resmi: sub-item tidak dihitung di
// Total A; B/C dihitung semua — sama seperti computeTotals admin & JS rekap).
// Tanpa ini portal double-count (induk + sub dijumlah semua).
$hasIncCol = false;
$chkInc = @mysqli_query($config, "SHOW COLUMNS FROM tbl_sdm_items LIKE 'include_in_total'");
if ($chkInc && mysqli_num_rows($chkInc) > 0) $hasIncCol = true;
$sdmIncMap = null;
function sdm_include_map(){
    global $config, $hasIncCol, $sdmIncMap;
    if ($sdmIncMap !== null) return $sdmIncMap;
    $sdmIncMap = [];
    if (!$hasIncCol) return $sdmIncMap;
    $q = @mysqli_query($config, "SELECT id, include_in_total FROM tbl_sdm_items");
    while ($q && ($r = mysqli_fetch_assoc($q))) $sdmIncMap[(int)$r['id']] = (int)$r['include_in_total'];
    return $sdmIncMap;
}
// Total resmi dari list item: A hanya include=1; B/C semua baris.
function sdm_official_totals($items){
    $inc = sdm_include_map();
    $t = ['Tenaga Kesehatan'=>0,'Asisten Tenaga Kesehatan'=>0,'Tenaga Penunjang'=>0];
    foreach ($items as $it) {
        $k = $it['kategori'] ?? 'Tenaga Kesehatan';
        if (!isset($t[$k])) $t[$k] = 0;
        if ($k === 'Tenaga Kesehatan' && !empty($inc) && isset($inc[(int)($it['id'] ?? 0)]) && $inc[(int)$it['id']] === 0) continue;
        $t[$k] += (int)($it['nilai'] ?? 0);
    }
    $t['grand'] = $t['Tenaga Kesehatan'] + $t['Asisten Tenaga Kesehatan'] + $t['Tenaga Penunjang'];
    return $t;
}
// Fragment filter item per jenis faskes + union item yang sudah punya data
// di faskes tsb (anti-hilang-data). $fid int, $alias alias tabel items.
function sdm_scope_sql($jenis, $fid, $alias='si'){
    global $hasScopeCol, $config;
    if (!$hasScopeCol) return '';
    $sc = sdm_scope_for_jenis($jenis);
    $fid = (int)$fid;
    return " AND ($alias.scope='' OR FIND_IN_SET('$sc',$alias.scope) OR $alias.id IN (SELECT id_profesi FROM tbl_sdm_faskes WHERE id_faskes=$fid AND aktif='Y'))";
}

// Resolve kecamatan
// Helper kategori totals for frontend ringkasan (A/B/C)
$kategoriLabels = ['Tenaga Kesehatan','Asisten Tenaga Kesehatan','Tenaga Penunjang'];
if ($kecamatan !== '') {
    $kecEsc = mysqli_real_escape_string($config, $kecamatan);
    // support id numerik atau nama
    if (ctype_digit($kecEsc)) {
        $kecQuery = mysqli_query($config, "SELECT id_kecamatan, nama_kecamatan FROM tbl_kecamatan WHERE id_kecamatan=".(int)$kecEsc." AND aktif='Y' LIMIT 1");
    } else {
        $kecQuery = mysqli_query($config, "SELECT id_kecamatan, nama_kecamatan FROM tbl_kecamatan WHERE LOWER(nama_kecamatan)=LOWER('$kecEsc') AND aktif='Y' LIMIT 1");
    }
    $kecRow = $kecQuery ? mysqli_fetch_assoc($kecQuery) : null;
    if ($kecRow) {
        $id_kecamatan = (int)$kecRow['id_kecamatan'];
        $nama_kecamatan = $kecRow['nama_kecamatan'];
    }
}

// Jika ada filter faskes spesifik — validasi faskes milik kecamatan (jika kecamatan juga disupply)
$id_faskes_filter = null;
if ($faskesParam !== '' && ctype_digit($faskesParam)) {
    $id_faskes_filter = (int)$faskesParam;
}

// Mode: jika kecamatan ditemukan → ambil dari tbl_sdm_faskes (baru) + fallback tbl_sdm_kecamatan (lama)
// Total konsisten: panel kanan = modal total = SUM(tbl_sdm_faskes) + SUM(tbl_sdm_kecamatan yang belum migrasi) atau MAX?
// Strategi: jika ada data di tbl_sdm_faskes untuk kecamatan itu → pakai itu sebagai source of truth per-fasyankes, total = SUM(tbl_sdm_faskes)
// Jika belum ada data faskes → fallback ke tbl_sdm_kecamatan (agregat lama) agar tidak 0 mendadak

if ($id_kecamatan !== null) {
    // cek apakah ada data faskes untuk kecamatan ini
    $cekF = mysqli_query($config, "SELECT COUNT(*) c FROM tbl_sdm_faskes WHERE id_kecamatan=$id_kecamatan AND aktif='Y'");
    $hasFaskesData = $cekF && (int)mysqli_fetch_assoc($cekF)['c'] > 0;

    if ($hasFaskesData) {
        // Total per jenis dari tbl_sdm_faskes
        $sqlJenis = "
            SELECT si.id, si.nama_item, si.kategori, COALESCE(SUM(sf.jumlah),0) AS nilai
            FROM tbl_sdm_items si
            LEFT JOIN tbl_sdm_faskes sf ON sf.id_profesi=si.id AND sf.id_kecamatan=$id_kecamatan AND sf.aktif='Y'
                " . ($id_faskes_filter ? " AND sf.id_faskes=$id_faskes_filter " : "") . "
            WHERE si.aktif='Y'
            GROUP BY si.id, si.nama_item, si.kategori, si.urutan
            ORDER BY si.urutan
        ";
        $q = mysqli_query($config, $sqlJenis);
        while ($row = mysqli_fetch_assoc($q)) {
            $items[] = ['id'=>(int)$row['id'], 'nama'=>$row['nama_item'], 'kategori'=>$row['kategori'], 'nilai'=>(int)$row['nilai']];
        }

        // Breakdown spesialis dokter (jika ada)
        $spesialisAgg = [];
        if ($hasSpesialisCol && $hasSpTable) {
            $sqlSp = "
                SELECT sp.id, sp.nama_spesialis, sp.kode, COALESCE(SUM(sf.jumlah),0) AS nilai
                FROM tbl_spesialis sp
                LEFT JOIN tbl_sdm_faskes sf ON sf.id_spesialis=sp.id AND sf.id_kecamatan=$id_kecamatan AND sf.aktif='Y'
                    " . ($id_faskes_filter ? " AND sf.id_faskes=$id_faskes_filter " : "") . "
                WHERE sp.aktif='Y'
                GROUP BY sp.id, sp.nama_spesialis, sp.kode, sp.urutan
                HAVING nilai > 0
                ORDER BY sp.urutan
            ";
            $qSp = mysqli_query($config, $sqlSp);
            if ($qSp) while ($rsp = mysqli_fetch_assoc($qSp)) {
                $spesialisAgg[] = ['id'=>(int)$rsp['id'], 'nama'=>$rsp['nama_spesialis'], 'kode'=>$rsp['kode'], 'nilai'=>(int)$rsp['nilai']];
            }
        }

        // Daftar faskes + per_jenis (+ per_spesialis)
        $faskesList = [];
        $sqlF = "SELECT f.id_faskes, f.nama_faskes, f.jenis, f.id_kecamatan FROM tbl_faskes f WHERE f.id_kecamatan=$id_kecamatan AND f.aktif='Y' ORDER BY f.jenis, f.nama_faskes";
        $qF = mysqli_query($config, $sqlF);
        while ($f = mysqli_fetch_assoc($qF)) {
            $fid = (int)$f['id_faskes'];
            if ($id_faskes_filter && $fid !== $id_faskes_filter) continue;
            // per jenis untuk faskes ini — SUM agar spesialis teragregasi ke Dokter
            // + filter scope jenis faskes (item RS tak tampil di Puskesmas dll).
            $perJenis = [];
            $scopeFragPJ = sdm_scope_sql($f['jenis'] ?? '', $fid, 'si');
            $sqlPJ = "SELECT si.id, si.nama_item, si.kategori, COALESCE(SUM(sf.jumlah),0) AS nilai FROM tbl_sdm_items si LEFT JOIN tbl_sdm_faskes sf ON sf.id_profesi=si.id AND sf.id_faskes=$fid AND sf.aktif='Y' WHERE si.aktif='Y'$scopeFragPJ GROUP BY si.id, si.nama_item, si.kategori, si.urutan ORDER BY si.urutan";
            $qPJ = mysqli_query($config, $sqlPJ);
            $totalF = 0;
            $incPJ = sdm_include_map();
            while ($pj = mysqli_fetch_assoc($qPJ)) {
                $perJenis[] = ['id'=>(int)$pj['id'], 'nama'=>$pj['nama_item'], 'kategori'=>$pj['kategori'], 'nilai'=>(int)$pj['nilai']];
                // Total resmi: sub-item (include=0) tidak dihitung
                $kPJ = $pj['kategori'] ?? 'Tenaga Kesehatan';
                if ($kPJ === 'Tenaga Kesehatan' && !empty($incPJ) && isset($incPJ[(int)$pj['id']]) && $incPJ[(int)$pj['id']] === 0) continue;
                $totalF += (int)$pj['nilai'];
            }
            // per spesialis untuk faskes ini
            $perSpesialis = [];
            if ($hasSpesialisCol && $hasSpTable) {
                $sqlPS = "SELECT sp.id, sp.nama_spesialis, sp.kode, COALESCE(sf.jumlah,0) AS nilai FROM tbl_spesialis sp LEFT JOIN tbl_sdm_faskes sf ON sf.id_spesialis=sp.id AND sf.id_faskes=$fid AND sf.aktif='Y' WHERE sp.aktif='Y' ORDER BY sp.urutan";
                $qPS = mysqli_query($config, $sqlPS);
                if ($qPS) while ($ps = mysqli_fetch_assoc($qPS)) {
                    if ((int)$ps['nilai'] > 0) $perSpesialis[] = ['id'=>(int)$ps['id'], 'nama'=>$ps['nama_spesialis'], 'kode'=>$ps['kode'], 'nilai'=>(int)$ps['nilai']];
                }
            }
            $faskesList[] = [
                'id_faskes'=>$fid,
                'nama_faskes'=>$f['nama_faskes'],
                'jenis'=>$f['jenis'],
                'total'=>$totalF,
                'per_jenis'=>$perJenis,
                'per_spesialis'=>$perSpesialis
            ];
        }
        // Total resmi (tanpa double-count sub-item) — sinkron dgn admin
        $katTotals = sdm_official_totals($items);
        $total = $katTotals['grand'];
        // Hitung belum_ditentukan: selisih tbl_sdm yang id_faskes NULL? untuk info
        $belum = 0;
        $qBelum = mysqli_query($config, "SELECT COUNT(*) c FROM tbl_sdm WHERE id_kecamatan=$id_kecamatan AND id_faskes IS NULL AND aktif='Y'");
        if ($qBelum) $belum = (int)mysqli_fetch_assoc($qBelum)['c'];

        $resp = [
            "status"=>true,
            "kecamatan"=>$nama_kecamatan,
            "id_kecamatan"=>$id_kecamatan,
            "filter_faskes"=>$id_faskes_filter,
            "data"=>$items,               // legacy key untuk renderSdm lama
            "total"=>$total,
            "total_per_jenis"=>$items,
            "kategori_totals"=>$katTotals,
            "faskes"=>$faskesList,
            "belum_ditentukan"=>$belum,
            "source"=>"tbl_sdm_faskes"
        ];
        if ($hasSpTable) $resp["spesialis"] = $spesialisAgg;
        echo json_encode($resp);
        exit;
    } else {
        // Fallback ke agregat lama tbl_sdm_kecamatan (agar tidak pecah data existing)
        $sql = "
            SELECT si.id, si.nama_item, si.kategori, COALESCE(sk.jumlah, 0) AS nilai
            FROM tbl_sdm_items si
            LEFT JOIN tbl_sdm_kecamatan sk ON sk.id_item=si.id AND sk.id_kecamatan=$id_kecamatan
            WHERE si.aktif='Y'
            ORDER BY si.urutan
        ";
        $query = mysqli_query($config, $sql);
        while ($row = mysqli_fetch_assoc($query)) {
            $items[] = ['id'=>(int)$row['id'],'nama'=>$row['nama_item'],'kategori'=>$row['kategori'],'nilai'=>(int)$row['nilai']];
        }
        $total = array_sum(array_column($items, 'nilai'));
        // faskes kosong karena belum ada data
        $faskesList = [];
        $sqlF = "SELECT id_faskes, nama_faskes, jenis FROM tbl_faskes WHERE id_kecamatan=$id_kecamatan AND aktif='Y' ORDER BY jenis, nama_faskes";
        $qF = mysqli_query($config, $sqlF);
        while ($f = mysqli_fetch_assoc($qF)) {
            $faskesList[] = ['id_faskes'=>(int)$f['id_faskes'],'nama_faskes'=>$f['nama_faskes'],'jenis'=>$f['jenis'],'total'=>0,'per_jenis'=>[]];
        }
        echo json_encode([
            "status"=>true,
            "kecamatan"=>$nama_kecamatan,
            "id_kecamatan"=>$id_kecamatan,
            "data"=>$items,
            "total"=>$total,
            "total_per_jenis"=>$items,
            "faskes"=>$faskesList,
            "source"=>"tbl_sdm_kecamatan_fallback"
        ]);
        exit;
    }
} else if ($kecamatan === '' && $id_faskes_filter) {
    // hanya filter faskes (mis. api/get_sdm.php?faskes=7)
    $fid = $id_faskes_filter;
    $qf = mysqli_query($config, "SELECT f.id_faskes, f.nama_faskes, f.jenis, f.id_kecamatan, k.nama_kecamatan FROM tbl_faskes f LEFT JOIN tbl_kecamatan k ON k.id_kecamatan=f.id_kecamatan WHERE f.id_faskes=$fid AND f.aktif='Y' LIMIT 1");
    $frow = $qf ? mysqli_fetch_assoc($qf) : null;
    if ($frow) {
        $scopeFragF = sdm_scope_sql($frow['jenis'] ?? '', $fid, 'si');
        $sqlPJ = "SELECT si.id, si.nama_item, si.kategori, COALESCE(SUM(sf.jumlah),0) AS nilai FROM tbl_sdm_items si LEFT JOIN tbl_sdm_faskes sf ON sf.id_profesi=si.id AND sf.id_faskes=$fid AND sf.aktif='Y' WHERE si.aktif='Y'$scopeFragF GROUP BY si.id, si.nama_item, si.kategori, si.urutan ORDER BY si.urutan";
        $qPJ = mysqli_query($config, $sqlPJ);
        while ($pj = mysqli_fetch_assoc($qPJ)) $items[] = ['id'=>(int)$pj['id'],'nama'=>$pj['nama_item'],'kategori'=>$pj['kategori'],'nilai'=>(int)$pj['nilai']];
        $katTotalsF = sdm_official_totals($items);
        // spesialis untuk faskes ini
        $spFaskes = [];
        if ($hasSpesialisCol && $hasSpTable) {
            $sqlSpF = "SELECT sp.id, sp.nama_spesialis, sp.kode, COALESCE(sf.jumlah,0) AS nilai FROM tbl_spesialis sp LEFT JOIN tbl_sdm_faskes sf ON sf.id_spesialis=sp.id AND sf.id_faskes=$fid AND sf.aktif='Y' WHERE sp.aktif='Y' ORDER BY sp.urutan";
            $qSpF = mysqli_query($config, $sqlSpF);
            if ($qSpF) while ($rs = mysqli_fetch_assoc($qSpF)) if ((int)$rs['nilai']>0) $spFaskes[] = ['id'=>(int)$rs['id'],'nama'=>$rs['nama_spesialis'],'kode'=>$rs['kode'],'nilai'=>(int)$rs['nilai']];
        }
        $respF = [
            "status"=>true,
            "kecamatan"=>$frow['nama_kecamatan'],
            "id_kecamatan"=>(int)$frow['id_kecamatan'],
            "filter_faskes"=>$fid,
            "data"=>$items,
            "total"=>$katTotalsF['grand'],
            "total_per_jenis"=>$items,
            "kategori_totals"=>['Tenaga Kesehatan'=>$katTotalsF['Tenaga Kesehatan'],'Asisten Tenaga Kesehatan'=>$katTotalsF['Asisten Tenaga Kesehatan'],'Tenaga Penunjang'=>$katTotalsF['Tenaga Penunjang']],
            "faskes"=>[[
                'id_faskes'=>$fid,
                'nama_faskes'=>$frow['nama_faskes'],
                'jenis'=>$frow['jenis'],
                'total'=>$katTotalsF['grand'],
                'per_jenis'=>array_map(function($x){return ['nama'=>$x['nama'],'nilai'=>$x['nilai']];}, $items),
                'per_spesialis'=>$spFaskes
            ]],
            "source"=>"tbl_sdm_faskes"
        ];
        if ($hasSpTable) $respF["spesialis"] = $spFaskes;
        echo json_encode($respF);
        exit;
    }
}

// Default: TOTAL kabupaten — KORELASI: sum per kecamatan (hybrid faskes/kecamatan)
// Kabupaten total = SUM( per kecamatan: jika ada data faskes → SUM faskes, else SUM kecamatan ) → berkorelasi
$items = [];
$hybridSql = "
    SELECT si.id, si.nama_item, si.kategori, si.urutan,
           COALESCE(sf2.sf_total,0) + COALESCE(sk2.sk_total,0) AS nilai
    FROM tbl_sdm_items si
    LEFT JOIN (
        SELECT id_profesi, SUM(jumlah) sf_total FROM tbl_sdm_faskes WHERE aktif='Y' GROUP BY id_profesi
    ) sf2 ON sf2.id_profesi = si.id
    LEFT JOIN (
        SELECT id_item, SUM(jumlah) sk_total FROM tbl_sdm_kecamatan sk
        WHERE sk.aktif='Y' AND sk.id_kecamatan NOT IN (SELECT id_kecamatan FROM tbl_sdm_faskes WHERE aktif='Y' GROUP BY id_kecamatan)
        GROUP BY id_item
    ) sk2 ON sk2.id_item = si.id
    WHERE si.aktif='Y'
    ORDER BY si.urutan
";
$qHybrid = mysqli_query($config, $hybridSql);
$hybridHasData = false;
if ($qHybrid) {
    while ($row = mysqli_fetch_assoc($qHybrid)) {
        $val = (int)$row['nilai'];
        if ($val > 0) $hybridHasData = true;
        $items[] = ['id'=>(int)$row['id'],'nama'=>$row['nama_item'],'kategori'=>$row['kategori'],'nilai'=>$val];
    }
}
// Fallback ke legacy tbl_sdm_items.nilai jika hybrid masih 0 semua (belum ada data kecamatan/faskes)
if (!$hybridHasData) {
    $items = [];
    $sqlLegacy = "SELECT id, nama_item, kategori, nilai FROM tbl_sdm_items WHERE aktif='Y' ORDER BY urutan";
    $qLeg = mysqli_query($config, $sqlLegacy);
    while ($row = mysqli_fetch_assoc($qLeg)) $items[] = ['id'=>(int)$row['id'],'nama'=>$row['nama_item'],'kategori'=>$row['kategori'],'nilai'=>(int)$row['nilai']];
    $sourceKab = "tbl_sdm_items_legacy";
} else {
    $sourceKab = "correlated_hybrid";
}
if (count($items)>0) {
    $katKab = sdm_official_totals($items);
    $respAll = ["status"=>true,"scope"=>"kabupaten","kecamatan"=>null,"data"=>$items,"total"=>$katKab['grand'],"total_per_jenis"=>$items,"kategori_totals"=>['Tenaga Kesehatan'=>$katKab['Tenaga Kesehatan'],'Asisten Tenaga Kesehatan'=>$katKab['Asisten Tenaga Kesehatan'],'Tenaga Penunjang'=>$katKab['Tenaga Penunjang']],"source"=>$sourceKab];
    if ($hasSpesialisCol && $hasSpTable) {
        $qSpAll = mysqli_query($config, "SELECT sp.id, sp.nama_spesialis, sp.kode, COALESCE(SUM(sf.jumlah),0) AS nilai FROM tbl_spesialis sp LEFT JOIN tbl_sdm_faskes sf ON sf.id_spesialis=sp.id AND sf.aktif='Y' WHERE sp.aktif='Y' GROUP BY sp.id, sp.nama_spesialis, sp.kode, sp.urutan HAVING nilai>0 ORDER BY sp.urutan");
        $spAll = [];
        if ($qSpAll) while ($rs=mysqli_fetch_assoc($qSpAll)) $spAll[] = ['id'=>(int)$rs['id'],'nama'=>$rs['nama_spesialis'],'kode'=>$rs['kode'],'nilai'=>(int)$rs['nilai']];
        $respAll["spesialis"] = $spAll;
    }
    echo json_encode($respAll);
} else {
    echo json_encode(["status"=>true,"scope"=>"kabupaten","kecamatan"=>null,"data"=>[['nama'=>'Dokter','nilai'=>0],['nama'=>'Perawat','nilai'=>0],['nama'=>'Bidan','nilai'=>0],['nama'=>'Nakes Lainnya','nilai'=>0]]]);
}
