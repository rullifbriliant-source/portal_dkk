<?php
require_once '../config.php';
requireLogin();
require_once __DIR__ . '/../../vendor/autoload.php';
$tab = $_GET['tab'] ?? 'faskes';
if(!in_array($tab, ['items','faskes'], true)) $tab='faskes';
$msg = $_GET['msg'] ?? '';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx as ReaderXlsx;
use PhpOffice\PhpSpreadsheet\Reader\Xls as ReaderXls;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

// ---------- SCOPE JENIS FASKES ----------
// Kolom tbl_sdm_items.scope: '' = semua jenis; selain itu CSV subset dari
// {rs, puskesmas, lainnya}. Item tampil di suatu faskes bila scope cocok
// ATAU faskes itu sudah punya baris data pada item tersebut (anti-hilang-data).
function scopeForJenis($jenis){
    $j=strtolower(trim((string)$jenis));
    if(strpos($j,'rumah sakit')!==false || $j==='rs' || strpos($j,'rs ')===0 || strpos($j,'rsud')!==false) return 'rs';
    if(strpos($j,'puskesmas')!==false) return 'puskesmas';
    return 'lainnya';
}
function scopeMatch($itemScope, $scope){
    $s=trim((string)$itemScope);
    if($s==='') return true;
    $parts=array_map('trim', explode(',', strtolower($s)));
    return in_array($scope, $parts, true);
}
// Normalisasi input checkbox scope[] -> '' (semua) atau CSV subset valid.
function parseScopeInput($raw){
    $allowed=['rs','puskesmas','lainnya'];
    if(!is_array($raw)) return '';
    $out=[];
    foreach($raw as $v){
        $v=strtolower(trim((string)$v));
        if(in_array($v,$allowed,true) && !in_array($v,$out,true)) $out[]=$v;
    }
    return implode(',',$out);
}
// Parent harus terlihat di semua cakupan anak (semua='' mencakup semua).
function scopeCovers($parentScope, $childScope){
    $p=trim((string)$parentScope);
    $c=trim((string)$childScope);
    if($p==='') return true;
    if($c==='') return false;
    $pp=array_map('trim', explode(',', strtolower($p)));
    foreach(array_map('trim', explode(',', strtolower($c))) as $t){
        if(!in_array($t,$pp,true)) return false;
    }
    return true;
}
// Urutan tampil hierarkis: anak SELALU tepat setelah induknya.
// Nilai `urutan` hanya menentukan posisi antar-saudara (sibling) dan antar
// induk top-level, BUKAN posisi global flat. Ini memperbaiki kasus anak
// "terlempar" jauh dari induknya (mis. anak urutan=1 milik induk urutan=13).
function sdmkParentId($it){
    $p = $it['parent_id'] ?? null;
    if($p===null || $p==='' || (int)$p===0) $p = $it['id_parent'] ?? null;
    if($p===null || $p==='' || (int)$p===0) return 0;
    return (int)$p;
}
function sdmkKategoriRank($kat){
    static $m=['Tenaga Kesehatan'=>0,'Asisten Tenaga Kesehatan'=>1,'Tenaga Penunjang'=>2];
    return $m[$kat] ?? 9;
}
function sdmkCmpSiblings($a,$b){
    $ua=(int)($a['urutan'] ?? 0); $ub=(int)($b['urutan'] ?? 0);
    if($ua!==$ub) return $ua<=>$ub;
    return ((int)$a['id'])<=>((int)$b['id']);
}
// Satu kelompok kategori: top-level urut (urutan,id), tiap induk langsung
// diikuti anak-anaknya urut (urutan,id), rekursif untuk kedalaman >1.
// Anak yatim (parent tidak ada di kelompok ini) di-append di akhir.
function orderSdmGroupHierarchical($list){
    if(empty($list)) return $list;
    $byId=[]; foreach($list as $it) $byId[(int)$it['id']]=$it;
    $tops=[]; $children=[]; $orphans=[];
    foreach($list as $it){
        $pid=sdmkParentId($it);
        if($pid===0) $tops[]=$it;
        elseif(isset($byId[$pid])) $children[$pid][]=$it;
        else $orphans[]=$it;
    }
    usort($tops, 'sdmkCmpSiblings');
    foreach($children as &$ch) usort($ch, 'sdmkCmpSiblings');
    unset($ch);
    usort($orphans, 'sdmkCmpSiblings');
    $out=[];
    $emit=null;
    $emit=function($node) use (&$emit, &$out, $children){
        $out[]=$node;
        $cid=(int)$node['id'];
        if(!empty($children[$cid])) foreach($children[$cid] as $ch) $emit($ch);
    };
    foreach($tops as $t) $emit($t);
    foreach($orphans as $o) $emit($o);
    return $out;
}
// Multi-kategori: kelompokkan per kategori (urutan A/B/C), tiap kelompok
// diurut hierarkis. Hasilnya stabil & deterministik antar reload.
function orderSdmItemsHierarchical($items){
    if(empty($items)) return $items;
    $byKat=[];
    foreach($items as $it) $byKat[$it['kategori'] ?? ''][]=$it;
    uksort($byKat, function($a,$b){ return sdmkKategoriRank($a)<=>sdmkKategoriRank($b); });
    $out=[];
    foreach($byKat as $list) $out=array_merge($out, orderSdmGroupHierarchical($list));
    return $out;
}
// Urutan default yang masuk akal relatif terhadap kelompoknya: anak =
// MAX sibling + 1 (di bawah induknya), top-level = MAX se-kategori + 1.
// Nilai eksplisit (>0) dari form tetap dihormati (edit manual).
function sdmkNextUrutan($config, $kategori, $parentId){
    if($parentId!==null){
        $pid=(int)$parentId;
        $st=$config->prepare("SELECT MAX(urutan) m FROM tbl_sdm_items WHERE parent_id=? OR id_parent=?");
        if($st){
            $st->bind_param("ii",$pid,$pid);
            $st->execute();
            $r=$st->get_result()->fetch_assoc();
            if($r && $r['m']!==null) return ((int)$r['m'])+1;
        }
    }
    $st=$config->prepare("SELECT MAX(urutan) m FROM tbl_sdm_items WHERE kategori=?");
    if($st){
        $st->bind_param("s",$kategori);
        $st->execute();
        $r=$st->get_result()->fetch_assoc();
        if($r && $r['m']!==null) return ((int)$r['m'])+1;
    }
    $q=$config->query("SELECT MAX(urutan) m FROM tbl_sdm_items");
    if($q && ($r=$q->fetch_assoc()) && $r['m']!==null) return ((int)$r['m'])+1;
    return 1;
}
function getItems($config, $scope=null) {
    $items=[];
    // Kolom scope mungkin belum ada di install lama -> fallback tanpa filter.
    $hasScope=false;
    $chk=@$config->query("SHOW COLUMNS FROM tbl_sdm_items LIKE 'scope'");
    if($chk && $chk->num_rows>0) $hasScope=true;
    // Tie-breaker `, id` agar urutan deterministik saat nilai `urutan`
    // kembar (MySQL tidak menjamin urutan baris tanpa tie-breaker).
    // Urutan final induk-anak dirapikan oleh orderSdmItemsHierarchical().
    $sel = $hasScope
        ? "SELECT id, nama_item, kategori, scope, parent_id, id_parent, urutan, is_total_row, include_in_total FROM tbl_sdm_items WHERE aktif='Y' ORDER BY FIELD(kategori,'Tenaga Kesehatan','Asisten Tenaga Kesehatan','Tenaga Penunjang'), urutan, id"
        : "SELECT id, nama_item, kategori, parent_id, id_parent, urutan, is_total_row, include_in_total FROM tbl_sdm_items WHERE aktif='Y' ORDER BY FIELD(kategori,'Tenaga Kesehatan','Asisten Tenaga Kesehatan','Tenaga Penunjang'), urutan, id";
    $q=$config->query($sel);
    while($r=$q->fetch_assoc()){
        if(!isset($r['scope'])) $r['scope']='';
        $items[]=$r;
    }
    if($scope===null || !$hasScope) return orderSdmItemsHierarchical($items);
    // Filter per scope + cuti anak yatim (parent tak tampil -> anak tak tampil).
    $byId=[];
    foreach($items as $it) $byId[$it['id']]=$it;
    $out=[];
    foreach($items as $it){
        if(!scopeMatch($it['scope'] ?? '', $scope)) continue;
        $pid = $it['parent_id'] ?? $it['id_parent'];
        if($pid!==null && $pid!=='' && (int)$pid!==0){
            if(!isset($byId[(int)$pid])) continue;
            if(!scopeMatch($byId[(int)$pid]['scope'] ?? '', $scope)) continue;
        }
        $out[]=$it;
    }
    return orderSdmItemsHierarchical($out);
}
// Item yang sudah punya baris data di faskes tsb (untuk union anti-hilang-data).
function faskesItemIds($config, $id_faskes){
    $ids=[];
    $stmt=$config->prepare("SELECT DISTINCT id_profesi FROM tbl_sdm_faskes WHERE id_faskes=? AND aktif='Y'");
    if(!$stmt) return $ids;
    $stmt->bind_param("i",$id_faskes);
    $stmt->execute();
    $res=$stmt->get_result();
    while($res && ($r=$res->fetch_assoc())) $ids[(int)$r['id_profesi']]=true;
    return $ids;
}
// Daftar item tampil untuk faskes: scope cocok UNION punya-data.
function getItemsForFaskes($config, $id_faskes, $jenis){
    $scope=scopeForJenis($jenis);
    $scoped=getItems($config, $scope);
    $have=faskesItemIds($config, $id_faskes);
    if(empty($have)) return $scoped;
    $ids=[]; foreach($scoped as $it) $ids[$it['id']]=true;
    $missing=array_diff(array_keys($have), array_keys($ids));
    if(empty($missing)) return $scoped;
    $all=getItems($config);
    $byId=[]; foreach($all as $it) $byId[$it['id']]=$it;
    foreach($missing as $mid){
        if(isset($byId[$mid])) $scoped[]=$byId[$mid];
    }
    // Susun ulang SEMUA $scoped hierarkis (induk lalu anak-anaknya) dengan
    // tie-breaker deterministik (urutan,id) — bukan sekadar append di akhir.
    return orderSdmItemsHierarchical($scoped);
}
function getFaskesList($config){
    $list=[];
    $q=$config->query("SELECT f.id_faskes, f.nama_faskes, f.jenis, k.nama_kecamatan FROM tbl_faskes f LEFT JOIN tbl_kecamatan k ON k.id_kecamatan=f.id_kecamatan WHERE f.aktif='Y' ORDER BY FIELD(f.jenis,'Puskesmas','Pustu','Rumah Sakit','Klinik','Poskesdes','Apotek','Laboratorium'), f.nama_faskes");
    while($r=$q->fetch_assoc()) $list[]=$r;
    return $list;
}
function labelJenisFaskes($jenis){
    $map=[
        'Puskesmas'=>'PUSKESMAS',
        'Pustu'=>'PUSTU',
        'Rumah Sakit'=>'RUMAH SAKIT',
        'Klinik'=>'KLINIK',
        'Poskesdes'=>'POSKESDES',
        'Apotek'=>'APOTEK',
        'Laboratorium'=>'LABORATORIUM',
    ];
    return $map[$jenis] ?? strtoupper($jenis);
}
function normalizeNama($s){
    $s = trim((string)$s);
    $s = preg_replace('/\s+/', ' ', $s);
    $s = strtolower($s);
    $s = preg_replace('/^[\-\•\*\s]+/', '', $s);
    $s = preg_replace('/^[a-z]\.\s*/', '', $s);
    $s = preg_replace('/^\d+[\.\)]\s*/', '', $s);
    $s = trim($s);
    $s = preg_replace('/\s+/', ' ', $s);
    return $s;
}
function computeTotals($config, $id_faskes, $items=null){
    // Total A = SUM where kategori='Tenaga Kesehatan' AND include_in_total=1, Total B/C accordingly per spec
    $totals = ['A'=>0,'B'=>0,'C'=>0,'grand'=>0];
    // Fetch items to know include_in_total per id
    if($items===null) $items = getItems($config);
    $includeMap = [];
    foreach($items as $it){ $includeMap[$it['id']] = (int)$it['include_in_total']; }
    // Fetch aggregated per profesi
    $stmt = $config->prepare("SELECT id_profesi, SUM(jumlah) as tot FROM tbl_sdm_faskes WHERE id_faskes=? AND aktif='Y' GROUP BY id_profesi");
    $stmt->bind_param("i", $id_faskes);
    $stmt->execute();
    $res=$stmt->get_result();
    $perProf=[];
    while($row=$res->fetch_assoc()) $perProf[$row['id_profesi']]=(int)$row['tot'];
    foreach($items as $it){
        $pid=$it['id'];
        $val=$perProf[$pid] ?? 0;
        if($it['kategori']==='Tenaga Kesehatan'){
            if((int)$it['include_in_total']===1) $totals['A'] += $val;
        } elseif($it['kategori']==='Asisten Tenaga Kesehatan'){
            // per spec Total B = SUM semua baris asisten (but currently all include=1, so same)
            $totals['B'] += $val;
        } elseif($it['kategori']==='Tenaga Penunjang'){
            $totals['C'] += $val;
        }
    }
    $totals['grand'] = $totals['A'] + $totals['B'] + $totals['C'];
    return $totals;
}

// ---------- HANDLE POST MASTER ITEMS ----------
if($_SERVER['REQUEST_METHOD']==='POST' && in_array($_POST['action'] ?? '', ['add_item','edit_item','delete_item'])){
    $act0 = $_POST['action'];
    if($act0==='add_item'){
        $nama = trim($_POST['nama_item'] ?? '');
        $kategori = $_POST['kategori'] ?? 'Tenaga Kesehatan';
        $allowedKat = ['Tenaga Kesehatan','Asisten Tenaga Kesehatan','Tenaga Penunjang'];
        if (!in_array($kategori, $allowedKat, true)) $kategori = 'Tenaga Kesehatan';
        $id_parent = !empty($_POST['id_parent']) ? (int)$_POST['id_parent'] : null;
        $urutan = (int)($_POST['urutan'] ?? 0);
        $scope = parseScopeInput($_POST['scope'] ?? null);
        if ($nama !== '') {
            // UNIQUE is now (nama_item,kategori)
            $stmt = $config->prepare("SELECT id FROM tbl_sdm_items WHERE nama_item=? AND kategori=? LIMIT 1");
            $stmt->bind_param("ss", $nama, $kategori);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows === 0) {
                // validate parent + cakupan parent harus mencakup anak
                if ($id_parent !== null) {
                    $chk = $config->prepare("SELECT id, scope FROM tbl_sdm_items WHERE id=? AND aktif='Y' LIMIT 1");
                    $chk->bind_param("i", $id_parent);
                    $chk->execute();
                    $prow=$chk->get_result()->fetch_assoc();
                    if (!$prow) { $id_parent = null; }
                    elseif (!scopeCovers($prow['scope'] ?? '', $scope)) { item_redirect('parent_scope'); }
                }
                $is_total = 0;
                $include = $id_parent === null ? 1 : 0;
                // Auto-urutan cerdas: kosong/0 -> menempel di kelompoknya
                // (sibling+1 / kategori+1), bukan increment global.
                if($urutan<=0) $urutan=sdmkNextUrutan($config, $kategori, $id_parent);
                if ($id_parent === null) {
                    $stmt2 = $config->prepare("INSERT INTO tbl_sdm_items (nama_item, kategori, scope, parent_id, id_parent, urutan, is_total_row, include_in_total, aktif) VALUES (?, ?, ?, NULL, NULL, ?, ?, ?, 'Y')");
                    $stmt2->bind_param("sssiii", $nama, $kategori, $scope, $urutan, $is_total, $include);
                } else {
                    $stmt2 = $config->prepare("INSERT INTO tbl_sdm_items (nama_item, kategori, scope, parent_id, id_parent, urutan, is_total_row, include_in_total, aktif) VALUES (?, ?, ?, ?, ?, ?, ?, 'Y')");
                    $stmt2->bind_param("sssiiiii", $nama, $kategori, $scope, $id_parent, $id_parent, $urutan, $is_total, $include);
                }
                $ok=$stmt2->execute();
                if(!$ok){ item_redirect('error'); }
                item_redirect('added');
            } else { item_redirect('exists'); }
        }
        item_redirect('invalid');
    }
    if($act0==='edit_item'){
        $id = (int)($_POST['id'] ?? 0);
        $nama = trim($_POST['nama_item'] ?? '');
        $kategori = $_POST['kategori'] ?? 'Tenaga Kesehatan';
        $allowedKat = ['Tenaga Kesehatan','Asisten Tenaga Kesehatan','Tenaga Penunjang'];
        if (!in_array($kategori, $allowedKat, true)) $kategori = 'Tenaga Kesehatan';
        $id_parent = $_POST['id_parent'] ?? '';
        $id_parent = $id_parent === '' ? null : (int)$id_parent;
        $urutan = (int)($_POST['urutan'] ?? 0);
        $aktif = ($_POST['aktif'] ?? 'Y') === 'N' ? 'N' : 'Y';
        $scope = parseScopeInput($_POST['scope'] ?? null);
        if ($id && $nama !== '') {
            if ($id_parent === $id) $id_parent = null;
            if ($id_parent !== null) {
                $chk = $config->prepare("SELECT id, scope FROM tbl_sdm_items WHERE id=? AND aktif='Y' LIMIT 1");
                $chk->bind_param("i", $id_parent);
                $chk->execute();
                $prow=$chk->get_result()->fetch_assoc();
                if (!$prow) { $id_parent = null; }
                elseif (!scopeCovers($prow['scope'] ?? '', $scope)) { item_redirect('parent_scope'); }
            }
            $include = $id_parent === null ? 1 : 0;
            // check duplicate (nama,kategori) except self
            $dup=$config->prepare("SELECT id FROM tbl_sdm_items WHERE nama_item=? AND kategori=? AND id<>? LIMIT 1");
            $dup->bind_param("ssi", $nama, $kategori, $id);
            $dup->execute();
            $dup->store_result();
            if($dup->num_rows>0){ item_redirect('exists'); }
            if ($id_parent === null) {
                $stmt = $config->prepare("UPDATE tbl_sdm_items SET nama_item=?, kategori=?, scope=?, parent_id=NULL, id_parent=NULL, urutan=?, include_in_total=?, aktif=? WHERE id=?");
                $stmt->bind_param("sssiisi", $nama, $kategori, $scope, $urutan, $include, $aktif, $id);
            } else {
                $stmt = $config->prepare("UPDATE tbl_sdm_items SET nama_item=?, kategori=?, scope=?, parent_id=?, id_parent=?, urutan=?, include_in_total=?, aktif=? WHERE id=?");
                $stmt->bind_param("sssiiiisi", $nama, $kategori, $scope, $id_parent, $id_parent, $urutan, $include, $aktif, $id);
            }
            $stmt->execute();
            item_redirect('updated');
        }
        item_redirect('invalid');
    }
    if($act0==='delete_item'){
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            // PROTEKSI HAPUS: tolak bila item masih punya sub-item aktif atau
            // sudah ada data angka (>0) di faskes. Pencegah hapus tak sengaja
            // saat testing (kasus: Dokter Umum, Sp.A, Dokter Spesialis, dst).
            $nm='(item)';
            $gq=$config->prepare("SELECT nama_item FROM tbl_sdm_items WHERE id=? LIMIT 1");
            if($gq){ $gq->bind_param("i",$id); $gq->execute(); $gr=$gq->get_result()->fetch_assoc(); if($gr) $nm=$gr['nama_item']; }
            $nChild=0; $nFaskes=0;
            $cq=$config->prepare("SELECT COUNT(*) c FROM tbl_sdm_items WHERE parent_id=? AND aktif='Y'");
            if($cq){ $cq->bind_param("i",$id); $cq->execute(); $cr=$cq->get_result()->fetch_assoc(); $nChild=(int)($cr['c'] ?? 0); }
            $fq=$config->prepare("SELECT COUNT(*) c FROM (SELECT id_faskes FROM tbl_sdm_faskes WHERE id_profesi=? AND aktif='Y' GROUP BY id_faskes HAVING SUM(jumlah)>0) t");
            if($fq){ $fq->bind_param("i",$id); $fq->execute(); $fr=$fq->get_result()->fetch_assoc(); $nFaskes=(int)($fr['c'] ?? 0); }
            if($nChild>0 || $nFaskes>0){
                if(session_status()===PHP_SESSION_NONE) session_start();
                $bits=[];
                if($nChild>0) $bits[]="$nChild sub-item aktif";
                if($nFaskes>0) $bits[]="data di $nFaskes faskes";
                $_SESSION['protect_info']="Hapus '$nm' DITOLAK (" . implode(' + ', $bits) . "). Kosongkan/reset datanya dan nonaktifkan sub-itemnya dulu bila memang ingin menghapus.";
                item_redirect('protected');
            }
            $stmt = $config->prepare("UPDATE tbl_sdm_items SET aktif='N' WHERE id=?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
        }
        item_redirect('deleted');
    }
}

// ---------- HANDLE EXPORT / TEMPLATE BEFORE HTML ----------
$action = $_GET['action'] ?? $_POST['action'] ?? '';
$id_faskes_req = (int)($_GET['id_faskes'] ?? $_POST['id_faskes'] ?? 0);

if (in_array($action, ['template','export'])) {
    if (!$id_faskes_req) { header("Location: sdmk.php?msg=need_faskes"); exit; }
    $stmt=$config->prepare("SELECT f.id_faskes, f.kode_faskes, f.nama_faskes, f.jenis, f.id_kecamatan, k.nama_kecamatan FROM tbl_faskes f LEFT JOIN tbl_kecamatan k ON k.id_kecamatan=f.id_kecamatan WHERE f.id_faskes=? AND f.aktif='Y' LIMIT 1");
    $stmt->bind_param("i",$id_faskes_req);
    $stmt->execute();
    $faskes=$stmt->get_result()->fetch_assoc();
    if(!$faskes){ die("Fasyankes tidak ditemukan"); }
    // Template/export hanya memuat item sesuai jenis faskes (+ yang sudah ada datanya).
    $items=getItemsForFaskes($config, $id_faskes_req, $faskes['jenis']);
    $dataMap=[];
    if($action==='export'){
        $stmt2=$config->prepare("SELECT id_profesi, SUM(asn_l) as asn_l, SUM(asn_p) as asn_p, SUM(nonasn_l) as nonasn_l, SUM(nonasn_p) as nonasn_p, SUM(jumlah) as jumlah FROM tbl_sdm_faskes WHERE id_faskes=? AND aktif='Y' GROUP BY id_profesi");
        $stmt2->bind_param("i",$id_faskes_req);
        $stmt2->execute();
        $res=$stmt2->get_result();
        while($row=$res->fetch_assoc()) $dataMap[$row['id_profesi']]=$row;
    }
    $labelJenis = labelJenisFaskes($faskes['jenis']);
    $ss=new Spreadsheet();
    $sheet=$ss->getActiveSheet();
    $sheet->setTitle('SDMK');
    $title="DATA KETERSEDIAAN SDM KESEHATAN DAN TENAGA PENUNJANG DI ".$labelJenis." ".strtoupper($faskes['nama_faskes'])." TAHUN ".date('Y');
    $sheet->mergeCells('A1:H1');
    $sheet->setCellValue('A1',$title);
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(11);
    $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->setCellValue('A2',$faskes['jenis'].': '.$faskes['nama_faskes'].' | Kecamatan: '.$faskes['nama_kecamatan']);
    $sheet->mergeCells('A2:H2');
    $sheet->getStyle('A2')->getFont()->setItalic(true)->setSize(9);
    $sheet->setCellValue('A3','Kode Faskes');
    $sheet->setCellValue('B3','No');
    $sheet->setCellValue('C3','Jenis SDM');
    $sheet->setCellValue('D3','ASN');
    $sheet->setCellValue('F3','Non ASN');
    $sheet->setCellValue('H3','Jumlah');
    $sheet->mergeCells('D3:E3');
    $sheet->mergeCells('F3:G3');
    $sheet->setCellValue('D4','L');
    $sheet->setCellValue('E4','P');
    $sheet->setCellValue('F4','L');
    $sheet->setCellValue('G4','P');
    $sheet->setCellValue('A4','');
    $sheet->setCellValue('B4','');
    $sheet->setCellValue('C4','');
    $sheet->setCellValue('H4','');
    $headerStyle=[
        'font'=>['bold'=>true,'color'=>['rgb'=>'000000']],
        'fill'=>['fillType'=>Fill::FILL_SOLID,'startColor'=>['rgb'=>'BDD7EE']],
        'alignment'=>['horizontal'=>Alignment::HORIZONTAL_CENTER,'vertical'=>Alignment::VERTICAL_CENTER],
        'borders'=>['allBorders'=>['borderStyle'=>Border::BORDER_THIN,'color'=>['rgb'=>'000000']]]
    ];
    $sheet->getStyle('A3:H4')->applyFromArray($headerStyle);
    $sheet->getRowDimension(3)->setRowHeight(22);
    $sheet->getRowDimension(4)->setRowHeight(18);
    $sheet->getColumnDimension('A')->setWidth(14);
    $sheet->getColumnDimension('B')->setWidth(6);
    $sheet->getColumnDimension('C')->setWidth(42);
    $sheet->getColumnDimension('D')->setWidth(10);
    $sheet->getColumnDimension('E')->setWidth(10);
    $sheet->getColumnDimension('F')->setWidth(10);
    $sheet->getColumnDimension('G')->setWidth(10);
    $sheet->getColumnDimension('H')->setWidth(12);
    $rowIdx=5;
    $kategoriOrder=['Tenaga Kesehatan','Asisten Tenaga Kesehatan','Tenaga Penunjang'];
    $kategoriLabel=['Tenaga Kesehatan'=>'A. Tenaga Kesehatan','Asisten Tenaga Kesehatan'=>'B. Asisten Tenaga Kesehatan','Tenaga Penunjang'=>'C. Tenaga Penunjang'];
    $grandTotal=0;
    $grouped=[];
    foreach($items as $it) $grouped[$it['kategori']][]=$it;
    // Pertahanan lapis-2: tiap kelompok kategori diurut hierarkis (induk
    // lalu anak-anaknya) agar export/template konsisten walau $items datar.
    foreach($grouped as $k=>$g) $grouped[$k]=orderSdmGroupHierarchical($g);
    foreach($kategoriOrder as $kat){
        if(empty($grouped[$kat])) continue;
        $sheet->mergeCells("A{$rowIdx}:H{$rowIdx}");
        $sheet->setCellValue("A{$rowIdx}", $kategoriLabel[$kat]);
        $catHeaderStyle=[
            'font'=>['bold'=>true,'color'=>['rgb'=>'000000']],
            'fill'=>['fillType'=>Fill::FILL_SOLID,'startColor'=>['rgb'=>'FFF2CC']],
            'alignment'=>['horizontal'=>Alignment::HORIZONTAL_LEFT],
            'borders'=>['allBorders'=>['borderStyle'=>Border::BORDER_THIN,'color'=>['rgb'=>'000000']]]
        ];
        $sheet->getStyle("A{$rowIdx}:G{$rowIdx}")->applyFromArray($catHeaderStyle);
        $sheet->getRowDimension($rowIdx)->setRowHeight(18);
        $rowIdx++;
        $catSum=0;
        // number counter per kategori for numeric No., letter for children
        $numericNo=1;
        // build sibling map for letter numbering
        $parentChildren = [];
        foreach($grouped[$kat] as $it){
            $pid = $it['parent_id'] ?? $it['id_parent'];
            if($pid) {
                if(!isset($parentChildren[$pid])) $parentChildren[$pid]=[];
                $parentChildren[$pid][]=$it;
            }
        }
        foreach($grouped[$kat] as $it){
            $d=$dataMap[$it['id']] ?? ['asn_l'=>0,'asn_p'=>0,'nonasn_l'=>0,'nonasn_p'=>0,'jumlah'=>0];
            $pid = $it['parent_id'] ?? $it['id_parent'];
            $isChild = $pid !== null && $pid !== '' && (int)$pid !== 0;
            if($isChild){
                $siblings=$parentChildren[$pid] ?? [];
                $idx=array_search($it['id'], array_column($siblings,'id'));
                $letter=chr(97+($idx===false?0:$idx));
                $displayName = "   {$letter}. ".$it['nama_item'];
                $noDisplay = $letter.'.';
            } else {
                $displayName = $it['nama_item'];
                $noDisplay = (string)$numericNo;
                $numericNo++;
            }
            $sheet->setCellValue("A{$rowIdx}", $faskes['kode_faskes'] ?? '');
            $sheet->setCellValue("B{$rowIdx}", $noDisplay);
            $sheet->setCellValue("C{$rowIdx}", $displayName);
            $sheet->setCellValue("D{$rowIdx}", (int)$d['asn_l']);
            $sheet->setCellValue("E{$rowIdx}", (int)$d['asn_p']);
            $sheet->setCellValue("F{$rowIdx}", (int)$d['nonasn_l']);
            $sheet->setCellValue("G{$rowIdx}", (int)$d['nonasn_p']);
            $sheet->setCellValue("H{$rowIdx}", (int)$d['jumlah']);
            $sheet->getStyle("A{$rowIdx}:H{$rowIdx}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            $sheet->getStyle("A{$rowIdx}:B{$rowIdx}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("D{$rowIdx}:H{$rowIdx}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("C{$rowIdx}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            // sum only include_in_total for A to avoid double count
            $shouldInclude = true;
            if($kat==='Tenaga Kesehatan' && (int)$it['include_in_total']===0) $shouldInclude=false;
            if($shouldInclude) $catSum += (int)$d['jumlah'];
            $rowIdx++;
        }
        $sheet->mergeCells("A{$rowIdx}:C{$rowIdx}");
        $sheet->setCellValue("A{$rowIdx}", "Total ".$kategoriLabel[$kat]);
        $sheet->setCellValue("H{$rowIdx}", $catSum);
        $totalStyle=[
            'font'=>['bold'=>true],
            'fill'=>['fillType'=>Fill::FILL_SOLID,'startColor'=>['rgb'=>'DDEBF7']],
            'borders'=>['allBorders'=>['borderStyle'=>Border::BORDER_THIN,'color'=>['rgb'=>'000000']]]
        ];
        $sheet->getStyle("A{$rowIdx}:H{$rowIdx}")->applyFromArray($totalStyle);
        $sheet->getStyle("H{$rowIdx}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("A{$rowIdx}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $rowIdx++;
        $grandTotal+=$catSum;
    }
    $sheet->mergeCells("A{$rowIdx}:G{$rowIdx}");
    $sheet->setCellValue("A{$rowIdx}", "TOTAL SDM KESEHATAN dan TENAGA PENUNJANG DI ".$labelJenis." ".strtoupper($faskes['nama_faskes'])." TAHUN ".date('Y'));
    $sheet->setCellValue("H{$rowIdx}", $grandTotal);
    $grandStyle=[
        'font'=>['bold'=>true,'color'=>['rgb'=>'FFFFFF']],
        'fill'=>['fillType'=>Fill::FILL_SOLID,'startColor'=>['rgb'=>'4472C4']],
        'alignment'=>['horizontal'=>Alignment::HORIZONTAL_CENTER],
        'borders'=>['allBorders'=>['borderStyle'=>Border::BORDER_THIN,'color'=>['rgb'=>'000000']]]
    ];
    $sheet->getStyle("A{$rowIdx}:H{$rowIdx}")->applyFromArray($grandStyle);
    $sheet->getRowDimension($rowIdx)->setRowHeight(20);
    $sheet->freezePane('A5');
    $sheet->setAutoFilter('A3:H4');
    $filename = ($action==='template' ? 'Template_SDMK_' : 'Export_SDMK_') . preg_replace('/[^A-Za-z0-9_]/','_', $faskes['nama_faskes']) . '_' . date('Ymd') . '.xlsx';
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="'.$filename.'"');
    header('Cache-Control: max-age=0');
    $writer=new Xlsx($ss);
    $writer->save('php://output');
    exit;
}

// ---------- HANDLE POST ACTIONS ----------
// Redirect pasca-aksi item: kembali ke tab asal. Form dari tab Rekap
// menyertakan return_to ter-whitelist; form Master tidak (perilaku lama).
function item_redirect($msg){
    $ret = $_POST['return_to'] ?? '';
    if(is_string($ret) && preg_match('/^sdmk\.php\?tab=faskes&id_faskes=\d+$/', $ret)){
        $map=['added'=>'item_added','updated'=>'item_updated','deleted'=>'item_deleted',
              'exists'=>'item_exists','invalid'=>'item_invalid','error'=>'item_error',
              'parent_scope'=>'parent_scope','protected'=>'protected'];
        $m = $map[$msg] ?? $msg;
        header("Location: $ret&msg=$m"); exit;
    }
    header("Location: sdmk.php?tab=items&msg=$msg"); exit;
}
$msg=''; $importResult=null; $saveResult=null;
if($_SERVER['REQUEST_METHOD']==='POST'){
    $act=$_POST['action'] ?? '';
    if($act==='save_rekap'){
        $id_faskes=(int)($_POST['id_faskes'] ?? 0);
        if(!$id_faskes){ header("Location: sdmk.php?msg=need_faskes"); exit; }
        $fchk=$config->prepare("SELECT id_kecamatan, jenis FROM tbl_faskes WHERE id_faskes=? LIMIT 1");
        $fchk->bind_param("i",$id_faskes);
        $fchk->execute();
        $frow=$fchk->get_result()->fetch_assoc();
        if(!$frow){ header("Location: sdmk.php?id_faskes=$id_faskes&msg=faskes_not_found"); exit; }
        $id_kecamatan=(int)$frow['id_kecamatan'];
        // Hanya proses item yang tampil untuk jenis faskes ini (id di luar
        // scope diabaikan meski dikirim manual via POST).
        $items=getItemsForFaskes($config, $id_faskes, $frow['jenis'] ?? '');
        $asn_l=$_POST['asn_l'] ?? [];
        $asn_p=$_POST['asn_p'] ?? [];
        $nonasn_l=$_POST['nonasn_l'] ?? [];
        $nonasn_p=$_POST['nonasn_p'] ?? [];
        $config->begin_transaction();
        $success=0; $warnings=[];
        try{
            foreach($items as $it){
                if((int)$it['is_total_row']===1){
                    // editable rule #4: tolak row total/header
                    continue;
                }
                $pid=$it['id'];
                $al=isset($asn_l[$pid]) ? max(0,(int)$asn_l[$pid]) : 0;
                $ap=isset($asn_p[$pid]) ? max(0,(int)$asn_p[$pid]) : 0;
                $nl=isset($nonasn_l[$pid]) ? max(0,(int)$nonasn_l[$pid]) : 0;
                $np=isset($nonasn_p[$pid]) ? max(0,(int)$nonasn_p[$pid]) : 0;
                $chk=$config->prepare("SELECT id FROM tbl_sdm_faskes WHERE id_faskes=? AND id_profesi=? AND id_spesialis IS NULL LIMIT 1");
                $chk->bind_param("ii",$id_faskes,$pid);
                $chk->execute();
                $ex=$chk->get_result()->fetch_assoc();
                if($ex){
                    $stmt=$config->prepare("UPDATE tbl_sdm_faskes SET asn_l=?, asn_p=?, nonasn_l=?, nonasn_p=?, id_kecamatan=?, aktif='Y', updated_at=NOW() WHERE id=?");
                    $stmt->bind_param("iiiiii",$al,$ap,$nl,$np,$id_kecamatan,$ex['id']);
                    $ok=$stmt->execute();
                    if(!$ok){ $warnings[]="Gagal update ".htmlspecialchars($it['nama_item']).": ".$stmt->error; } else { $success++; }
                } else {
                    if($al+$ap+$nl+$np >0){
                        $stmt=$config->prepare("INSERT INTO tbl_sdm_faskes (id_kecamatan, id_faskes, id_profesi, id_spesialis, asn_l, asn_p, nonasn_l, nonasn_p, aktif) VALUES (?, ?, ?, NULL, ?, ?, ?, ?, 'Y')");
                        $stmt->bind_param("iiiiiii",$id_kecamatan,$id_faskes,$pid,$al,$ap,$nl,$np);
                        $ok=$stmt->execute();
                        if(!$ok){ $warnings[]="Gagal insert ".htmlspecialchars($it['nama_item']).": ".$stmt->error; } else { $success++; }
                    } else {
                        $success++;
                    }
                }
            }
            $config->commit();
            // if AJAX request, return JSON with recomputed totals
            $isAjax = isset($_POST['ajax']) || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH'])==='xmlhttprequest') || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'],'application/json')!==false);
            // also check explicit param ajax=1
            if($isAjax || (isset($_POST['ajax']) && $_POST['ajax']=='1')){
                header('Content-Type: application/json');
                $totals=computeTotals($config, $id_faskes, $items);
                echo json_encode(['status'=>true,'success'=>$success,'warnings'=>$warnings,'totals'=>$totals]);
                exit;
            }
            if(!empty($warnings)){
                if(session_status()===PHP_SESSION_NONE) session_start();
                $_SESSION['save_result']=['success'=>$success,'warnings'=>$warnings];
                header("Location: sdmk.php?id_faskes=$id_faskes&msg=saved_warn");
                exit;
            }
            header("Location: sdmk.php?id_faskes=$id_faskes&msg=saved");
            exit;
        } catch(Exception $e){
            $config->rollback();
            $isAjax = isset($_POST['ajax']) || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH'])==='xmlhttprequest');
            if($isAjax){
                header('Content-Type: application/json', true, 500);
                echo json_encode(['status'=>false,'error'=>$e->getMessage()]);
                exit;
            }
            if(session_status()===PHP_SESSION_NONE) session_start();
            $_SESSION['save_result']=['success'=>0,'warnings'=>[$e->getMessage()]];
            header("Location: sdmk.php?id_faskes=$id_faskes&msg=error");
            exit;
        }
    }
    if($act==='update_row'){
        // AJAX single row update: expects id_profesi, asn_l, asn_p, nonasn_l, nonasn_p
        $id_faskes=(int)($_POST['id_faskes'] ?? 0);
        $id_profesi=(int)($_POST['id_profesi'] ?? 0);
        if(!$id_faskes || !$id_profesi){
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(['status'=>false,'error'=>'Missing id_faskes or id_profesi']);
            exit;
        }
        // validate is_total_row
        $chkItem=$config->prepare("SELECT is_total_row, kategori FROM tbl_sdm_items WHERE id=? LIMIT 1");
        $chkItem->bind_param("i",$id_profesi);
        $chkItem->execute();
        $itemRow=$chkItem->get_result()->fetch_assoc();
        if(!$itemRow){ http_response_code(404); header('Content-Type: application/json'); echo json_encode(['status'=>false,'error'=>'Item not found']); exit; }
        if((int)$itemRow['is_total_row']===1){
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['status'=>false,'error'=>'Row is read-only (is_total_row=1)']);
            exit;
        }
        $fchk=$config->prepare("SELECT id_kecamatan, jenis FROM tbl_faskes WHERE id_faskes=? LIMIT 1");
        $fchk->bind_param("i",$id_faskes);
        $fchk->execute();
        $frow=$fchk->get_result()->fetch_assoc();
        if(!$frow){ http_response_code(404); header('Content-Type: application/json'); echo json_encode(['status'=>false,'error'=>'Faskes not found']); exit; }
        // Item harus tampil untuk jenis faskes ini (atau sudah punya data di sini).
        $visItems=getItemsForFaskes($config, $id_faskes, $frow['jenis'] ?? '');
        $visIds=[]; foreach($visItems as $vi) $visIds[$vi['id']]=true;
        if(!isset($visIds[$id_profesi])){ http_response_code(403); header('Content-Type: application/json'); echo json_encode(['status'=>false,'error'=>'Item tidak berlaku untuk jenis faskes ini']); exit; }
        $id_kecamatan=(int)$frow['id_kecamatan'];
        $al=max(0,(int)($_POST['asn_l'] ?? 0));
        $ap=max(0,(int)($_POST['asn_p'] ?? 0));
        $nl=max(0,(int)($_POST['nonasn_l'] ?? 0));
        $np=max(0,(int)($_POST['nonasn_p'] ?? 0));
        $chk=$config->prepare("SELECT id FROM tbl_sdm_faskes WHERE id_faskes=? AND id_profesi=? AND id_spesialis IS NULL LIMIT 1");
        $chk->bind_param("ii",$id_faskes,$id_profesi);
        $chk->execute();
        $ex=$chk->get_result()->fetch_assoc();
        if($ex){
            $stmt=$config->prepare("UPDATE tbl_sdm_faskes SET asn_l=?, asn_p=?, nonasn_l=?, nonasn_p=?, id_kecamatan=?, aktif='Y', updated_at=NOW() WHERE id=?");
            $stmt->bind_param("iiiiii",$al,$ap,$nl,$np,$id_kecamatan,$ex['id']);
            $stmt->execute();
        } else {
            $stmt=$config->prepare("INSERT INTO tbl_sdm_faskes (id_kecamatan, id_faskes, id_profesi, id_spesialis, asn_l, asn_p, nonasn_l, nonasn_p, aktif) VALUES (?, ?, ?, NULL, ?, ?, ?, ?, 'Y')");
            $stmt->bind_param("iiiiiii",$id_kecamatan,$id_faskes,$id_profesi,$al,$ap,$nl,$np);
            $stmt->execute();
        }
        $totals=computeTotals($config, $id_faskes, $visItems);
        header('Content-Type: application/json');
        echo json_encode(['status'=>true,'totals'=>$totals,'jumlah'=>$al+$ap+$nl+$np]);
        exit;
    }
    if($act==='reset'){
        $id_faskes=(int)($_POST['id_faskes'] ?? 0);
        if($id_faskes){
            $stmt=$config->prepare("UPDATE tbl_sdm_faskes SET asn_l=0, asn_p=0, nonasn_l=0, nonasn_p=0, aktif='Y' WHERE id_faskes=? AND aktif='Y'");
            $ok=$stmt->bind_param("i",$id_faskes) && $stmt->execute();
            if(!$ok){ error_log("RESET fail id_faskes=$id_faskes: ".$stmt->error); }
        }
        header("Location: sdmk.php?id_faskes=$id_faskes&msg=reset");
        exit;
    }
    if($act==='reset_row'){
        $id_faskes=(int)($_POST['id_faskes'] ?? 0);
        $row_id=(int)($_POST['row_id'] ?? 0);
        if($row_id){
            // validate that row's profesi is not is_total_row
            $chk=$config->prepare("SELECT i.is_total_row FROM tbl_sdm_faskes f JOIN tbl_sdm_items i ON i.id=f.id_profesi WHERE f.id=? LIMIT 1");
            $chk->bind_param("i",$row_id);
            $chk->execute();
            $r=$chk->get_result()->fetch_assoc();
            if($r && (int)$r['is_total_row']===1){ http_response_code(403); die("Row is read-only"); }
            $stmt=$config->prepare("UPDATE tbl_sdm_faskes SET asn_l=0, asn_p=0, nonasn_l=0, nonasn_p=0, aktif='Y', updated_at=NOW() WHERE id=?");
            $stmt->bind_param("i",$row_id);
            $ok=$stmt->execute();
            if(!$ok){ error_log("reset_row fail id=$row_id: ".$stmt->error); }
        }
        header("Location: sdmk.php?id_faskes=$id_faskes&msg=reset_row");
        exit;
    }
    if($act==='delete_row'){
        $id_faskes=(int)($_POST['id_faskes'] ?? 0);
        $row_id=(int)($_POST['row_id'] ?? 0);
        if($row_id){
            $chk=$config->prepare("SELECT i.is_total_row FROM tbl_sdm_faskes f JOIN tbl_sdm_items i ON i.id=f.id_profesi WHERE f.id=? LIMIT 1");
            $chk->bind_param("i",$row_id);
            $chk->execute();
            $r=$chk->get_result()->fetch_assoc();
            if($r && (int)$r['is_total_row']===1){ http_response_code(403); die("Row is read-only"); }
            $stmt=$config->prepare("UPDATE tbl_sdm_faskes SET aktif='N', updated_at=NOW() WHERE id=?");
            $stmt->bind_param("i",$row_id);
            $ok=$stmt->execute();
            if(!$ok){ error_log("delete_row fail id=$row_id: ".$stmt->error); }
        }
        header("Location: sdmk.php?id_faskes=$id_faskes&msg=deleted");
        exit;
    }
    if($act==='import'){
        $id_faskes=(int)($_POST['id_faskes'] ?? 0);
        if(!$id_faskes){ header("Location: sdmk.php?msg=need_faskes"); exit; }
        $fchk=$config->prepare("SELECT id_kecamatan FROM tbl_faskes WHERE id_faskes=? LIMIT 1");
        $fchk->bind_param("i",$id_faskes);
        $fchk->execute();
        $frow=$fchk->get_result()->fetch_assoc();
        if(!$frow){ header("Location: sdmk.php?msg=faskes_not_found"); exit; }
        $id_kecamatan=(int)$frow['id_kecamatan'];
        // Peta faskes untuk mode multi-faskes: Kode (eksak + case-insensitif)
        // dan Nama (normalisasi, dengan deteksi ambiguitas). id_kecamatan
        // selalu di-derive dari tbl_faskes, TIDAK dari input/Excel.
        // 'jenis' ikut disimpan untuk scope matching per baris.
        $mapKode=[]; $mapNama=[]; $jenisByFaskes=[];
        $qfAll=$config->query("SELECT id_faskes, kode_faskes, nama_faskes, jenis, id_kecamatan FROM tbl_faskes WHERE aktif='Y'");
        while($qfAll && ($fr=$qfAll->fetch_assoc())){
            $fid=(int)$fr['id_faskes']; $fkec=(int)$fr['id_kecamatan']; $fjen=(string)($fr['jenis'] ?? '');
            $jenisByFaskes[$fid]=$fjen;
            $code=trim((string)($fr['kode_faskes'] ?? ''));
            if($code!==''){
                if(!isset($mapKode[$code])) $mapKode[$code]=['id'=>$fid,'kec'=>$fkec,'jenis'=>$fjen];
                $up=strtoupper($code);
                if(!isset($mapKode[$up])) $mapKode[$up]=['id'=>$fid,'kec'=>$fkec,'jenis'=>$fjen];
            }
            $nm=normalizeNama($fr['nama_faskes'] ?? '');
            if($nm!==''){
                if(!isset($mapNama[$nm])) $mapNama[$nm]=['id'=>$fid,'kec'=>$fkec,'jenis'=>$fjen,'amb'=>false];
                else $mapNama[$nm]['amb']=true;
            }
        }
        $uiJenis=$jenisByFaskes[$id_faskes] ?? '';
        $uiScope=scopeForJenis($uiJenis);
        if(!isset($_FILES['excel_file']) || $_FILES['excel_file']['error']!==UPLOAD_ERR_OK){
            header("Location: sdmk.php?id_faskes=$id_faskes&msg=import_no_file");
            exit;
        }
        $tmp=$_FILES['excel_file']['tmp_name'];
        $ext=strtolower(pathinfo($_FILES['excel_file']['name'], PATHINFO_EXTENSION));
        $success=0; $skipped=0; $failed=0; $warnings=[]; $affectedKec=[];
        try{
            if($ext==='xls') $reader=new ReaderXls(); else $reader=new ReaderXlsx();
            $reader->setReadDataOnly(true);
            $ss=$reader->load($tmp);
            $sheet=$ss->getActiveSheet();
            $rows=$sheet->toArray(null,true,true,true);
            // Helper indeks kolom (A=>0, ..., Z=>25, AA=>26, ...)
            $colIdx=function($letter){ $letter=strtoupper($letter); $n=0; for($i=0;$i<strlen($letter);$i++){ $n=$n*26+(ord($letter[$i])-64); } return $n-1; };
            $colLetter=function($idx){ $s=''; $idx++; while($idx>0){ $m=($idx-1)%26; $s=chr(65+$m).$s; $idx=intdiv($idx-1,26); } return $s; };
            // Deteksi header: cari sel 'Jenis SDM' di kolom mana pun (kompatibel
            // template lama A=No/B=Jenis maupun template baru A=Kode/B=No/C=Jenis).
            // Kolom 'Kode Faskes' bersifat opsional -> penanda mode multi-faskes.
            $headerRow=null; $idxJenis=null; $idxKode=null;
            foreach($rows as $rNum=>$row){
                foreach($row as $letter=>$val){
                    $t=strtolower(trim((string)$val));
                    if($t==='jenis sdm' && $headerRow===null){ $headerRow=(int)$rNum; $idxJenis=$colIdx($letter); }
                    if($t==='kode faskes' && $idxKode===null){ $idxKode=$colIdx($letter); }
                }
                if($headerRow!==null && (int)$rNum>$headerRow+2) break;
            }
            if($headerRow===null || $idxJenis===null){
                if(session_status()===PHP_SESSION_NONE) session_start();
                $_SESSION['import_result']=['success'=>0,'skipped'=>0,'failed'=>0,'rekap'=>[],'new_items'=>[],'similar'=>[],'checksum'=>[],'failed_rows'=>[],'warnings'=>["Header 'Jenis SDM' tidak ditemukan. Pastikan file adalah Template/Export SDMK yang valid."]];
                header("Location: sdmk.php?id_faskes=$id_faskes&msg=import_invalid_header");
                exit;
            }
            $cJ=$colLetter($idxJenis);
            $v0=$idxJenis+1; $v1=$idxJenis+2; $v2=$idxJenis+3; $v3=$idxJenis+4;
            $cV0=$colLetter($v0); $cV1=$colLetter($v1); $cV2=$colLetter($v2); $cV3=$colLetter($v3);
            $isMulti = ($idxKode!==null);
            // STEP 2 — VALIDASI TEMPLATE KETAT (tolak file buatan sendiri).
            // Kanonis (dari tombol Download Template): Kode Faskes | No |
            // Jenis SDM | ASN-L | ASN-P | Non ASN-L | Non ASN-P.
            // Legacy yang masih diterima: No | Jenis SDM | ... (tanpa Kode,
            // mode single-faskes). Selain itu -> tolak dengan pesan kolom.
            $hdr1=$rows[$headerRow] ?? [];
            $cellAt=function($idx) use ($hdr1,$colLetter){ $L=$colLetter($idx); return trim((string)($hdr1[$L] ?? '')); };
            $reject=function($why) use ($config,$id_faskes){
                if(session_status()===PHP_SESSION_NONE) session_start();
                $_SESSION['import_result']=['success'=>0,'skipped'=>0,'failed'=>0,'rekap'=>[],'new_items'=>[],'similar'=>[],'checksum'=>[],'failed_rows'=>[],'warnings'=>[$why]];
                header("Location: sdmk.php?id_faskes=$id_faskes&msg=import_invalid_header");
                exit;
            };
            $preKode = $idxJenis>=2 ? $cellAt($idxJenis-2) : '';
            $preNo = $idxJenis>=1 ? $cellAt($idxJenis-1) : '';
            $layoutOk = false; $layoutName = '';
            if(strcasecmp($preKode,'Kode Faskes')===0 && strcasecmp($preNo,'No')===0){
                $layoutOk = true; $layoutName = 'kanonis';
            } elseif($idxJenis===1 && strcasecmp($preNo,'No')===0){
                $layoutOk = true; $layoutName = 'legacy';
            }
            if(!$layoutOk){
                $reject("Struktur header tidak sesuai template baris $headerRow: sebelum 'Jenis SDM' harus 'No' (legacy) atau 'Kode Faskes | No' (kanonis). Ditemukan: '".($preKode!==''?$preKode.' | ':'').$preNo."'. Download ulang template via tombol Download Template.");
            }
            if($isMulti && $layoutName!=='kanonis'){
                $reject("Kolom 'Kode Faskes' tidak pada posisi yang benar (harus 2 kolom sebelum 'Jenis SDM'). Download ulang template via tombol Download Template.");
            }
            // Grup nilai: sel (headerRow, v0) harus memuat 'ASN', sel
            // (headerRow, v2) harus memuat 'Non ASN'.
            $g1=$cellAt($v0); $g2=$cellAt($v2);
            $asnOk = stripos($g1,'ASN')!==false && stripos($g2,'Non ASN')!==false;
            if(!$asnOk){
                $reject("Struktur header tidak valid baris $headerRow: 4 kolom setelah 'Jenis SDM' harus dikelompokkan 'ASN' dan 'Non ASN' (ditemukan '$g1' dan '$g2'). Download ulang template via tombol Download Template.");
            }
            // Sub-header L/P/L/P di baris berikutnya, tepat di 4 kolom nilai.
            $hdr2=$rows[$headerRow+1] ?? null;
            if($hdr2){
                $actual=[$cV0,$cV1,$cV2,$cV3];
                $actual=array_map(fn($L)=>trim((string)($hdr2[$L] ?? '')), $actual);
                $actualUp=array_map(fn($x)=>strtoupper($x), $actual);
                if($actualUp !== ['L','P','L','P']){
                    $reject("Struktur header tidak valid di baris ".($headerRow+1).": 4 kolom nilai harus 'L','P','L','P' (ditemukan '".implode("','",$actual)."'). Download ulang template via tombol Download Template.");
                }
            } else {
                $reject("Baris sub-header L/P/L/P (baris ".($headerRow+1).") tidak ditemukan. File mungkin corrupt atau bukan template SDMK.");
            }
            $dataStart=$headerRow+2;
            // File tidak kosong: harus ada minimal 1 baris data.
            $maxDataRow=(int)$sheet->getHighestDataRow();
            if($maxDataRow < $dataStart){
                $reject("File tidak berisi baris data (hanya header). Upload file Template/Export SDMK yang sudah diisi.");
            }
            // Peta matching per scope jenis faskes (rs/puskesmas/lainnya):
            // item tampil = scope cocok. Union punya-data dihitung per baris
            // (faskes baris tsb) agar data lama yang terlanjur masuk tetap match.
            $itemsAll=getItems($config);
            $itemsById=[]; foreach($itemsAll as $ia) $itemsById[$ia['id']]=$ia;
            $maps=['rs'=>[],'puskesmas'=>[],'lainnya'=>[]];
            foreach($itemsAll as $it){
                $norm=normalizeNama($it['nama_item']);
                $key=$norm.'|'.strtolower($it['kategori']);
                foreach(['rs','puskesmas','lainnya'] as $sc){
                    if(scopeMatch($it['scope'] ?? '', $sc)) $maps[$sc][$key]=$it['id'];
                }
            }
            // Peta item-yang-punya-data per faskes (sekali query, dipakai union).
            $dataProfesiByFaskes=[];
            $qDP=$config->query("SELECT DISTINCT id_faskes, id_profesi FROM tbl_sdm_faskes WHERE aktif='Y'");
            while($qDP && ($rdp=$qDP->fetch_assoc())) $dataProfesiByFaskes[(int)$rdp['id_faskes']][(int)$rdp['id_profesi']]=true;
            $config->begin_transaction();
            // STEP 3 — state machine parsing hierarkis:
            // $kategori_aktif = kategori seksi berjalan; $last_parent_id = id
            // ITEM UTAMA terakhir (untuk SUB-ITEM); $checksum = akumulasi
            // nilai per kategori untuk validasi baris "Total".
            $kategori_aktif = null;
            $last_parent_id = null;
            $checksum = [];
            $newItems = [];
            // STEP 6 — struktur laporan terstruktur (selain string warnings).
            $repSimilar = [];
            $repChecksum = [];
            $repFailed = [];
            // Helper baris gagal: catat terstruktur + string + counter.
            $failRow = function($rNum, $jenisText, $reason) use (&$warnings, &$repFailed, &$failed) {
                $warnings[] = "Baris $rNum: $reason";
                $repFailed[] = ['row' => $rNum, 'jenis' => $jenisText, 'reason' => $reason];
                $failed++;
            };
            // STEP 4 — data bantu auto-insert master:
            // Urutan baru dialokasikan per kelompok (bukan increment global):
            // anak = MAX sibling + 1 (menempel di induknya), top-level =
            // MAX se-kategori + 1. Cache di-memory selama proses import.
            $maxByKat=[]; $maxByParent=[];
            $qKat=$config->query("SELECT kategori, MAX(urutan) m FROM tbl_sdm_items GROUP BY kategori");
            while($qKat && ($rK=$qKat->fetch_assoc())) $maxByKat[$rK['kategori']]=(int)$rK['m'];
            $qPar=$config->query("SELECT COALESCE(parent_id, id_parent) pid, MAX(urutan) m FROM tbl_sdm_items WHERE COALESCE(parent_id, id_parent) IS NOT NULL GROUP BY COALESCE(parent_id, id_parent)");
            while($qPar && ($rP=$qPar->fetch_assoc())) $maxByParent[(int)$rP['pid']]=(int)$rP['m'];
            $existingNames = []; $itemNames = [];
            $qNm = $config->query("SELECT id, nama_item FROM tbl_sdm_items WHERE aktif='Y'");
            while($qNm && ($rN=$qNm->fetch_assoc())){ $existingNames[]=$rN['nama_item']; $itemNames[(int)$rN['id']]=$rN['nama_item']; }
            // Deteksi baris kategori dari teks (tanpa posisi tetap).
            $detectKategori = function($teks){
                $t = strtolower(trim((string)$teks));
                if(strpos($t,'asisten tenaga')!==false) return 'Asisten Tenaga Kesehatan';
                if(strpos($t,'tenaga penunjang')!==false) return 'Tenaga Penunjang';
                if(strpos($t,'tenaga kesehatan')!==false) return 'Tenaga Kesehatan';
                return null;
            };
            $currentKategori = null;
            $cKode = $idxKode!==null ? $colLetter($idxKode) : null;
            $cNo = $idxJenis>0 ? $colLetter($idxJenis-1) : null;
            foreach($rows as $rNum=>$row){
                if((int)$rNum < $dataStart) continue;
                $colB = trim((string)($row[$cJ] ?? ''));
                $colA = $cNo!==null ? trim((string)($row[$cNo] ?? '')) : '';
                if($colB==='' && $colA==='') continue;
                // Baris merged dari template: teks penanda ada di kolom No,
                // sel Jenis kosong. Deteksi memakai sel yang terisi.
                $textDetect = $colB!=='' ? $colB : $colA;
                $markerInNo = ($colB==='');
                // Nilai mentah 4 kolom angka (untuk deteksi baris-berangka & checksum).
                $rawVals = [trim((string)($row[$cV0] ?? '')), trim((string)($row[$cV1] ?? '')),
                            trim((string)($row[$cV2] ?? '')), trim((string)($row[$cV3] ?? ''))];
                $rowHasNums = false;
                foreach($rawVals as $rv){ if($rv!==''){ $rowHasNums=true; break; } }

                // --- STEP 3a: baris TOTAL (salah satu sel No/Jenis kosong +
                // teks memuat 'total') ---
                // Bukan data: validasi checksum vs akumulasi kategori berjalan.
                if(($colA==='' || $colB==='') && stripos($textDetect,'total')!==false){
                    if($kategori_aktif!==null && isset($checksum[$kategori_aktif])){
                        $pv=[]; $badParse=false;
                        foreach($rawVals as $vv){
                            $t=$vv===''?'0':str_replace(',','',$vv);
                            if(!is_numeric($t)){ $badParse=true; break; }
                            $pv[]=(int)$t;
                        }
                        if(!$badParse){
                            $acc=$checksum[$kategori_aktif];
                            $lbl=['ASN-L','ASN-P','NonASN-L','NonASN-P'];
                            $mm=[];
                            foreach(['al','ap','nl','np'] as $ii=>$kk){
                                if($pv[$ii]!==$acc[$kk]) $mm[]=$lbl[$ii]." file={$pv[$ii]} vs hitung={$acc[$kk]}";
                            }
                            if(!empty($mm)){
                                $warnings[]="Baris $rNum (Total $kategori_aktif): checksum tidak cocok (".implode(', ',$mm).") — data tetap diproses.";
                                $repChecksum[]=['kategori'=>$kategori_aktif,'file'=>$pv,
                                    'sys'=>[$acc['al'],$acc['ap'],$acc['nl'],$acc['np']]];
                            }
                        }
                    } else {
                        $warnings[]="Baris $rNum (Total): tidak bisa divalidasi checksum (di luar seksi kategori) — dilewati.";
                    }
                    $skipped++; continue;
                }

                // --- STEP 3b: baris kategori (teks di sel mana pun yang terisi,
                // sel pasangannya kosong) ---
                $katFound = null;
                if($colA==='' || $colB==='') $katFound = $detectKategori($textDetect);
                $effKategori = $kategori_aktif; $effParent = null; $effName = $colB!=='' ? $colB : $textDetect;
                $isKategoriDataRow = false;
                if($katFound!==null){
                    if($rowHasNums){
                        // Kategori berangka SEKALIGUS 1 item data (tanpa parent).
                        $effKategori=$katFound; $kategori_aktif=$katFound;
                        $isKategoriDataRow=true;
                    } else {
                        // Header seksi murni: ganti konteks, reset rantai parent.
                        $kategori_aktif=$katFound; $last_parent_id=null;
                        $skipped++; continue;
                    }
                } else {
                    // --- STEP 3c: baris item ---
                    $noLetter = rtrim($colA,'.');
                    if(preg_match('/^[a-zA-Z]$/',$noLetter)){
                        // SUB-ITEM: wajib ada item utama sebelumnya.
                        if($last_parent_id===null){
                            $failRow($rNum, $textDetect, "baris sub-item ditemukan tanpa item utama sebelumnya, No baris di Excel: '$colA'");
                            continue;
                        }
                        $effParent=$last_parent_id;
                    }
                    // else: ITEM UTAMA (effParent null).
                }

                // --- STEP 3d: resolusi faskes baris (dipindah ke depan agar
                // matching memakai scope jenis faskes baris tsb). Baris tanpa
                // kode memakai faskes UI. id_kecamatan selalu dari tbl_faskes.
                $rf=$id_faskes; $rk=$id_kecamatan; $rowJenis=$uiJenis;
                if($isMulti){
                    $kodeCell=trim((string)($row[$cKode] ?? ''));
                    if($kodeCell!==''){
                        $hit=null;
                        if(isset($mapKode[$kodeCell])) $hit=$mapKode[$kodeCell];
                        elseif(isset($mapKode[strtoupper($kodeCell)])) $hit=$mapKode[strtoupper($kodeCell)];
                        else {
                            $nmN=normalizeNama($kodeCell);
                            if(isset($mapNama[$nmN]) && !$mapNama[$nmN]['amb']) $hit=$mapNama[$nmN];
                            elseif(isset($mapNama[$nmN]) && $mapNama[$nmN]['amb']){
                                $failRow($rNum, $kodeCell, "nama faskes cocok dengan lebih dari satu faskes. Gunakan Kode Faskes yang eksak.");
                                continue;
                            }
                        }
                        if($hit===null){
                            $failRow($rNum, $kodeCell, "faskes tidak ditemukan di database (faskes baru wajib diinput via Kelola Fasyankes).");
                            continue;
                        }
                        $rf=$hit['id']; $rk=$hit['kec']; $rowJenis=$hit['jenis'] ?? $uiJenis;
                    }
                }
                $rowScope=scopeForJenis($rowJenis);
                // Kandidat matching: item se-scope + item yang sudah punya data
                // di faskes baris ini (union anti-hilang-data, cth. Bulu).
                $candMap=$maps[$rowScope] ?? [];
                if(!empty($dataProfesiByFaskes[$rf])){
                    foreach($dataProfesiByFaskes[$rf] as $dpid=>$_){
                        if(isset($itemsById[$dpid])){
                            $di=$itemsById[$dpid];
                            $candMap[normalizeNama($di['nama_item']).'|'.strtolower($di['kategori'])]=$dpid;
                        }
                    }
                }

                // --- STEP 4: MATCHING & AUTO-INSERT ke tbl_sdm_items ---
                // Aturan: (nama, kategori) dalam kandidat se-scope; id_spesialis
                // TIDAK dipakai (Opsi A).
                $effNameTrim = trim($effName);
                $norm = normalizeNama($effNameTrim);
                $katKey = $effKategori!==null ? strtolower($effKategori) : '';
                $pid = null;
                if($effKategori!==null && isset($candMap[$norm.'|'.$katKey])){
                    $pid=$candMap[$norm.'|'.$katKey];
                } else {
                    // fallback: nama unik lintas kategori (toleransi file lama).
                    $candidates=[];
                    foreach($candMap as $k=>$v){
                        $parts=explode('|',$k);
                        if($parts[0]===$norm) $candidates[]=$v;
                    }
                    if(count($candidates)===1) $pid=$candidates[0];
                    elseif(count($candidates)>1){
                        $failRow($rNum, $effNameTrim, "Jenis SDM ambigu (muncul di 2 kategori), butuh konteks kategori. Pastikan file memiliki header kategori A/B/C.");
                        continue;
                    }
                }
                if($pid===null){
                    // TIDAK COCOK -> INSERT OTOMATIS tanpa approval, scope ikut
                    // jenis faskes baris ini (tidak lagi mencemari jenis lain).
                    $insKat = $effKategori ?? 'Tenaga Kesehatan';
                    if($effKategori===null){
                        $warnings[]="Baris $rNum: kategori tidak diketahui (tanpa header kategori), dipakai default 'Tenaga Kesehatan' untuk '".htmlspecialchars($effNameTrim). "'.";
                    }
                    // Cek kemiripan (peringatan saja, tetap insert).
                    $bestName=''; $bestPct=0;
                    foreach($existingNames as $en){
                        similar_text(strtolower($effNameTrim), strtolower($en), $pct);
                        if($pct>$bestPct){ $bestPct=$pct; $bestName=$en; }
                    }
                    if($bestPct>=75){
                        $warnings[]="Baris $rNum: '".htmlspecialchars($effNameTrim)."' mirip dengan existing '".htmlspecialchars($bestName)."' (".round($bestPct)."%) — tetap ditambahkan sebagai item baru, cek manual.";
                        $repSimilar[]=['file'=>$effNameTrim,'existing'=>$bestName,'score'=>round($bestPct)];
                    }
                    // Alokasi urutan menempel di kelompoknya (anak: sibling+1,
                    // top-level: kategori+1) agar tampil tepat di bawah induk.
                    if($effParent!==null){
                        $pk=(int)$effParent;
                        $newUrut=($maxByParent[$pk] ?? 0)+1;
                        $maxByParent[$pk]=$newUrut;
                        if($newUrut > ($maxByKat[$insKat] ?? 0)) $maxByKat[$insKat]=$newUrut;
                    } else {
                        $newUrut=($maxByKat[$insKat] ?? 0)+1;
                        $maxByKat[$insKat]=$newUrut;
                    }
                    $incTotal = $effParent===null ? 1 : 0;
                    $ins=$config->prepare("INSERT INTO tbl_sdm_items (nama_item, kategori, scope, parent_id, id_parent, urutan, is_total_row, include_in_total, aktif) VALUES (?,?,?,?,?,?,0,?,'Y')");
                    $ins->bind_param("sssiiii",$effNameTrim,$insKat,$rowScope,$effParent,$effParent,$newUrut,$incTotal);
                    if(!$ins->execute()){
                        $failRow($rNum, $effNameTrim, "gagal menambah master: ".$ins->error);
                        continue;
                    }
                    $pid=$ins->insert_id;
                    if($effParent!==null) $maxByParent[(int)$effParent]=max($maxByParent[(int)$effParent] ?? $newUrut, $newUrut);
                    $maps[$rowScope][$norm.'|'.strtolower($insKat)]=$pid;
                    $dataProfesiByFaskes[$rf][$pid]=true;
                    $existingNames[]=$effNameTrim;
                    $itemNames[$pid]=$effNameTrim;
                    $newItems[]=['nama'=>$effNameTrim,'kategori'=>$insKat,'scope'=>$rowScope,'urutan'=>$newUrut,
                        'parent'=>($effParent!==null && isset($itemNames[$effParent]) ? $itemNames[$effParent] : '-')];
                }
                // validate is_total_row not allowed
                $chkItem=$config->prepare("SELECT is_total_row FROM tbl_sdm_items WHERE id=? LIMIT 1");
                $chkItem->bind_param("i",$pid);
                $chkItem->execute();
                $itRow=$chkItem->get_result()->fetch_assoc();
                if($itRow && (int)$itRow['is_total_row']===1){
                    $skipped++; continue;
                }
                // Resolusi faskes sudah dilakukan di STEP 3d di atas ($rf/$rk/$rowScope).
                $c = $row[$cV0] ?? 0; $d=$row[$cV1] ?? 0; $e=$row[$cV2] ?? 0; $f=$row[$cV3] ?? 0;
                $vals=[$c,$d,$e,$f]; $parsed=[];
                foreach($vals as $idx=>$v){
                    $orig=(string)$v;
                    $v=trim((string)$v);
                    if($v==='') $v=0;
                    $v=str_replace(',','',$v);
                    if(!is_numeric($v) || (int)$v<0){
                        $warnings[]="Baris $rNum (".htmlspecialchars($effName)."): nilai '".htmlspecialchars($orig)."' pada kolom ".chr(67+$idx)." tidak valid (harus angka ≥0), dianggap 0.";
                        $v=0;
                    }
                    $parsed[]=(int)$v;
                }
                [$al,$ap,$nl,$np]=$parsed;
                $ckKat = $effKategori ?? $kategori_aktif ?? 'Tanpa Kategori';
                $chk=$config->prepare("SELECT id FROM tbl_sdm_faskes WHERE id_faskes=? AND id_profesi=? AND id_spesialis IS NULL LIMIT 1");
                $chk->bind_param("ii",$rf,$pid);
                $chk->execute();
                $ex=$chk->get_result()->fetch_assoc();
                if($ex){
                    $stmt=$config->prepare("UPDATE tbl_sdm_faskes SET asn_l=?, asn_p=?, nonasn_l=?, nonasn_p=?, id_kecamatan=?, aktif='Y', updated_at=NOW() WHERE id=?");
                    $stmt->bind_param("iiiiii",$al,$ap,$nl,$np,$rk,$ex['id']);
                    $ok=$stmt->execute();
                    if(!$ok){ $failRow($rNum, $effName, "gagal update DB: ".$stmt->error); }
                    else { $success++; $affectedKec[$rk]=true; $rowSaved=true; }
                } else {
                    $stmt=$config->prepare("INSERT INTO tbl_sdm_faskes (id_kecamatan, id_faskes, id_profesi, id_spesialis, asn_l, asn_p, nonasn_l, nonasn_p, aktif) VALUES (?, ?, ?, NULL, ?, ?, ?, ?, 'Y')");
                    $stmt->bind_param("iiiiiii",$rk,$rf,$pid,$al,$ap,$nl,$np);
                    $ok=$stmt->execute();
                    if(!$ok){ $failRow($rNum, $effName, "gagal insert DB: ".$stmt->error); }
                    else { $success++; $affectedKec[$rk]=true; $rowSaved=true; }
                }
                if(!empty($rowSaved)){
                    // Akumulasi checksum per kategori + rantai parent.
                    // Sub-item tidak mengubah last_parent_id (sibling menempel
                    // ke induk yang sama); item utama/kategori-data menjadi
                    // induk berikutnya.
                    if(!isset($checksum[$ckKat])) $checksum[$ckKat]=['al'=>0,'ap'=>0,'nl'=>0,'np'=>0];
                    $checksum[$ckKat]['al']+=$al; $checksum[$ckKat]['ap']+=$ap;
                    $checksum[$ckKat]['nl']+=$nl; $checksum[$ckKat]['np']+=$np;
                    if($effParent===null) $last_parent_id=$pid;
                    $rowSaved=false;
                }
            }
            $config->commit();
            // Rekap agregasi otomatis per kecamatan (1 sumber kebenaran:
            // id_kecamatan hasil derive dari tbl_faskes saat import).
            $rekap=[];
            if(!empty($affectedKec)){
                $ids=implode(',',array_map('intval',array_keys($affectedKec)));
                $qR=$config->query("SELECT k.nama_kecamatan, COALESCE(SUM(sf.jumlah),0) AS total FROM tbl_kecamatan k LEFT JOIN tbl_sdm_faskes sf ON sf.id_kecamatan=k.id_kecamatan AND sf.aktif='Y' WHERE k.id_kecamatan IN ($ids) GROUP BY k.id_kecamatan, k.nama_kecamatan ORDER BY k.nama_kecamatan");
                while($qR && ($rr=$qR->fetch_assoc())) $rekap[]=$rr;
            }
            if(session_status()===PHP_SESSION_NONE) session_start();
            $_SESSION['import_result']=['success'=>$success,'skipped'=>$skipped,'failed'=>$failed,'warnings'=>$warnings,'rekap'=>$rekap,'multi'=>$isMulti,'new_items'=>$newItems,'similar'=>$repSimilar,'checksum'=>$repChecksum,'failed_rows'=>$repFailed];
            header("Location: sdmk.php?id_faskes=$id_faskes&msg=import_done");
            exit;
        } catch(Exception $e){
            $config->rollback();
            if(session_status()===PHP_SESSION_NONE) session_start();
            $_SESSION['import_result']=['success'=>0,'skipped'=>0,'failed'=>0,'rekap'=>[],'new_items'=>[],'similar'=>[],'checksum'=>[],'failed_rows'=>[],'warnings'=>["Fatal error: ".$e->getMessage()]];
            header("Location: sdmk.php?id_faskes=$id_faskes&msg=import_error");
            exit;
        }
    }
    // ---------- PASTE IMPORT (AJAX JSON) ----------
    // Helper matching paste: dipakai paste_check (dry-run) & paste_import.
    // Return ['pid'=>?int,'via'=>exact|exact_kat|fuzzy|none|ambiguous,'matched'=>namaMaster].
    $paste_match = function($items, $nameMap, $nama, $kategori) {
        $norm = normalizeNama($nama);
        $pid = null; $via = 'none'; $matched = null;
        $byId = [];
        foreach ($items as $it) $byId[$it['id']] = $it;
        if (!empty($kategori)) {
            $katKey = strtolower(trim($kategori));
            if (isset($nameMap[$norm . '|' . $katKey])) {
                $pid = $nameMap[$norm . '|' . $katKey];
                $via = 'exact_kat';
            }
        }
        if ($pid === null && isset($nameMap[$norm])) { $pid = $nameMap[$norm]; $via = 'exact'; }
        if ($pid === null) {
            $bestPid = null; $bestPct = 0;
            foreach ($items as $it) {
                similar_text(strtolower($norm), strtolower(normalizeNama($it['nama_item'])), $pct);
                if ($pct > $bestPct) { $bestPct = $pct; $bestPid = $it['id']; }
            }
            if ($bestPct >= 80) { $pid = $bestPid; $via = 'fuzzy'; }
        }
        if ($pid !== null && isset($byId[$pid])) $matched = $byId[$pid]['nama_item'];
        return ['pid' => $pid, 'via' => $via, 'matched' => $matched];
    };
    // DRY-RUN untuk preview: tanpa tulis DB, kembalikan status match per baris.
    if ($act === 'paste_check') {
        header('Content-Type: application/json');
        $id_faskes = (int)($_POST['id_faskes'] ?? 0);
        if (!$id_faskes) { echo json_encode(['status'=>false,'error'=>'id_faskes missing']); exit; }
        $fchk = $config->prepare("SELECT id_kecamatan, jenis FROM tbl_faskes WHERE id_faskes=? LIMIT 1");
        $fchk->bind_param('i', $id_faskes);
        $fchk->execute();
        $frow = $fchk->get_result()->fetch_assoc();
        if (!$frow) { echo json_encode(['status'=>false,'error'=>'Faskes tidak ditemukan']); exit; }
        $rows = json_decode($_POST['rows'] ?? '[]', true);
        if (!is_array($rows)) { echo json_encode(['status'=>false,'error'=>'Rows tidak valid']); exit; }
        $items = getItemsForFaskes($config, $id_faskes, $frow['jenis'] ?? '');
        $nameMap = [];
        foreach ($items as $it) {
            $norm = normalizeNama($it['nama_item']);
            $nameMap[$norm . '|' . strtolower($it['kategori'])] = $it['id'];
            if (!isset($nameMap[$norm])) $nameMap[$norm] = $it['id'];
        }
        $out = [];
        foreach ($rows as $i => $row) {
            $nama = trim((string)($row['nama'] ?? ''));
            if ($nama === '') { $out[] = ['i'=>$i,'match'=>null,'via'=>'empty']; continue; }
            $m = $paste_match($items, $nameMap, $nama, $row['kategori'] ?? '');
            $out[] = ['i'=>$i,'match'=>$m['matched'],'via'=>$m['via']];
        }
        echo json_encode(['status'=>true,'checked'=>$out]);
        exit;
    }
    if ($act === 'paste_import') {
        header('Content-Type: application/json');
        $id_faskes = (int)($_POST['id_faskes'] ?? 0);
        if (!$id_faskes) { echo json_encode(['status'=>false,'error'=>'id_faskes missing']); exit; }
        $fchk = $config->prepare("SELECT id_kecamatan, jenis FROM tbl_faskes WHERE id_faskes=? LIMIT 1");
        $fchk->bind_param('i', $id_faskes);
        $fchk->execute();
        $frow = $fchk->get_result()->fetch_assoc();
        if (!$frow) { echo json_encode(['status'=>false,'error'=>'Faskes tidak ditemukan']); exit; }
        $id_kecamatan = (int)$frow['id_kecamatan'];
        $rows = json_decode($_POST['rows'] ?? '[]', true);
        if (!is_array($rows) || empty($rows)) { echo json_encode(['status'=>false,'error'=>'Tidak ada baris data']); exit; }
        // Matching dibatasi item yang tampil untuk jenis faskes ini
        // (scope cocok UNION sudah-punya-data) agar paste RS tidak bocor ke
        // Puskesmas dan sebaliknya.
        $items = getItemsForFaskes($config, $id_faskes, $frow['jenis'] ?? '');
        // Build normalized name map: normalizedName|kat => id
        $nameMap = [];
        foreach ($items as $it) {
            $norm = normalizeNama($it['nama_item']);
            $nameMap[$norm . '|' . strtolower($it['kategori'])] = $it['id'];
            // also store by name alone for fallback
            if (!isset($nameMap[$norm])) $nameMap[$norm] = $it['id'];
        }
        $success = 0; $skipped = 0; $unmatched = [];
        $config->begin_transaction();
        try {
            foreach ($rows as $row) {
                $nama = trim((string)($row['nama'] ?? ''));
                if ($nama === '') { $skipped++; continue; }
                $m = $paste_match($items, $nameMap, $nama, $row['kategori'] ?? '');
                $pid = $m['pid'];
                if ($pid === null) {
                    $unmatched[] = $nama;
                    $skipped++;
                    continue;
                }
                // validate is_total_row
                $chkIt = $config->prepare('SELECT is_total_row FROM tbl_sdm_items WHERE id=? LIMIT 1');
                $chkIt->bind_param('i', $pid);
                $chkIt->execute();
                $itRow = $chkIt->get_result()->fetch_assoc();
                if ($itRow && (int)$itRow['is_total_row'] === 1) { $skipped++; continue; }
                $al = max(0, (int)($row['asn_l'] ?? 0));
                $ap = max(0, (int)($row['asn_p'] ?? 0));
                $nl = max(0, (int)($row['nonasn_l'] ?? 0));
                $np = max(0, (int)($row['nonasn_p'] ?? 0));
                $chk = $config->prepare('SELECT id FROM tbl_sdm_faskes WHERE id_faskes=? AND id_profesi=? AND id_spesialis IS NULL LIMIT 1');
                $chk->bind_param('ii', $id_faskes, $pid);
                $chk->execute();
                $ex = $chk->get_result()->fetch_assoc();
                if ($ex) {
                    $stmt = $config->prepare('UPDATE tbl_sdm_faskes SET asn_l=?, asn_p=?, nonasn_l=?, nonasn_p=?, id_kecamatan=?, aktif=\'Y\', updated_at=NOW() WHERE id=?');
                    $stmt->bind_param('iiiiii', $al, $ap, $nl, $np, $id_kecamatan, $ex['id']);
                } else {
                    $stmt = $config->prepare('INSERT INTO tbl_sdm_faskes (id_kecamatan, id_faskes, id_profesi, id_spesialis, asn_l, asn_p, nonasn_l, nonasn_p, aktif) VALUES (?, ?, ?, NULL, ?, ?, ?, ?, \'Y\')');
                    $stmt->bind_param('iiiiiii', $id_kecamatan, $id_faskes, $pid, $al, $ap, $nl, $np);
                }
                $ok = $stmt->execute();
                if ($ok) $success++; else $skipped++;
            }
            $config->commit();
            echo json_encode(['status'=>true,'success'=>$success,'skipped'=>$skipped,'unmatched'=>$unmatched]);
        } catch (Exception $e) {
            $config->rollback();
            echo json_encode(['status'=>false,'error'=>$e->getMessage()]);
        }
        exit;
    }
}

// ---------- DATA FOR TAB ITEMS ----------
$allItems = [];
$q = $config->query("SELECT id, nama_item, kategori, scope, parent_id, id_parent, urutan, is_total_row, include_in_total, aktif FROM tbl_sdm_items ORDER BY FIELD(kategori,'Tenaga Kesehatan','Asisten Tenaga Kesehatan','Tenaga Penunjang'), urutan, id");
while ($r = $q->fetch_assoc()) $allItems[] = $r;
// Tampilkan master hierarkis juga (anak tepat di bawah induknya).
$allItems = orderSdmItemsHierarchical($allItems);
$parents = array_filter($allItems, fn($x)=> $x['aktif']==='Y' && (int)$x['is_total_row']===0);
$parentMap = [];
foreach ($allItems as $it) $parentMap[$it['id']] = $it['nama_item'];
// Peta untuk warning CRUD dari tab Rekap: jumlah faskes berisi angka>0
// per item + jumlah sub-item aktif per item (tidak mengubah data).
$itemFaskesCount = [];
$qFC = $config->query("SELECT id_profesi, COUNT(*) c FROM (SELECT id_profesi, id_faskes FROM tbl_sdm_faskes WHERE aktif='Y' GROUP BY id_profesi, id_faskes HAVING SUM(jumlah)>0) t GROUP BY id_profesi");
while ($qFC && ($rF = $qFC->fetch_assoc())) $itemFaskesCount[(int)$rF['id_profesi']] = (int)$rF['c'];
$itemChildCount = [];
$qCh = $config->query("SELECT parent_id, COUNT(*) c FROM tbl_sdm_items WHERE aktif='Y' AND parent_id IS NOT NULL GROUP BY parent_id");
while ($qCh && ($rC = $qCh->fetch_assoc())) $itemChildCount[(int)$rC['parent_id']] = (int)$rC['c'];
// ENHANCEMENT UX — referensi nama sub-spesialis dokter (READ-ONLY).
// tbl_spesialis HANYA dibaca sebagai pilihan cepat pengisian field Nama;
// penyimpanan tetap via parent_id di tbl_sdm_items (id_spesialis TIDAK
// dipakai). Baris 'Dokter Umum' dikecualikan: itu item top-level, bukan
// sub-spesialis (format "Dokter Spesialis Dokter Umum (Umum)" tidak masuk akal).
$spesialisRef = [];
$qRef = $config->query("SELECT nama_spesialis, kode FROM tbl_spesialis WHERE aktif='Y' ORDER BY urutan, nama_spesialis");
while ($qRef && ($rR = $qRef->fetch_assoc())) {
    if (strtolower(trim($rR['nama_spesialis'])) === 'dokter umum') continue;
    $spesialisRef[] = ['nama' => $rR['nama_spesialis'], 'kode' => trim((string)($rR['kode'] ?? ''))];
}

// ---------- DISPLAY DATA (FASKES) ----------
$faskesList=getFaskesList($config);
$selectedId=(int)($_GET['id_faskes'] ?? 0);
$selectedFaskes=null;
if($selectedId){
    $stmt=$config->prepare("SELECT f.id_faskes, f.nama_faskes, f.jenis, f.id_kecamatan, k.nama_kecamatan FROM tbl_faskes f LEFT JOIN tbl_kecamatan k ON k.id_kecamatan=f.id_kecamatan WHERE f.id_faskes=? LIMIT 1");
    $stmt->bind_param("i",$selectedId);
    $stmt->execute();
    $selectedFaskes=$stmt->get_result()->fetch_assoc();
}
// Daftar item tampil mengikuti jenis faskes terpilih (RS/Puskesmas/lainnya)
// + item yang sudah punya data di faskes ini (anti-hilang-data).
$items=$selectedFaskes ? getItemsForFaskes($config, $selectedId, $selectedFaskes['jenis'] ?? '') : getItems($config);
$dataMap=[];
if($selectedFaskes){
    $stmt=$config->prepare("SELECT id_profesi, SUM(asn_l) as asn_l, SUM(asn_p) as asn_p, SUM(nonasn_l) as nonasn_l, SUM(nonasn_p) as nonasn_p, SUM(jumlah) as jumlah, MAX(id) as id FROM tbl_sdm_faskes WHERE id_faskes=? AND aktif='Y' GROUP BY id_profesi");
    $stmt->bind_param("i",$selectedId);
    $stmt->execute();
    $res=$stmt->get_result();
    while($row=$res->fetch_assoc()) $dataMap[$row['id_profesi']]=$row;
}
if(isset($_SESSION['import_result'])){
    $importResult=$_SESSION['import_result'];
    unset($_SESSION['import_result']);
}
if(isset($_SESSION['save_result'])){
    $saveResult=$_SESSION['save_result'];
    unset($_SESSION['save_result']);
}
$protectInfo=$_SESSION['protect_info'] ?? '';
unset($_SESSION['protect_info']);
$flash=$_GET['msg'] ?? '';
$username=$_SESSION['admin_username'] ?? 'Admin';
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>SDMK — Master &amp; Rekap Fasyankes - Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
<style>
*{margin:0;padding:0;box-sizing:border-box} body{font-family:'Poppins',sans-serif;background:#061426;min-height:100vh;display:flex;color:#fff}
.sidebar{width:260px;min-height:100vh;background:rgba(255,255,255,0.04);backdrop-filter:blur(12px);border-right:1px solid rgba(255,255,255,0.06);padding:30px 20px;flex-shrink:0;position:sticky;top:0;height:100vh;overflow-y:auto}
.sidebar-brand{display:flex;align-items:center;gap:14px;padding-bottom:30px;border-bottom:1px solid rgba(255,255,255,0.06);margin-bottom:24px}
.sidebar-brand img{width:48px;height:48px;object-fit:contain}
.sidebar-brand h2{color:#fff;font-size:16px;font-weight:700;line-height:1.2}
.sidebar-brand small{display:block;color:#87e3ff;font-size:10px;letter-spacing:1px}
.sidebar-menu{list-style:none}.sidebar-menu li{margin-bottom:4px}
.sidebar-menu a{display:flex;align-items:center;gap:12px;padding:12px 16px;border-radius:12px;color:rgba(255,255,255,0.6);text-decoration:none;font-size:14px;font-weight:500;transition:.3s}
.sidebar-menu a:hover,.sidebar-menu a.active{background:rgba(0,212,255,0.12);color:#fff}
.sidebar-menu a i{width:20px;color:rgba(255,255,255,0.3)}
.sidebar-menu a.active i{color:#00d4ff}
.sidebar-menu .logout{margin-top:30px;border-top:1px solid rgba(255,255,255,0.06);padding-top:20px}
.sidebar-menu .logout a{color:rgba(255,82,82,0.7)}
.main-content{flex:1;padding:30px 40px}
.page-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;flex-wrap:wrap;gap:12px}
.page-header h1{font-size:28px;font-weight:700}
.page-header p{color:#87e3ff;font-size:14px;margin-top:4px}
.page-header .back-link{color:#87e3ff;text-decoration:none;font-size:14px;display:flex;align-items:center;gap:8px}
.card{background:rgba(255,255,255,0.05);backdrop-filter:blur(16px);border-radius:20px;padding:28px;border:1px solid rgba(255,255,255,0.08);margin-bottom:24px}
.card h3{color:#84e7ff;font-size:18px;font-weight:600;margin-bottom:16px;display:flex;align-items:center;gap:10px}
.form-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:16px}
.form-group{display:flex;flex-direction:column}
.form-group label{color:#87e3ff;font-size:12px;font-weight:600;margin-bottom:6px}
.form-group input{padding:10px 14px;border-radius:10px;border:1px solid rgba(255,255,255,0.1);background:rgba(255,255,255,0.06);color:#fff;font-size:13px;font-family:'Poppins',sans-serif}
.form-group select{padding:10px 14px;border-radius:10px;border:1px solid rgba(0,212,255,0.4);background:linear-gradient(135deg,#0b3a5a,#0a2e48);color:#fff;font-size:13px;font-family:'Poppins',sans-serif;font-weight:600}
.form-group select option{background:#0b223c;color:#fff}
.form-group select optgroup{background:#0b223c;color:#00d4ff;font-weight:700}
.btn-primary{padding:10px 24px;border-radius:10px;border:none;background:linear-gradient(135deg,#00d4ff,#0088cc);color:#fff;font-weight:600;cursor:pointer;display:inline-flex;align-items:center;gap:8px}
.btn-icon{padding:6px 12px;border-radius:8px;border:none;background:rgba(0,212,255,0.15);color:#00d4ff;cursor:pointer;font-size:12px;font-weight:600;text-decoration:none;display:inline-flex;align-items:center;gap:6px}
.btn-danger{background:rgba(255,82,82,0.15);color:#ff6b6b}
.badge{padding:3px 10px;border-radius:20px;font-size:11px;font-weight:600}
.badge-A{background:rgba(0,212,255,0.15);color:#72e8ff;border:1px solid rgba(0,212,255,0.3)}
.badge-B{background:rgba(255,193,7,0.15);color:#ffd54f;border:1px solid rgba(255,193,7,0.3)}
.badge-C{background:rgba(76,175,80,0.15);color:#81c784;border:1px solid rgba(76,175,80,0.3)}
.alert{padding:14px 20px;border-radius:12px;margin-bottom:20px;display:flex;align-items:center;gap:12px;font-size:14px}
.alert-success{background:rgba(0,212,255,0.12);border:1px solid rgba(0,212,255,0.2);color:#72e8ff}
.alert-warning{background:rgba(255,193,7,0.12);border:1px solid rgba(255,193,7,0.25);color:#ffd54f}
.alert-error{background:rgba(255,82,82,0.12);border:1px solid rgba(255,82,82,0.2);color:#ff8a80}
.sdmk-wrap{overflow:auto;border-radius:16px;border:1px solid rgba(255,255,255,0.08);background:rgba(255,255,255,0.02)}
#rekapTable{width:100%;min-width:760px;border-collapse:separate;border-spacing:0;table-layout:fixed}
#rekapTable col.col-no{width:52px} #rekapTable col.col-jenis{width:auto} #rekapTable col.col-num{width:74px} #rekapTable col.col-jml{width:78px} #rekapTable col.col-aksi{width:150px}
table.master-table{width:100%;min-width:720px;border-collapse:separate;border-spacing:0}
table.master-table th{padding:10px 10px;color:#87e3ff;font-weight:700;font-size:12px;letter-spacing:.3px;background:#0b223c;border-bottom:1px solid rgba(255,255,255,0.08);position:sticky;top:0;z-index:2;text-align:left}
table.master-table td{padding:9px 10px;border-bottom:1px solid rgba(255,255,255,0.05);font-size:13px;vertical-align:middle}
table.master-table tbody tr:hover{background:rgba(0,212,255,0.06)}
.master-wrap{max-height:60vh}
#rekapTable th{padding:10px 8px;color:#87e3ff;font-weight:700;font-size:12px;letter-spacing:.3px;background:#0b223c;border-bottom:1px solid rgba(255,255,255,0.08);position:sticky;top:0;z-index:2}
#rekapTable th small{font-weight:500;opacity:.8}
#rekapTable td{padding:8px 8px;border-bottom:1px solid rgba(255,255,255,0.05);font-size:13px;vertical-align:middle}
#rekapTable tbody tr[data-kat]:nth-child(odd){background:rgba(255,255,255,0.02)}
#rekapTable tbody tr[data-kat]:hover{background:rgba(0,212,255,0.06)}
#rekapTable td.num{text-align:center;font-variant-numeric:tabular-nums}
#rekapTable td.input-cell{padding:3px}
#rekapTable td.input-cell input{width:100%;padding:7px 4px;border-radius:8px;border:1px solid rgba(255,255,255,0.12);background:rgba(255,255,255,0.06);color:#fff;text-align:center;font-weight:600;font-size:13px;transition:.15s}
#rekapTable td.input-cell input:focus{outline:none;border-color:rgba(0,212,255,.6);background:rgba(0,212,255,.08);box-shadow:0 0 0 3px rgba(0,212,255,.12)}
#rekapTable td.input-cell input::-webkit-outer-spin-button,#rekapTable td.input-cell input::-webkit-inner-spin-button{-webkit-appearance:none;margin:0}
#rekapTable td.input-cell input[type=number]{-moz-appearance:textfield}
.jumlah-cell{font-weight:700;background:rgba(255,255,255,0.04);border-left:1px solid rgba(255,255,255,0.06)}
.kategori-row{background:#FFF2CC;color:#111;font-weight:800;letter-spacing:.3px}
.kategori-row td{padding:11px 12px;border-bottom:1px solid rgba(0,0,0,.08)}
.total-row{background:#DDEBF7;color:#0b223c;font-weight:800}
.total-row td{border-bottom:1px solid rgba(0,0,0,.08)}
.grand-row{background:linear-gradient(135deg,#3a5bc7,#4472C4);color:#fff;font-weight:800}
.grand-row td{padding:12px 10px}
.child-row td:nth-child(2){border-left:3px solid rgba(255,213,79,.35);background:rgba(255,213,79,.04)}
.th-center{text-align:center}
#editModal{display:none;position:fixed;inset:0;background:rgba(0,0,0,0.65);backdrop-filter:blur(6px);z-index:999;justify-content:center;align-items:center}
.modal-box{background:#0b223c;padding:30px;border-radius:20px;max-width:600px;width:95%;border:1px solid rgba(255,255,255,0.1);max-height:90vh;overflow-y:auto}
.tab-nav{display:flex;gap:12px;margin-bottom:24px}
.tab-btn{flex:1;padding:14px 18px;border-radius:14px;border:1px solid rgba(255,255,255,0.08);background:rgba(255,255,255,0.04);color:rgba(255,255,255,0.6);font-weight:600;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:8px;transition:.3s}
.tab-btn.active{background:linear-gradient(135deg,#00d4ff,#0088cc);color:#fff;border-color:rgba(0,212,255,0.3);box-shadow:0 8px 25px rgba(0,212,255,0.2)}
.toolbar{display:flex;gap:10px;flex-wrap:wrap;align-items:center}
/* ===== PASTE IMPORT PANEL ===== */
.paste-panel{border:2px dashed rgba(0,212,255,0.3);border-radius:16px;padding:20px;margin-bottom:20px;background:rgba(0,212,255,0.04);transition:.3s}
.paste-panel.drag-over,.paste-panel.listening{border-color:#00d4ff;background:rgba(0,212,255,0.1)}
.paste-zone{min-height:56px;display:flex;align-items:center;justify-content:center;gap:12px;cursor:pointer;border-radius:10px;padding:14px;color:rgba(255,255,255,0.5);font-size:14px;font-weight:500;transition:.3s;border:1px solid transparent;user-select:none}
.paste-zone:hover,.paste-zone.active{color:#00d4ff;border-color:rgba(0,212,255,0.3);background:rgba(0,212,255,0.07)}
.paste-zone i{font-size:22px;color:#00d4ff}
.paste-preview-wrap{margin-top:14px;border-radius:12px;overflow:auto;max-height:320px;border:1px solid rgba(255,255,255,0.08)}
#pastePreviewTable{width:100%;border-collapse:collapse;font-size:13px}
#pastePreviewTable th{padding:8px 10px;color:#87e3ff;font-weight:700;font-size:11px;background:#0b223c;border-bottom:1px solid rgba(255,255,255,0.1);text-align:center}
#pastePreviewTable th.col-nama{text-align:left}
#pastePreviewTable td{padding:7px 10px;border-bottom:1px solid rgba(255,255,255,0.05);text-align:center}
#pastePreviewTable td.col-nama{text-align:left}
#pastePreviewTable tr.row-ok{background:rgba(76,175,80,0.07)}
#pastePreviewTable tr.row-warn{background:rgba(255,193,7,0.08)}
#pastePreviewTable tr.row-err{background:rgba(255,82,82,0.08)}
.paste-legend{display:flex;gap:16px;font-size:11px;margin-top:10px;flex-wrap:wrap}
.paste-legend span{display:flex;align-items:center;gap:6px;color:rgba(255,255,255,0.6)}
.paste-legend .dot{width:10px;height:10px;border-radius:50%}
.dot-ok{background:#4caf50}.dot-warn{background:#ffc107}.dot-err{background:#ff5252}
@media(max-width:768px){.sidebar{display:none}.main-content{padding:20px}.form-grid{grid-template-columns:1fr}.tab-nav{flex-direction:column}}
</style>
</head>
<body>
<div class="sidebar">
<div class="sidebar-brand"><img src="../../assets/img/kabupaten.png" alt="Logo"><h2>Portal DKK<br><small>Dashboard Admin</small></h2></div>
<ul class="sidebar-menu">
<li><a href="../index.php"><i class="fas fa-chart-pie"></i> Dashboard</a></li>
<li><a href="fasyankes.php"><i class="fas fa-hospital"></i> Fasyankes</a></li>
<li><a href="faskes_rekap.php"><i class="fas fa-table"></i> Rekap Fasyankes</a></li>
<li><a href="sdmk.php" class="active"><i class="fas fa-hospital-user"></i> SDMK</a></li>
<li><a href="kecamatan.php"><i class="fas fa-map"></i> Kecamatan</a></li>
<li><a href="penyakit.php"><i class="fas fa-disease"></i> Penyakit</a></li>
<li><a href="spm.php"><i class="fas fa-chart-pie"></i> SPM Target</a></li>
        <li><a href="spm_realisasi.php"><i class="fas fa-chart-line"></i> SPM Realisasi</a></li>
<li><a href="portal_info.php"><i class="fas fa-circle-info"></i> Informasi Portal</a></li>
<li class="logout"><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
</ul>
</div>
<div class="main-content">
<div class="page-header"><div><h1>SDMK Terpadu</h1><p>Master Jenis SDM &amp; Rekap per Fasyankes — satu halaman, dua tab</p></div><a href="../index.php" class="back-link"><i class="fas fa-arrow-left"></i> Kembali</a></div>
<div class="tab-nav">
<button class="tab-btn <?= $tab==='items'?'active':'' ?>" data-tab="items" onclick="switchTab('items')"><i class="fas fa-list"></i> Master Jenis SDM</button>
<button class="tab-btn <?= $tab==='faskes'?'active':'' ?>" data-tab="faskes" onclick="switchTab('faskes')"><i class="fas fa-hospital-user"></i> Rekap per Fasyankes</button>
</div>
<div id="tab-items" style="display:<?= $tab==='items'?'block':'none' ?>">
<?php if(in_array($msg,['added','updated','deleted','exists','invalid','error','protected','parent_scope'])): ?>
<?php if($msg==='added'):?><div class="alert alert-success"><i class="fas fa-check-circle"></i> Jenis SDM berhasil ditambahkan.</div>
<?php elseif($msg==='updated'):?><div class="alert alert-success"><i class="fas fa-check-circle"></i> Berhasil diperbarui.</div>
<?php elseif($msg==='deleted'):?><div class="alert alert-success"><i class="fas fa-check-circle"></i> Dihapus (soft delete).</div>
<?php elseif($msg==='exists'):?><div class="alert alert-warning"><i class="fas fa-exclamation-triangle"></i> Nama item sudah ada (duplikat nama+kategori).</div>
<?php elseif($msg==='protected'):?><div class="alert alert-error"><i class="fas fa-shield-alt"></i> <?= htmlspecialchars($protectInfo !== '' ? $protectInfo : 'Hapus ditolak: item masih dipakai.') ?></div>
<?php elseif($msg==='parent_scope'):?><div class="alert alert-warning"><i class="fas fa-exclamation-triangle"></i> Parent tidak mencakup scope anak (anak akan yatim di sebagian jenis faskes). Pilih parent yang cakupannya sama/lebih luas, atau kosongkan scope anak.</div>
<?php elseif($msg==='error'):?><div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> Gagal memproses — cek log.</div>
<?php else:?><div class="alert alert-warning"><i class="fas fa-exclamation-triangle"></i> Input tidak valid.</div>
<?php endif; ?>
<?php endif; ?>
<div class="card" style="background:linear-gradient(135deg, rgba(0,212,255,0.08), rgba(0,136,204,0.06));border:1px solid rgba(0,212,255,0.2)"><h3><i class="fas fa-plus-circle" style="color:#00d4ff"></i> Tambah Jenis SDM</h3>
<form method="POST" onsubmit="return fillNamaFromPick('fSpesialis','fNama')"><input type="hidden" name="action" value="add_item"><div class="form-grid">
<div class="form-group"><label>Nama Jenis SDM *</label><input type="text" name="nama_item" id="fNama" placeholder="Contoh: Apoteker" required></div>
<div class="form-group"><label>Kategori *</label><select name="kategori" required><option value="Tenaga Kesehatan">A. Tenaga Kesehatan</option><option value="Asisten Tenaga Kesehatan">B. Asisten Tenaga Kesehatan</option><option value="Tenaga Penunjang">C. Tenaga Penunjang</option></select></div>
<div class="form-group"><label>Berlaku untuk <span style="font-weight:400;color:rgba(255,255,255,0.5)">(kosongkan = semua jenis faskes)</span></label><div style="display:flex;gap:12px;flex-wrap:wrap;font-size:13px"><label><input type="checkbox" name="scope[]" value="rs"> RS</label><label><input type="checkbox" name="scope[]" value="puskesmas"> Puskesmas</label><label><input type="checkbox" name="scope[]" value="lainnya"> Lainnya</label></div></div>
<div class="form-group"><label>Parent (untuk sub-item)</label><select name="id_parent" id="fParent" onchange="syncSpesialisRef('fParent','fSpesialisWrap','fNama')"><option value="">-- Tanpa Parent (top-level) --</option><?php foreach($parents as $p):?><option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nama_item']) ?> (<?= htmlspecialchars($p['kategori']) ?><?= ($p['scope']??'')!=='' ? ' ['.htmlspecialchars($p['scope']).']' : '' ?>)</option><?php endforeach;?></select></div>
<div class="form-group" id="fSpesialisWrap" style="display:none"><label>Pilih cepat sub-spesialis <span style="font-weight:400;color:rgba(255,255,255,0.5)">(opsional — mengisi Nama otomatis, tetap bisa diedit)</span></label><select id="fSpesialis" onchange="pickSpesialis('fSpesialis','fNama')"><option value="">-- Pilih sub-spesialis --</option><?php foreach($spesialisRef as $sp):?><option value="Dokter <?= htmlspecialchars($sp['nama']) ?><?= $sp['kode']!=='' ? ' ('.htmlspecialchars($sp['kode']).')' : '' ?>">Dokter <?= htmlspecialchars($sp['nama']) ?><?= $sp['kode']!=='' ? ' ('.htmlspecialchars($sp['kode']).')' : '' ?></option><?php endforeach;?></select></div>
<div class="form-group"><label>Urutan</label><input type="number" name="urutan" value="<?= count($allItems)+1 ?>" min="0"></div>
</div><div style="margin-top:16px"><button type="submit" class="btn-primary"><i class="fas fa-save"></i> Tambah</button></div></form></div>
<div class="card"><h3><i class="fas fa-table" style="color:#00d4ff"></i> Daftar Jenis SDM (<?= count($allItems) ?>)</h3>
<div class="sdmk-wrap master-wrap">
<?php
function scopeBadge($s){
    $s=trim((string)$s);
    if($s==='') return '<span style="color:rgba(255,255,255,0.5)">Semua</span>';
    $lbl=['rs'=>'RS','puskesmas'=>'Pusk','lainnya'=>'Lain'];
    $out=[];
    foreach(array_map('trim', explode(',', strtolower($s))) as $t){
        $out[]='<span class="badge" style="margin-right:4px">'.htmlspecialchars($lbl[$t] ?? $t).'</span>';
    }
    return implode('', $out);
}
?>
<table class="master-table"><thead><tr><th>#</th><th>Nama</th><th>Kategori</th><th>Berlaku</th><th>Parent</th><th>Urutan</th><th>Include</th><th>Aktif</th><th>Aksi</th></tr></thead><tbody>
<?php foreach($allItems as $idx=>$row):
$katClass = $row['kategori']==='Tenaga Kesehatan'?'badge-A':($row['kategori']==='Asisten Tenaga Kesehatan'?'badge-B':'badge-C');
$letter = $row['kategori']==='Tenaga Kesehatan'?'A':($row['kategori']==='Asisten Tenaga Kesehatan'?'B':'C');
$incLabel = (int)$row['include_in_total']===1 ? '<span style="color:#81c784">YA</span>' : '<span style="color:#ff8a80">TIDAK</span>';
?>
<tr style="<?= $row['aktif']==='N'?'opacity:0.45':'' ?>">
<td><?= $idx+1 ?></td>
<td style="<?= $row['parent_id']?'padding-left:28px':'' ?>"><?php if($row['parent_id']):?><span style="color:#ffd54f">↳</span> <?php endif;?><?= htmlspecialchars($row['nama_item']) ?></td>
<td><span class="badge <?= $katClass ?>"><?= $letter ?>. <?= htmlspecialchars($row['kategori']) ?></span></td>
<td><?= scopeBadge($row['scope'] ?? '') ?></td>
<td><?= $row['parent_id'] ? htmlspecialchars($parentMap[$row['parent_id']] ?? '-') : '<span style="color:rgba(255,255,255,0.3)">-</span>' ?></td>
<td><?= (int)$row['urutan'] ?></td>
<td><?= $incLabel ?></td>
<td><?= $row['aktif']==='Y' ? '<span style="color:#81c784">Y</span>' : '<span style="color:#ff6b6b">N</span>' ?></td>
<td>
<button class="btn-icon edit-btn" data-id="<?= $row['id'] ?>" data-nama="<?= htmlspecialchars($row['nama_item']) ?>" data-kategori="<?= htmlspecialchars($row['kategori']) ?>" data-scope="<?= htmlspecialchars($row['scope'] ?? '') ?>" data-parent="<?= (int)($row['parent_id']??0) ?>" data-urutan="<?= (int)$row['urutan'] ?>" data-aktif="<?= $row['aktif'] ?>"><i class="fas fa-pen"></i> Edit</button>
<form method="POST" style="display:inline" onsubmit="return confirm('Nonaktifkan jenis ini? (soft delete)')"><input type="hidden" name="action" value="delete_item"><input type="hidden" name="id" value="<?= $row['id'] ?>"><button type="submit" class="btn-icon btn-danger"><i class="fas fa-trash"></i></button></form>
</td>
</tr>
<?php endforeach; ?>
</tbody></table></div></div>
</div>
<div id="tab-faskes" style="display:<?= $tab==='faskes'?'block':'none' ?>">
<?php if($flash==='saved'):?><div class="alert alert-success"><i class="fas fa-check-circle"></i> Data berhasil disimpan. Total tersimpan sesuai hitung JS &amp; kolom generated.</div>
<?php elseif($flash==='saved_warn'):?><div class="alert alert-warning"><i class="fas fa-exclamation-triangle"></i> Data tersimpan sebagian — ada baris yang gagal (lihat detail di bawah).</div>
<?php elseif($flash==='reset'):?><div class="alert alert-success"><i class="fas fa-check-circle"></i> Data direset (semua nilai jadi 0).</div>
<?php elseif($flash==='reset_row'):?><div class="alert alert-success"><i class="fas fa-check-circle"></i> Baris di-reset ke 0.</div>
<?php elseif($flash==='deleted'):?><div class="alert alert-success"><i class="fas fa-check-circle"></i> Baris berhasil dihapus (soft delete — tidak dihitung di total/export).</div>
<?php elseif($flash==='need_faskes'):?><div class="alert alert-warning"><i class="fas fa-exclamation-triangle"></i> Pilih Fasyankes terlebih dahulu.</div>
<?php elseif($flash==='import_done'):?><div class="alert alert-success"><i class="fas fa-check-circle"></i> Import selesai — file Export bisa langsung di-import ulang (1:1).</div>
<?php elseif($flash==='import_invalid_header'):?><div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> Header Excel tidak valid — import dibatalkan.</div>
<?php elseif($flash==='import_no_file'):?><div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> File tidak ditemukan.</div>
<?php elseif($flash==='import_error'):?><div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> Gagal import, transaksi dibatalkan.</div>
<?php elseif($flash==='error'):?><div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> Gagal menyimpan — cek detail.</div>
<?php elseif($flash==='item_added'):?><div class="alert alert-success"><i class="fas fa-check-circle"></i> Jenis SDM berhasil ditambahkan — baris baru tampil di bawah dengan nilai 0, siap diisi.</div>
<?php elseif($flash==='item_updated'):?><div class="alert alert-success"><i class="fas fa-check-circle"></i> Jenis SDM berhasil diperbarui.</div>
<?php elseif($flash==='item_deleted'):?><div class="alert alert-success"><i class="fas fa-check-circle"></i> Jenis SDM dinonaktifkan (soft delete).</div>
<?php elseif($flash==='item_exists'):?><div class="alert alert-warning"><i class="fas fa-exclamation-triangle"></i> Nama item sudah ada (duplikat nama+kategori).</div>
<?php elseif($flash==='item_invalid'):?><div class="alert alert-warning"><i class="fas fa-exclamation-triangle"></i> Input jenis SDM tidak valid.</div>
<?php elseif($flash==='item_error'):?><div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> Gagal memproses jenis SDM — cek log.</div>
<?php elseif($flash==='parent_scope'):?><div class="alert alert-warning"><i class="fas fa-exclamation-triangle"></i> Parent tidak mencakup scope anak (anak akan yatim di sebagian jenis faskes). Pilih parent yang cakupannya sama/lebih luas, atau kosongkan scope anak.</div>
<?php elseif($flash==='protected'):?><div class="alert alert-error"><i class="fas fa-shield-alt"></i> <?= htmlspecialchars($protectInfo !== '' ? $protectInfo : 'Hapus ditolak: item masih dipakai.') ?></div>
<?php endif; ?>
<?php if($saveResult):?>
<div class="card" style="border-color:rgba(255,193,7,0.25)"><h3><i class="fas fa-exclamation-triangle" style="color:#ffd54f"></i> Hasil Simpan Manual</h3>
<p>Berhasil proses: <strong><?= (int)$saveResult['success'] ?></strong> baris.</p>
<?php if(!empty($saveResult['warnings'])):?><ul style="margin-top:12px;max-height:200px;overflow:auto;background:rgba(255,193,7,0.08);padding:12px;border-radius:10px;font-size:12px"><?php foreach($saveResult['warnings'] as $w):?><li><?= htmlspecialchars($w) ?></li><?php endforeach;?></ul><?php endif;?></div>
<?php endif;?>
<?php if($importResult):?>
<div class="card" style="border-color:rgba(0,212,255,0.25)"><h3><i class="fas fa-file-excel" style="color:#4CAF50"></i> Hasil Import</h3>
<p style="font-size:15px">Berhasil: <strong style="color:#81c784"><?= (int)$importResult['success'] ?></strong> baris &nbsp;|&nbsp; Dilewati: <strong><?= (int)$importResult['skipped'] ?></strong> baris &nbsp;|&nbsp; Gagal: <strong style="color:<?= ((int)($importResult['failed'] ?? 0))>0 ? '#ff8a80' : '#81c784' ?>"><?= (int)($importResult['failed'] ?? 0) ?></strong> baris<?php if(!empty($importResult['multi'])): ?> &nbsp;|&nbsp; <em>Mode multi-faskes (kolom Kode Faskes)</em><?php endif; ?></p>
<?php $ni = $importResult['new_items'] ?? []; ?>
<h4 style="margin:14px 0 8px;font-size:14px;color:#84e7ff">Item baru otomatis ditambahkan ke master (<?= count($ni) ?>)</h4>
<?php if(empty($ni)): ?><p style="font-size:13px;color:rgba(255,255,255,0.5)">Tidak ada item baru.</p>
<?php else: ?><details open style="font-size:13px"><summary style="cursor:pointer;color:#87e3ff">Tampilkan daftar</summary><div style="max-height:220px;overflow:auto;margin-top:8px">
<table style="width:100%;border-collapse:collapse;font-size:12px">
<tr><th style="text-align:left;padding:6px;border-bottom:1px solid rgba(255,255,255,0.15)">Nama item</th><th style="text-align:left;padding:6px;border-bottom:1px solid rgba(255,255,255,0.15)">Kategori</th><th style="text-align:left;padding:6px;border-bottom:1px solid rgba(255,255,255,0.15)">Parent</th><th style="text-align:right;padding:6px;border-bottom:1px solid rgba(255,255,255,0.15)">Urutan</th></tr>
<?php foreach($ni as $it): ?><tr><td style="padding:6px;border-bottom:1px solid rgba(255,255,255,0.06)"><?= htmlspecialchars($it['nama']) ?></td><td style="padding:6px;border-bottom:1px solid rgba(255,255,255,0.06)"><?= htmlspecialchars($it['kategori']) ?></td><td style="padding:6px;border-bottom:1px solid rgba(255,255,255,0.06)"><?= htmlspecialchars($it['parent']) ?></td><td style="padding:6px;border-bottom:1px solid rgba(255,255,255,0.06);text-align:right"><?= (int)$it['urutan'] ?></td></tr><?php endforeach; ?>
</table></div></details><?php endif; ?>
<?php $sm = $importResult['similar'] ?? []; ?>
<h4 style="margin:14px 0 8px;font-size:14px;color:#84e7ff">Warning kemiripan nama (<?= count($sm) ?>)</h4>
<?php if(empty($sm)): ?><p style="font-size:13px;color:rgba(255,255,255,0.5)">Tidak ada.</p>
<?php else: ?><details open style="font-size:13px"><summary style="cursor:pointer;color:#87e3ff">Tampilkan daftar</summary><div style="max-height:220px;overflow:auto;margin-top:8px">
<table style="width:100%;border-collapse:collapse;font-size:12px">
<tr><th style="text-align:left;padding:6px;border-bottom:1px solid rgba(255,255,255,0.15)">Nama di file</th><th style="text-align:left;padding:6px;border-bottom:1px solid rgba(255,255,255,0.15)">Mirip dengan</th><th style="text-align:right;padding:6px;border-bottom:1px solid rgba(255,255,255,0.15)">Skor</th></tr>
<?php foreach($sm as $s): ?><tr><td style="padding:6px;border-bottom:1px solid rgba(255,255,255,0.06)"><?= htmlspecialchars($s['file']) ?></td><td style="padding:6px;border-bottom:1px solid rgba(255,255,255,0.06)"><?= htmlspecialchars($s['existing']) ?></td><td style="padding:6px;border-bottom:1px solid rgba(255,255,255,0.06);text-align:right"><?= (int)$s['score'] ?>%</td></tr><?php endforeach; ?>
</table>
<p style="font-size:12px;color:rgba(255,255,255,0.5)">Item tetap disimpan sebagai baru. Cek manual di Master Jenis SDM kalau ini duplikat, gabungkan/hapus salah satu.</p></div></details><?php endif; ?>
<?php $ck = $importResult['checksum'] ?? []; ?>
<h4 style="margin:14px 0 8px;font-size:14px;color:#84e7ff">Warning checksum kategori</h4>
<?php if(empty($ck)): ?><p style="font-size:13px;color:rgba(255,255,255,0.5)">Semua checksum kategori sesuai.</p>
<?php else: ?><div style="max-height:220px;overflow:auto;font-size:12px">
<table style="width:100%;border-collapse:collapse">
<tr><th style="text-align:left;padding:6px;border-bottom:1px solid rgba(255,255,255,0.15)">Kategori</th><th style="text-align:right;padding:6px;border-bottom:1px solid rgba(255,255,255,0.15)">Total di file</th><th style="text-align:right;padding:6px;border-bottom:1px solid rgba(255,255,255,0.15)">Hasil parsing</th><th style="text-align:right;padding:6px;border-bottom:1px solid rgba(255,255,255,0.15)">Selisih</th></tr>
<?php foreach($ck as $c): ?><tr><td style="padding:6px;border-bottom:1px solid rgba(255,255,255,0.06)"><?= htmlspecialchars($c['kategori']) ?></td><td style="padding:6px;border-bottom:1px solid rgba(255,255,255,0.06);text-align:right"><?= implode(' / ', array_map('intval', $c['file'])) ?></td><td style="padding:6px;border-bottom:1px solid rgba(255,255,255,0.06);text-align:right"><?= implode(' / ', array_map('intval', $c['sys'])) ?></td><td style="padding:6px;border-bottom:1px solid rgba(255,255,255,0.06);text-align:right"><?php $d=[]; foreach([0,1,2,3] as $ii){ $d[]=(int)$c['file'][$ii]-(int)$c['sys'][$ii]; } echo implode(' / ', $d); ?></td></tr><?php endforeach; ?>
</table></div><?php endif; ?>
<?php $fr = $importResult['failed_rows'] ?? []; ?>
<h4 style="margin:14px 0 8px;font-size:14px;color:#84e7ff">Baris gagal (<?= count($fr) ?>)</h4>
<?php if(empty($fr)): ?><p style="font-size:13px;color:rgba(255,255,255,0.5)">Tidak ada.</p>
<?php else: ?><details open style="font-size:13px"><summary style="cursor:pointer;color:#87e3ff">Tampilkan daftar</summary><div style="max-height:220px;overflow:auto;margin-top:8px">
<table style="width:100%;border-collapse:collapse;font-size:12px">
<tr><th style="text-align:right;padding:6px;border-bottom:1px solid rgba(255,255,255,0.15)">Baris Excel</th><th style="text-align:left;padding:6px;border-bottom:1px solid rgba(255,255,255,0.15)">Isi kolom Jenis SDM</th><th style="text-align:left;padding:6px;border-bottom:1px solid rgba(255,255,255,0.15)">Alasan gagal</th></tr>
<?php foreach($fr as $f): ?><tr><td style="padding:6px;border-bottom:1px solid rgba(255,255,255,0.06);text-align:right"><?= (int)$f['row'] ?></td><td style="padding:6px;border-bottom:1px solid rgba(255,255,255,0.06)"><?= htmlspecialchars($f['jenis']) ?></td><td style="padding:6px;border-bottom:1px solid rgba(255,255,255,0.06)"><?= htmlspecialchars($f['reason']) ?></td></tr><?php endforeach; ?>
</table></div></details><?php endif; ?>
<?php if(!empty($importResult['rekap'])):?>
<h4 style="margin:14px 0 8px;font-size:14px;color:#84e7ff">Agregasi per kecamatan</h4>
<table style="width:100%;border-collapse:collapse;font-size:13px">
<tr><th style="text-align:left;padding:6px;border-bottom:1px solid rgba(255,255,255,0.15)">Kecamatan (agregasi otomatis)</th><th style="text-align:right;padding:6px;border-bottom:1px solid rgba(255,255,255,0.15)">Total SDMK</th></tr>
<?php foreach($importResult['rekap'] as $rk):?><tr><td style="padding:6px;border-bottom:1px solid rgba(255,255,255,0.06)"><?= htmlspecialchars($rk['nama_kecamatan']) ?></td><td style="padding:6px;border-bottom:1px solid rgba(255,255,255,0.06);text-align:right"><strong><?= number_format((int)$rk['total'],0,',','.') ?></strong></td></tr><?php endforeach;?>
</table>
<?php endif;?>
<?php if(!empty($importResult['warnings'])):?><div style="margin-top:12px;max-height:240px;overflow:auto;background:rgba(0,0,0,0.2);padding:12px;border-radius:10px;font-size:12px"><ul style="margin:0;padding-left:18px"><?php foreach($importResult['warnings'] as $w):?><li style="margin-bottom:4px"><?= htmlspecialchars($w) ?></li><?php endforeach;?></ul></div><?php endif;?></div>
<?php endif;?>
<div class="card" style="background:linear-gradient(135deg, rgba(0,212,255,0.08), rgba(0,136,204,0.06));border:1px solid rgba(0,212,255,0.2)"><h3><i class="fas fa-filter" style="color:#00d4ff"></i> Pilih Fasyankes</h3>
<form method="GET" class="toolbar">
<input type="hidden" name="tab" value="faskes">
<div class="form-group" style="min-width:320px"><select name="id_faskes" onchange="this.form.submit()" required><option value="">-- Pilih Fasyankes --</option><?php
$byJenis=[]; foreach($faskesList as $p){ $byJenis[$p['jenis']][]=$p; }
foreach($byJenis as $jenis=>$listJ):?>
<optgroup label="<?= htmlspecialchars($jenis) ?> (<?= count($listJ) ?>)"><?php foreach($listJ as $p):?><option value="<?= $p['id_faskes'] ?>" <?= $selectedId==$p['id_faskes']?'selected':'' ?>><?= htmlspecialchars($p['nama_faskes']) ?> — <?= htmlspecialchars($p['nama_kecamatan']) ?></option><?php endforeach;?></optgroup><?php endforeach;?></select></div>
<?php if($selectedFaskes):?>
<a href="sdmk.php?tab=faskes&action=template&id_faskes=<?= $selectedId ?>" class="btn-primary" style="background:linear-gradient(135deg,#4CAF50,#2E7D32)"><i class="fas fa-download"></i> Template</a>
<a href="sdmk.php?tab=faskes&action=export&id_faskes=<?= $selectedId ?>" class="btn-primary" style="background:linear-gradient(135deg,#FF9800,#EF6C00)"><i class="fas fa-file-export"></i> Export</a>
<?php endif;?>
</form></div>
<?php if($selectedFaskes):?>
<!-- ===== PASTE FROM EXCEL PANEL ===== -->
<div class="card paste-panel" id="pastePanelCard">
<h3 style="margin-bottom:10px"><i class="fas fa-clipboard" style="color:#00d4ff"></i> Paste dari Excel <span style="font-size:12px;font-weight:400;color:rgba(255,255,255,0.45);margin-left:6px">— salin baris dari Excel kamu, klik area di bawah, lalu Ctrl+V</span></h3>
<div class="paste-zone" id="pasteZone" tabindex="0" title="Klik di sini lalu tekan Ctrl+V">
  <i class="fas fa-paste"></i>
  <span id="pasteHint">Klik di sini lalu <strong>Ctrl+V</strong> untuk paste dari Excel</span>
</div>
<div id="pastePreviewSection" style="display:none">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-top:14px;flex-wrap:wrap;gap:8px">
    <div class="paste-legend">
      <span><span class="dot dot-ok"></span> Dikenali & akan disimpan</span>
      <span><span class="dot dot-warn"></span> Tidak dikenali (dilewati)</span>
      <span><span class="dot" style="background:#9e9e9e"></span> Info/baris total (tak disimpan)</span>
    </div>
    <div style="display:flex;gap:8px">
      <button type="button" class="btn-icon" onclick="clearPaste()"><i class="fas fa-times"></i> Batal</button>
      <button type="button" class="btn-primary" id="btnSavePaste" onclick="savePaste()"><i class="fas fa-save"></i> Simpan <span id="pasteCountBadge"></span></button>
    </div>
  </div>
  <div class="paste-preview-wrap">
  <table id="pastePreviewTable">
    <thead><tr>
      <th style="width:44px">No</th>
      <th class="col-nama">Nama Profesi (dari Excel)</th>
      <th>ASN L</th><th>ASN P</th><th>Non-ASN L</th><th>Non-ASN P</th><th>Jumlah</th><th>Status</th>
    </tr></thead>
    <tbody id="pastePreviewBody"></tbody>
  </table>
  </div>
</div>
<div id="pasteResult" style="display:none;margin-top:12px"></div>
</div>
<div class="card">
<div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;margin-bottom:16px">
<h3 style="margin:0"><i class="fas fa-table" style="color:#00d4ff"></i> <?= htmlspecialchars($selectedFaskes['nama_faskes']) ?> <span style="font-weight:400;color:#87e3ff;font-size:12px">(<?= htmlspecialchars($selectedFaskes['jenis']) ?> — <?= htmlspecialchars($selectedFaskes['nama_kecamatan']) ?>)</span></h3>
<div class="toolbar">
<form method="POST" enctype="multipart/form-data" style="display:flex;gap:8px;align-items:center">
<input type="hidden" name="action" value="import"><input type="hidden" name="id_faskes" value="<?= $selectedId ?>">
<input type="file" name="excel_file" accept=".xlsx,.xls" required style="font-size:12px" title="Satu file boleh memuat banyak faskes bila ada kolom Kode Faskes; baris tanpa kode memakai faskes terpilih">
<button type="submit" class="btn-primary" style="background:linear-gradient(135deg,#9C27B0,#6A1B9A)"><i class="fas fa-upload"></i> Import</button>
</form>
<p style="flex-basis:100%;font-size:11px;color:rgba(255,255,255,0.45);margin:6px 0 0">Tips: file Template/Export terbaru memuat kolom <strong>Kode Faskes</strong> — satu file bisa berisi banyak faskes sekaligus; kecamatan diambil otomatis dari data faskes. Baris tanpa kode memakai faskes terpilih di atas.</p>
<form method="POST" onsubmit="return confirm('Reset semua nilai jadi 0 untuk fasyankes ini?')">
<input type="hidden" name="action" value="reset"><input type="hidden" name="id_faskes" value="<?= $selectedId ?>">
<button type="submit" class="btn-primary btn-danger"><i class="fas fa-undo"></i> Reset</button>
</form>
<button type="button" class="btn-primary" style="background:linear-gradient(135deg,#00bcd4,#00838f)" onclick="document.getElementById('addItemModal').style.display='flex'" title="Tambah jenis SDM baru (tersedia untuk semua faskes)"><i class="fas fa-plus"></i> Tambah Jenis SDM</button>
</div>
</div>
<div id="addItemModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.65);backdrop-filter:blur(6px);z-index:999;justify-content:center;align-items:center" onclick="if(event.target===this)this.style.display='none'">
<div class="modal-box">
<h2 style="color:#84e7ff;margin-bottom:6px"><i class="fas fa-plus-circle" style="color:#00d4ff;"></i> Tambah Jenis SDM</h2>
<p style="font-size:12px;color:rgba(255,193,7,0.85);margin-bottom:16px"><i class="fas fa-exclamation-triangle"></i> Atur cakupan "Berlaku untuk" agar jenis baru tidak muncul di semua jenis faskes. Kosongkan untuk semua faskes.</p>
<form method="POST" onsubmit="return fillNamaFromPick('rSpesialis','rNama')"><input type="hidden" name="action" value="add_item"><input type="hidden" name="return_to" value="sdmk.php?tab=faskes&id_faskes=<?= $selectedId ?>"><div class="form-grid">
<div class="form-group"><label>Nama Jenis SDM *</label><input type="text" name="nama_item" id="rNama" placeholder="Contoh: Apoteker" required></div>
<div class="form-group"><label>Kategori *</label><select name="kategori" required><option value="Tenaga Kesehatan">A. Tenaga Kesehatan</option><option value="Asisten Tenaga Kesehatan">B. Asisten Tenaga Kesehatan</option><option value="Tenaga Penunjang">C. Tenaga Penunjang</option></select></div>
<div class="form-group"><label>Berlaku untuk <span style="font-weight:400;color:rgba(255,255,255,0.5)">(kosong = semua)</span></label><div style="display:flex;gap:12px;flex-wrap:wrap;font-size:13px"><label><input type="checkbox" name="scope[]" value="rs"> RS</label><label><input type="checkbox" name="scope[]" value="puskesmas"> Puskesmas</label><label><input type="checkbox" name="scope[]" value="lainnya"> Lainnya</label></div></div>
<div class="form-group"><label>Parent (untuk sub-item)</label><select name="id_parent" id="rParent" onchange="syncSpesialisRef('rParent','rSpesialisWrap','rNama')"><option value="">-- Tanpa Parent (top-level) --</option><?php foreach($parents as $p):?><option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nama_item']) ?> (<?= htmlspecialchars($p['kategori']) ?><?= ($p['scope']??'')!=='' ? ' ['.htmlspecialchars($p['scope']).']' : '' ?>)</option><?php endforeach;?></select></div>
<div class="form-group" id="rSpesialisWrap" style="display:none"><label>Pilih cepat sub-spesialis <span style="font-weight:400;color:rgba(255,255,255,0.5)">(opsional — mengisi Nama otomatis, tetap bisa diedit)</span></label><select id="rSpesialis" onchange="pickSpesialis('rSpesialis','rNama')"><option value="">-- Pilih sub-spesialis --</option><?php foreach($spesialisRef as $sp):?><option value="Dokter <?= htmlspecialchars($sp['nama']) ?><?= $sp['kode']!=='' ? ' ('.htmlspecialchars($sp['kode']).')' : '' ?>">Dokter <?= htmlspecialchars($sp['nama']) ?><?= $sp['kode']!=='' ? ' ('.htmlspecialchars($sp['kode']).')' : '' ?></option><?php endforeach;?></select></div>
<div class="form-group"><label>Urutan</label><input type="number" name="urutan" value="<?= count($allItems)+1 ?>" min="0"></div>
</div><div style="display:flex;gap:12px;margin-top:20px;justify-content:flex-end"><button type="submit" class="btn-primary"><i class="fas fa-save"></i> Tambah</button><button type="button" class="btn-icon btn-danger" onclick="document.getElementById('addItemModal').style.display='none'">Batal</button></div></form>
</div>
</div>
<form method="POST" id="rekapForm">
<input type="hidden" name="action" value="save_rekap"><input type="hidden" name="id_faskes" value="<?= $selectedId ?>">
<div class="sdmk-wrap">
<table id="rekapTable">
<colgroup><col class="col-no"><col class="col-jenis"><col class="col-num"><col class="col-num"><col class="col-num"><col class="col-num"><col class="col-jml"><col class="col-aksi"></colgroup>
<thead>
<tr><th rowspan="2">No</th><th rowspan="2">Jenis SDM</th><th colspan="2">ASN</th><th colspan="2">Non ASN</th><th rowspan="2">Jumlah</th><th rowspan="2">Aksi</th></tr>
<tr><th>L</th><th>P</th><th>L</th><th>P</th></tr>
</thead>
<tbody>
<?php
$kategoriOrder=['Tenaga Kesehatan','Asisten Tenaga Kesehatan','Tenaga Penunjang'];
$kategoriLabel=['Tenaga Kesehatan'=>'A. Tenaga Kesehatan','Asisten Tenaga Kesehatan'=>'B. Asisten Tenaga Kesehatan','Tenaga Penunjang'=>'C. Tenaga Penunjang'];
$grouped=[]; foreach($items as $it) $grouped[$it['kategori']][]=$it;
// Susunan tampil utama (#rekapTable): tiap kelompok diurut hierarkis —
// anak SELALU tepat setelah induknya (urut sibling: urutan,id), terlepas
// dari nilai `urutan` global masing-masing. $items dari
// getItemsForFaskes() sudah hierarkis; ini pertahanan lapis-2.
foreach($grouped as $k=>$g) $grouped[$k]=orderSdmGroupHierarchical($g);
foreach($kategoriOrder as $kat){
 if(empty($grouped[$kat])) continue;
 echo '<tr class="kategori-row"><td colspan="8">'.htmlspecialchars($kategoriLabel[$kat]).'</td></tr>';
 // prepare sibling groups for letter indexing
 $parentChildren=[];
 foreach($grouped[$kat] as $it){
   $pid = $it['parent_id'] ?? $it['id_parent'];
   if($pid){ if(!isset($parentChildren[$pid])) $parentChildren[$pid]=[]; $parentChildren[$pid][]=$it; }
 }
 $numericNo=1;
 foreach($grouped[$kat] as $it){
   $pid = $it['parent_id'] ?? $it['id_parent'];
   $isChild = $pid !== null && $pid !== '' && (int)$pid !== 0;
   $d=$dataMap[$it['id']] ?? ['asn_l'=>0,'asn_p'=>0,'nonasn_l'=>0,'nonasn_p'=>0,'jumlah'=>0,'id'=>0];
   $al=(int)$d['asn_l']; $ap=(int)$d['asn_p']; $nl=(int)$d['nonasn_l']; $np=(int)$d['nonasn_p']; $jum=$al+$ap+$nl+$np;
   if($isChild){
     $siblings=$parentChildren[$pid] ?? [];
     $idx=array_search($it['id'], array_column($siblings,'id'));
     $letter=chr(97+($idx===false?0:$idx));
     $display = htmlspecialchars($letter.'. '.$it['nama_item']);
     $noDisplay = $letter.'.';
     $prefix='&nbsp;&nbsp;&nbsp;';
   } else {
     $display = htmlspecialchars($it['nama_item']);
     $noDisplay = (string)$numericNo;
     $prefix='';
     $numericNo++;
   }
    $includeFlag = (int)$it['include_in_total'];
    $rowCls = $isChild ? ' child-row' : '';
    echo '<tr class="'.trim($rowCls).'" data-kat="'.htmlspecialchars($kat).'" data-include="'.$includeFlag.'" data-profesi="'.$it['id'].'">';
    echo '<td class="num">'.$noDisplay.'</td>';
    echo '<td>'.$prefix.$display.'</td>';
   echo '<td class="input-cell"><input type="number" min="0" name="asn_l['.$it['id'].']" value="'.$al.'" class="inp" data-profesi="'.$it['id'].'"></td>';
   echo '<td class="input-cell"><input type="number" min="0" name="asn_p['.$it['id'].']" value="'.$ap.'" class="inp" data-profesi="'.$it['id'].'"></td>';
   echo '<td class="input-cell"><input type="number" min="0" name="nonasn_l['.$it['id'].']" value="'.$nl.'" class="inp" data-profesi="'.$it['id'].'"></td>';
   echo '<td class="input-cell"><input type="number" min="0" name="nonasn_p['.$it['id'].']" value="'.$np.'" class="inp" data-profesi="'.$it['id'].'"></td>';
   echo '<td class="num jumlah-cell" style="font-weight:700;background:rgba(255,255,255,0.04)">'.$jum.'</td>';
     echo '<td class="num" style="display:flex;gap:6px;justify-content:center;flex-wrap:wrap">';
     // Aksi nilai (per faskes ini)
     if($d['id']){
       echo '<button type="button" onclick="if(confirm(\'Reset nilai baris ini ke 0?\')) postRowAction(\'reset_row\','.$d['id'].')" title="Reset ke 0" class="btn-primary" style="padding:4px 8px;font-size:11px;background:rgba(255,193,7,0.15);color:#ffd54f;border:1px solid rgba(255,193,7,0.25)"><i class="fas fa-undo"></i></button>';
       echo '<button type="button" onclick="if(confirm(\'Hapus baris ini? (item akan hilang dari rekap sampai diisi ulang)\')) postRowAction(\'delete_row\','.$d['id'].')" title="Hapus Baris" class="btn-primary btn-danger" style="padding:4px 8px;font-size:11px"><i class="fas fa-trash"></i></button>';
     }
     // Aksi master item (berlaku global): Edit + Hapus jenis SDM.
     $fcCnt = $itemFaskesCount[$it['id']] ?? 0;
     $chCnt = $itemChildCount[$it['id']] ?? 0;
      echo '<button type="button" class="btn-icon edit-btn-rekap" title="Edit Jenis SDM" style="padding:4px 8px;font-size:11px" data-id="'.$it['id'].'" data-nama="'.htmlspecialchars($it['nama_item']).'" data-kategori="'.htmlspecialchars($it['kategori']).'" data-scope="'.htmlspecialchars($it['scope'] ?? '').'" data-parent="'.(int)($it['parent_id']??0).'" data-urutan="'.(int)$it['urutan'].'" data-aktif="Y" data-faskes="'.$fcCnt.'" data-child="'.$chCnt.'" onclick="openRekapEdit(this)"><i class="fas fa-pen"></i></button>';
      echo '<button type="button" class="btn-icon btn-danger" title="Nonaktifkan Jenis SDM" style="padding:4px 8px;font-size:11px" data-id="'.$it['id'].'" data-nama="'.htmlspecialchars($it['nama_item']).'" data-child="'.$chCnt.'" data-faskes="'.$fcCnt.'" data-return="sdmk.php?tab=faskes&id_faskes='.$selectedId.'" onclick="postMasterDelete(this)"><i class="fas fa-trash"></i></button>';
     echo '</td>';
   echo '</tr>';
 }
 echo '<tr class="total-row" data-total-kat="'.htmlspecialchars($kat).'"><td colspan="6" style="text-align:right">Total '.htmlspecialchars($kategoriLabel[$kat]).'</td><td class="num cat-total">0</td><td></td></tr>';
}
$lblGT = labelJenisFaskes($selectedFaskes['jenis']); echo '<tr class="grand-row"><td colspan="6" style="text-align:right">TOTAL SDM KESEHATAN dan TENAGA PENUNJANG DI '.$lblGT.' '.strtoupper(htmlspecialchars($selectedFaskes['nama_faskes'])).' TAHUN '.date('Y').'</td><td class="num" id="grandTotal">0</td><td></td></tr>';
?>
</tbody>
</table>
</div>
<div style="margin-top:16px;display:flex;gap:12px;align-items:center;flex-wrap:wrap"><button type="submit" class="btn-primary"><i class="fas fa-save"></i> Simpan Rekap</button></div>
</form>
<form id="rowActionForm" method="POST" style="display:none">
<input type="hidden" name="action" id="rowAction" value="">
<input type="hidden" name="id_faskes" value="<?= $selectedId ?>">
<input type="hidden" name="row_id" id="rowId" value="">
</form>
<form id="masterDeleteForm" method="POST" style="display:none">
<input type="hidden" name="action" value="delete_item">
<input type="hidden" name="id" id="masterDelId" value="">
<input type="hidden" name="return_to" id="masterDelReturn" value="">
</form>
<script>
var CURRENT_FASKES = <?= (int)$selectedId ?>;
function postRowAction(action, rowId){
  document.getElementById('rowAction').value = action;
  document.getElementById('rowId').value = rowId;
  document.getElementById('rowActionForm').submit();
}
function postMasterDelete(btn){
  // Hapus master dari tab Rekap via form TERPISAH (tidak boleh nested di
  // dalam rekapForm — nested form membuat submit Simpan Rekap terbajak
  // menjadi delete_item + merusak penyimpanan).
  var nama = btn.dataset.nama || '';
  var ch = parseInt(btn.dataset.child || '0', 10);
  var fk = parseInt(btn.dataset.faskes || '0', 10);
  var msg = "Nonaktifkan jenis '" + nama + "'? (soft delete)";
  if(ch > 0) msg += "\n\nItem ini memiliki " + ch + " sub-item aktif.";
  if(fk > 0) msg += "\nItem ini sudah memiliki data di " + fk + " faskes.";
  if(!confirm(msg)) return;
  document.getElementById('masterDelId').value = btn.dataset.id;
  document.getElementById('masterDelReturn').value = btn.dataset.return || '';
  document.getElementById('masterDeleteForm').submit();
}
</script>
<script>
(function(){
 const table=document.getElementById('rekapTable');
 if(!table) return;
 function recalc(){
   const rows=table.querySelectorAll('tbody tr[data-kat]');
   const catSums={};
   let grand=0;
   rows.forEach(tr=>{
     const kat=tr.dataset.kat;
     const include=tr.dataset.include;
     const inputs=tr.querySelectorAll('input.inp');
     let s=0;
     inputs.forEach(i=>{ let v=parseInt(i.value,10); if(isNaN(v)||v<0) v=0; s+=v; });
     tr.querySelector('.jumlah-cell').textContent=s;
     // For Tenaga Kesehatan, only include_in_total=1 counts to total
     let counts=true;
     if(kat==='Tenaga Kesehatan' && include==='0') counts=false;
     if(counts){
       catSums[kat]=(catSums[kat]||0)+s;
       grand+=s;
     }
   });
   table.querySelectorAll('tr[data-total-kat]').forEach(tr=>{
     const k=tr.dataset.totalKat;
     tr.querySelector('.cat-total').textContent=catSums[k]||0;
   });
   const gt=document.getElementById('grandTotal');
   if(gt) gt.textContent=grand;
 }
 table.addEventListener('input', recalc);
 recalc();
})();
</script>
<?php else:?>
<div class="card" style="text-align:center;color:rgba(255,255,255,0.4);padding:40px"><i class="fas fa-hand-pointer" style="font-size:32px;color:#00d4ff;margin-bottom:12px"></i><p>Pilih Fasyankes di atas untuk mulai input rekap SDMK.</p></div>
<?php endif;?>
</div>
</div>
<div id="editModal"><div class="modal-box">
<h2 style="color:#84e7ff;margin-bottom:16px"><i class="fas fa-pen" style="color:#00d4ff"></i> Edit Jenis SDM</h2>
<form method="POST" onsubmit="return fillNamaFromPick('eSpesialis','eNama')"><input type="hidden" name="action" value="edit_item"><input type="hidden" name="id" id="eId"><input type="hidden" name="return_to" id="eReturn" value=""><div class="form-grid">
<div class="form-group"><label>Nama *</label><input type="text" name="nama_item" id="eNama" required></div>
<div class="form-group"><label>Kategori *</label><select name="kategori" id="eKat"><option value="Tenaga Kesehatan">A. Tenaga Kesehatan</option><option value="Asisten Tenaga Kesehatan">B. Asisten Tenaga Kesehatan</option><option value="Tenaga Penunjang">C. Tenaga Penunjang</option></select></div>
<div class="form-group"><label>Berlaku untuk <span style="font-weight:400;color:rgba(255,255,255,0.5)">(kosong = semua)</span></label><div style="display:flex;gap:12px;flex-wrap:wrap;font-size:13px"><label><input type="checkbox" name="scope[]" value="rs" class="eScope"> RS</label><label><input type="checkbox" name="scope[]" value="puskesmas" class="eScope"> Puskesmas</label><label><input type="checkbox" name="scope[]" value="lainnya" class="eScope"> Lainnya</label></div></div>
<div class="form-group"><label>Parent</label><select name="id_parent" id="eParent" onchange="syncSpesialisRef('eParent','eSpesialisWrap','eNama')"><option value="">-- Tanpa Parent --</option><?php foreach($parents as $p):?><option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nama_item']) ?><?= ($p['scope']??'')!=='' ? ' ['.htmlspecialchars($p['scope']).']' : '' ?></option><?php endforeach;?></select></div>
<div class="form-group" id="eSpesialisWrap" style="display:none"><label>Pilih cepat sub-spesialis <span style="font-weight:400;color:rgba(255,255,255,0.5)">(opsional)</span></label><select id="eSpesialis" onchange="pickSpesialis('eSpesialis','eNama')"><option value="">-- Pilih sub-spesialis --</option><?php foreach($spesialisRef as $sp):?><option value="Dokter <?= htmlspecialchars($sp['nama']) ?><?= $sp['kode']!=='' ? ' ('.htmlspecialchars($sp['kode']).')' : '' ?>">Dokter <?= htmlspecialchars($sp['nama']) ?><?= $sp['kode']!=='' ? ' ('.htmlspecialchars($sp['kode']).')' : '' ?></option><?php endforeach;?></select></div>
<div class="form-group"><label>Urutan</label><input type="number" name="urutan" id="eUrutan" min="0"></div>
<div class="form-group"><label>Aktif</label><select name="aktif" id="eAktif"><option value="Y">Y - Aktif</option><option value="N">N - Nonaktif</option></select></div>
</div><div style="display:flex;gap:12px;margin-top:20px;justify-content:flex-end"><button type="submit" class="btn-primary"><i class="fas fa-save"></i> Simpan</button><button type="button" class="btn-icon btn-danger" onclick="document.getElementById('editModal').style.display='none'">Batal</button></div></form>
</div></div>
<script>
function syncSpesialisRef(selId, wrapId, namaId){
  // Tampilkan pilihan cepat sub-spesialis hanya bila parent terpilih
  // nama-nya mengandung "dokter" (mis. Dokter Umum, Dokter Gigi).
  var sel=document.getElementById(selId), wrap=document.getElementById(wrapId);
  if(!sel||!wrap) return;
  var txt=sel.options.length&&sel.selectedIndex>=0?sel.options[sel.selectedIndex].text:'';
  var isDokter=/dokter/i.test(txt)&&sel.value!=='';
  wrap.style.display=isDokter?'block':'none';
}
function pickSpesialis(selId, namaId){
  // Isi field Nama dari dropdown pilihan cepat (boleh diedit manual).
  var sel=document.getElementById(selId), inp=document.getElementById(namaId);
  if(sel&&inp&&sel.value!==''){ inp.value=sel.value; }
}
function fillNamaFromPick(selId, namaId){
  // Pengaman submit: bila Nama kosong tapi pilihan cepat terisi,
  // salin dulu supaya tidak tersimpan baris tanpa nama.
  var sel=document.getElementById(selId), inp=document.getElementById(namaId);
  if(sel&&inp&&inp.value.trim()===''&&sel.value!==''){ inp.value=sel.value; }
  return true;
}
function openRekapEdit(btn){
  // Edit jenis SDM dari tab Rekap: reuse modal + handler edit_item,
  // TANPA pindah tab. Warning bila item sudah punya data di N faskes.
  var n = parseInt(btn.dataset.faskes || '0', 10);
  if(n > 0){
    if(!confirm('Item ini sudah memiliki data di ' + n + ' faskes.\nMengubah kategori/parent akan memengaruhi Total di faskes tsb.\n\nLanjutkan edit?')) return;
  }
  document.getElementById('eId').value = btn.dataset.id;
  document.getElementById('eNama').value = btn.dataset.nama;
  document.getElementById('eKat').value = btn.dataset.kategori;
  document.getElementById('eParent').value = btn.dataset.parent || '';
  document.getElementById('eUrutan').value = btn.dataset.urutan;
  document.getElementById('eAktif').value = btn.dataset.aktif;
  document.getElementById('eSpesialis').value = '';
  setScopeCheckboxes(btn.dataset.scope || '');
  document.getElementById('eReturn').value = 'sdmk.php?tab=faskes&id_faskes=' + CURRENT_FASKES;
  syncSpesialisRef('eParent','eSpesialisWrap','eNama');
  document.getElementById('editModal').style.display = 'flex';
}
function confirmRekapDelete(form){
  // LEGACY: tombol hapus tab Rekap kini via postMasterDelete + masterDeleteForm
  // (form terpisah). Fungsi ini dipertahankan agar tidak ada referensi gantung.
  var namaEl = form.querySelector('.del-nama');
  var nama = namaEl ? namaEl.value : '';
  var chEl = form.querySelector('.del-child');
  var fkEl = form.querySelector('.del-faskes');
  var ch = parseInt((chEl && chEl.value) || '0', 10);
  var fk = parseInt((fkEl && fkEl.value) || '0', 10);
  var msg = "Nonaktifkan jenis '" + nama + "'? (soft delete)";
  if(ch > 0) msg += "\n\nItem ini memiliki " + ch + " sub-item aktif.";
  if(fk > 0) msg += "\nItem ini sudah memiliki data di " + fk + " faskes.";
  return confirm(msg);
}
function switchTab(name){
  document.querySelectorAll('.tab-btn').forEach(b=>b.classList.toggle('active', b.dataset.tab===name));
  document.getElementById('tab-items').style.display = name==='items'?'block':'none';
  document.getElementById('tab-faskes').style.display = name==='faskes'?'block':'none';
  const url=new URL(window.location);
  url.searchParams.set('tab', name);
  history.replaceState({},'',url);
}
function setScopeCheckboxes(scopeCsv){
  var set={}; String(scopeCsv||'').split(',').forEach(function(v){ set[v.trim()]=true; });
  document.querySelectorAll('.eScope').forEach(function(cb){ cb.checked=!!set[cb.value]; });
}
document.querySelectorAll('.edit-btn').forEach(b=>b.onclick=function(){
  document.getElementById('eId').value=this.dataset.id;
  document.getElementById('eNama').value=this.dataset.nama;
  document.getElementById('eKat').value=this.dataset.kategori;
  document.getElementById('eParent').value=this.dataset.parent||'';
  document.getElementById('eUrutan').value=this.dataset.urutan;
  document.getElementById('eAktif').value=this.dataset.aktif;
  document.getElementById('eSpesialis').value='';
  setScopeCheckboxes(this.dataset.scope||'');
  syncSpesialisRef('eParent','eSpesialisWrap','eNama');
  document.getElementById('editModal').style.display='flex';
  switchTab('items');
});
document.getElementById('editModal').onclick=function(e){if(e.target===this)this.style.display='none'}
</script>
<!-- Notify tab Portal setelah mutasi SDM berhasil (?msg sukses) -->
<script>
// ===== PASTE FROM EXCEL =====
(function(){
  var pasteZone = document.getElementById('pasteZone');
  var previewSection = document.getElementById('pastePreviewSection');
  var previewBody = document.getElementById('pastePreviewBody');
  var pasteResult = document.getElementById('pasteResult');
  var pasteCountBadge = document.getElementById('pasteCountBadge');
  var pasteCard = document.getElementById('pastePanelCard');
  var pendingRows = [];

  if (!pasteZone) return;

  // Activate focus when clicked
  pasteZone.addEventListener('click', function(){
    pasteZone.classList.add('active');
    pasteZone.focus();
    document.getElementById('pasteHint').innerHTML = '<strong style="color:#00d4ff">Siap! Tekan Ctrl+V sekarang</strong>&nbsp;&nbsp;<i class="fas fa-keyboard" style="color:#00d4ff"></i>';
  });
  pasteZone.addEventListener('blur', function(){
    pasteZone.classList.remove('active');
    if (pendingRows.length === 0) document.getElementById('pasteHint').innerHTML = 'Klik di sini lalu <strong>Ctrl+V</strong> untuk paste dari Excel';
  });

  // Listen paste on zone AND document (in case focus moved)
  function handlePaste(e){
    var cd = e.clipboardData || window.clipboardData;
    if (!cd) return;
    var text = cd.getData('text/plain') || cd.getData('text');
    if (!text || !text.trim()) return;
    e.preventDefault();
    parseAndPreview(text.trim());
  }
  pasteZone.addEventListener('paste', handlePaste);
  document.addEventListener('paste', function(e){
    // Only process if paste-zone is focused or card is active
    if (document.activeElement === pasteZone || pasteCard.contains(document.activeElement)) {
      handlePaste(e);
    }
  });

  function normalizeNum(v){
    if (v === undefined || v === null || v === '') return 0;
    v = String(v).replace(/,/g,'').trim();
    var n = parseInt(v, 10);
    return isNaN(n) || n < 0 ? 0 : n;
  }

  function parseAndPreview(text){
    var lines = text.split(/\r?\n/).filter(function(l){ return l.trim() !== ''; });
    if (!lines.length) return;

    // ----------------------------------------------------------------
    // STEP 1: Temukan baris header — cari yang mengandung 'JENIS SDM'
    // ----------------------------------------------------------------
    var dataStart = 0;
    var colJenis = -1;
    var colNo    = -1;
    var colAsn1  = -1; // ASN L
    var colAsn2  = -1; // ASN P
    var colNon1  = -1; // Non-ASN L
    var colNon2  = -1; // Non-ASN P

    for (var hi = 0; hi < Math.min(lines.length, 6); hi++) {
      var hcols = lines[hi].split('\t').map(function(c){ return c.trim(); });
      var jiIdx = -1;
      for (var ci = 0; ci < hcols.length; ci++) {
        if (/^jenis\s+sdm$/i.test(hcols[ci])) { jiIdx = ci; break; }
      }
      if (jiIdx >= 0) {
        colJenis = jiIdx;
        colNo    = jiIdx > 0 ? jiIdx - 1 : -1;
        // Format DKK: Jenis+1=ASN-L, Jenis+2=ASN-P, Jenis+3=NonASN-L, Jenis+4=NonASN-P
        colAsn1 = jiIdx + 1;
        colAsn2 = jiIdx + 2;
        colNon1 = jiIdx + 3;
        colNon2 = jiIdx + 4;
        // Skip baris sub-header L P L P jika ada di baris berikutnya
        var nextLine = (hi + 1 < lines.length) ? lines[hi + 1].split('\t').map(function(c){ return c.trim().toUpperCase(); }) : [];
        var lpCount = nextLine.filter(function(c){ return c === 'L' || c === 'P'; }).length;
        dataStart = hi + 1 + (lpCount >= 2 ? 1 : 0);
        break;
      }
    }

    // Jika tidak ada header, gunakan heuristik
    if (colJenis === -1) {
      var fc = lines[0].split('\t');
      var numericCount = fc.filter(function(c){ var n = parseInt(c,10); return !isNaN(n); }).length;
      if (fc.length >= 5 && numericCount >= 3) {
        // Nama | ASN-L | ASN-P | Non-L | Non-P [| Jumlah]
        colJenis = 0; colNo = -1;
        colAsn1 = 1; colAsn2 = 2; colNon1 = 3; colNon2 = 4;
        dataStart = 0;
      } else {
        // Format paling sederhana: Nama | Jumlah
        colJenis = 0; colNo = -1;
        colAsn1 = 1; colAsn2 = -1; colNon1 = -1; colNon2 = -1;
        dataStart = 0;
      }
    }

    // ----------------------------------------------------------------
    // STEP 2: Parse baris data — PERTAHANKAN STRUKTUR (kategori/sub/total)
    // agar preview mirip tabel sumber, bukan daftar datar.
    // kind: 'cat' (band seksi) | 'item' (disimpan) | 'total' (info, tak disimpan)
    // ----------------------------------------------------------------
    function detectKatPaste(t){
      var s = String(t || '').toLowerCase();
      if (s.indexOf('asisten tenaga') >= 0) return 'Asisten Tenaga Kesehatan';
      if (s.indexOf('tenaga penunjang') >= 0) return 'Tenaga Penunjang';
      if (s.indexOf('tenaga kesehatan') >= 0) return 'Tenaga Kesehatan';
      return null;
    }
    function cleanNamaPaste(v){
      return String(v || '').replace(/^\s*[a-z]\.?\s+/i, '').replace(/^\s*\d+[\.)\s]+/, '').trim();
    }
    pendingRows = [];
    var dispRows = [];
    var curKat = '';

    for (var li = dataStart; li < lines.length; li++) {
      var cols = lines[li].split('\t');
      var noVal   = colNo >= 0 ? (cols[colNo] || '').trim() : '';
      var namaVal = colJenis >= 0 ? (cols[colJenis] || '').trim() : '';
      var asnLVal = colAsn1 >= 0 ? (cols[colAsn1] || '') : '';
      var asnPVal = colAsn2 >= 0 ? (cols[colAsn2] || '') : '';
      var nonLVal = colNon1 >= 0 ? (cols[colNon1] || '') : '';
      var nonPVal = colNon2 >= 0 ? (cols[colNon2] || '') : '';

      var displayText = namaVal || noVal;
      if (!displayText) continue;

      var hasNums = (String(asnLVal).trim() !== '' || String(asnPVal).trim() !== '' ||
                     String(nonLVal).trim() !== '' || String(nonPVal).trim() !== '');
      // SKIP: header label saja (ASN, Non ASN, Jumlah, No)
      if (/^(asn|non\s+asn|jumlah|no\.?)$/i.test(displayText.trim())) continue;

      // Baris kategori: penanda di SISI MANA PUN (No atau Jenis), tanpa angka.
      // Cth template-merge: teks di satu sisi; cth tabel foto: "A." di No +
      // "Tenaga Kesehatan" di Jenis (dua-duanya terisi). Jadi band seksi.
      var katHere = detectKatPaste(displayText) || detectKatPaste(noVal === displayText ? namaVal : noVal);
      if (katHere && !hasNums) {
        curKat = katHere;
        dispRows.push({ kind: 'cat', kat: katHere });
        continue;
      }
      // Baris total / grand total: penanda di sisi mana pun -> info redup, tak disimpan.
      // Cth: "Total X" di No + angka di Jenis, atau "TOTAL SDM..." satu sel.
      // Label pakai sisi yang memuat kata Total (bukan sisa angkanya).
      var otherSide = (noVal === displayText ? namaVal : noVal);
      if (/^(total\b|TOTAL\s+SDM)/i.test(displayText) || /^(total\b|TOTAL\s+SDM)/i.test(otherSide)) {
        var totalLabel = /^(total\b|TOTAL\s+SDM)/i.test(displayText) ? displayText : otherSide;
        dispRows.push({ kind: 'total', nama: totalLabel,
          asn_l: normalizeNum(asnLVal), asn_p: normalizeNum(asnPVal),
          nonasn_l: normalizeNum(nonLVal), nonasn_p: normalizeNum(nonPVal) });
        continue;
      }

      // Baris item (termasuk kategori-berangka spt "B. Asisten..." -> data + konteks).
      var nama = cleanNamaPaste(namaVal || noVal);
      if (!nama) continue;
      // Pengaman: nama murni angka = sisa baris total/pecahan (cth "8","0","90"
      // dari "Total X | 8 | ..." atau lanjutan grand total). Bukan item SDM.
      if (/^\d+$/.test(nama)) continue;
      var katRow = katHere || curKat;
      if (katHere && hasNums) curKat = katHere;
      var noLetter = noVal.replace(/\.+$/, '');
      var isSub = /^[a-zA-Z]$/.test(noLetter);
      dispRows.push({ kind: 'item', no: noVal, sub: isSub, nama: nama,
        kategori: katRow,
        asn_l: normalizeNum(asnLVal), asn_p: normalizeNum(asnPVal),
        nonasn_l: normalizeNum(nonLVal), nonasn_p: normalizeNum(nonPVal),
        check: null });
    }

    var dataRows = dispRows.filter(function(r){ return r.kind === 'item'; });
    if (!dataRows.length) {
      showResult('warning','Tidak ada baris data yang bisa diparse. Pastikan kamu menyalin baris yang benar dari Excel.');
      return;
    }

    pendingRows = dataRows;
    previewSection.style.display = 'block';
    pasteZone.classList.remove('active');
    document.getElementById('pasteHint').innerHTML = '<i class="fas fa-spinner fa-spin" style="color:#00d4ff"></i> &nbsp;Memeriksa ' + dataRows.length + ' baris ke master...';
    pasteCountBadge.textContent = '(' + dataRows.length + ' baris)';
    renderPreview(dispRows, false);
    // Cek match ke server (dry-run), lalu render ulang berstatus.
    var fdCheck = new FormData();
    fdCheck.append('action', 'paste_check');
    fdCheck.append('id_faskes', '<?= (int)$selectedId ?>');
    fdCheck.append('rows', JSON.stringify(dataRows.map(function(r){
      return { nama: r.nama, kategori: r.kategori };
    })));
    fetch('sdmk.php', { method: 'POST', body: fdCheck })
      .then(function(resp){ return resp.json(); })
      .then(function(data){
        if (data.status && data.checked) {
          data.checked.forEach(function(c){
            if (dataRows[c.i]) dataRows[c.i].check = c;
          });
        }
        renderPreview(dispRows, true);
        var nOk = dataRows.filter(function(r){ return r.check && r.check.match; }).length;
        var nSkip = dataRows.length - nOk;
        document.getElementById('pasteHint').innerHTML = '<i class="fas fa-check" style="color:#4caf50"></i> &nbsp;' + dataRows.length + ' baris terpaste (' + nOk + ' dikenali, ' + nSkip + ' dilewati). Cek preview lalu klik <strong>Simpan</strong>.';
      })
      .catch(function(){
        renderPreview(dispRows, true);
        document.getElementById('pasteHint').innerHTML = '<i class="fas fa-check" style="color:#4caf50"></i> &nbsp;' + dataRows.length + ' baris terpaste (status match tak bisa dicek). Cek preview lalu klik <strong>Simpan</strong>.';
      });
  }


  function renderPreview(dispRows, checked){
    var html = '';
    dispRows.forEach(function(r){
      if (r.kind === 'cat') {
        html += '<tr><td colspan="8" style="text-align:left;background:rgba(255,242,204,0.12);color:#ffe082;font-weight:700;padding:7px 10px">' + escHtml(r.kat) + '</td></tr>';
        return;
      }
      var jml = r.asn_l + r.asn_p + r.nonasn_l + r.nonasn_p;
      if (r.kind === 'total') {
        html += '<tr style="opacity:.55">' +
          '<td style="text-align:center;color:rgba(255,255,255,0.5)">∑</td>' +
          '<td class="col-nama"><em>' + escHtml(r.nama) + '</em> <small style="opacity:.6">(info, tak disimpan)</small></td>' +
          '<td>' + r.asn_l + '</td><td>' + r.asn_p + '</td>' +
          '<td>' + r.nonasn_l + '</td><td>' + r.nonasn_p + '</td>' +
          '<td><strong>' + jml + '</strong></td><td>—</td></tr>';
        return;
      }
      var cls = 'row-ok', status;
      if (!checked) {
        cls = '';
        status = '<span style="color:rgba(255,255,255,0.4)"><i class="fas fa-spinner fa-spin"></i></span>';
      } else if (r.check && r.check.match) {
        var viaNote = r.check.via === 'fuzzy' ? ' <small style="opacity:.65">(mirip)</small>' : '';
        status = '<span style="color:#81c784" title="Cocok: ' + escHtml(r.check.match) + '"><i class="fas fa-check"></i> ' + escHtml(r.check.match) + viaNote + '</span>';
      } else {
        cls = 'row-warn';
        status = '<span style="color:#ffd54f"><i class="fas fa-exclamation-triangle"></i> dilewati</span>';
      }
      html += '<tr class="' + cls + '">' +
        '<td style="text-align:center;color:rgba(255,255,255,0.5">' + escHtml(r.no || '') + '</td>' +
        '<td class="col-nama">' + (r.sub ? '<span style="color:#ffd54f">↳</span> ' : '') + escHtml(r.nama) + '</td>' +
        '<td>' + r.asn_l + '</td>' +
        '<td>' + r.asn_p + '</td>' +
        '<td>' + r.nonasn_l + '</td>' +
        '<td>' + r.nonasn_p + '</td>' +
        '<td><strong>' + jml + '</strong></td>' +
        '<td>' + status + '</td>' +
        '</tr>';
    });
    previewBody.innerHTML = html;
  }

  function escHtml(s){
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }

  window.clearPaste = function(){
    pendingRows = [];
    previewSection.style.display = 'none';
    previewBody.innerHTML = '';
    pasteResult.style.display = 'none';
    pasteResult.innerHTML = '';
    document.getElementById('pasteHint').innerHTML = 'Klik di sini lalu <strong>Ctrl+V</strong> untuk paste dari Excel';
    pasteCountBadge.textContent = '';
  };

  function showResult(type, msg){
    var colors = { success: 'rgba(76,175,80,0.15)', warning: 'rgba(255,193,7,0.12)', error: 'rgba(255,82,82,0.12)' };
    var borders = { success: 'rgba(76,175,80,0.3)', warning: 'rgba(255,193,7,0.3)', error: 'rgba(255,82,82,0.3)' };
    var icons = { success: 'fa-check-circle', warning: 'fa-exclamation-triangle', error: 'fa-times-circle' };
    var textColors = { success: '#81c784', warning: '#ffd54f', error: '#ff8a80' };
    pasteResult.innerHTML = '<div style="padding:14px 18px;border-radius:12px;background:'+colors[type]+';border:1px solid '+borders[type]+';color:'+textColors[type]+';font-size:14px;display:flex;align-items:flex-start;gap:12px"><i class="fas '+icons[type]+'" style="margin-top:2px"></i><div>'+msg+'</div></div>';
    pasteResult.style.display = 'block';
  }

  window.savePaste = function(){
    if (!pendingRows.length) return;
    var btn = document.getElementById('btnSavePaste');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyimpan...';
    pasteResult.style.display = 'none';

    var fd = new FormData();
    fd.append('action', 'paste_import');
    fd.append('id_faskes', '<?= (int)$selectedId ?>');
    fd.append('rows', JSON.stringify(pendingRows));

    fetch('sdmk.php', { method: 'POST', body: fd })
      .then(function(r){ return r.json(); })
      .then(function(data){
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save"></i> Simpan <span id="pasteCountBadge">' + pasteCountBadge.textContent + '</span>';
        if (data.status) {
          var msg = '<strong>' + data.success + ' baris berhasil disimpan</strong>.';
          if (data.skipped > 0) msg += ' ' + data.skipped + ' baris dilewati.';
          if (data.unmatched && data.unmatched.length) {
            msg += '<br><br><strong>Tidak dikenali (tidak disimpan):</strong><ul style="margin:6px 0 0 16px">' +
              data.unmatched.map(function(n){ return '<li>' + escHtml(n) + '</li>'; }).join('') +
              '</ul><small style="opacity:.7">Nama di atas tidak cocok dengan master Jenis SDM. Periksa ejaan atau tambah dulu via Master Jenis SDM.</small>';
          }
          showResult('success', msg);
          // Refresh input values in rekap table
          setTimeout(function(){ location.reload(); }, 2200);
        } else {
          showResult('error', 'Gagal: ' + escHtml(data.error || 'unknown error'));
        }
      })
      .catch(function(err){
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save"></i> Simpan';
        showResult('error', 'Error jaringan: ' + escHtml(String(err)));
      });
  };
})();
</script>
<script>window.PORTAL_NOTIFY_OK=["added","updated","deleted","saved","saved_warn","reset","reset_row","import_done"];</script>
<script src="../assetsadmin/portal-notify.js"></script>
</body>
</html>
