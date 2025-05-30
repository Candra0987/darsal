<?php
session_start();
date_default_timezone_set('Asia/Jakarta');
require '../config/db.php';

// Memastikan hanya guru yang bisa mengakses halaman ini
if (!isset($_SESSION["role"]) || $_SESSION["role"] != "guru") {
    header("Location: ../index.php");
    exit();
}

// Mengambil ID ujian dari URL
$id_ujian = isset($_GET['id_ujian']) ? intval($_GET['id_ujian']) : 0;

// Jika ID ujian tidak valid
if ($id_ujian == 0) {
    echo "ID ujian tidak valid.";
    exit();
}

// Ambil soal yang bertipe 'essay' untuk ujian ini
$qEssay = mysqli_query($conn, "
    SELECT j.*, s.pertanyaan, u.nama_ujian, us.id
    FROM jawaban j
    JOIN soal s ON j.id_soal = s.id_soal
    JOIN ujian u ON s.id_ujian = u.id_ujian
    JOIN users us ON j.id_siswa = us.id
    WHERE j.id_ujian = $id_ujian AND s.tipe ='essay'  
    ORDER BY j.id_siswa
");

// Debugging: Cek jika query berhasil
if (!$qEssay) {
    echo "Query gagal: " . mysqli_error($conn);
    exit();
}

// Debugging: Cek jika query mengembalikan hasil
if (mysqli_num_rows($qEssay) == 0) {
    echo "Tidak ada soal bertipe 'essay' atau soal tersebut tidak memiliki jawaban yang dinilai.";
    exit();
}

// Jika form disubmit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($_POST['skor'] as $id_jawaban => $nilai) {
        $nilai = intval($nilai);
        if ($nilai > 4) {
            $nilai = 4; // Maksimal skor per soal adalah 4
        }
        // Update skor jawaban di database
        mysqli_query($conn, "UPDATE jawaban SET skor = $nilai WHERE id_jawaban = $id_jawaban");
    }
    // Feedback dan redirect
    echo "<script>alert('Skor berhasil disimpan.');window.location.href='nilai_essay.php?id_ujian=$id_ujian';</script>";
    exit();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Penilaian Essay</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .container { margin-top: 50px; }
        .card { margin-bottom: 20px; }
    </style>
</head>
<body>
<div class="container">
    <h2 class="mb-4 text-center">Penilaian Jawaban Essay - Ujian ID <?= $id_ujian ?></h2>

    <form method="POST">
        <?php 
        $counter = 1;
        while ($row = mysqli_fetch_assoc($qEssay)): 
        ?>
            <div class="card">
                <div class="card-header">
                    <strong>Nama Siswa:</strong> <?= htmlspecialchars($row['id']) ?>
                </div>
                <div class="card-body">
                    <p><strong>Pertanyaan <?= $counter ?>:</strong></p>
                    <div><?= html_entity_decode($row['pertanyaan']) ?></div>

                    <p class="mt-3"><strong>Jawaban Siswa:</strong></p>
                    <div><?= nl2br(htmlspecialchars($row['jawaban'])) ?></div>

                    <div class="mt-3">
                        <label><strong>Skor (0-4):</strong></label>
                        <input type="number" name="skor[<?= $row['id_jawaban'] ?>]" value="<?= is_numeric($row['skor']) ? $row['skor'] : '' ?>" class="form-control" min="0" max="4" required>
                    </div>
                </div>
            </div>
        <?php 
        $counter++; 
        endwhile; 
        ?>

        <button type="submit" class="btn btn-success">Simpan Semua Nilai</button>
        <a href="dashboard.php" class="btn btn-secondary">Kembali ke Dashboard</a>
    </form>
</div>
</body>
</html>
