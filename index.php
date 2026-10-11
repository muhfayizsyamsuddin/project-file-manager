
<?php

session_start();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require __DIR__ . '/connection.php';

// Mengambil seluruh data proyek dari database
$query = "SELECT id, title, description, tech, category, file_path, created_at
          FROM projects
          ORDER BY created_at DESC";

$result = mysqli_query($connection, $query);

if (!$result) {
    die("Gagal mengambil data proyek: " . mysqli_error($connection));
}

$projects = mysqli_fetch_all($result, MYSQLI_ASSOC);

// Helper untuk mengamankan teks saat ditampilkan ke HTML
function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Project & File Manager</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 40px 24px;
            font-family: Arial, sans-serif;
            color: #172033;
            background: #f4f6fa;
        }

        .container {
            max-width: 1180px;
            margin: 0 auto;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            margin-bottom: 28px;
        }

        .header h1 {
            margin: 0 0 8px;
            font-size: 30px;
            letter-spacing: -0.8px;
        }

        .subtitle {
            margin: 0;
            color: #64748b;
            line-height: 1.6;
        }

        .button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 10px 14px;
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

        .button-add {
            color: white;
            background: #2563eb;
        }

        .button-add:hover {
            background: #1d4ed8;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
            margin-bottom: 28px;
        }

        .stat-card {
            padding: 20px 22px;
            background: white;
            border: 1px solid #e5eaf2;
            border-radius: 14px;
        }

        .stat-label {
            color: #64748b;
            font-size: 13px;
        }

        .stat-value {
            display: block;
            margin-top: 8px;
            font-size: 28px;
            font-weight: 700;
            letter-spacing: -0.5px;
        }

        .projects-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 22px;
        }

        .project-card {
            min-width: 0;
            height: 100%;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            background: white;
            border: 1px solid #e5eaf2;
            border-radius: 15px;
            box-shadow: 0 3px 10px rgb(15 23 42 / 3%);
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .project-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 28px rgb(15 23 42 / 8%);
        }

        .project-preview {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            aspect-ratio: 16 / 9;
            overflow: hidden;
            background: linear-gradient(
                135deg,
                #f8fafc 0%,
                #eef2ff 100%
            );
        }

        .project-preview img {
            display: block;
            width: 100%;
            height: 100%;
            padding: 0;
            object-fit: cover;
        }

        .file-preview {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
            color: #64748b;
        }

        .file-icon {
            display: grid;
            width: 58px;
            height: 66px;
            place-items: center;
            color: #2563eb;
            background: white;
            border: 1px solid #dbe3ef;
            border-radius: 10px;
            font-size: 17px;
            font-weight: 700;
        }

        .category-badge {
            position: absolute;
            top: 14px;
            left: 14px;
            padding: 6px 10px;
            color: #1d4ed8;
            background: rgb(255 255 255 / 95%);
            border: 1px solid #dbeafe;
            border-radius: 7px;
            font-size: 11px;
            font-weight: 700;
        }

        .project-content {
            display: flex;
            flex: 1;
            flex-direction: column;
            padding: 21px;
        }

        .project-title {
            margin: 0 0 10px;
            font-size: 19px;
            line-height: 1.4;
            overflow-wrap: anywhere;
        }

        .project-description {
            margin: 0 0 18px;
            color: #64748b;
            font-size: 14px;
            line-height: 1.7;
            overflow-wrap: anywhere;
        }

        .project-meta {
            display: grid;
            gap: 10px;
            padding-top: 16px;
            border-top: 1px solid #edf0f5;
            font-size: 13px;
            line-height: 1.6;
            overflow-wrap: anywhere;
        }

        .meta-label {
            display: block;
            margin-bottom: 3px;
            color: #94a3b8;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .project-actions {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 9px;
            margin-top: auto;
            padding-top: 20px;
        }

        .project-actions form {
            margin: 0;
        }

        .button-download {
            color: #1d4ed8;
            background: #eff6ff;
            border-color: #dbeafe;
        }

        .button-edit {
            color: #334155;
            background: white;
            border-color: #dbe3ef;
        }

        .button-delete {
            color: #b91c1c;
            background: #fef2f2;
            border-color: #fee2e2;
        }

        .empty {
            grid-column: 1 / -1;
            padding: 56px 24px;
            text-align: center;
            color: #64748b;
            background: white;
            border: 1px dashed #cbd5e1;
            border-radius: 14px;
        }

        .empty h2 {
            margin-top: 0;
            color: #172033;
            font-size: 20px;
        }
        .notice {
            margin-bottom: 22px;
            padding: 13px 16px;
            border: 1px solid #bbf7d0;
            border-radius: 10px;
            background: #f0fdf4;
            color: #166534;
            font-size: 14px;
            line-height: 1.5;
        }

        @media (max-width: 760px) {
            body {
                padding: 26px 16px;
            }

            .projects-grid {
                grid-template-columns: 1fr;
            }

            .header {
                align-items: flex-start;
                flex-direction: column;
            }

            .header h1 {
                font-size: 26px;
            }
        }

        @media (max-width: 420px) {
            .stats {
                grid-template-columns: 1fr;
            }

            .project-content {
                padding: 17px;
            }
        }
    </style>
</head>

<body>
    <main class="container">

        <header class="header">
            <div>
                <h1>Project Manager</h1>
                <p class="subtitle">
                    Kelola data proyek dan file pendukung.
                </p>
            </div>

            <a href="add_project.php" class="button button-add">
                + Tambah Proyek
            </a>
        </header>

        <?php
            $successMessages = [
                'created' => 'Proyek berhasil ditambahkan.',
                'updated' => 'Perubahan proyek berhasil disimpan.',
                'deleted' => 'Proyek dan file terkait berhasil dihapus.',
            ];

            $success = $_GET['success'] ?? '';
            ?>

            <?php if (isset($successMessages[$success])): ?>
                <div class="notice" role="status">
                    <?= e($successMessages[$success]) ?>
                </div>
            <?php endif; ?>

        <?php
        $totalProjects = count($projects);

        $totalFiles = count(array_filter(
            $projects,
            function ($project) {
                return !empty($project['file_path']);
            }
        ));
        ?>

        <section class="stats">
            <div class="stat-card">
                <span class="stat-label">Total Proyek</span>
                <strong class="stat-value">
                    <?= $totalProjects ?>
                </strong>
            </div>

            <div class="stat-card">
                <span class="stat-label">File Tersimpan</span>
                <strong class="stat-value">
                    <?= $totalFiles ?>
                </strong>
            </div>
        </section>

        <section class="projects-grid">
            <?php if (empty($projects)): ?>

                <div class="card empty">
                    <h2>Belum Ada Proyek</h2>
                    <p>
                        Data proyek yang tersimpan di database
                        akan ditampilkan di sini.
                    </p>
                </div>

            <?php else: ?>

                <?php foreach ($projects as $project): ?>

                    <?php
                    $filePath = $project['file_path'] ?? '';

                    $extension = strtolower(
                        pathinfo($filePath, PATHINFO_EXTENSION)
                    );

                    $isImage = in_array(
                        $extension,
                        ['jpg', 'jpeg', 'png'],
                        true
                    );
                    ?>

                    <article class="project-card">

                        <div class="project-preview">
                            <?php if ($filePath !== '' && $isImage): ?>

                                <img
                                    src="<?= e($filePath) ?>"
                                    alt="File gambar untuk <?= e($project['title']) ?>"
                                    loading="lazy"
                                >

                            <?php else: ?>

                                <div class="file-preview">
                                    <div class="file-icon">
                                        <?= $extension === 'pdf' ? 'PDF' : 'FILE' ?>
                                    </div>
                                    <span>File pendukung proyek</span>
                                </div>

                            <?php endif; ?>

                            <span class="category-badge">
                                <?= e($project['category']) ?>
                            </span>
                        </div>

                        <div class="project-content">

                            <h2 class="project-title">
                                <?= e($project['title']) ?>
                            </h2>

                            <p class="project-description">
                                <?= nl2br(e($project['description'])) ?>
                            </p>

                            <div class="project-meta">

                                <div>
                                    <span class="meta-label">Teknologi</span>
                                    <?= e($project['tech']) ?>
                                </div>

                                <div>
                                    <span class="meta-label">Dibuat</span>
                                    <?= e($project['created_at']) ?>
                                </div>

                            </div>

                            <div class="project-actions">

                                <?php if ($filePath !== ''): ?>
                                    <a
                                        href="download.php?id=<?= (int) $project['id'] ?>"
                                        class="button button-download"
                                    >
                                        Download File
                                    </a>
                                <?php endif; ?>

                                <a
                                    href="edit_project.php?id=<?= (int) $project['id'] ?>"
                                    class="button button-edit"
                                >
                                    Edit
                                </a>

                                <form
                                    action="delete_project.php"
                                    method="POST"
                                    onsubmit="return confirm('Yakin ingin menghapus proyek ini beserta filenya?');"
                                >
                                    <input
                                        type="hidden"
                                        name="id"
                                        value="<?= (int) $project['id'] ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="csrf_token"
                                        value="<?= e($_SESSION['csrf_token']) ?>"
                                    >

                                    <button
                                        type="submit"
                                        class="button button-delete"
                                    >
                                        Hapus
                                    </button>
                                </form>

                            </div>
                        </div>
                    </article>

                <?php endforeach; ?>

            <?php endif; ?>
        </section>

    </main>
</body>
</html>
