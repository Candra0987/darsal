<?php
session_start();
require '../config/db.php';

// Cek apakah pengguna sudah login dengan role 'siswa'
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'siswa') {
    header("Location: ../index.php");
    exit();
}

// Ambil id_jurusan dari session
$id_jurusan = $_SESSION['id_jurusan'] ?? null;
$id_kelas = $_SESSION['id_kelas'] ?? null;

// Cek apakah id_jurusan ada di session
if (!$id_jurusan) {
    echo "Jurusan tidak ditemukan di session.";
    exit();
}

date_default_timezone_set('Asia/Jakarta');
$now = date('Y-m-d H:i:s');

// Query untuk mengambil ujian yang tersedia hari ini sesuai jurusan
$query = "
    SELECT * FROM ujian 
    WHERE id_jurusan = $id_jurusan 
    AND id_kelas = $id_kelas
      AND waktu_mulai <= '$now' 
      AND waktu_selesai >= '$now'
    ORDER BY waktu_mulai ASC
";

$result = mysqli_query($conn, $query);

// Proses Logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: ../index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1"> <!-- Penting untuk responsif -->
    <title>Pilih Ujian</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-4">
    <!-- Header dan tombol logout -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-2">
        <h3 class="mb-0">Pilih Ujian Hari Ini</h3>
        <div class="d-flex flex-column flex-sm-row gap-2">
            <a href="hasil_ujian.php" class="btn btn-success">Lihat Nilai Siswa</a>
            <a href="?logout=true" class="btn btn-danger">Logout</a>
        </div>
    </div>

    <?php if (mysqli_num_rows($result) > 0): ?>
        <form method="GET" action="exam.php">
            <div class="mb-3">
                <label for="id_ujian" class="form-label">Pilih Ujian:</label>
                <select name="id_ujian" id="id_ujian" class="form-select" required>
                    <option value="">-- Pilih Ujian --</option>
                    <?php while ($ujian = mysqli_fetch_assoc($result)): ?>
                        <option value="<?= $ujian['id_ujian'] ?>">
                            <?= htmlspecialchars($ujian['nama_ujian']) ?> 
                            (<?= date('H:i', strtotime($ujian['waktu_mulai'])) ?> - <?= date('H:i', strtotime($ujian['waktu_selesai'])) ?>)
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-primary w-100 w-md-auto">Mulai Ujian</button>
        </form>
    <?php else: ?>
        <div class="alert alert-info">Tidak ada ujian yang tersedia hari ini.</div>
    <?php endif; ?>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
