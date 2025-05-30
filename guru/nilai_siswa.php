<?php
session_start();
require '../config/db.php';

// Cek apakah user sudah login sebagai guru
if (!isset($_SESSION["role"]) || $_SESSION["role"] != "guru") {
    header("Location: ../index.php");
    exit();
}

$id_ujian = intval($_GET['id_ujian']);
$id_siswa = intval($_GET['id_siswa']);

// Ambil data siswa
$qSiswa = mysqli_query($conn, "SELECT id FROM users WHERE id = $id_siswa");
$siswa = mysqli_fetch_assoc($qSiswa);

// --- Hitung nilai PG ---
$qPG = mysqli_query($conn, "
    SELECT j.jawaban, s.jawaban AS kunci_jawaban, s.id_soal
    FROM jawaban j
    JOIN soal s ON j.id_soal = s.id_soal
    WHERE j.id_ujian = $id_ujian AND j.id_siswa = $id_siswa AND s.tipe = 'pg'
");

$total_pg = mysqli_num_rows($qPG);
$benar_pg = 0;
while ($pg = mysqli_fetch_assoc($qPG)) {
    if (trim(strtolower($pg['jawaban'])) == trim(strtolower($pg['kunci_jawaban']))) {
        $benar_pg++;
    }
}
$nilai_pg = $benar_pg * 2;

// --- Essay: Ambil soal essay untuk siswa ini ---
$qEssay = mysqli_query($conn, "
    SELECT j.id_jawaban, s.pertanyaan, j.jawaban, j.skor
    FROM jawaban j
    JOIN soal s ON j.id_soal = s.id_soal
    WHERE j.id_ujian = $id_ujian AND j.id_siswa = $id_siswa AND s.tipe = 'essay'
");

// Jika form dikoreksi
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($_POST['skor'] as $id_jawaban => $nilai) {
        $nilai = min(4, max(0, intval($nilai))); // Range 0–4
        mysqli_query($conn, "UPDATE jawaban SET skor = $nilai WHERE id_jawaban = $id_jawaban");
    }

    // Hitung ulang skor essay
    $qNilaiEssay = mysqli_query($conn, "
        SELECT j.skor
        FROM jawaban j
        JOIN soal s ON j.id_soal = s.id_soal
        WHERE j.id_ujian = $id_ujian AND j.id_siswa = $id_siswa AND s.tipe = 'essay'");

    $total_skor = 0;
    $jumlah_essay = 0;
    while ($r = mysqli_fetch_assoc($qNilaiEssay)) {
        if (is_numeric($r['skor'])) {
            $total_skor += $r['skor'];
            $jumlah_essay++;
        }
    }
    $nilai_essay = $total_skor * 2;
    $nilai_akhir = $nilai_pg + $nilai_essay;

    // Simpan ke tabel hasil_ujian
    $cek = mysqli_query($conn, "SELECT * FROM hasil_ujian WHERE id_ujian = $id_ujian AND id_siswa = $id_siswa");
    $now = date('Y-m-d H:i:s');

    if (mysqli_num_rows($cek) > 0) {
        mysqli_query($conn, "
            UPDATE hasil_ujian SET
                benar_pg = $benar_pg,
                total_pg = $total_pg,
                benar_essay = $jumlah_essay,
                total_essay = $jumlah_essay,
                nilai_pg = $nilai_pg,
                nilai_essay = $nilai_essay,
                nilai_akhir = $nilai_akhir,
                waktu_nilai = '$now'
            WHERE id_ujian = $id_ujian AND id_siswa = $id_siswa
        ");
    } else {
        mysqli_query($conn, "
            INSERT INTO hasil_ujian (id_siswa, id_ujian, benar_pg, total_pg, benar_essay, total_essay, nilai_pg, nilai_essay, nilai_akhir, waktu_nilai)
            VALUES ($id_siswa, $id_ujian, $benar_pg, $total_pg, $jumlah_essay, $jumlah_essay, $nilai_pg, $nilai_essay, $nilai_akhir, '$now')
        ");
    }

    echo "<script>alert('Skor essay disimpan dan nilai akhir diperbarui.');window.location.href='nilai_siswa.php?id_ujian=$id_ujian&id_siswa=$id_siswa';</script>";
    exit();
}

// Hitung nilai essay (untuk tampilan awal)
$qNilaiEssay = mysqli_query($conn, "
    SELECT j.skor
    FROM jawaban j
    JOIN soal s ON j.id_soal = s.id_soal
    WHERE j.id_ujian = $id_ujian AND j.id_siswa = $id_siswa AND s.tipe = 'essay'");
$total_skor = 0;
$jumlah_essay = 0;
while ($r = mysqli_fetch_assoc($qNilaiEssay)) {
    if (is_numeric($r['skor'])) {
        $total_skor += $r['skor'];
        $jumlah_essay++;
    }
}
$nilai_essay = $total_skor * 2;
$nilai_akhir = $nilai_pg + $nilai_essay;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Penilaian Siswa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-4">
    <h3>Penilaian Ujian - <?= htmlspecialchars($siswa['id']) ?></h3>
    <hr>

    <div class="mb-4">
        <h5>Jawaban Pilihan Ganda (PG)</h5>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>No. Soal</th>
                    <th>Jawaban Siswa</th>
                    <th>Jawaban Benar</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $no_pg = 1;
                mysqli_data_seek($qPG, 0);
                while ($pg = mysqli_fetch_assoc($qPG)) {
                    $status = (trim(strtolower($pg['jawaban'])) == trim(strtolower($pg['kunci_jawaban']))) ? 'Benar' : 'Salah';
                ?>
                    <tr>
                        <td><?= $no_pg++ ?></td>
                        <td><?= htmlspecialchars($pg['jawaban']) ?></td>
                        <td><?= htmlspecialchars($pg['kunci_jawaban']) ?></td>
                        <td class="<?= ($status == 'Benar') ? 'text-success' : 'text-danger' ?>"><?= $status ?></td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
        <p>Benar: <strong><?= $benar_pg ?>/<?= $total_pg ?></strong></p>
        <p>Skor PG (×2): <strong><?= $nilai_pg ?></strong></p>
    </div>

    <form method="POST">
        <h5>Penilaian Essay (Maks 4 per soal)</h5>
        <?php $no = 1; mysqli_data_seek($qEssay, 0); while ($row = mysqli_fetch_assoc($qEssay)): ?>
            <div class="card mb-3">
                <div class="card-header">
                    Soal Essay #<?= $no++ ?>
                </div>
                <div class="card-body">
                    <p><strong>Pertanyaan:</strong></p>
                    <div><?= html_entity_decode($row['pertanyaan']) ?></div>

                    <p class="mt-2"><strong>Jawaban Siswa:</strong></p>
                    <div><?= nl2br(htmlspecialchars($row['jawaban'])) ?></div>

                    <div class="mt-3">
                        <label>Skor:</label>
                        <input type="number" name="skor[<?= $row['id_jawaban'] ?>]" value="<?= is_numeric($row['skor']) ? $row['skor'] : '' ?>" min="0" max="4" class="form-control" required>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>

        <button type="submit" class="btn btn-success">Simpan Skor Essay</button>
        <a href="daftar_siswa.php?id_ujian=<?= $id_ujian ?>&id_kelas=..." class="btn btn-secondary">Kembali</a>
    </form>

    <hr>
    <h5>Nilai Essay (×2): <strong><?= $nilai_essay ?></strong></h5>
    <h4 class="text-primary">Nilai Akhir: <strong><?= $nilai_akhir ?></strong></h4>
</div>
</body>
</html>
