<?php
require '../config/db.php';
require '../vendor/autoload.php';

use Dompdf\Dompdf;

date_default_timezone_set('Asia/Jakarta'); // Atur zona waktu ke WIB

$id_ujian = intval($_GET['id_ujian']);
$id_kelas = intval($_GET['id_kelas']); // Ini adalah kelas.id

// Ambil data ujian dulu
$qUjian = mysqli_query($conn, "
    SELECT id_ujian, nama_ujian
    FROM ujian
    WHERE id_ujian = $id_ujian
");
$ujian = mysqli_fetch_assoc($qUjian);

if (!$ujian) {
    die("Data ujian tidak ditemukan.");
}

// Ambil semua siswa di kelas beserta nilai ujian mereka (jika ada)
$qSiswa = mysqli_query($conn, "
    SELECT 
        s.user_id,  -- Ambil user_id dari tabel siswa
        u.username AS nama,
        s.id_kelas,
        hu.nilai_akhir
    FROM siswa s
    JOIN users u ON s.user_id = u.id
    LEFT JOIN hasil_ujian hu ON hu.id_siswa = s.user_id AND hu.id_ujian = $id_ujian
    WHERE s.id_kelas = $id_kelas
    ORDER BY u.username ASC
");

if (!$qSiswa || mysqli_num_rows($qSiswa) == 0) {
    die("Data siswa tidak ditemukan.");
}

// Mulai bangun HTML tabel untuk semua siswa
$html = '
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Nilai Ujian</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 14px; }
        h2 { text-align: center; margin-bottom: 30px; }
        table { margin: 0 auto; border-collapse: collapse; width: 90%; }
        th, td { border: 1px solid #000; padding: 8px; text-align: center; }
        th { background-color: #f2f2f2; }
        .footer { margin-top: 30px; text-align: center; font-size: 12px; color: #555; }
    </style>
</head>
<body>
    <h2>Laporan Nilai Ujian - ' . htmlspecialchars($ujian['nama_ujian']) . '</h2>
    <table>
        <thead>
            <tr>
                <th>No.</th>
                <th>User ID</th>  <!-- Ganti ID Siswa menjadi User ID -->
                <th>Nama Siswa</th>
                <th>ID Kelas</th>
                <th>Nilai Akhir</th>
            </tr>
        </thead>
        <tbody>
';

$no = 1;
while ($row = mysqli_fetch_assoc($qSiswa)) {
    $nilai = isset($row['nilai_akhir']) ? $row['nilai_akhir'] : '-';
    $html .= '
        <tr>
            <td>' . $no++ . '</td>
            <td>' . htmlspecialchars($row['user_id']) . '</td>  <!-- Tampilkan User ID -->
            <td>' . htmlspecialchars($row['nama']) . '</td>
            <td>' . htmlspecialchars($row['id_kelas']) . '</td>
            <td>' . htmlspecialchars($nilai) . '</td>
        </tr>
    ';
}

$html .= '
        </tbody>
    </table>

    <div class="footer">
        Dicetak pada: ' . date("d-m-Y H:i:s") . ' WIB
    </div>
</body>
</html>
';

// Generate PDF
$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream("Laporan_Nilai_Ujian_Kelas_{$id_kelas}.pdf", ["Attachment" => false]);
exit;
?>
