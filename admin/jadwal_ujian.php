<?php
session_start();
include '../config/db.php';
require '../vendor/autoload.php';

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: ../index.php");
    exit();
}

if (!isset($_SESSION["role"]) || $_SESSION["role"] != "admin") {
    header("Location: ../index.php");
    exit();
}
// Ambil data ujian untuk tampilan
$result_exams = mysqli_query($conn, "SELECT * FROM ujian");

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <style>
        body {
            padding-top: 4rem;
        }
        .sidebar {
            position: fixed;
            top: 0; left: 0; bottom: 0;
            width: 200px;
            background-color: #eee;
            padding: 1rem;
        }
        main {
            margin-left: 210px;
            padding: 1rem;
        }
        .hidden {
            display: none;
        }
    </style>
</head>
<body>
<div class="sidebar">
        <h5>Menu Admin</h5>
        <ul class="list-unstyled">
            <li><a href="manage_users.php">Manage Users</a></li>
            <li><a href="jadwal_ujian.php">jadwal_ujian</a></li>
            <li><a href="?logout=1" onclick="return confirm('Yakin ingin logout?')">Logout</a></li>
        </ul>
    </div>
    <main>
        <h2>Manajemen Pengguna</h2>
     <!-- Form Tambah Ujian -->
     <h4>Tambah Ujian</h4>
        <form method="POST" class="mb-4">
            <div class="mb-3">
                <label>ID Ujian</label>
                <input type="number" name="id_ujian" class="form-control" required />
            </div>
            <div class="mb-3">
                <label>ID Guru</label>
                <select name="id_guru" class="form-select" required>
                    <option value="">-- Pilih Guru --</option>
                    <?php 
                    // Ambil data guru untuk dropdown
                    $result_guru = $conn->query("SELECT id, nama_guru FROM guru");
                    while ($guru = $result_guru->fetch_assoc()): ?>
                        <option value="<?= $guru['id'] ?>"><?= htmlspecialchars($guru['nama_guru']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="mb-3">
                <label>ID Jurusan</label>
                <select name="id_jurusan" class="form-select" required>
                    <option value="">-- Pilih Jurusan --</option>
                    <?php foreach ($jurusanList as $jurusan): ?>
                        <option value="<?= $jurusan['id_jurusan'] ?>"><?= htmlspecialchars($jurusan['nama_jurusan']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label>ID Kelas</label>
                <select name="id_kelas" class="form-select" required>
                    <option value="">-- Pilih Kelas --</option>
                    <?php foreach ($kelasList as $kelas): ?>
                        <option value="<?= $kelas['id'] ?>"><?= htmlspecialchars($kelas['nama_kelas']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label>ID Pelajaran</label>
                <select name="id_pelajaran" class="form-select" required>
                    <option value="">-- Pilih Pelajaran --</option>
                    <?php foreach ($pelajaranList as $mata_pelajaran): ?>
                        <option value="<?= $mata_pelajaran['id'] ?>"><?= htmlspecialchars($mata_pelajaran['nama_pelajaran']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label>Nama Ujian</label>
                <input type="text" name="nama_ujian" class="form-control" required />
            </div>
            <div class="mb-3">
                <label>Waktu Mulai</label>
                <input type="datetime-local" name="waktu_mulai" class="form-control" required />
            </div>
            <div class="mb-3">
                <label>Waktu Selesai</label>
                <input type="datetime-local" name="waktu_selesai" class="form-control" required />
            </div>
            <button type="submit" class="btn btn-success" name="add_exam">Tambah Ujian</button>
        </form>

        <hr />

        <!-- Tabel Ujian -->
        <h4>Daftar Ujian</h4>
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>ID Ujian</th><th>ID Guru</th><th>ID Jurusan</th><th>ID Kelas</th><th>ID Pelajaran</th><th>Nama Ujian</th><th>Waktu Mulai</th><th>Waktu Selesai</th>
                </tr>
            </thead>
            <tbody>
                <?php while($exam = mysqli_fetch_assoc($result_exams)): ?>
                <tr>
                    <td><?= $exam['id_ujian'] ?></td>
                    <td><?= htmlspecialchars($exam['id_guru']) ?></td>
                    <td><?= htmlspecialchars($exam['id_jurusan']) ?></td>
                    <td><?= htmlspecialchars($exam['id_kelas']) ?></td>
                    <td><?= htmlspecialchars($exam['id_pelajaran']) ?></td>
                    <td><?= htmlspecialchars($exam['nama_ujian']) ?></td>
                    <td><?= htmlspecialchars($exam['waktu_mulai']) ?></td>
                    <td><?= htmlspecialchars($exam['waktu_selesai']) ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </main>

</body>
</html>