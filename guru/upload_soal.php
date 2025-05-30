<?php
session_start();
require '../vendor/autoload.php';
require '../config/db.php';

use PhpOffice\PhpWord\IOFactory;

// Cek apakah user adalah guru
if (!isset($_SESSION["role"]) || $_SESSION["role"] != "guru") {
    header("Location: ../index.php");
    exit();
}

$user_id = $_SESSION["user_id"];

// Ambil id_guru berdasarkan user_id
$result_guru = mysqli_query($conn, "SELECT * FROM guru WHERE user_id = $user_id");
if ($row = mysqli_fetch_assoc($result_guru)) {
    $_SESSION['id_guru'] = $row['id'];
}
$idGuru = $_SESSION['id_guru'] ?? null;

if (!$idGuru) {
    die("ID Guru tidak ditemukan.");
}

// Proses saat form disubmit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_ujian = $_POST['id_ujian'];
    $id_kelas = $_POST['id_kelas'];
    $id_pelajaran = $_POST['id_pelajaran'];
    $tipe = $_POST['tipe'];

    $soal_ditambahkan = 0;
    $soal_duplikat = 0;

    if (isset($_FILES['file_docx']) && $_FILES['file_docx']['error'] == 0) {
        $file_tmp = $_FILES['file_docx']['tmp_name'];
        $phpWord = IOFactory::load($file_tmp);
        $tables = $phpWord->getSections()[0]->getElements();

        foreach ($tables as $element) {
            if ($element instanceof \PhpOffice\PhpWord\Element\Table) {
                foreach ($element->getRows() as $i => $row) {
                    if ($i == 0) continue; // skip header
                    $cells = $row->getCells();

                    if ($tipe == 'pg' && count($cells) >= 6) {
                        $pertanyaan = trim($cells[0]->getElements()[0]->getText());
                        $opsi_a     = trim($cells[1]->getElements()[0]->getText());
                        $opsi_b     = trim($cells[2]->getElements()[0]->getText());
                        $opsi_c     = trim($cells[3]->getElements()[0]->getText());
                        $opsi_d     = trim($cells[4]->getElements()[0]->getText());
                        $jawaban    = strtoupper(trim($cells[5]->getElements()[0]->getText()));

                        $cek = $conn->prepare("SELECT * FROM soal WHERE pertanyaan = ? AND id_ujian = ? AND id_kelas = ? AND id_pelajaran = ? AND id_guru = ?");
                        $cek->bind_param("siiii", $pertanyaan, $id_ujian, $id_kelas, $id_pelajaran, $idGuru);
                        $cek->execute();
                        $cek->store_result();

                        if ($cek->num_rows == 0) {
                            $stmt = $conn->prepare("INSERT INTO soal (id_ujian, id_guru, id_kelas, id_pelajaran, pertanyaan, opsi_a, opsi_b, opsi_c, opsi_d, jawaban, tipe) 
                                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                            $stmt->bind_param("iiiisssssss", $id_ujian, $idGuru, $id_kelas, $id_pelajaran, $pertanyaan, $opsi_a, $opsi_b, $opsi_c, $opsi_d, $jawaban, $tipe);
                            $stmt->execute();
                            $soal_ditambahkan++;
                        } else {
                            $soal_duplikat++;
                        }
                    }

                    if ($tipe == 'essay' && count($cells) >= 2) {
                        $pertanyaan = trim($cells[0]->getElements()[0]->getText());
                        $jawaban    = trim($cells[1]->getElements()[0]->getText());

                        $cek = $conn->prepare("SELECT * FROM soal WHERE pertanyaan = ? AND id_ujian = ? AND id_kelas = ? AND id_pelajaran = ? AND id_guru = ?");
                        $cek->bind_param("siiii", $pertanyaan, $id_ujian, $id_kelas, $id_pelajaran, $idGuru);
                        $cek->execute();
                        $cek->store_result();

                        if ($cek->num_rows == 0) {
                            $stmt = $conn->prepare("INSERT INTO soal (id_ujian, id_guru, id_kelas, id_pelajaran, pertanyaan, jawaban, tipe) 
                                                    VALUES (?, ?, ?, ?, ?, ?, ?)");
                            $stmt->bind_param("iiissss", $id_ujian, $idGuru, $id_kelas, $id_pelajaran, $pertanyaan, $jawaban, $tipe);
                            $stmt->execute();
                            $soal_ditambahkan++;
                        } else {
                            $soal_duplikat++;
                        }
                    }
                }
            }
        }

        echo "<script>alert('Import selesai! Soal ditambahkan: $soal_ditambahkan, duplikat dilewati: $soal_duplikat'); window.location.href = 'create_exam.php';</script>";
    } else {
        echo "<script>alert('Upload file gagal. Pastikan Anda memilih file DOCX.');</script>";
    }
}
?>
