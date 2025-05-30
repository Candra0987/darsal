<?php
session_start();
require '../config/db.php';

if (!isset($_SESSION["role"]) || $_SESSION["role"] != "siswa") {
    header("Location: ../index.php");
    exit();
}

$siswa_id = $_SESSION["user_id"];
$id_ujian = isset($_GET['id_ujian']) ? intval($_GET['id_ujian']) : 0;

if ($id_ujian == 0) {
    header("Location: daftar_mata_pelajaran.php");
    exit();
}

// Ambil semua jawaban untuk ujian ini
$qJawaban = mysqli_query($conn, "
    SELECT j.*, s.pertanyaan, s.tipe, s.jawaban AS kunci_jawaban
    FROM jawaban j 
    JOIN soal s ON j.id_soal = s.id_soal 
    WHERE j.id_siswa = $siswa_id AND j.id_ujian = $id_ujian
");

$jumlah_pg = $jumlah_essay = 0;
$benar_pg = 0;
$skor_essay_total = 0;
$essay_data = [];

while ($row = mysqli_fetch_assoc($qJawaban)) {
    if ($row['tipe'] === 'pg') {
        $jumlah_pg++;
        if (strtolower(trim($row['jawaban'])) === strtolower(trim($row['kunci_jawaban']))) {
            $benar_pg++;
        }
    } else {
        $jumlah_essay++;
        $skor_essay_total += is_numeric($row['skor']) ? (int)$row['skor'] : 0;
        $essay_data[] = $row;
    }
}

// Hitung nilai PG dan Essay
$nilai_pg = $benar_pg * 2; // max 60
$nilai_essay = $skor_essay_total * 2; // max 40
$nilai_akhir = $nilai_pg + $nilai_essay;

function getGrade($nilai) {
    if ($nilai >= 85) return "A";
    if ($nilai >= 75) return "B";
    if ($nilai >= 65) return "C";
    if ($nilai >= 55) return "D";
    return "E";
}
$grade = getGrade($nilai_akhir);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Hasil Ujian</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f4f7fc; }
        .container { margin-top: 50px; }
        .score-box { background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); margin-bottom: 20px; }
    </style>
</head>
<body>
<div class="container">
    <h2 class="mb-4 text-center">Hasil Ujian Anda</h2>

    <div class="score-box">
        <p><strong>Soal PG Benar:</strong> <?= $benar_pg ?>/<?= $jumlah_pg ?> (x2)</p>
        <p><strong>Nilai Pilihan Ganda:</strong> <?= $nilai_pg ?>/60</p>
        <p><strong>Nilai Essay:</strong> <?= $jumlah_essay > 0 ? $nilai_essay : "Belum Dinilai" ?>/40</p>
        <p><strong>Nilai Akhir:</strong> <?= $nilai_akhir ?>/100</p>
        <p><strong>Grade:</strong> <?= $grade ?></p>
    </div>

    <h5>Jawaban Essay:</h5>
    <?php if (count($essay_data) > 0): ?>
        <?php foreach ($essay_data as $essay): ?>
            <div class="score-box">
                <p><strong>Pertanyaan:</strong></p>
                <div><?= html_entity_decode($essay['pertanyaan']) ?></div>
                <p><strong>Jawaban Anda:</strong></p>
                <div><?= nl2br(htmlspecialchars($essay['jawaban'])) ?></div>
                <p><strong>Skor:</strong> <?= is_numeric($essay['skor']) ? $essay['skor'] : "Belum Dinilai" ?>/4 (x2 = <?= is_numeric($essay['skor']) ? $essay['skor'] * 2 : "" ?>)</p>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p>Tidak ada soal essay.</p>
    <?php endif; ?>

    <a href="daftar_mata_pelajaran.php" class="btn btn-secondary mt-4">Kembali ke Daftar Mata Pelajaran</a>
</div>
</body>
</html>
