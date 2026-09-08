<?php
// Admin → Kelola SPM → Download Template (.xlsx, struktur = export, target dikosongkan).
require_once '../config.php';
requireLogin();
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../App/Services/SpmLib.php';

use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$ss = SpmLib::buildWorkbook($config, 'template');
$filename = 'Template_SPM_' . date('Ymd') . '.xlsx';
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');
$writer = new Xlsx($ss);
$writer->save('php://output');
exit;
