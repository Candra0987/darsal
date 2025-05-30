<?php
session_start();
require '../config/db.php';

// Cek apakah user sudah login sebagai guru
if (!isset($_SESSION["role"]) || $_SESSION["role"] != "guru") {
    header("Location: ../index.php");
    exit();
}

// Ambil ID ujian dan ID kelas dari parameter URL
$id_ujian = intval($_GET['id_ujian']);
$id_kelas = intval($_GET['id_kelas']);

// Ambil daftar siswa dari kelas terkait
$qSiswa = mysqli_query($conn, "
    SELECT u.id, s.nama_siswa
    FROM users u
    JOIN siswa s ON u.id = s.user_id
    WHERE s.id_kelas = $id_kelas
") or die("Query error: " . mysqli_error($conn));

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Daftar Siswa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h2>Daftar Siswa</h2>
    <ul class="list-group">
        <?php if (mysqli_num_rows($qSiswa) === 0): ?>
            <li class="list-group-item">Tidak ada siswa di kelas ini.</li>
        <?php else: ?>
            <?php while ($row = mysqli_fetch_assoc($qSiswa)): ?>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <?= htmlspecialchars($row['nama_siswa']) ?> (ID: <?= $row['id'] ?>)
                    <a href="nilai_siswa.php?id_ujian=<?= $id_ujian ?>&id_siswa=<?= $row['id'] ?>" class="btn btn-success btn-sm">Nilai</a>
                </li>
            <?php endwhile; ?>
        <?php endif; ?>
    </ul>
  <a href="daftar_kelas.php?id_ujian=1" class="btn btn-secondary mt-4">Kembali ke Daftar Kelas</a>



</div>
</body>
</html>
