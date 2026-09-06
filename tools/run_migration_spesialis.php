<?php
require_once __DIR__ . '/../config/database.php';
$sqlFile = __DIR__ . '/../database/migrations/20260906_add_spesialis_dokter.sql';
$sql = file_get_contents($sqlFile);
if (!$sql) die("Gagal baca SQL\n");
// split not trivial due to PREPARE, so use mysqli_multi_query
if (mysqli_multi_query($config, $sql)) {
    do {
        if ($result = mysqli_store_result($config)) mysqli_free_result($result);
        if (mysqli_errno($config)) {
            echo "Error: ".mysqli_error($config)."\n";
        }
    } while (mysqli_next_result($config));
    echo "Migration executed. Check errors above.\n";
} else {
    echo "Multi query failed: ".mysqli_error($config)."\n";
}
// Verify
echo "--- VERIFY tbl_spesialis ---\n";
$r = mysqli_query($config, "SHOW TABLES LIKE 'tbl_spesialis'");
echo mysqli_num_rows($r) ? "tbl_spesialis exists\n" : "tbl_spesialis NOT exists\n";
$r = mysqli_query($config, "SELECT COUNT(*) c FROM tbl_spesialis");
if ($r) { $row=mysqli_fetch_assoc($r); echo "tbl_spesialis rows: ".$row['c']."\n"; }
$r = mysqli_query($config, "SELECT id, nama_spesialis, kode FROM tbl_spesialis ORDER BY urutan LIMIT 10");
while ($rw=mysqli_fetch_assoc($r)) echo $rw['id']." ".$rw['nama_spesialis']." (".$rw['kode'].")\n";
echo "--- cols tbl_sdm ---\n";
$r = mysqli_query($config, "SHOW COLUMNS FROM tbl_sdm");
while ($c=mysqli_fetch_assoc($r)) echo $c['Field']." ".$c['Type']."\n";
echo "--- cols tbl_sdm_faskes ---\n";
$r = mysqli_query($config, "SHOW COLUMNS FROM tbl_sdm_faskes");
while ($c=mysqli_fetch_assoc($r)) echo $c['Field']." ".$c['Type']."\n";
echo "--- cols tbl_sdm_kecamatan ---\n";
$r = mysqli_query($config, "SHOW COLUMNS FROM tbl_sdm_kecamatan");
while ($c=mysqli_fetch_assoc($r)) echo $c['Field']." ".$c['Type']."\n";
