
<?php

session_start();

require __DIR__ . '/connection.php';

// Pastikan penghapusan hanya melalui POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Metode tidak diizinkan.');
}

// Validasi CSRF token
$csrfToken = $_POST['csrf_token'] ?? '';

if (
    !is_string($csrfToken) ||
    empty($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $csrfToken)
) {
    http_response_code(403);
    exit('Permintaan tidak valid. Muat ulang halaman.');
}

// Validasi ID
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    http_response_code(400);
    exit('ID proyek tidak valid.');
}

// Cari file yang terkait dengan proyek
$sql = "SELECT file_path FROM projects WHERE id = ? LIMIT 1";
$stmt = mysqli_prepare($connection, $sql);

mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $filePath);

$projectExists = mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);

if (!$projectExists) {
    http_response_code(404);
    exit('Proyek tidak ditemukan.');
}

// Hapus file terkait jika ada
if (!empty($filePath)) {
    $relativePath = str_replace('\\', '/', $filePath);

    if (strpos($relativePath, 'uploads/') !== 0) {
        http_response_code(403);
        exit('Lokasi file tidak diizinkan.');
    }

    $uploadsDir = realpath(__DIR__ . '/uploads');
    $absolutePath = realpath(__DIR__ . '/' . $relativePath);

    if ($absolutePath !== false) {
        if (
            $uploadsDir === false ||
            strpos(
                $absolutePath,
                $uploadsDir . DIRECTORY_SEPARATOR
            ) !== 0 ||
            !is_file($absolutePath)
        ) {
            http_response_code(403);
            exit('Path file tidak valid.');
        }

        if (!unlink($absolutePath)) {
            http_response_code(500);
            exit('File gagal dihapus.');
        }
    }
}

// Hapus record dari database
$sql = "DELETE FROM projects WHERE id = ?";
$stmt = mysqli_prepare($connection, $sql);

mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);

$deleted = mysqli_stmt_affected_rows($stmt);
mysqli_stmt_close($stmt);

if ($deleted !== 1) {
    http_response_code(500);
    exit('Data proyek gagal dihapus.');
}

header('Location: index.php?success=deleted');
exit;
