
<?php

session_start();

require __DIR__ . '/connection.php';

function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// 1. Validasi ID proyek
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    http_response_code(400);
    exit('ID proyek tidak valid.');
}

// 2. Ambil data proyek yang akan diedit
$sql = "SELECT title, description, tech, category, file_path
        FROM projects
        WHERE id = ?
        LIMIT 1";

$stmt = mysqli_prepare($connection, $sql);
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);

mysqli_stmt_bind_result(
    $stmt,
    $title,
    $description,
    $tech,
    $category,
    $filePath
);

$found = mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);

if (!$found) {
    http_response_code(404);
    exit('Proyek tidak ditemukan.');
}

$errors = [];

// 3. Proses update ketika form dikirim
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';

    if (
        !is_string($csrfToken) ||
        !hash_equals($_SESSION['csrf_token'], $csrfToken)
    ) {
        http_response_code(403);
        exit('Permintaan tidak valid. Muat ulang halaman.');
    }

    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $tech = trim($_POST['tech'] ?? '');
    $category = trim($_POST['category'] ?? '');

    // 4. Validasi input
    if ($title === '') {
        $errors[] = 'Judul proyek wajib diisi.';
    } elseif (strlen($title) > 150) {
        $errors[] = 'Judul maksimal 150 karakter.';
    }

    if ($description === '') {
        $errors[] = 'Deskripsi proyek wajib diisi.';
    }

    if ($tech === '') {
        $errors[] = 'Teknologi wajib diisi.';
    }

    if ($category === '') {
        $errors[] = 'Kategori proyek wajib diisi.';
    } elseif (strlen($category) > 100) {
        $errors[] = 'Kategori maksimal 100 karakter.';
    }

    // 5. Simpan perubahan ke database
    if (empty($errors)) {
        try {
            $sql = "UPDATE projects
                    SET title = ?,
                        description = ?,
                        tech = ?,
                        category = ?
                    WHERE id = ?";

            $stmt = mysqli_prepare($connection, $sql);

            mysqli_stmt_bind_param(
                $stmt,
                'ssssi',
                $title,
                $description,
                $tech,
                $category,
                $id
            );

            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            header('Location: index.php?success=updated');
            exit;
        } catch (Throwable $exception) {
            $errors[] = 'Gagal memperbarui proyek. Silakan coba lagi.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Proyek</title>

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
        .current-file {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-top: 24px;
            padding: 16px;
            border: 1px solid #e5eaf2;
            border-radius: 12px;
            background: #f8fafc;
        }

        .current-file-icon {
            display: grid;
            flex-shrink: 0;
            width: 48px;
            height: 52px;
            place-items: center;
            border: 1px solid #dbeafe;
            border-radius: 9px;
            background: #eff6ff;
            color: #2563eb;
            font-size: 12px;
            font-weight: 700;
        }

        .current-file-info {
            display: flex;
            flex-direction: column;
            gap: 6px;
            min-width: 0;
        }

        .current-file-label {
            color: #64748b;
            font-size: 12px;
        }

        .current-file-link {
            width: fit-content;
            color: #2563eb;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
        }

        .current-file-link:hover {
            text-decoration: underline;
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
            <h1>Edit Proyek</h1>
            <p class="subtitle">
                Ubah informasi proyek, lalu simpan perubahan.
                File pendukung yang sudah diunggah tidak akan berubah.
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
                action="edit_project.php?id=<?= (int) $id ?>"
            >
                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= e($_SESSION['csrf_token']) ?>"
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
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="category">Kategori</label>
                    <input
                        type="text"
                        id="category"
                        name="category"
                        maxlength="100"
                        value="<?= e($category) ?>"
                        required
                    >
                </div>

                <?php if (!empty($filePath)): ?>
                    <?php
                    $currentExtension = strtoupper(
                        pathinfo($filePath, PATHINFO_EXTENSION)
                    );
                    ?>

                    <div class="current-file">
                        <div class="current-file-icon">
                            <?= e($currentExtension ?: 'FILE') ?>
                        </div>

                        <div class="current-file-info">
                            <span class="current-file-label">
                                File pendukung saat ini
                            </span>

                            <strong>
                                Lampiran <?= e($currentExtension) ?>
                            </strong>

                            <a
                                href="download.php?id=<?= (int) $id ?>"
                                class="current-file-link"
                            >
                                Download file saat ini
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <p class="hint">
                        Belum ada file pendukung untuk proyek ini.
                    </p>
                <?php endif; ?>

                <div class="actions">
                    <button type="submit" class="button primary">
                        Simpan Perubahan
                    </button>

                    <a href="index.php" class="button secondary">
                        Batal
                    </a>
                </div>
            </form>
        </section>
    </main>
</body>
</html>
