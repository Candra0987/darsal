<?php
session_start();
if (!isset($_SESSION["role"]) || $_SESSION["role"] != "siswa") {
    header("Location: ../index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Siswa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f7fc;
        }

        .container {
            margin-top: 50px;
        }

        h2 {
            text-align: center;
            color: #3a73a3;
            font-size: 2rem;
        }

        .rules-card {
            background-color: #ffffff;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-top: 20px;
        }

        .rules-card h4 {
            font-size: 1.5rem;
            color: #007bff;
        }

        .rules-card ul {
            list-style-type: none;
            padding-left: 0;
        }

        .rules-card ul li {
            margin: 10px 0;
            font-size: 1.1rem;
            color: #555;
        }

        .btn-start {
            background-color: #4caf50;
            color: white;
            font-weight: bold;
            padding: 10px 20px;
            border-radius: 5px;
            width: 100%;
            margin-top: 20px;
            transition: background-color 0.3s ease, transform 0.2s ease;
        }

        .btn-start:hover {
            background-color: #388e3c;
            transform: scale(1.05);
        }

        .alert {
            font-size: 1rem;
            margin-top: 20px;
        }
    </style>
</head>
<body>

<div class="container">
    <!-- Jika ada pesan sukses atau error -->
    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-<?= $_SESSION['message_type'] ?> alert-dismissible fade show" role="alert">
            <?= $_SESSION['message'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['message']); unset($_SESSION['message_type']); ?>
    <?php endif; ?>

    <h2>Dashboard Siswa</h2>

    <!-- Aturan Ujian -->
    <div class="rules-card">
        <h4>Aturan Ujian</h4>
        <ul>
            <li>1. Waktu ujian adalah 60 menit untuk menyelesaikan semua soal.</li>
            <li>2. Dilarang membuka situs lain atau aplikasi lain selama ujian berlangsung (Jika dilakukan akan dianggap selesai!!!)</li>
            <li>3. Anda tidak dapat kembali ke soal sebelumnya setelah menjawab soal.</li>
            <li>4. Dilarang menggunakan alat bantu selain kalkulator untuk menjawab soal.</li>
            <li>5. Dilarang membuka situs lain atau aplikasi lain selama ujian berlangsung.</li>
            <li>6. Pastikan jaringan internet Anda stabil sebelum memulai ujian.</li>
            <li>7. Ujian akan berakhir secara otomatis setelah waktu habis.</li>
        </ul>

        <a href="pilih_ujian.php" class="btn btn-start">Mulai Ujian</a>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
