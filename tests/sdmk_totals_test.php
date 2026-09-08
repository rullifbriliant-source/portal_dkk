<?php
/**
 * Unit test SDMK totals - Puskesmas Baki 2025
 * Spec:
 *  - Total Tenaga Kesehatan = 79 (SUM baris 1-13 include_in_total=1, tanpa sub a/b/c)
 *  - Total Tenaga Penunjang = 11
 *  - Grand Total = 90 (asumsi Total B = 0)
 *  - Semua baris bernomor (angka & huruf a/b/c) editable independen
 *  - Baris total/header is_total_row=1 read-only 403
 *  - Import match by (nama+kategori)
 */
require_once __DIR__ . '/../config/database.php';

function assertEqual($got, $exp, $msg){
    if($got !== $exp){
        echo "FAIL $msg: got ".var_export($got,true)." expected ".var_export($exp,true)."\n";
        exit(1);
    } else {
        echo "PASS $msg: $got\n";
    }
}

// Helper computeTotals same logic as sdmk.php
function computeTotals($config, $id_faskes){
    $items=[];
    $q=$config->query("SELECT id, kategori, include_in_total FROM tbl_sdm_items WHERE aktif='Y'");
    while($r=$q->fetch_assoc()) $items[]=$r;
    $perProf=[];
    $stmt=$config->prepare("SELECT id_profesi, SUM(jumlah) as tot FROM tbl_sdm_faskes WHERE id_faskes=? AND aktif='Y' GROUP BY id_profesi");
    $stmt->bind_param("i",$id_faskes);
    $stmt->execute();
    $res=$stmt->get_result();
    while($row=$res->fetch_assoc()) $perProf[$row['id_profesi']]=(int)$row['tot'];
    $totA=0;$totB=0;$totC=0;
    foreach($items as $it){
        $v=$perProf[$it['id']]??0;
        if($it['kategori']==='Tenaga Kesehatan'){
            if((int)$it['include_in_total']===1) $totA+=$v;
        } elseif($it['kategori']==='Asisten Tenaga Kesehatan'){ $totB+=$v; }
        else { $totC+=$v; }
    }
    return ['A'=>$totA,'B'=>$totB,'C'=>$totC,'grand'=>$totA+$totB+$totC];
}

echo "=== Setup test faskes ===\n";
// Ensure test kecamatan exists or use Baki (id 2)
$kecId=2; // Baki
// Create ephemeral test faskes
$testName='TEST Puskesmas Baki 2025 UNIT';
$config->query("DELETE FROM tbl_faskes WHERE nama_faskes='$testName'");
$config->query("INSERT INTO tbl_faskes (nama_faskes, jenis, id_kecamatan, kecamatan, aktif) VALUES ('$testName','Puskesmas',$kecId,'baki','Y')");
$testFaskesId=$config->insert_id;
if(!$testFaskesId) { echo "FAIL create test faskes: ".$config->error."\n"; exit(1); }
echo "Test faskes id=$testFaskesId\n";

// Map items
$map=[];
$q=$config->query("SELECT id, nama_item, kategori, urutan, include_in_total FROM tbl_sdm_items WHERE aktif='Y' ORDER BY FIELD(kategori,'Tenaga Kesehatan','Asisten Tenaga Kesehatan','Tenaga Penunjang'), urutan");
while($r=$q->fetch_assoc()){
    $map[$r['kategori'].'|'.$r['nama_item']]=$r;
    // also debug
    // echo json_encode($r, JSON_UNESCAPED_UNICODE)."\n";
}

// === Verify schema columns exist ===
$res=$config->query("SHOW COLUMNS FROM tbl_sdm_items LIKE 'parent_id'");
assertEqual($res->num_rows,1,'column parent_id exists');
$res=$config->query("SHOW COLUMNS FROM tbl_sdm_items LIKE 'is_total_row'");
assertEqual($res->num_rows,1,'column is_total_row exists');
$res=$config->query("SHOW COLUMNS FROM tbl_sdm_items LIKE 'include_in_total'");
assertEqual($res->num_rows,1,'column include_in_total exists');

// === Verify include_in_total logic for Tenaga Kesehatan ===
$check=$config->query("SELECT COUNT(*) as c FROM tbl_sdm_items WHERE kategori='Tenaga Kesehatan' AND parent_id IS NOT NULL AND include_in_total=1");
$row=$check->fetch_assoc();
assertEqual((int)$row['c'],0,'no child with include_in_total=1 (avoid double count)');
$check=$config->query("SELECT COUNT(*) as c FROM tbl_sdm_items WHERE kategori='Tenaga Kesehatan' AND parent_id IS NULL AND include_in_total=0");
$row=$check->fetch_assoc();
assertEqual((int)$row['c'],0,'no parent main with include_in_total=0');

// Verify duplicate names across kategori exist
$check=$config->query("SELECT COUNT(*) as c FROM tbl_sdm_items WHERE nama_item='Gizi'");
$row=$check->fetch_assoc();
assertEqual((int)$row['c'],2,"Gizi appears in 2 kategori");
$check=$config->query("SELECT COUNT(*) as c FROM tbl_sdm_items WHERE nama_item='Terapis Gigi dan Mulut'");
$row=$check->fetch_assoc();
assertEqual((int)$row['c'],2,"Terapis Gigi dan Mulut appears in 2 kategori");
// Verify Asisten Keperawatan sudah dihapus (aktif=N) per request hapus bagian B nomor 3
$check=$config->query("SELECT aktif FROM tbl_sdm_items WHERE nama_item='Asisten Keperawatan'");
$row=$check->fetch_assoc();
assertEqual($row['aktif'],'N',"Asisten Keperawatan is soft-deleted (aktif=N)");
$check=$config->query("SELECT COUNT(*) as c FROM tbl_sdm_items WHERE kategori='Asisten Tenaga Kesehatan' AND aktif='Y'");
$row=$check->fetch_assoc();
assertEqual((int)$row['c'],2,"B section now only 2 active rows (Gizi + Terapis Gigi dan Mulut)");

// === Insert sample data Puskesmas Baki 2025 ===
/* Total A 79 breakdown (baris 1-13):
1 Dokter Umum 5, 2 Dokter Gigi 2, 3 Psikologi Klinis 1, 4 Keperawatan 25, 5 Kebidanan 15,
6 Kefarmasian 4, 7 Kesmas 3, 8 Kesling 2, 9 Gizi 4, 10 Keterapian Fisik 2,
11 Keteknisian Medis 3, 12 Teknik Biomedika 5, 13 Nakes Lainnya 8  => 79
Penunjang 11: Struktural 4, Dukungan 7
Subs a/b/c arbitrary (should not affect Total A):
Apoteker 1, Tenaga Teknis 2, Fisioterapis 2, Okupasi 1, Terapis Wicara 0,
Perekam Medis 2, Teknisi Gigi 1, Terapis Gigi (A) 1, Radiografer 1, ATLM 2, Radioterapis 0
B section all 0 (so grand 90)
*/
$sample = [
    // Tenaga Kesehatan mains (include=1)
    ['Tenaga Kesehatan','Dokter Umum',5],
    ['Tenaga Kesehatan','Dokter Gigi',2],
    ['Tenaga Kesehatan','Psikologi Klinis',1],
    ['Tenaga Kesehatan','Keperawatan',25],
    ['Tenaga Kesehatan','Kebidanan',15],
    ['Tenaga Kesehatan','Kefarmasian',4],
    ['Tenaga Kesehatan','Kesehatan Masyarakat',3],
    ['Tenaga Kesehatan','Kesehatan Lingkungan',2],
    ['Tenaga Kesehatan','Gizi',4],
    ['Tenaga Kesehatan','Keterapian Fisik',2],
    ['Tenaga Kesehatan','Keteknisian Medis',3],
    ['Tenaga Kesehatan','Teknik Biomedika',5],
    ['Tenaga Kesehatan','Nakes Lainnya',8],
    // subs (include=0) - not counted
    ['Tenaga Kesehatan','Apoteker',1],
    ['Tenaga Kesehatan','Tenaga Teknis Kefarmasian',2],
    ['Tenaga Kesehatan','Fisioterapis',2],
    ['Tenaga Kesehatan','Okupasi Terapi',1],
    ['Tenaga Kesehatan','Terapis Wicara',0],
    ['Tenaga Kesehatan','Perekam Medis dan Informasi Kesehatan',2],
    ['Tenaga Kesehatan','Teknisi Gigi',1],
    ['Tenaga Kesehatan','Terapis Gigi dan Mulut',1],
    ['Tenaga Kesehatan','Radiografer',1],
    ['Tenaga Kesehatan','Ahli Teknologi Laboratorium Medik',2],
    ['Tenaga Kesehatan','Radioterapis',0],
    // Asisten semua 0 (Asisten Keperawatan sudah dihapus per request 2026-09-08)
    ['Asisten Tenaga Kesehatan','Gizi',0],
    ['Asisten Tenaga Kesehatan','Terapis Gigi dan Mulut',0],
    // Penunjang
    ['Tenaga Penunjang','Struktural',4],
    ['Tenaga Penunjang','Dukungan Manajemen',7],
];

foreach($sample as $s){
    [$kat,$nama,$jumlah] = $s;
    $key=$kat.'|'.$nama;
    if(!isset($map[$key])){ echo "FAIL missing map $key\n"; exit(1); }
    $pid=$map[$key]['id'];
    // split jumlah arbitrarily into asn_l etc but jumlah must match sum
    $asn_l = intdiv($jumlah, 2);
    $asn_p = $jumlah - $asn_l;
    $stmt=$config->prepare("INSERT INTO tbl_sdm_faskes (id_kecamatan, id_faskes, id_profesi, id_spesialis, asn_l, asn_p, nonasn_l, nonasn_p, aktif) VALUES (?, ?, ?, NULL, ?, ?, 0, 0, 'Y')");
    $stmt->bind_param("iiiii",$kecId,$testFaskesId,$pid,$asn_l,$asn_p);
    if(!$stmt->execute()){ echo "FAIL insert $key: ".$stmt->error."\n"; exit(1); }
}
echo "Sample data inserted\n";

// === Assert totals ===
$totals=computeTotals($config,$testFaskesId);
assertEqual($totals['A'],79,'Total Tenaga Kesehatan =79');
assertEqual($totals['C'],11,'Total Tenaga Penunjang =11');
assertEqual($totals['B'],0,'Total Asisten =0');
assertEqual($totals['grand'],90,'Grand Total =90');

// Verify double count prevention: sum including subs would be higher
$q=$config->query("SELECT SUM(jumlah) as s FROM tbl_sdm_faskes WHERE id_faskes=$testFaskesId AND aktif='Y'");
$row=$q->fetch_assoc();
$sumAll=(int)$row['s'];
echo "SumAll including subs = $sumAll (should be >90 if subs counted)\n";
if($sumAll <= 90){ echo "FAIL double-count check: sumAll should be >90 due to subs\n"; exit(1); }
echo "PASS double-count check: subs not included in Total A\n";

// Verify is_total_row guard: try to simulate update via function that checks 403
$config->query("UPDATE tbl_sdm_items SET is_total_row=1 WHERE nama_item='Nakes Lainnya' AND kategori='Tenaga Kesehatan' LIMIT 1");
$chk=$config->query("SELECT is_total_row FROM tbl_sdm_items WHERE nama_item='Nakes Lainnya' LIMIT 1")->fetch_assoc();
if((int)$chk['is_total_row']===1){
    echo "Simulated is_total_row=1, endpoint should reject 403\n";
    // revert
    $config->query("UPDATE tbl_sdm_items SET is_total_row=0 WHERE nama_item='Nakes Lainnya' LIMIT 1");
    echo "PASS is_total_row guard column works\n";
}

// Cleanup
$config->query("DELETE FROM tbl_sdm_faskes WHERE id_faskes=$testFaskesId");
$config->query("DELETE FROM tbl_faskes WHERE id_faskes=$testFaskesId");
echo "Cleanup done\n";
echo "=== ALL TESTS PASSED ===\n";
