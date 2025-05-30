<?php
require '../config/db.php';

$id_ujian = $_GET['id_ujian'];
$id_siswa = $_GET['id_siswa'];

// Ambil semua jawaban siswa
$query_jawaban = mysqli_query($conn, "
    SELECT s.id_soal, s.jawaban AS kunci, js.jawaban AS jawaban_siswa, s.tipe
    FROM jawaban_siswa js
    JOIN soal s ON js.id_soal = s.id_soal
    WHERE js.id_siswa = $id_siswa AND js.id_ujian = $id_ujian
");

$benar_pg = 0;
$total_pg = 0;
$benar_essay = 0;
$total_essay = 0;

while ($row = mysqli_fetch_assoc($query_jawaban)) {
    if ($row['tipe'] === 'pg') {
        $total_pg++;
        if (strtoupper(trim($row['jawaban_siswa'])) === strtoupper(trim($row['kunci']))) {
            $benar_pg++;
        }
    } elseif ($row['tipe'] === 'essay') {
        $total_essay++;
        if (strtolower(trim($row['jawaban_siswa'])) === strtolower(trim($row['kunci']))) {
            $benar_essay++;
        }
    }
}

// Fungsi hitung nilai
function hitungNilaiAkhir($benar_pg, $total_pg, $benar_essay, $total_essay) {
    $nilai_pg = ($total_pg > 0) ? ($benar_pg / $total_pg) * 100 : 0;
    $nilai_essay = ($total_essay > 0) ? ($benar_essay / $total_essay) * 100 : 0;
    $nilai_akhir = ($nilai_pg * 0.4) + ($nilai_essay * 0.6);
    return [
        'nilai_pg' => round($nilai_pg, 2),
        'nilai_essay' => round($nilai_essay, 2),
        'nilai_akhir' => round($nilai_akhir, 2)
    ];
}

// Proses
$hasil = hitungNilaiAkhir($benar_pg, $total_pg, $benar_essay, $total_essay);

// Simpan hasil ke tabel hasil_ujian
mysqli_query($conn, "
    INSERT INTO hasil_ujian (id_siswa, id_ujian, benar_pg, total_pg, benar_essay, total_essay, nilai_pg, nilai_essay, nilai_akhir)
    VALUES ($id_siswa, $id_ujian, $benar_pg, $total_pg, $benar_essay, $total_essay, {$hasil['nilai_pg']}, {$hasil['nilai_essay']}, {$hasil['nilai_akhir']})
");

// Tampilkan hasil
echo "<h3>Hasil Penilaian:</h3>";
echo "Benar PG: $benar_pg / $total_pg<br>";
echo "Benar Essay: $benar_essay / $total_essay<br>";
echo "Nilai PG (40%): {$hasil['nilai_pg']}<br>";
echo "Nilai Essay (60%): {$hasil['nilai_essay']}<br>";
echo "<strong>Nilai Akhir: {$hasil['nilai_akhir']}</strong>";
?>
