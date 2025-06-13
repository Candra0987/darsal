<?php
session_start();
include '../config/db.php';

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: ../index.php");
    exit();
}

if (!isset($_SESSION["role"]) || $_SESSION["role"] != "admin") {
    header("Location: ../index.php");
    exit();
}

// Tambah Ujian
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_exam'])) {
    $id_ujian = intval($_POST["id_ujian"]);
    $id_guru = intval($_POST["id_guru"]);
    $id_jurusan = intval($_POST["id_jurusan"]);
    $id_kelas = intval($_POST["id_kelas"]);
    $id_pelajaran = intval($_POST["id_pelajaran"]);
    $nama_ujian = $_POST["nama_ujian"];
    $waktu_mulai = $_POST["waktu_mulai"];
    $waktu_selesai = $_POST["waktu_selesai"];

    // Validasi waktu selesai harus lebih besar dari mulai
    if (strtotime($waktu_selesai) <= strtotime($waktu_mulai)) {
        echo "<script>alert('Waktu selesai harus lebih besar dari waktu mulai.');</script>";
        exit();
    }

    // Cek apakah id_ujian sudah ada
    $stmtCheck = $conn->prepare("SELECT COUNT(*) FROM ujian WHERE id_ujian = ?");
    $stmtCheck->bind_param("i", $id_ujian);
    $stmtCheck->execute();
    $stmtCheck->bind_result($count);
    $stmtCheck->fetch();
    $stmtCheck->close();

    if ($count > 0) {
        echo "<script>alert('ID Ujian sudah digunakan, silakan gunakan ID lain.');</script>";
        exit();
    }

    try {
        $conn->begin_transaction();

        $stmtExam = $conn->prepare("INSERT INTO ujian (id_ujian, id_guru, id_jurusan, id_kelas, id_pelajaran, nama_ujian, waktu_mulai, waktu_selesai) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmtExam->bind_param("iiiissss", $id_ujian, $id_guru, $id_jurusan, $id_kelas, $id_pelajaran, $nama_ujian, $waktu_mulai, $waktu_selesai);
        $stmtExam->execute();

        $conn->commit();
        echo "<script>alert('Ujian berhasil ditambahkan!'); window.location='jadwal_ujian.php';</script>";
        exit();
    } catch (Exception $e) {
        $conn->rollback();
        echo "<script>alert('Gagal menambahkan ujian: " . $e->getMessage() . "');</script>";
    }
}

// Edit Ujian
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit_exam'])) {
    $id_ujian = intval($_POST['id_ujian']);
    $id_guru = intval($_POST['id_guru']);
    $id_jurusan = intval($_POST['id_jurusan']);
    $id_kelas = intval($_POST['id_kelas']);
    $id_pelajaran = intval($_POST['id_pelajaran']);
    $nama_ujian = $_POST["nama_ujian"];
    $waktu_mulai = $_POST["waktu_mulai"];
    $waktu_selesai = $_POST["waktu_selesai"];

    // Validasi waktu selesai harus lebih besar dari mulai
    if (strtotime($waktu_selesai) <= strtotime($waktu_mulai)) {
        echo "<script>alert('Waktu selesai harus lebih besar dari waktu mulai.');</script>";
        exit();
    }

    try {
        $conn->begin_transaction();

        $stmtExam = $conn->prepare("UPDATE ujian SET id_guru = ?, id_jurusan = ?, id_kelas = ?, id_pelajaran = ?, nama_ujian = ?, waktu_mulai = ?, waktu_selesai = ? WHERE id_ujian = ?");
        $stmtExam->bind_param("iiiisssi", $id_guru, $id_jurusan, $id_kelas, $id_pelajaran, $nama_ujian, $waktu_mulai, $waktu_selesai, $id_ujian);
        $stmtExam->execute();

        $conn->commit();
        echo "<script>alert('Ujian berhasil diupdate!'); window.location='jadwal_ujian.php';</script>";
        exit();
    } catch (Exception $e) {
        $conn->rollback();
        echo "<script>alert('Gagal mengupdate ujian: " . $e->getMessage() . "');</script>";
    }
}

// Delete Ujian
if (isset($_GET['delete_exam'])) {
    $deleteId = intval($_GET['delete_exam']);
    $stmtDel = $conn->prepare("DELETE FROM ujian WHERE id_ujian = ?");
    $stmtDel->bind_param("i", $deleteId);
    if ($stmtDel->execute()) {
        echo "<script>alert('Ujian berhasil dihapus!'); window.location='manage_exams.php';</script>";
        exit();
    } else {
        echo "<script>alert('Gagal menghapus ujian.');</script>";
    }
}

// Ambil data ujian untuk edit (jika ada ?edit_exam=)
$editExam = null;
if (isset($_GET['edit_exam'])) {
    $editExamId = intval($_GET['edit_exam']);
    $resultEditExam = $conn->query("SELECT * FROM ujian WHERE id_ujian = $editExamId");
    $editExam = $resultEditExam->fetch_assoc();
}

// Ambil data ujian untuk tampilan
$result_exams = $conn->query("SELECT ujian.*, guru.nama_guru, jurusan.nama_jurusan, kelas.nama_kelas, mata_pelajaran.nama_pelajaran 
                                    FROM ujian 
                                    JOIN guru ON ujian.id_guru = guru.id 
                                    JOIN jurusan ON ujian.id_jurusan = jurusan.id_jurusan 
                                    JOIN kelas ON ujian.id_kelas = kelas.id 
                                    JOIN mata_pelajaran ON ujian.id_pelajaran = mata_pelajaran.id
                                    ORDER BY ujian.id_ujian DESC");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Atur Ujian - Admin</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet" />
    <style>
        body {
            padding-top: 56px;
            background: #f8f9fa;
        }
        .sidebar {
            position: fixed;
            top: 56px;
            left: 0;
            bottom: 0;
            width: 220px;
            background-color: black;
            padding: 1.5rem 1rem;
            color: white;
            overflow-y: auto;
        }
        .sidebar a {
            color: #adb5bd;
            text-decoration: none;
            display: block;
            padding: 10px 15px;
            border-radius: 6px;
            margin-bottom: 5px;
            transition: background-color 0.3s ease;
        }
        .sidebar a:hover, .sidebar a.active {
            background-color: #495057;
            color: white;
        }
        main {
            margin-left: 220px;
            padding: 2rem 2rem 3rem;
        }
        .form-section {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 0 12px rgba(0,0,0,0.05);
            margin-bottom: 2rem;
        }
        table th, table td {
            vertical-align: middle !important;
        }
        .btn-sm {
            padding: 0.25rem 0.5rem;
        }
        .header-navbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 56px;
            background-color: #212529;
            color: white;
            display: ;
            align-items: center;
            padding: 0 20px;
            z-index: 1030;
        }
        .header-navbar h4 {
            margin: 0;
            font-weight: 600;
            font-size: 1.25rem;
        }
        .form-section{
            background-color: rgb(73, 170, 255);
            color: #f8f9fa;
            
            
        }
        .navbar{
            background-color: black;
            color: beige;
        }
        .nav .navbar{
            color: aliceblue;
        }
        .header-navbar{
            background-color: black;
            color: white;
        }
        
    </style>
</head>
<body>

<header class="header-navbar">
   <!-- Navbar -->
<nav class="navbar ">
    <div class="container-fluid">
        <button class="btn btn-outline-light me-2" id="toggleSidebarBtn">☰</button>
        <span class="navbar-brand mb-0 h1" style="color: white;" >Manajemen Ujian </span>
        <a href="?logout=true" class="btn btn-danger">Logout</a>
    </div>
</nav>
</header>

<div class="sidebar">
    <h5 class="mb-4">Menu</h5>
    <a href="dashboard.php" class="active"><i class="fa fa-list"></i> Dashboard</a>
    <a href="manage_users.php" ></i>Manajemen User</a>
    <a href="manage_kelas.php" ></i>Manajemen Kelas</a>
    <a href="manage_pelajaran.php" ></i>Manajemen pelajaran</a>
    <a href="jadwal_ujian.php" ></i>Manajemen Ujian</a>
    
    



    <a href="?logout=1" onclick="return confirm('Yakin ingin logout?')"><i class="fa fa-sign-out-alt"></i> Logout</a>
</div>

<main>
    <div class="form-section">
        <h3 class="mb-4"><?= $editExam ? "Edit Ujian ID: " . $editExam['id_ujian'] : "Tambah Ujian Baru" ?></h3>
        <form method="POST" class="row g-3">
            <div class="col-md-2">
                <label for="id_ujian" class="form-label">ID Ujian</label>
                <input type="number" id="id_ujian" name="id_ujian" class="form-control" required
                    value="<?= $editExam ? htmlspecialchars($editExam['id_ujian']) : '' ?>" <?= $editExam ? 'readonly' : '' ?> />
                <?php if($editExam): ?>
                    <small class="text-muted">ID ujian tidak bisa diubah saat edit.</small>
                <?php endif; ?>
            </div>
            <div class="col-md-5">
                <label for="id_guru" class="form-label">Guru</label>
                <select id="id_guru" name="id_guru" class="form-select" required>
                    <option value="" disabled <?= !$editExam ? 'selected' : '' ?>>-- Pilih Guru --</option>
                    <?php 
                    $result_guru = $conn->query("SELECT id, nama_guru FROM guru");
                    while ($guru = $result_guru->fetch_assoc()): ?>
                        <option value="<?= $guru['id'] ?>" 
                            <?= $editExam && $editExam['id_guru'] == $guru['id'] ? 'selected' : '' ?> >
                            <?= htmlspecialchars($guru['nama_guru']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-md-5">
                <label for="id_jurusan" class="form-label"><strong>Jurusan</strong></label>
                <select id="id_jurusan" name="id_jurusan" class="form-select" required>
                    <option value="" disabled <?= !$editExam ? 'selected' : '' ?>>-- Pilih Jurusan --</option>
                    <?php 
                    $result_jurusan = $conn->query("SELECT * FROM jurusan");
                    while ($jurusan = $result_jurusan->fetch_assoc()): ?>
                        <option value="<?= $jurusan['id_jurusan'] ?>" 
                            <?= $editExam && $editExam['id_jurusan'] == $jurusan['id_jurusan'] ? 'selected' : '' ?> >
                            <?= htmlspecialchars($jurusan['nama_jurusan']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-md-5">
                <label for="id_kelas" class="form-label">Kelas</label>
                <select id="id_kelas" name="id_kelas" class="form-select" required>
                    <option value="" disabled <?= !$editExam ? 'selected' : '' ?>>-- Pilih Kelas --</option>
                    <?php 
                    $result_kelas = $conn->query("SELECT * FROM kelas");
                    while ($kelas = $result_kelas->fetch_assoc()): ?>
                        <option value="<?= $kelas['id'] ?>" 
                            <?= $editExam && $editExam['id_kelas'] == $kelas['id'] ? 'selected' : '' ?> >
                            <?= htmlspecialchars($kelas['nama_kelas']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-md-5">
                <label for="id_pelajaran" class="form-label">Mata Pelajaran</label>
                <select id="id_pelajaran" name="id_pelajaran" class="form-select" required>
                    <option value="" disabled <?= !$editExam ? 'selected' : '' ?>>-- Pilih Mapel --</option>
                    <?php 
                    $result_pelajaran = $conn->query("SELECT * FROM mata_pelajaran");
                    while ($mapel = $result_pelajaran->fetch_assoc()): ?>
                        <option value="<?= $mapel['id'] ?>" 
                            <?= $editExam && $editExam['id_pelajaran'] == $mapel['id'] ? 'selected' : '' ?> >
                            <?= htmlspecialchars($mapel['nama_pelajaran']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label for="nama_ujian" class="form-label">Nama Ujian</label>
                <input type="text" id="nama_ujian" name="nama_ujian" class="form-control" required
                    value="<?= $editExam ? htmlspecialchars($editExam['nama_ujian']) : '' ?>" />
            </div>
            <div class="col-md-3">
                <label for="waktu_mulai" class="form-label">Waktu Mulai</label>
                <input type="datetime-local" id="waktu_mulai" name="waktu_mulai" class="form-control" required
                    value="<?= $editExam ? date('Y-m-d\TH:i', strtotime($editExam['waktu_mulai'])) : '' ?>" />
            </div>
            <div class="col-md-3">
                <label for="waktu_selesai" class="form-label">Waktu Selesai</label>
                <input type="datetime-local" id="waktu_selesai" name="waktu_selesai" class="form-control" required
                    value="<?= $editExam ? date('Y-m-d\TH:i', strtotime($editExam['waktu_selesai'])) : '' ?>" />
            </div>
            <div class="col-12">
                <button type="submit" name="<?= $editExam ? 'edit_exam' : 'add_exam' ?>" class="btn btn-success">
                    <?= $editExam ? 'Simpan Perubahan' : 'Tambah Ujian' ?>
                </button>
                <?php if ($editExam): ?>
                    <a href="manage_exams.php" class="btn btn-secondary ms-2">Batal</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <div class="table-responsive bg-white p-4 rounded shadow-sm">
        <h4 class="mb-4">Daftar Ujian</h4>
        <table class="table table-striped table-hover table-bordered">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Nama Ujian</th>
                    <th>Guru</th>
                    <th>Jurusan</th>
                    <th>Kelas</th>
                    <th>Mapel</th>
                    <th>Mulai</th>
                    <th>Selesai</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($exam = $result_exams->fetch_assoc()): ?>
                    <tr>
                        <td><?= $exam['id_ujian'] ?></td>
                        <td><?= htmlspecialchars($exam['nama_ujian']) ?></td>
                        <td><?= htmlspecialchars($exam['nama_guru']) ?></td>
                        <td><?= htmlspecialchars($exam['nama_jurusan']) ?></td>
                        <td><?= htmlspecialchars($exam['nama_kelas']) ?></td>
                        <td><?= htmlspecialchars($exam['nama_pelajaran']) ?></td>
                        <td><?= date('d-m-Y H:i', strtotime($exam['waktu_mulai'])) ?></td>
                        <td><?= date('d-m-Y H:i', strtotime($exam['waktu_selesai'])) ?></td>
                        <td>
                            <a href="?edit_exam=<?= $exam['id_ujian'] ?>" class="btn btn-sm btn-warning"><i class="fa fa-edit"></i></a>
                            <a href="?delete_exam=<?= $exam['id_ujian'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Yakin ingin menghapus ujian ini?')"><i class="fa fa-trash"></i></a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</main>

<script>
    // Toggle sidebar
    document.getElementById("toggleSidebarBtn").addEventListener("click", function () {
        const sidebar = document.querySelector(".sidebar");
        const main = document.querySelector("main");
        if (sidebar.style.display === "none") {
            sidebar.style.display = "block";
            main.style.marginLeft = "220px";
        } else {
            sidebar.style.display = "none";
            main.style.marginLeft = "0";
        }
    });
</script>

</body>
</html>

