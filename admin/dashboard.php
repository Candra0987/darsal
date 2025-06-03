<?php
session_start();
if (!isset($_SESSION["role"]) || $_SESSION["role"] != "admin") {
    header("Location: ../index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Admin</title>
    <!-- Link Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="#">Admin Panel</a>
        <div class="collapse navbar-collapse">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link" href="manage_users.php">Kelola Pengguna</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="../index.php">Logout</a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- Konten -->
<div class="container mt-5">
    <div class="card shadow">
        <div class="card-body">
            <h2 class="card-title">Dashboard Admin</h2>
            <p>Selamat datang, Admin!</p>
            <a href="manage_users.php" class="btn btn-primary">Kelola Pengguna</a>
            <a href="manage_kelas.php" class="btn btn-danger">Kelola Kelas</a>
            <a href="manage_pelajaran.php" class="btn btn-warning">Kelola Pelajaran</a>
            <a href="jadwal_ujian.php" class="btn btn-primary">Jadwal Ujian</a>
        </div>
    </div>
</div>

<!-- Link Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
