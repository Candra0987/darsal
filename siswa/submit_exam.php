// siswa/submit_exam.php - Proses Submit Ujian
<?php
session_start();
if (!isset($_SESSION["role"]) || $_SESSION["role"] != "siswa") {
    header("Location: ../index.php");
    exit();
}

$nilai = 0;
if (isset($_POST["soal1"]) && $_POST["soal1"] == "4") {
    $nilai += 100;
}

echo "<h2>Nilai Anda: $nilai</h2>";
session_destroy();
?>
