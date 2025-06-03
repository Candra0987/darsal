<?php
session_start();
include '../config/db.php';

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: ../index.php");
    exit();
}

if (!isset($_SESSION["role"]) || $_SESSION["role"] != "staff") {
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
        echo "<script>alert('Ujian berhasil ditambahkan!'); window.location='dashboard.php';</script>";
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
        echo "<script>alert('Ujian berhasil diupdate!'); window.location='dashboard.php';</script>";
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
    <title>Atur Ujian - Staf</title>
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
            background-color: #343a40;
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
            display: flex;
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
            background-color: peru;
            
        }
    </style>
</head>
<body>

<header class="header-navbar">
    <h4>Panel Staf - Atur Ujian</h4>
</header>

<div class="sidebar">
    <h5 class="mb-4">Menu Staf</h5>
    <a href="dashboard.php" class="active"><i class="fa fa-list"></i> Manage Ujian</a>
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
                <label for="id_jurusan" class="form-label">Jurusan</label>
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
                    <option value="" disabled <?= !$editExam ? 'selected' : '' ?>>-- Pilih Mata Pelajaran --</option>
                    <?php 
                    $result_mapel = $conn->query("SELECT * FROM mata_pelajaran");
                    while ($mapel = $result_mapel->fetch_assoc()): ?>
                        <option value="<?= $mapel['id'] ?>" 
                            <?= $editExam && $editExam['id_pelajaran'] == $mapel['id'] ? 'selected' : '' ?> >
                            <?= htmlspecialchars($mapel['nama_pelajaran']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-md-12">
                <label for="nama_ujian" class="form-label">Nama Ujian</label>
                <input type="text" id="nama_ujian" name="nama_ujian" class="form-control" required
                    value="<?= $editExam ? htmlspecialchars($editExam['nama_ujian']) : '' ?>" />
            </div>
            <div class="col-md-6">
                <label for="waktu_mulai" class="form-label">Waktu Mulai</label>
                <input type="datetime-local" id="waktu_mulai" name="waktu_mulai" class="form-control" required
                    value="<?= $editExam ? date('Y-m-d\TH:i', strtotime($editExam['waktu_mulai'])) : '' ?>" />
            </div>
            <div class="col-md-6">
                <label for="waktu_selesai" class="form-label">Waktu Selesai</label>
                <input type="datetime-local" id="waktu_selesai" name="waktu_selesai" class="form-control" required
                    value="<?= $editExam ? date('Y-m-d\TH:i', strtotime($editExam['waktu_selesai'])) : '' ?>" />
            </div>
            <div class="col-12 mt-3">
                <?php if ($editExam): ?>
                    <button type="submit" name="edit_exam" class="btn btn-warning">Update Ujian</button>
                    <a href="dashboard.php" class="btn btn-secondary">Batal</a>
                <?php else: ?>
                    <button type="submit" name="add_exam" class="btn btn-primary">Tambah Ujian</button>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <div class="table-responsive">
        <h3>Daftar Ujian</h3>
        <table class="table table-bordered table-striped align-middle">
            <thead class="table-dark">
                <tr>
                    <th>ID Ujian</th>
                    <th>Nama Ujian</th>
                    <th>Guru</th>
                    <th>Jurusan</th>
                    <th>Kelas</th>
                    <th>Mata Pelajaran</th>
                    <th>Waktu Mulai</th>
                    <th>Waktu Selesai</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result_exams->num_rows > 0): ?>
                    <?php while ($row = $result_exams->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['id_ujian']) ?></td>
                            <td><?= htmlspecialchars($row['nama_ujian']) ?></td>
                            <td><?= htmlspecialchars($row['nama_guru']) ?></td>
                            <td><?= htmlspecialchars($row['nama_jurusan']) ?></td>
                            <td><?= htmlspecialchars($row['nama_kelas']) ?></td>
                            <td><?= htmlspecialchars($row['nama_pelajaran']) ?></td>
                            <td><?= date('d M Y H:i', strtotime($row['waktu_mulai'])) ?></td>
                            <td><?= date('d M Y H:i', strtotime($row['waktu_selesai'])) ?></td>
                            <td>
                                <a href="?edit_exam=<?= $row['id_ujian'] ?>" class="btn btn-sm btn-warning" title="Edit"><i class="fa fa-edit"></i></a>
                                <a href="?delete_exam=<?= $row['id_ujian'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Yakin ingin menghapus ujian ini?')" title="Hapus"><i class="fa fa-trash"></i></a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="9" class="text-center">Belum ada data ujian.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
