<?php
session_start();
require '../config/db.php';

if (!isset($_SESSION["role"]) || $_SESSION["role"] != "guru") {
    header("Location: ../index.php");
    exit();
}

// Ambil daftar ujian yang punya soal essay
$qUjian = mysqli_query($conn, "
    SELECT DISTINCT u.id_ujian, u.nama_ujian
    FROM ujian u
    JOIN soal s ON u.id_ujian = s.id_ujian
    WHERE s.tipe = 'essay'
");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Daftar Ujian Essay</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .container { margin-top: 50px; }
    </style>
</head>
<body>
<div class="container">
    <h2 class="mb-4 text-center">Daftar Ujian dengan Soal Essay</h2>

    <ul class="list-group">
        <?php while ($row = mysqli_fetch_assoc($qUjian)): ?>
            <li class="list-group-item d-flex justify-content-between align-items-center">
                <?= htmlspecialchars($row['nama_ujian']) ?>
                <!-- <a href="nilai_essay.php?id_ujian=<?= $row['id_ujian'] ?>" class="btn btn-primary btn-sm">Nilai Essay</a> -->
                <a href="daftar_kelas.php?id_ujian=<?= $row['id_ujian']?>" class="btn btn-primary btn-sm">Nilai Essay</a>
            </li>
            
        <?php endwhile; ?>
    </ul>

    <?php 
    ?> 
    <!-- <a href="cetak_nilai.php?id_ujian=<?= $id_ujian ?>&id_siswa=<?= $id_siswa ?>" class="btn btn-danger mt-3" target="_blank">
    🖨 Cetak PDF Nilai
</a> -->

    <a href="dashboard.php" class="btn btn-secondary mt-4">Kembali ke Dashboard</a>
</div>
</body>
</html>
