
<?php

require __DIR__ . '/connection.php';

$errors = [];

$title = '';
$description = '';
$tech = '';
$category = '';

function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Ambil dan validasi input
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $tech = trim($_POST['tech'] ?? '');
    $category = trim($_POST['category'] ?? '');

    if ($title === '') {
        $errors[] = 'Judul proyek wajib diisi.';
    }

    if ($description === '') {
        $errors[] = 'Deskripsi proyek wajib diisi.';
    }

    if ($tech === '') {
        $errors[] = 'Teknologi wajib diisi.';
    }

    if ($category === '') {
        $errors[] = 'Kategori proyek wajib diisi.';
    }

    // 2. Validasi file upload
    $file = $_FILES['project_file'] ?? null;
    $extension = '';
    $mimeType = '';

    if (!$file || $file['error'] === UPLOAD_ERR_NO_FILE) {
        $errors[] = 'File pendukung wajib diunggah.';
    } elseif ($file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'Upload gagal. Silakan coba lagi.';
    } else {
        $extension = strtolower(
            pathinfo($file['name'], PATHINFO_EXTENSION)
        );

        $allowedTypes = [
            'pdf' => 'application/pdf',
            'jpg' => 'image/jpeg',
            'png' => 'image/png',
        ];

        if ($file['size'] > 2 * 1024 * 1024) {
            $errors[] = 'Ukuran file maksimal 2 MB.';
        }

        if (!array_key_exists($extension, $allowedTypes)) {
            $errors[] = 'Format file harus PDF, JPG, atau PNG.';
        } else {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->file($file['tmp_name']);

            if ($mimeType !== $allowedTypes[$extension]) {
                $errors[] = 'Isi file tidak sesuai dengan formatnya.';
            }
        }
    }

    // 3. Buat folder uploads secara otomatis
    $uploadDir = __DIR__ . '/uploads';

    if (
        empty($errors) &&
        !is_dir($uploadDir) &&
        !mkdir($uploadDir, 0755, true) &&
        !is_dir($uploadDir)
    ) {
        $errors[] = 'Folder upload gagal dibuat.';
    }

    if (empty($errors) && !is_writable($uploadDir)) {
        $errors[] = 'Folder uploads tidak dapat ditulisi.';
    }

    // 4. Simpan file dan data ke database
    if (empty($errors)) {
        try {
            // Nama acak agar file tidak menimpa file lain
            $filename = bin2hex(random_bytes(16)) . '.' . $extension;

            $destination = $uploadDir . '/' . $filename;
            $filePath = 'uploads/' . $filename;

            if (!move_uploaded_file(
                $file['tmp_name'],
                $destination
            )) {
                $errors[] = 'File gagal disimpan ke folder uploads.';
            } else {
                $sql = "INSERT INTO projects
                        (title, description, tech, category, file_path)
                        VALUES (?, ?, ?, ?, ?)";

                $stmt = mysqli_prepare($connection, $sql);

                mysqli_stmt_bind_param(
                    $stmt,
                    'sssss',
                    $title,
                    $description,
                    $tech,
                    $category,
                    $filePath
                );

                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);

                header('Location: index.php?success=created');
                exit;
            }
        } catch (Throwable $exception) {
            // Hapus file jika penyimpanan database gagal
            if (isset($destination) && is_file($destination)) {
                unlink($destination);
            }

            $errors[] = 'Proyek gagal disimpan. Silakan coba lagi.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Proyek - Project Manager</title>

    <style>
        * {
            box-sizing: border-box;
        }

        :root {
            color-scheme: light;
        }

        body {
            margin: 0;
            min-height: 100vh;
            padding: 48px 20px;
            font-family: Arial, Helvetica, sans-serif;
            color: #172033;
            background: #f4f6fa;
        }

        .container {
            width: 100%;
            max-width: 760px;
            margin: 0 auto;
        }

        .card {
            padding: 32px;
            background: #ffffff;
            border: 1px solid #e5eaf2;
            border-radius: 18px;
            box-shadow: 0 8px 28px rgb(15 23 42 / 4%);
        }

        h1 {
            margin: 0 0 10px;
            font-size: 28px;
            font-weight: 700;
            letter-spacing: -0.7px;
            line-height: 1.3;
        }

        .subtitle {
            margin: 0 0 28px;
            color: #64748b;
            font-size: 14px;
            line-height: 1.7;
        }

        .form-group {
            margin-bottom: 22px;
        }

        label {
            display: block;
            margin-bottom: 9px;
            color: #334155;
            font-size: 13px;
            font-weight: 700;
        }

        input:not([type="hidden"]):not([type="file"]),
        textarea {
            display: block;
            width: 100%;
            min-height: 46px;
            padding: 12px 14px;
            border: 1px solid #dbe2ec;
            border-radius: 10px;
            outline: none;
            background: #ffffff;
            color: #172033;
            font: inherit;
            font-size: 14px;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        textarea {
            min-height: 130px;
            line-height: 1.7;
            resize: vertical;
        }

        input:not([type="hidden"]):not([type="file"]):focus,
        textarea:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgb(37 99 235 / 10%);
        }

        input[type="file"] {
            display: block;
            width: 100%;
            padding: 12px;
            border: 1px dashed #cbd5e1;
            border-radius: 10px;
            background: #f8fafc;
            color: #475569;
            font: inherit;
            font-size: 13px;
        }

        input[type="file"]::file-selector-button {
            margin-right: 12px;
            padding: 9px 13px;
            border: 0;
            border-radius: 7px;
            background: #eaf1ff;
            color: #1d4ed8;
            font-weight: 600;
            cursor: pointer;
        }

        .hint {
            margin: 8px 0 0;
            color: #64748b;
            font-size: 12px;
            line-height: 1.7;
            overflow-wrap: anywhere;
        }

        .errors {
            margin-bottom: 24px;
            padding: 15px 18px;
            border: 1px solid #fecaca;
            border-radius: 10px;
            background: #fef2f2;
            color: #b91c1c;
            font-size: 13px;
            line-height: 1.7;
        }

        .errors ul {
            margin: 8px 0 0;
            padding-left: 20px;
        }

        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 30px;
            padding-top: 22px;
            border-top: 1px solid #edf0f5;
        }

        .button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 42px;
            padding: 11px 17px;
            border: 1px solid transparent;
            border-radius: 9px;
            font: inherit;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: background 0.2s, transform 0.2s;
        }

        .button:hover {
            transform: translateY(-1px);
        }

        .primary {
            background: #2563eb;
            color: #ffffff;
        }

        .primary:hover {
            background: #1d4ed8;
        }

        .secondary {
            border-color: #e2e8f0;
            background: #ffffff;
            color: #475569;
        }

        .secondary:hover {
            background: #f8fafc;
        }

        @media (max-width: 600px) {
            body {
                padding: 24px 14px;
            }

            .card {
                padding: 22px 18px;
                border-radius: 14px;
            }

            h1 {
                font-size: 24px;
            }

            .actions {
                flex-direction: column;
            }

            .button {
                width: 100%;
            }
        }
    </style>
</head>

<body>
    <main class="container">
        <section class="card">
            <h1>Tambah Proyek</h1>
            <p class="subtitle">
                Masukkan informasi proyek dan file pendukungnya.
            </p>

            <?php if (!empty($errors)): ?>
                <div class="errors">
                    <strong>Periksa kembali data berikut:</strong>
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?= e($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form
                method="POST"
                enctype="multipart/form-data"
            >
                <div class="form-group">
                    <label for="title">Judul Proyek</label>
                    <input
                        type="text"
                        id="title"
                        name="title"
                        maxlength="150"
                        value="<?= e($title) ?>"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="description">Deskripsi</label>
                    <textarea
                        id="description"
                        name="description"
                        required
                    ><?= e($description) ?></textarea>
                </div>

                <div class="form-group">
                    <label for="tech">Teknologi</label>
                    <input
                        type="text"
                        id="tech"
                        name="tech"
                        value="<?= e($tech) ?>"
                        placeholder="PHP, MySQL, HTML, CSS"
                        required
                    >
                    <p class="hint">
                        Pisahkan nama teknologi dengan koma.
                    </p>
                </div>

                <div class="form-group">
                    <label for="category">Kategori</label>
                    <input
                        type="text"
                        id="category"
                        name="category"
                        maxlength="100"
                        value="<?= e($category) ?>"
                        placeholder="Web Application"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="project_file">File Pendukung</label>
                    <input
                        type="file"
                        id="project_file"
                        name="project_file"
                        accept=".pdf,.jpg,.png"
                        required
                    >
                    <p class="hint">
                        Format PDF, JPG, atau PNG. Maksimal 2 MB.
                    </p>
                </div>

                <div class="actions">
                    <button type="submit" class="button primary">
                        Simpan Proyek
                    </button>

                    <a
                        href="index.php"
                        class="button secondary"
                    >
                        Batal
                    </a>
                </div>
            </form>
        </section>
    </main>
</body>
</html>
