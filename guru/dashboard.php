<?php
session_start();
include '../config/db.php';
require '../vendor/autoload.php';

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: ../index.php");
    exit();
}

if (!isset($_SESSION["role"]) || $_SESSION["role"] != "guru") {
    header("Location: ../index.php");
    exit();
}

use PhpOffice\PhpSpreadsheet\IOFactory;

// Hapus user
if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    try {
        $conn->begin_transaction();

        $conn->query("DELETE FROM siswa WHERE user_id = $delete_id");
        $conn->query("DELETE FROM guru WHERE user_id = $delete_id");
        $conn->query("DELETE FROM users WHERE id = $delete_id");

        $conn->commit();
        echo "<script>alert('Pengguna berhasil dihapus!'); window.location='manage_users.php';</script>";
        exit();
    } catch (Exception $e) {
        $conn->rollback();
        echo "<script>alert('Gagal menghapus data: " . $e->getMessage() . "');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Manage Users</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
<style>
    * {
        font-family: 'Poppins', sans-serif;
    }
    html, body {
        height: 100%;
        margin: 0;
        background-color: #f8f9fa;
        overflow-x: hidden;
    }
    body {
        display: flex;
        min-height: 100vh;
        transition: margin-left 0.3s ease;
    }
    .sidebar {
        width: 250px;
        background-color: #343a40;
        color: white;
        padding: 1.5rem 1rem;
        position: fixed;
        top: 0;
        left: 0; /* Default show sidebar */
        bottom: 0;
        height: 100vh;
        overflow-y: auto;
        transition: left 0.3s ease;
        z-index: 1040;
        box-shadow: 2px 0 5px rgba(0,0,0,0.1);
    }
    .sidebar.hide {
        left: -250px; /* Hidden sidebar */
    }
    .sidebar h5 {
        margin-bottom: 1.5rem;
        font-weight: 600;
        font-size: 1.2rem;
    }
    .sidebar a {
        color: white;
        text-decoration: none;
        display: block;
        padding: 12px 15px;
        margin: 8px 0;
        border-radius: 8px;
        transition: background-color 0.2s ease;
        font-weight: 500;
    }
    .sidebar a:hover, .sidebar a:focus {
        background-color: #495057;
    }
    .content {
        flex: 1;
        margin-left: 250px; /* Default margin */
        padding: 1.5rem;
        width: 100%;
        transition: margin-left 0.3s ease;
    }
    .content.full-width {
        margin-left: 0;
    }
    .navbar {
        z-index: 1050;
        background-color: #ffffff !important;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
    .navbar-brand {
        font-weight: 600;
    }
    .btn-outline-primary {
        border-radius: 8px;
    }
    .card {
        border: none;
        border-radius: 16px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        background-color: #ffffff;
    }
    .card-title {
        font-weight: 600;
        font-size: 1.5rem;
    }
    .btn {
        border-radius: 8px;
    }
    /* Toggle button styling */
    .btn-toggle-sidebar {
        cursor: pointer;
    }
</style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar" id="sidebar">
    <h5>Panel Guru</h5>
    <a href="create_exam.php">📝 Buat Ujian</a>
    <a href="daftar_ujian_essay.php">📑 Penilaian Soal</a>
</div>

<!-- Konten utama -->
<div class="content" id="mainContent">
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-light shadow-sm">
        <div class="container-fluid">
            <button class="btn btn-outline-primary btn-toggle-sidebar me-2" type="button" onclick="toggleSidebar()">☰</button>
            <a class="navbar-brand" href="#">Sistem Ujian Online</a>
            <div class="d-flex ms-auto">
                <a href="?logout=true" class="btn btn-danger">Logout</a>
            </div>
        </div>
    </nav>

    <!-- Dashboard Content -->
    <div class="card mt-4">
        <div class="card-body">
            <h2 class="card-title">Dashboard Guru</h2>
            <p class="mb-4">Selamat datang, Guru!</p>

            <!-- Tombol download template -->
            <div class="d-grid gap-3 d-sm-block">
                <a href="download_template.php?type=pg" class="btn btn-outline-success mb-2 me-2">
                    📄 Download Template Pilihan Ganda (DOCX)
                </a>
                <a href="download_template.php?type=essay" class="btn btn-outline-info mb-2">
                    📄 Download Template Essay (DOCX)
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.getElementById('mainContent');

    function toggleSidebar() {
        sidebar.classList.toggle('hide');
        if (sidebar.classList.contains('hide')) {
            mainContent.classList.add('full-width');
        } else {
            mainContent.classList.remove('full-width');
        }
    }

    // Init on load, sidebar visible, content margin left 250px
    window.addEventListener('load', () => {
        sidebar.classList.remove('hide');
        mainContent.classList.remove('full-width');
    });
</script>
</body>
</html>
