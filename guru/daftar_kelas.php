<?php
session_start();
require '../config/db.php';

if (!isset($_SESSION["role"]) || $_SESSION["role"] != "guru") {
    header("Location: ../index.php");
    exit();
}

$id_ujian = intval($_GET['id_ujian']);
// $qKelas = mysqli_query($conn, "
//     SELECT DISTINCT k.id_kelas, k.nama_kelas
//     FROM kelas k
//     JOIN siswa s ON k.id_kelas = s.id_kelas
//     JOIN jawaban j ON j.id_siswa = s.id
//     WHERE j.id_ujian = $id_ujian
// ");

$qKelas = mysqli_query($conn, "
SELECT k.id AS id_kelas, k.nama_kelas
FROM ujian u
JOIN kelas k ON u.id_kelas = k.id
WHERE u.id_ujian = $id_ujian;
");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Daftar Kelas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h2>Daftar Kelas yang Mengikuti Ujian</h2>
    <ul class="list-group">
        <?php while ($row = mysqli_fetch_assoc($qKelas)): ?>
            <li class="list-group-item d-flex justify-content-between align-items-center">
                <?= htmlspecialchars($row['nama_kelas']) ?>
                <a href="daftar_siswa_ujian.php?id_ujian=<?= $id_ujian ?>&id_kelas=<?= $row['id_kelas'] ?>" class="btn btn-primary btn-sm">Lihat Siswa</a>
            </li>
            <a href="cetak_nilai.php?id_ujian=<?= $id_ujian ?>&id_kelas=<?= $row['id_kelas'] ?>" class="btn btn-danger mt-3" target="_blank">
    🖨 Cetak PDF Nilai
</a>
        <?php endwhile; ?>
    </ul>
    <!-- <a href="cetak_nilai.php?id_ujian=<?= $id_ujian ?>&id_siswa=<?= $id_siswa ?>" class="btn btn-danger mt-3" target="_blank">
    🖨 Cetak PDF Nilai
</a> -->


<a href="daftar_ujian_essay.php" class="btn btn-secondary mt-4">Kembali ke Daftar Ujian</a>
</div>
</body>
</html>
