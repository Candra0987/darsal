<?php
session_start();
require '../config/db.php';

if (!isset($_SESSION["role"]) || $_SESSION["role"] != "siswa") {
    header("Location: ../index.php");
    exit();
}

$siswa_id = $_SESSION["user_id"];

// Ambil daftar mata pelajaran yang sudah dikerjakan oleh siswa
$qMataPelajaran = mysqli_query($conn, "
    SELECT DISTINCT s.id_pelajaran, u.id_ujian
    FROM jawaban j 
    JOIN soal s ON j.id_soal = s.id_soal 
    JOIN ujian u ON s.id_ujian = u.id_ujian
    WHERE j.id_siswa = $siswa_id
");

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Daftar Mata Pelajaran yang Dikerjakan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f4f7fc; }
        .container { margin-top: 50px; }
    </style>
</head>
<body>
<div class="container">
    <h2 class="mb-4 text-center">Daftar Mata Pelajaran yang Sudah Dikerjakan</h2>

    <ul class="list-group">
        <?php while ($row = mysqli_fetch_assoc($qMataPelajaran)): ?>
            <li class="list-group-item d-flex justify-content-between align-items-center">
                <?= htmlspecialchars($row['id_pelajaran']) ?>
                <a href="hasil_ujian.php?id_ujian=<?= $row['id_ujian'] ?>" class="btn btn-primary btn-sm">Lihat Hasil</a>
            </li>
        <?php endwhile; ?>
    </ul>

    <a href="pilih_ujian.php" class="btn btn-secondary mt-4">Kembali Pilih Ujian</a>
</div>
</body>
</html>
