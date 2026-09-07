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

function getItems($config) {
    $items=[];
    $q=$config->query("SELECT id, nama_item, kategori, id_parent, urutan FROM tbl_sdm_items WHERE aktif='Y' ORDER BY FIELD(kategori,'Tenaga Kesehatan','Asisten Tenaga Kesehatan','Tenaga Penunjang'), urutan");
    while($r=$q->fetch_assoc()) $items[]=$r;
    return $items;
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
function buildParentIds($items){
    $ids=[];
    foreach($items as $it){ if($it['id_parent']) $ids[$it['id_parent']]=true; }
    return $ids;
}
function normalizeNama($s){
    $s = trim((string)$s);
    // collapse multiple spaces to single
    $s = preg_replace('/\s+/', ' ', $s);
    $s = strtolower($s);
    // remove leading bullets / numbering: "a.", "b.", "-", "•", "1.", "1)", "01."
    $s = preg_replace('/^[\-\•\*\s]+/', '', $s);
    $s = preg_replace('/^[a-z]\.\s*/', '', $s);
    $s = preg_replace('/^\d+[\.\)]\s*/', '', $s);
    $s = trim($s);
    $s = preg_replace('/\s+/', ' ', $s);
    return $s;
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
        if ($nama !== '') {
            $stmt = $config->prepare("SELECT id FROM tbl_sdm_items WHERE nama_item=? LIMIT 1");
            $stmt->bind_param("s", $nama);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows === 0) {
                if ($id_parent === null) {
                    $stmt2 = $config->prepare("INSERT INTO tbl_sdm_items (nama_item, kategori, id_parent, urutan, aktif) VALUES (?, ?, NULL, ?, 'Y')");
                    $stmt2->bind_param("ssi", $nama, $kategori, $urutan);
                } else {
                    $chk = $config->prepare("SELECT id FROM tbl_sdm_items WHERE id=? AND aktif='Y' LIMIT 1");
                    $chk->bind_param("i", $id_parent);
                    $chk->execute();
                    $chk->store_result();
                    if ($chk->num_rows===0) $id_parent = null;
                    if($id_parent===null){
                        $stmt2 = $config->prepare("INSERT INTO tbl_sdm_items (nama_item, kategori, id_parent, urutan, aktif) VALUES (?, ?, NULL, ?, 'Y')");
                        $stmt2->bind_param("ssi", $nama, $kategori, $urutan);
                    } else {
                        $stmt2 = $config->prepare("INSERT INTO tbl_sdm_items (nama_item, kategori, id_parent, urutan, aktif) VALUES (?, ?, ?, ?, 'Y')");
                        $stmt2->bind_param("ssii", $nama, $kategori, $id_parent, $urutan);
                    }
                }
                $ok=$stmt2->execute();
                if(!$ok){ header("Location: sdmk.php?tab=items&msg=error"); exit; }
                header("Location: sdmk.php?tab=items&msg=added"); exit;
            } else { header("Location: sdmk.php?tab=items&msg=exists"); exit; }
        }
        header("Location: sdmk.php?tab=items&msg=invalid"); exit;
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
        if ($id && $nama !== '') {
            if ($id_parent === $id) $id_parent = null;
            if ($id_parent !== null) {
                $chk = $config->prepare("SELECT id FROM tbl_sdm_items WHERE id=? AND aktif='Y' LIMIT 1");
                $chk->bind_param("i", $id_parent);
                $chk->execute();
                $chk->store_result();
                if ($chk->num_rows===0) $id_parent = null;
            }
            if ($id_parent === null) {
                $stmt = $config->prepare("UPDATE tbl_sdm_items SET nama_item=?, kategori=?, id_parent=NULL, urutan=?, aktif=? WHERE id=?");
                $stmt->bind_param("ssisi", $nama, $kategori, $urutan, $aktif, $id);
            } else {
                $stmt = $config->prepare("UPDATE tbl_sdm_items SET nama_item=?, kategori=?, id_parent=?, urutan=?, aktif=? WHERE id=?");
                $stmt->bind_param("ssiisi", $nama, $kategori, $id_parent, $urutan, $aktif, $id);
            }
            $stmt->execute();
            header("Location: sdmk.php?tab=items&msg=updated"); exit;
        }
        header("Location: sdmk.php?tab=items&msg=invalid"); exit;
    }
    if($act0==='delete_item'){
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            $stmt = $config->prepare("UPDATE tbl_sdm_items SET aktif='N' WHERE id=?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
        }
        header("Location: sdmk.php?tab=items&msg=deleted"); exit;
    }
}

// ---------- HANDLE EXPORT / TEMPLATE BEFORE HTML ----------
$action = $_GET['action'] ?? $_POST['action'] ?? '';
$id_faskes_req = (int)($_GET['id_faskes'] ?? $_POST['id_faskes'] ?? 0);

if (in_array($action, ['template','export'])) {
    if (!$id_faskes_req) { header("Location: sdmk.php?msg=need_faskes"); exit; }
    $stmt=$config->prepare("SELECT f.id_faskes, f.nama_faskes, f.jenis, f.id_kecamatan, k.nama_kecamatan FROM tbl_faskes f LEFT JOIN tbl_kecamatan k ON k.id_kecamatan=f.id_kecamatan WHERE f.id_faskes=? AND f.aktif='Y' LIMIT 1");
    $stmt->bind_param("i",$id_faskes_req);
    $stmt->execute();
    $faskes=$stmt->get_result()->fetch_assoc();
    if(!$faskes){ die("Fasyankes tidak ditemukan"); }
    $items=getItems($config);
    $parentIds=buildParentIds($items);
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
    $sheet->mergeCells('A1:G1');
    $sheet->setCellValue('A1',$title);
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(11);
    $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->setCellValue('A2',$faskes['jenis'].': '.$faskes['nama_faskes'].' | Kecamatan: '.$faskes['nama_kecamatan']);
    $sheet->mergeCells('A2:G2');
    $sheet->getStyle('A2')->getFont()->setItalic(true)->setSize(9);
    $sheet->setCellValue('A3','No');
    $sheet->setCellValue('B3','Jenis SDM');
    $sheet->setCellValue('C3','ASN');
    $sheet->setCellValue('E3','Non ASN');
    $sheet->setCellValue('G3','Jumlah');
    $sheet->mergeCells('C3:D3');
    $sheet->mergeCells('E3:F3');
    $sheet->setCellValue('C4','L');
    $sheet->setCellValue('D4','P');
    $sheet->setCellValue('E4','L');
    $sheet->setCellValue('F4','P');
    $sheet->setCellValue('A4','');
    $sheet->setCellValue('B4','');
    $sheet->setCellValue('G4','');
    $headerStyle=[
        'font'=>['bold'=>true,'color'=>['rgb'=>'000000']],
        'fill'=>['fillType'=>Fill::FILL_SOLID,'startColor'=>['rgb'=>'BDD7EE']],
        'alignment'=>['horizontal'=>Alignment::HORIZONTAL_CENTER,'vertical'=>Alignment::VERTICAL_CENTER],
        'borders'=>['allBorders'=>['borderStyle'=>Border::BORDER_THIN,'color'=>['rgb'=>'000000']]]
    ];
    $sheet->getStyle('A3:G4')->applyFromArray($headerStyle);
    $sheet->getRowDimension(3)->setRowHeight(22);
    $sheet->getRowDimension(4)->setRowHeight(18);
    $sheet->getColumnDimension('A')->setWidth(6);
    $sheet->getColumnDimension('B')->setWidth(42);
    $sheet->getColumnDimension('C')->setWidth(10);
    $sheet->getColumnDimension('D')->setWidth(10);
    $sheet->getColumnDimension('E')->setWidth(10);
    $sheet->getColumnDimension('F')->setWidth(10);
    $sheet->getColumnDimension('G')->setWidth(12);
    $rowIdx=5;
    $kategoriOrder=['Tenaga Kesehatan','Asisten Tenaga Kesehatan','Tenaga Penunjang'];
    $kategoriLabel=['Tenaga Kesehatan'=>'A. Tenaga Kesehatan','Asisten Tenaga Kesehatan'=>'B. Asisten Tenaga Kesehatan','Tenaga Penunjang'=>'C. Tenaga Penunjang'];
    $no=1;
    $grandTotal=0;
    $grouped=[];
    foreach($items as $it) $grouped[$it['kategori']][]=$it;
    foreach($kategoriOrder as $kat){
        if(empty($grouped[$kat])) continue;
        $sheet->mergeCells("A{$rowIdx}:G{$rowIdx}");
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
        foreach($grouped[$kat] as $it){
            $isParent = isset($parentIds[$it['id']]);
            if($isParent){
                $sheet->setCellValue("A{$rowIdx}", '');
                $sheet->setCellValue("B{$rowIdx}", $it['nama_item']);
                $sheet->mergeCells("B{$rowIdx}:G{$rowIdx}");
                $parentStyle=[
                    'font'=>['bold'=>true,'italic'=>true],
                    'fill'=>['fillType'=>Fill::FILL_SOLID,'startColor'=>['rgb'=>'E2EFDA']],
                    'borders'=>['allBorders'=>['borderStyle'=>Border::BORDER_THIN,'color'=>['rgb'=>'000000']]]
                ];
                $sheet->getStyle("A{$rowIdx}:G{$rowIdx}")->applyFromArray($parentStyle);
                $rowIdx++;
                continue;
            }
            $d=$dataMap[$it['id']] ?? ['asn_l'=>0,'asn_p'=>0,'nonasn_l'=>0,'nonasn_p'=>0,'jumlah'=>0];
            $sheet->setCellValue("A{$rowIdx}", $no);
            $displayName = $it['nama_item'];
            if($it['id_parent']){
                $siblings=array_values(array_filter($grouped[$kat], fn($x)=>$x['id_parent']==$it['id_parent']));
                $idx=array_search($it['id'], array_column($siblings,'id'));
                $letter=chr(97+($idx===false?0:$idx));
                $displayName = "   {$letter}. ".$it['nama_item'];
            }
            $sheet->setCellValue("B{$rowIdx}", $displayName);
            $sheet->setCellValue("C{$rowIdx}", (int)$d['asn_l']);
            $sheet->setCellValue("D{$rowIdx}", (int)$d['asn_p']);
            $sheet->setCellValue("E{$rowIdx}", (int)$d['nonasn_l']);
            $sheet->setCellValue("F{$rowIdx}", (int)$d['nonasn_p']);
            $sheet->setCellValue("G{$rowIdx}", (int)$d['jumlah']);
            $sheet->getStyle("A{$rowIdx}:G{$rowIdx}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            $sheet->getStyle("A{$rowIdx}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("C{$rowIdx}:G{$rowIdx}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("B{$rowIdx}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $catSum += (int)$d['jumlah'];
            $no++;
            $rowIdx++;
        }
        $sheet->mergeCells("A{$rowIdx}:B{$rowIdx}");
        $sheet->setCellValue("A{$rowIdx}", "Total ".$kategoriLabel[$kat]);
        $sheet->setCellValue("G{$rowIdx}", $catSum);
        $totalStyle=[
            'font'=>['bold'=>true],
            'fill'=>['fillType'=>Fill::FILL_SOLID,'startColor'=>['rgb'=>'DDEBF7']],
            'borders'=>['allBorders'=>['borderStyle'=>Border::BORDER_THIN,'color'=>['rgb'=>'000000']]]
        ];
        $sheet->getStyle("A{$rowIdx}:G{$rowIdx}")->applyFromArray($totalStyle);
        $sheet->getStyle("G{$rowIdx}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("A{$rowIdx}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $rowIdx++;
        $grandTotal+=$catSum;
    }
    $sheet->mergeCells("A{$rowIdx}:F{$rowIdx}");
    $sheet->setCellValue("A{$rowIdx}", "TOTAL SDM KESEHATAN dan TENAGA PENUNJANG DI ".$labelJenis." ".strtoupper($faskes['nama_faskes'])." TAHUN ".date('Y'));
    $sheet->setCellValue("G{$rowIdx}", $grandTotal);
    $grandStyle=[
        'font'=>['bold'=>true,'color'=>['rgb'=>'FFFFFF']],
        'fill'=>['fillType'=>Fill::FILL_SOLID,'startColor'=>['rgb'=>'4472C4']],
        'alignment'=>['horizontal'=>Alignment::HORIZONTAL_CENTER],
        'borders'=>['allBorders'=>['borderStyle'=>Border::BORDER_THIN,'color'=>['rgb'=>'000000']]]
    ];
    $sheet->getStyle("A{$rowIdx}:G{$rowIdx}")->applyFromArray($grandStyle);
    $sheet->getRowDimension($rowIdx)->setRowHeight(20);
    $sheet->freezePane('A5');
    $sheet->setAutoFilter('A3:G4');
    $filename = ($action==='template' ? 'Template_SDMK_' : 'Export_SDMK_') . preg_replace('/[^A-Za-z0-9_]/','_', $faskes['nama_faskes']) . '_' . date('Ymd') . '.xlsx';
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="'.$filename.'"');
    header('Cache-Control: max-age=0');
    $writer=new Xlsx($ss);
    $writer->save('php://output');
    exit;
}

// ---------- HANDLE POST ACTIONS ----------
$msg=''; $importResult=null; $saveResult=null;
if($_SERVER['REQUEST_METHOD']==='POST'){
    $act=$_POST['action'] ?? '';
    if($act==='save_rekap'){
        $id_faskes=(int)($_POST['id_faskes'] ?? 0);
        if(!$id_faskes){ header("Location: sdmk.php?msg=need_faskes"); exit; }
        $fchk=$config->prepare("SELECT id_kecamatan FROM tbl_faskes WHERE id_faskes=? LIMIT 1");
        $fchk->bind_param("i",$id_faskes);
        $fchk->execute();
        $frow=$fchk->get_result()->fetch_assoc();
        if(!$frow){ header("Location: sdmk.php?id_faskes=$id_faskes&msg=faskes_not_found"); exit; }
        $id_kecamatan=(int)$frow['id_kecamatan'];
        $items=getItems($config);
        $parentIds=buildParentIds($items);
        $asn_l=$_POST['asn_l'] ?? [];
        $asn_p=$_POST['asn_p'] ?? [];
        $nonasn_l=$_POST['nonasn_l'] ?? [];
        $nonasn_p=$_POST['nonasn_p'] ?? [];
        $config->begin_transaction();
        $success=0; $warnings=[];
        try{
            foreach($items as $it){
                if(isset($parentIds[$it['id']])) continue;
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
                        // all zero and no existing row => count as success but no DB write
                        $success++;
                    }
                }
            }
            $config->commit();
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
            if(session_status()===PHP_SESSION_NONE) session_start();
            $_SESSION['save_result']=['success'=>0,'warnings'=>[$e->getMessage()]];
            header("Location: sdmk.php?id_faskes=$id_faskes&msg=error");
            exit;
        }
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
        if(!isset($_FILES['excel_file']) || $_FILES['excel_file']['error']!==UPLOAD_ERR_OK){
            header("Location: sdmk.php?id_faskes=$id_faskes&msg=import_no_file");
            exit;
        }
        $tmp=$_FILES['excel_file']['tmp_name'];
        $ext=strtolower(pathinfo($_FILES['excel_file']['name'], PATHINFO_EXTENSION));
        $success=0; $skipped=0; $warnings=[];
        try{
            if($ext==='xls') $reader=new ReaderXls(); else $reader=new ReaderXlsx();
            $reader->setReadDataOnly(true);
            $ss=$reader->load($tmp);
            $sheet=$ss->getActiveSheet();
            $rows=$sheet->toArray(null,true,true,true);
            // auto detect header row containing "Jenis SDM"
            $headerRow=null;
            foreach($rows as $rNum=>$row){
                $b=trim((string)($row['B'] ?? ''));
                if(strcasecmp($b,'Jenis SDM')===0){ $headerRow=(int)$rNum; break; }
            }
            if($headerRow===null){
                if(session_status()===PHP_SESSION_NONE) session_start();
                $_SESSION['import_result']=['success'=>0,'skipped'=>0,'warnings'=>["Header 'Jenis SDM' tidak ditemukan di kolom B. Pastikan file adalah Template/Export SDMK yang valid."]];
                header("Location: sdmk.php?id_faskes=$id_faskes&msg=import_invalid_header");
                exit;
            }
            // validate L/P header in row headerRow+1 (C-F should be L,P,L,P)
            $hdr2=$rows[$headerRow+1] ?? null;
            if($hdr2){
                $c=trim((string)($hdr2['C'] ?? '')); $d=trim((string)($hdr2['D'] ?? ''));
                $e=trim((string)($hdr2['E'] ?? '')); $f=trim((string)($hdr2['F'] ?? ''));
                $expected=['L','P','L','P'];
                $actual=[$c,$d,$e,$f];
                $actualUp=array_map(fn($x)=>strtoupper(trim($x)), $actual);
                if($actualUp !== $expected){
                    // also check if row above has ASN/Non ASN
                    $hdr1=$rows[$headerRow] ?? null;
                    $c1=trim((string)($hdr1['C'] ?? '')); $e1=trim((string)($hdr1['E'] ?? ''));
                    $asnOk = strcasecmp($c1,'ASN')===0 && strcasecmp($e1,'Non ASN')===0;
                    if(!$asnOk && $actualUp !== $expected){
                        if(session_status()===PHP_SESSION_NONE) session_start();
                        $_SESSION['import_result']=['success'=>0,'skipped'=>0,'warnings'=>["Struktur header tidak valid di baris ".($headerRow+1).": kolom C-F harus 'L','P','L','P' (ditemukan '".implode("','",$actual)."'). File mungkin bukan template SDMK."]];
                        header("Location: sdmk.php?id_faskes=$id_faskes&msg=import_invalid_header");
                        exit;
                    }
                }
            }
            $dataStart=$headerRow+2;
            $items=getItems($config);
            $map=[];
            foreach($items as $it){
                $norm=normalizeNama($it['nama_item']);
                $map[$norm]=$it['id'];
            }
            $parentIds=buildParentIds($items);
            $config->begin_transaction();
            foreach($rows as $rNum=>$row){
                if((int)$rNum < $dataStart) continue;
                $colB = trim((string)($row['B'] ?? ''));
                if($colB==='') continue;
                $lowerB=strtolower($colB);
                if(strpos($lowerB,'tenaga kesehatan')!==false || strpos($lowerB,'asisten tenaga')!==false || strpos($lowerB,'tenaga penunjang')!==false) { $skipped++; continue; }
                if(strpos($lowerB,'total')!==false) { $skipped++; continue; }
                // parent names that are headers (exact parent name without child prefix)
                $normExact=normalizeNama($colB);
                // if exact parent name and it's a parent id, skip (it's header row)
                if(isset($map[$normExact]) && isset($parentIds[$map[$normExact]]) ){
                    // check if this row was parent header (has merged B-G, no numbers). Skip.
                    // To distinguish parent header vs actual data, we can check if C-F empty and G empty: it's header.
                    $cVal=trim((string)($row['C'] ?? '')); $dVal=trim((string)($row['D'] ?? '')); $eVal=trim((string)($row['E'] ?? '')); $fVal=trim((string)($row['F'] ?? ''));
                    if($cVal==='' && $dVal==='' && $eVal==='' && $fVal===''){ $skipped++; continue; }
                }
                // normalize for matching
                $norm = normalizeNama($colB);
                if(!isset($map[$norm])){
                    $warnings[]="Baris $rNum: Jenis SDM '".htmlspecialchars($colB)."' tidak ditemukan di master (normalisasi: '$norm'), di-skip. Tambahkan dulu di Master Jenis SDM.";
                    $skipped++;
                    continue;
                }
                $pid=$map[$norm];
                if(isset($parentIds[$pid])){
                    // parent should not have data, but if somehow matched as leaf, skip
                    $skipped++; continue;
                }
                $c = $row['C'] ?? 0; $d=$row['D'] ?? 0; $e=$row['E'] ?? 0; $f=$row['F'] ?? 0;
                $vals=[$c,$d,$e,$f]; $parsed=[];
                foreach($vals as $idx=>$v){
                    $orig=(string)$v;
                    $v=trim((string)$v);
                    if($v==='') $v=0;
                    // allow commas
                    $v=str_replace(',','',$v);
                    if(!is_numeric($v) || (int)$v<0){
                        $warnings[]="Baris $rNum (".htmlspecialchars($colB)."): nilai '".htmlspecialchars($orig)."' pada kolom ".chr(67+$idx)." tidak valid (harus angka ≥0), dianggap 0.";
                        $v=0;
                    }
                    $parsed[]=(int)$v;
                }
                [$al,$ap,$nl,$np]=$parsed;
                $chk=$config->prepare("SELECT id FROM tbl_sdm_faskes WHERE id_faskes=? AND id_profesi=? AND id_spesialis IS NULL LIMIT 1");
                $chk->bind_param("ii",$id_faskes,$pid);
                $chk->execute();
                $ex=$chk->get_result()->fetch_assoc();
                if($ex){
                    $stmt=$config->prepare("UPDATE tbl_sdm_faskes SET asn_l=?, asn_p=?, nonasn_l=?, nonasn_p=?, id_kecamatan=?, aktif='Y', updated_at=NOW() WHERE id=?");
                    $stmt->bind_param("iiiiii",$al,$ap,$nl,$np,$id_kecamatan,$ex['id']);
                    $ok=$stmt->execute();
                    if(!$ok){ $warnings[]="Baris $rNum (".htmlspecialchars($colB)."): gagal update DB: ".$stmt->error; }
                    else { $success++; }
                } else {
                    $stmt=$config->prepare("INSERT INTO tbl_sdm_faskes (id_kecamatan, id_faskes, id_profesi, id_spesialis, asn_l, asn_p, nonasn_l, nonasn_p, aktif) VALUES (?, ?, ?, NULL, ?, ?, ?, ?, 'Y')");
                    $stmt->bind_param("iiiiiii",$id_kecamatan,$id_faskes,$pid,$al,$ap,$nl,$np);
                    $ok=$stmt->execute();
                    if(!$ok){ $warnings[]="Baris $rNum (".htmlspecialchars($colB)."): gagal insert DB: ".$stmt->error; }
                    else { $success++; }
                }
            }
            $config->commit();
            if(session_status()===PHP_SESSION_NONE) session_start();
            $_SESSION['import_result']=['success'=>$success,'skipped'=>$skipped,'warnings'=>$warnings];
            header("Location: sdmk.php?id_faskes=$id_faskes&msg=import_done");
            exit;
        } catch(Exception $e){
            $config->rollback();
            if(session_status()===PHP_SESSION_NONE) session_start();
            $_SESSION['import_result']=['success'=>0,'skipped'=>0,'warnings'=>["Fatal error: ".$e->getMessage()]];
            header("Location: sdmk.php?id_faskes=$id_faskes&msg=import_error");
            exit;
        }
    }
}

// ---------- DATA FOR TAB ITEMS ----------
$allItems = [];
$q = $config->query("SELECT id, nama_item, kategori, id_parent, urutan, aktif FROM tbl_sdm_items ORDER BY FIELD(kategori,'Tenaga Kesehatan','Asisten Tenaga Kesehatan','Tenaga Penunjang'), urutan, nama_item");
while ($r = $q->fetch_assoc()) $allItems[] = $r;
$parents = array_filter($allItems, fn($x)=> $x['aktif']==='Y');
$parentMap = [];
foreach ($allItems as $it) $parentMap[$it['id']] = $it['nama_item'];

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
$items=getItems($config);
$parentIds=buildParentIds($items);
$dataMap=[];
if($selectedFaskes){
    // SUM across possible multiple spesialis rows per profesi (robust against duplicate NULL handling)
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
table{width:100%;border-collapse:collapse} table th{padding:12px 10px;color:#87e3ff;font-weight:600;font-size:13px;border-bottom:2px solid rgba(255,255,255,0.08)}
table td{padding:12px 10px;border-bottom:1px solid rgba(255,255,255,0.05);font-size:13px;vertical-align:middle}
.th-center{text-align:center}
table td.input-cell{padding:4px}
table td.input-cell input{width:100%;padding:6px 8px;border-radius:6px;border:1px solid rgba(255,255,255,0.1);background:rgba(255,255,255,0.06);color:#fff;text-align:center}
table td.num{text-align:center}
.kategori-row{background:#FFF2CC;color:#000;font-weight:700}
.parent-row{background:#E2EFDA;color:#000;font-weight:600;font-style:italic}
.total-row{background:#DDEBF7;color:#000;font-weight:700}
.grand-row{background:#4472C4;color:#fff;font-weight:800}
#editModal{display:none;position:fixed;inset:0;background:rgba(0,0,0,0.65);backdrop-filter:blur(6px);z-index:999;justify-content:center;align-items:center}
.modal-box{background:#0b223c;padding:30px;border-radius:20px;max-width:600px;width:95%;border:1px solid rgba(255,255,255,0.1);max-height:90vh;overflow-y:auto}
.tab-nav{display:flex;gap:12px;margin-bottom:24px}
.tab-btn{flex:1;padding:14px 18px;border-radius:14px;border:1px solid rgba(255,255,255,0.08);background:rgba(255,255,255,0.04);color:rgba(255,255,255,0.6);font-weight:600;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:8px;transition:.3s}
.tab-btn.active{background:linear-gradient(135deg,#00d4ff,#0088cc);color:#fff;border-color:rgba(0,212,255,0.3);box-shadow:0 8px 25px rgba(0,212,255,0.2)}
.toolbar{display:flex;gap:10px;flex-wrap:wrap;align-items:center}
@media(max-width:768px){.sidebar{display:none}.main-content{padding:20px}.form-grid{grid-template-columns:1fr}.tab-nav{flex-direction:column}}
</style>
</head>
<body>
<div class="sidebar">
<div class="sidebar-brand"><img src="../../assets/img/kabupaten.png" alt="Logo"><h2>Portal DKK<br><small>Dashboard Admin</small></h2></div>
<ul class="sidebar-menu">
<li><a href="../index.php"><i class="fas fa-chart-pie"></i> Dashboard</a></li>
<li><a href="fasyankes.php"><i class="fas fa-hospital"></i> Fasyankes</a></li>
<li><a href="sdmk.php" class="active"><i class="fas fa-hospital-user"></i> SDMK</a></li>
<li><a href="sdm.php"><i class="fas fa-users"></i> SDM (legacy)</a></li>
<li><a href="kecamatan.php"><i class="fas fa-map"></i> Kecamatan</a></li>
<li><a href="penyakit.php"><i class="fas fa-disease"></i> Penyakit</a></li>
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
<?php if(in_array($msg,['added','updated','deleted','exists','invalid','error'])): ?>
<?php if($msg==='added'):?><div class="alert alert-success"><i class="fas fa-check-circle"></i> Jenis SDM berhasil ditambahkan.</div>
<?php elseif($msg==='updated'):?><div class="alert alert-success"><i class="fas fa-check-circle"></i> Berhasil diperbarui.</div>
<?php elseif($msg==='deleted'):?><div class="alert alert-success"><i class="fas fa-check-circle"></i> Dihapus (soft delete).</div>
<?php elseif($msg==='exists'):?><div class="alert alert-warning"><i class="fas fa-exclamation-triangle"></i> Nama item sudah ada.</div>
<?php elseif($msg==='error'):?><div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> Gagal memproses — cek log.</div>
<?php else:?><div class="alert alert-warning"><i class="fas fa-exclamation-triangle"></i> Input tidak valid.</div>
<?php endif; ?>
<?php endif; ?>
<div class="card" style="background:linear-gradient(135deg, rgba(0,212,255,0.08), rgba(0,136,204,0.06));border:1px solid rgba(0,212,255,0.2)"><h3><i class="fas fa-plus-circle" style="color:#00d4ff"></i> Tambah Jenis SDM</h3>
<form method="POST"><input type="hidden" name="action" value="add_item"><div class="form-grid">
<div class="form-group"><label>Nama Jenis SDM *</label><input type="text" name="nama_item" placeholder="Contoh: Apoteker" required></div>
<div class="form-group"><label>Kategori *</label><select name="kategori" required><option value="Tenaga Kesehatan">A. Tenaga Kesehatan</option><option value="Asisten Tenaga Kesehatan">B. Asisten Tenaga Kesehatan</option><option value="Tenaga Penunjang">C. Tenaga Penunjang</option></select></div>
<div class="form-group"><label>Parent (untuk sub-item)</label><select name="id_parent"><option value="">-- Tanpa Parent (top-level) --</option><?php foreach($parents as $p):?><option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nama_item']) ?> (<?= htmlspecialchars($p['kategori']) ?>)</option><?php endforeach;?></select></div>
<div class="form-group"><label>Urutan</label><input type="number" name="urutan" value="<?= count($allItems)+1 ?>" min="0"></div>
</div><div style="margin-top:16px"><button type="submit" class="btn-primary"><i class="fas fa-save"></i> Tambah</button></div></form></div>
<div class="card"><h3><i class="fas fa-table" style="color:#00d4ff"></i> Daftar Jenis SDM (<?= count($allItems) ?>)</h3>
<table><thead><tr><th>#</th><th>Nama</th><th>Kategori</th><th>Parent</th><th>Urutan</th><th>Aktif</th><th>Aksi</th></tr></thead><tbody>
<?php foreach($allItems as $idx=>$row):
$katClass = $row['kategori']==='Tenaga Kesehatan'?'badge-A':($row['kategori']==='Asisten Tenaga Kesehatan'?'badge-B':'badge-C');
$letter = $row['kategori']==='Tenaga Kesehatan'?'A':($row['kategori']==='Asisten Tenaga Kesehatan'?'B':'C');
?>
<tr style="<?= $row['aktif']==='N'?'opacity:0.45':'' ?>">
<td><?= $idx+1 ?></td>
<td style="<?= $row['id_parent']?'padding-left:28px':'' ?>"><?php if($row['id_parent']):?><span style="color:#ffd54f">↳</span> <?php endif;?><?= htmlspecialchars($row['nama_item']) ?></td>
<td><span class="badge <?= $katClass ?>"><?= $letter ?>. <?= htmlspecialchars($row['kategori']) ?></span></td>
<td><?= $row['id_parent'] ? htmlspecialchars($parentMap[$row['id_parent']] ?? '-') : '<span style="color:rgba(255,255,255,0.3)">-</span>' ?></td>
<td><?= (int)$row['urutan'] ?></td>
<td><?= $row['aktif']==='Y' ? '<span style="color:#81c784">Y</span>' : '<span style="color:#ff6b6b">N</span>' ?></td>
<td>
<button class="btn-icon edit-btn" data-id="<?= $row['id'] ?>" data-nama="<?= htmlspecialchars($row['nama_item']) ?>" data-kategori="<?= htmlspecialchars($row['kategori']) ?>" data-parent="<?= (int)($row['id_parent']??0) ?>" data-urutan="<?= (int)$row['urutan'] ?>" data-aktif="<?= $row['aktif'] ?>"><i class="fas fa-pen"></i> Edit</button>
<form method="POST" style="display:inline" onsubmit="return confirm('Nonaktifkan jenis ini? (soft delete)')"><input type="hidden" name="action" value="delete_item"><input type="hidden" name="id" value="<?= $row['id'] ?>"><button type="submit" class="btn-icon btn-danger"><i class="fas fa-trash"></i></button></form>
</td>
</tr>
<?php endforeach; ?>
</tbody></table></div>
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
<?php endif; ?>
<?php if($saveResult):?>
<div class="card" style="border-color:rgba(255,193,7,0.25)"><h3><i class="fas fa-exclamation-triangle" style="color:#ffd54f"></i> Hasil Simpan Manual</h3>
<p>Berhasil proses: <strong><?= (int)$saveResult['success'] ?></strong> baris.</p>
<?php if(!empty($saveResult['warnings'])):?><ul style="margin-top:12px;max-height:200px;overflow:auto;background:rgba(255,193,7,0.08);padding:12px;border-radius:10px;font-size:12px"><?php foreach($saveResult['warnings'] as $w):?><li><?= htmlspecialchars($w) ?></li><?php endforeach;?></ul><?php endif;?></div>
<?php endif;?>
<?php if($importResult):?>
<div class="card" style="border-color:rgba(0,212,255,0.25)"><h3><i class="fas fa-file-excel" style="color:#4CAF50"></i> Hasil Import</h3>
<p>Berhasil disimpan: <strong><?= (int)$importResult['success'] ?></strong> baris | Di-skip: <strong><?= (int)$importResult['skipped'] ?></strong></p>
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
<div class="card">
<div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;margin-bottom:16px">
<h3 style="margin:0"><i class="fas fa-table" style="color:#00d4ff"></i> <?= htmlspecialchars($selectedFaskes['nama_faskes']) ?> <span style="font-weight:400;color:#87e3ff;font-size:12px">(<?= htmlspecialchars($selectedFaskes['jenis']) ?> — <?= htmlspecialchars($selectedFaskes['nama_kecamatan']) ?>)</span></h3>
<div class="toolbar">
<form method="POST" enctype="multipart/form-data" style="display:flex;gap:8px;align-items:center">
<input type="hidden" name="action" value="import"><input type="hidden" name="id_faskes" value="<?= $selectedId ?>">
<input type="file" name="excel_file" accept=".xlsx,.xls" required style="font-size:12px">
<button type="submit" class="btn-primary" style="background:linear-gradient(135deg,#9C27B0,#6A1B9A)"><i class="fas fa-upload"></i> Import</button>
</form>
<form method="POST" onsubmit="return confirm('Reset semua nilai jadi 0 untuk fasyankes ini?')">
<input type="hidden" name="action" value="reset"><input type="hidden" name="id_faskes" value="<?= $selectedId ?>">
<button type="submit" class="btn-primary btn-danger"><i class="fas fa-undo"></i> Reset</button>
</form>
</div>
</div>
<form method="POST" id="rekapForm">
<input type="hidden" name="action" value="save_rekap"><input type="hidden" name="id_faskes" value="<?= $selectedId ?>">
<div style="overflow:auto">
<table id="rekapTable">
<thead>
<tr><th rowspan="2" style="width:40px">No</th><th rowspan="2">Jenis SDM</th><th colspan="2">ASN</th><th colspan="2">Non ASN</th><th rowspan="2">Jumlah</th><th rowspan="2" style="min-width:110px">Aksi</th></tr>
<tr><th>L</th><th>P</th><th>L</th><th>P</th></tr>
</thead>
<tbody>
<?php
$kategoriOrder=['Tenaga Kesehatan','Asisten Tenaga Kesehatan','Tenaga Penunjang'];
$kategoriLabel=['Tenaga Kesehatan'=>'A. Tenaga Kesehatan','Asisten Tenaga Kesehatan'=>'B. Asisten Tenaga Kesehatan','Tenaga Penunjang'=>'C. Tenaga Penunjang'];
$grouped=[]; foreach($items as $it) $grouped[$it['kategori']][]=$it;
$no=1;
foreach($kategoriOrder as $kat){
 if(empty($grouped[$kat])) continue;
 echo '<tr class="kategori-row"><td colspan="8">'.htmlspecialchars($kategoriLabel[$kat]).'</td></tr>';
 foreach($grouped[$kat] as $it){
   $isParent=isset($parentIds[$it['id']]);
   if($isParent){
     echo '<tr class="parent-row"><td></td><td colspan="7">'.htmlspecialchars($it['nama_item']).'</td></tr>';
     continue;
   }
   $d=$dataMap[$it['id']] ?? ['asn_l'=>0,'asn_p'=>0,'nonasn_l'=>0,'nonasn_p'=>0,'jumlah'=>0,'id'=>0];
   $al=(int)$d['asn_l']; $ap=(int)$d['asn_p']; $nl=(int)$d['nonasn_l']; $np=(int)$d['nonasn_p']; $jum=$al+$ap+$nl+$np;
   $display = htmlspecialchars($it['nama_item']);
   if($it['id_parent']){
     $siblings=array_values(array_filter($grouped[$kat], fn($x)=>$x['id_parent']==$it['id_parent']));
     $idx=array_search($it['id'], array_column($siblings,'id'));
     $letter=chr(97+($idx===false?0:$idx));
     $display = htmlspecialchars($letter.'. '.$it['nama_item']);
   }
   $prefix = $it['id_parent'] ? '&nbsp;&nbsp;&nbsp;' : '';
   echo '<tr data-kat="'.htmlspecialchars($kat).'">';
   echo '<td class="num">'.$no.'</td>';
   echo '<td>'.$prefix.$display.'</td>';
   echo '<td class="input-cell"><input type="number" min="0" name="asn_l['.$it['id'].']" value="'.$al.'" class="inp"></td>';
   echo '<td class="input-cell"><input type="number" min="0" name="asn_p['.$it['id'].']" value="'.$ap.'" class="inp"></td>';
   echo '<td class="input-cell"><input type="number" min="0" name="nonasn_l['.$it['id'].']" value="'.$nl.'" class="inp"></td>';
   echo '<td class="input-cell"><input type="number" min="0" name="nonasn_p['.$it['id'].']" value="'.$np.'" class="inp"></td>';
   echo '<td class="num jumlah-cell" style="font-weight:700;background:rgba(255,255,255,0.04)">'.$jum.'</td>';
    echo '<td class="num" style="display:flex;gap:6px;justify-content:center">';
    if($d['id']){
      echo '<button type="button" onclick="if(confirm(\'Reset nilai baris ini ke 0?\')) postRowAction(\'reset_row\','.$d['id'].')" title="Reset ke 0" class="btn-primary" style="padding:4px 8px;font-size:11px;background:rgba(255,193,7,0.15);color:#ffd54f;border:1px solid rgba(255,193,7,0.25)"><i class="fas fa-undo"></i></button>';
      echo '<button type="button" onclick="if(confirm(\'Hapus baris ini? (item akan hilang dari rekap sampai diisi ulang)\')) postRowAction(\'delete_row\','.$d['id'].')" title="Hapus Baris" class="btn-primary btn-danger" style="padding:4px 8px;font-size:11px"><i class="fas fa-trash"></i></button>';
    } else {
      echo '<span style="color:rgba(255,255,255,0.2)">-</span>';
    }
    echo '</td>';
   echo '</tr>';
   $no++;
 }
 echo '<tr class="total-row" data-total-kat="'.htmlspecialchars($kat).'"><td colspan="2" style="text-align:right">Total '.htmlspecialchars($kategoriLabel[$kat]).'</td><td colspan="4"></td><td class="num cat-total">0</td><td></td></tr>';
}
$lblGT = labelJenisFaskes($selectedFaskes['jenis']); echo '<tr class="grand-row"><td colspan="2" style="text-align:right">TOTAL SDM KESEHATAN dan TENAGA PENUNJANG DI '.$lblGT.' '.strtoupper(htmlspecialchars($selectedFaskes['nama_faskes'])).' TAHUN '.date('Y').'</td><td colspan="4"></td><td class="num" id="grandTotal">0</td><td></td></tr>';
?>
</tbody>
</table>
</div>
<div style="margin-top:16px;display:flex;gap:12px;align-items:center;flex-wrap:wrap"><button type="submit" class="btn-primary"><i class="fas fa-save"></i> Simpan Rekap</button><span style="font-size:11px;color:rgba(255,255,255,0.4)">Validasi: nilai negatif →0 | Baris kategori/parent tidak punya input | DB generated = JS</span></div>
</form>
<form id="rowActionForm" method="POST" style="display:none">
<input type="hidden" name="action" id="rowAction" value="">
<input type="hidden" name="id_faskes" value="<?= $selectedId ?>">
<input type="hidden" name="row_id" id="rowId" value="">
</form>
<script>
function postRowAction(action, rowId){
  document.getElementById('rowAction').value = action;
  document.getElementById('rowId').value = rowId;
  document.getElementById('rowActionForm').submit();
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
     const inputs=tr.querySelectorAll('input.inp');
     let s=0;
     inputs.forEach(i=>{ let v=parseInt(i.value,10); if(isNaN(v)||v<0) v=0; s+=v; });
     tr.querySelector('.jumlah-cell').textContent=s;
     catSums[kat]=(catSums[kat]||0)+s;
     grand+=s;
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
<form method="POST"><input type="hidden" name="action" value="edit_item"><input type="hidden" name="id" id="eId"><div class="form-grid">
<div class="form-group"><label>Nama *</label><input type="text" name="nama_item" id="eNama" required></div>
<div class="form-group"><label>Kategori *</label><select name="kategori" id="eKat"><option value="Tenaga Kesehatan">A. Tenaga Kesehatan</option><option value="Asisten Tenaga Kesehatan">B. Asisten Tenaga Kesehatan</option><option value="Tenaga Penunjang">C. Tenaga Penunjang</option></select></div>
<div class="form-group"><label>Parent</label><select name="id_parent" id="eParent"><option value="">-- Tanpa Parent --</option><?php foreach($parents as $p):?><option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nama_item']) ?></option><?php endforeach;?></select></div>
<div class="form-group"><label>Urutan</label><input type="number" name="urutan" id="eUrutan" min="0"></div>
<div class="form-group"><label>Aktif</label><select name="aktif" id="eAktif"><option value="Y">Y - Aktif</option><option value="N">N - Nonaktif</option></select></div>
</div><div style="display:flex;gap:12px;margin-top:20px;justify-content:flex-end"><button type="submit" class="btn-primary"><i class="fas fa-save"></i> Simpan</button><button type="button" class="btn-icon btn-danger" onclick="document.getElementById('editModal').style.display='none'">Batal</button></div></form>
</div></div>
<script>
function switchTab(name){
  document.querySelectorAll('.tab-btn').forEach(b=>b.classList.toggle('active', b.dataset.tab===name));
  document.getElementById('tab-items').style.display = name==='items'?'block':'none';
  document.getElementById('tab-faskes').style.display = name==='faskes'?'block':'none';
  const url=new URL(window.location);
  url.searchParams.set('tab', name);
  history.replaceState({},'',url);
}
document.querySelectorAll('.edit-btn').forEach(b=>b.onclick=function(){
  document.getElementById('eId').value=this.dataset.id;
  document.getElementById('eNama').value=this.dataset.nama;
  document.getElementById('eKat').value=this.dataset.kategori;
  document.getElementById('eParent').value=this.dataset.parent||'';
  document.getElementById('eUrutan').value=this.dataset.urutan;
  document.getElementById('eAktif').value=this.dataset.aktif;
  document.getElementById('editModal').style.display='flex';
  switchTab('items');
});
document.getElementById('editModal').onclick=function(e){if(e.target===this)this.style.display='none'}
</script>
</body>
</html>
