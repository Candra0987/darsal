<?php
session_start();
require '../config/db.php';

if (!isset($_SESSION["role"]) || $_SESSION["role"] != "kepala sekolah") {
    header("Location: ../index.php");
    exit();
}

if (!isset($_GET['id_ujian'])) {
    die("Parameter id_ujian harus disertakan.");
}

$id_ujian = intval($_GET['id_ujian']);

$qKelas = mysqli_query($conn, "
    SELECT DISTINCT s.id_kelas
    FROM siswa s
    JOIN hasil_ujian hu ON hu.id_siswa = s.user_id
    WHERE hu.id_ujian = $id_ujian
");

if (!$qKelas || mysqli_num_rows($qKelas) == 0) {
    die("Tidak ada kelas dengan data ujian ini.");
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <title>Daftar Kelas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet" />
    <style>
        body {
            background-color: #f8f9fa;
        }
        .header {
            background: linear-gradient(to right, #198754, #20c997);
            color: white;
            padding: 2rem;
            border-radius: 0 0 1rem 1rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
        }
        .card-kelas {
            border-left: 5px solid #198754;
            border-radius: 1rem;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-dark bg-success">
    <div class="container">
        <a class="navbar-brand" href="dashboard.php">← Dashboard</a>
        <span class="navbar-text text-white">
            Kepala Sekolah: <?= $_SESSION['nama'] ?? 'Login' ?>
        </span>
    </div>
</nav>

<div class="container">
    <div class="header text-center">
        <h2><i class="bi bi-people-fill"></i> Daftar Kelas</h2>
        <p class="mb-0">Pilih kelas untuk mencetak nilai ujian.</p>
    </div>

    <div class="row">
        <?php while ($kelas = mysqli_fetch_assoc($qKelas)) : ?>
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card card-kelas shadow-sm">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Kelas ID: <?= htmlspecialchars($kelas['id_kelas']) ?></h5>
                        <a href="cetak_nilai.php?id_ujian=<?= $id_ujian ?>&id_kelas=<?= $kelas['id_kelas'] ?>" 
                           target="_blank" class="btn btn-outline-danger btn-sm">
                            <i class="bi bi-printer-fill"></i> Cetak
                        </a>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    </div>

    <div class="text-center mt-4">
        <a href="dashboard.php" class="btn btn-secondary">
            <i class="bi bi-arrow-left-circle"></i> Kembali ke Dashboard
        </a>
    </div>
</div>

</body>
</html>
