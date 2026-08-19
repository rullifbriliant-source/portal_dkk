<?php
/**
 * ==========================================================
 * PORTAL DKK
 * MAP BUILDER
 * SAVE ENGINE
 * ==========================================================
 */

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);

    echo json_encode([
        'status' => false,
        'message' => 'Method tidak diizinkan'
    ]);

    exit;
}

$json = file_get_contents("php://input");

if (!$json) {

    echo json_encode([
        'status' => false,
        'message' => 'Data kosong'
    ]);

    exit;
}

$data = json_decode($json, true);

if (!is_array($data)) {

    echo json_encode([
        'status' => false,
        'message' => 'JSON tidak valid'
    ]);

    exit;
}

$file = __DIR__ . '/data.json';

$result = file_put_contents(
    $file,
    json_encode(
        $data,
        JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
    )
);

if ($result === false) {

    echo json_encode([
        'status' => false,
        'message' => 'Gagal menyimpan data'
    ]);

    exit;
}

echo json_encode([
    'status' => true,
    'message' => 'Berhasil disimpan',
    'total' => count($data)
]);