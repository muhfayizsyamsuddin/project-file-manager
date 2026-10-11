
<?php

require __DIR__ . '/connection.php';

// 1. Validasi ID proyek
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    http_response_code(400);
    exit('ID proyek tidak valid.');
}

// 2. Ambil data proyek menggunakan prepared statement
$sql = "SELECT title, file_path
        FROM projects
        WHERE id = ?
        LIMIT 1";

$stmt = mysqli_prepare($connection, $sql);
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $title, $filePath);

if (!mysqli_stmt_fetch($stmt)) {
    mysqli_stmt_close($stmt);
    http_response_code(404);
    exit('Proyek tidak ditemukan.');
}

mysqli_stmt_close($stmt);

// 3. Pastikan path mengarah ke folder uploads
$relativePath = str_replace('\\', '/', $filePath ?? '');

if (strpos($relativePath, 'uploads/') !== 0) {
    http_response_code(404);
    exit('File tidak ditemukan.');
}

$storedName = basename($relativePath);
$filePath = __DIR__ . '/uploads/' . $storedName;

if (!is_file($filePath) || !is_readable($filePath)) {
    http_response_code(404);
    exit('File tidak ditemukan di server.');
}

// 4. Periksa tipe file yang diizinkan
$extension = strtolower(
    pathinfo($storedName, PATHINFO_EXTENSION)
);

$allowedTypes = [
    'pdf' => 'application/pdf',
    'jpg' => 'image/jpeg',
    'png' => 'image/png',
];

if (!isset($allowedTypes[$extension])) {
    http_response_code(403);
    exit('Tipe file tidak diizinkan.');
}

$finfo = new finfo(FILEINFO_MIME_TYPE);
$mimeType = $finfo->file($filePath);

if ($mimeType !== $allowedTypes[$extension]) {
    http_response_code(403);
    exit('Tipe file tidak sesuai.');
}

// 5. Kirim file sebagai unduhan
$downloadName = preg_replace(
    '/[^A-Za-z0-9_-]+/',
    '_',
    $title
);

$downloadName = trim($downloadName, '_');

if ($downloadName === '') {
    $downloadName = 'project';
}

$downloadName .= '.' . $extension;

header('Content-Type: ' . $mimeType);
header('X-Content-Type-Options: nosniff');
header('Content-Disposition: attachment; filename="' . $downloadName . '"');
header('Content-Length: ' . filesize($filePath));
header('Cache-Control: no-store');

readfile($filePath);
exit;
