<?php
session_start();
require '../config/db.php';

if (!isset($_SESSION["role"]) || $_SESSION["role"] != "kepala sekolah") {
    header("Location: ../index.php");
    exit();
}

$qUjian = mysqli_query($conn, "
    SELECT DISTINCT u.id_ujian, u.nama_ujian
    FROM ujian u
    JOIN soal s ON u.id_ujian = s.id_ujian
");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Kepala Sekolah</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .card {
            border-radius: 1rem;
        }
        .header {
            background: linear-gradient(to right, #0d6efd, #6610f2);
            color: white;
            padding: 1rem 2rem;
            border-radius: 0 0 1rem 1rem;
            margin-bottom: 2rem;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
        <a class="navbar-brand" href="#">SEKIHMA - Kepala Sekolah</a>
        <div class="d-flex">
            <span class="navbar-text me-3">Halo, <?= $_SESSION["nama"] ?? 'Kepala Sekolah'; ?></span>
            <a href="../logout.php" class="btn btn-outline-light btn-sm">Logout</a>
        </div>
    </div>
</nav>

<div class="container mt-4">
    <div class="header text-center shadow-sm">
        <h2><i class="bi bi-journal-text"></i> Daftar Ujian Tersedia</h2>
        <p class="mb-0">Silakan pilih ujian untuk melihat hasil per kelas.</p>
    </div>

    <div class="row">
        <?php while ($row = mysqli_fetch_assoc($qUjian)): ?>
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card shadow-sm">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title"><?= htmlspecialchars($row['nama_ujian']) ?></h5>
                        </div>
                        <a href="daftar_kelas.php?id_ujian=<?= $row['id_ujian'] ?>" class="btn btn-outline-primary btn-sm">
                            <i class="bi bi-box-arrow-in-right"></i> Lihat
                        </a>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
</div>

</body>
</html>
