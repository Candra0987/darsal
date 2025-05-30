<?php
session_start();
$id_ujian = intval($_POST['id_ujian'] ?? 0);
$jawaban = $_POST['jawaban'] ?? [];

if ($id_ujian > 0 && is_array($jawaban)) {
    if (!isset($_SESSION['jawaban_siswa'])) {
        $_SESSION['jawaban_siswa'] = [];
    }

    // Simpan atau gabungkan jawaban sebelumnya
    if (!isset($_SESSION['jawaban_siswa'][$id_ujian])) {
        $_SESSION['jawaban_siswa'][$id_ujian] = [];
    }

    foreach ($jawaban as $id_soal => $jwb) {
        $_SESSION['jawaban_siswa'][$id_ujian][$id_soal] = $jwb;
    }

    echo json_encode(['status' => 'success']);
} else {
    echo json_encode(['status' => 'error']);
}
