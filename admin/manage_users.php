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


use PhpOffice\PhpSpreadsheet\IOFactory;


// Function to delete user
function deleteUser ($conn, $delete_id) {
    try {
        $conn->begin_transaction();
        $conn->query("DELETE FROM siswa WHERE user_id = $delete_id");
        $conn->query("DELETE FROM guru WHERE user_id = $delete_id");
        $conn->query("DELETE FROM users WHERE id = $delete_id");
        $conn->commit();
        echo "<script>alert('Pengguna berhasil dihapus!'); window.location='manage_users.php';</script>";
    } catch (Exception $e) {
        $conn->rollback();
        echo "<script>alert('Gagal menghapus data: " . $e->getMessage() . "');</script>";
    }
}


// Handle delete requests
if (isset($_GET['delete'])) {
    deleteUser ($conn, intval($_GET['delete']));
}


if (isset($_GET["delete_kelas"])) {
    $id = $_GET["delete_kelas"];
    $conn->query("DELETE FROM kelas WHERE id='$id'");
    echo "<script>alert('Kelas Berhasil dihapus!'); window.location='manage_kelas.php';</script>";
    exit();
}


if (isset($_GET["delete_pelajaran"])) {
    $id = $_GET["delete_pelajaran"];
    $conn->query("DELETE FROM mata_pelajaran WHERE id='$id'");
    echo "<script>alert('Pelajaran Berhasil dihapus!'); window.location='manage_pelajaran.php';</script>";
    exit();
}


// Import Excel
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['import_excel'])) {
    if (isset($_FILES['excel_file']) && $_FILES['excel_file']['error'] == 0) {
        $fileTmpName = $_FILES['excel_file']['tmp_name'];


        try {
            $spreadsheet = IOFactory::load($fileTmpName);
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray();


            foreach ($rows as $index => $row) {
                if ($index == 0) continue; // Skip header row


                $user_id = $row[0];
                $username = $row[1];
                $password = $row[2];
                $role = $row[3];
                $nama = $row[4];
                $nama_siswa = $row[5];
                $kelas = $row[6];
                $jurusan = $row[7];


                $stmtCheck = $conn->prepare("SELECT id FROM users WHERE username = ?");
                $stmtCheck->bind_param("s", $username);
                $stmtCheck->execute();
                $stmtCheck->store_result();


                if ($stmtCheck->num_rows > 0) {
                    echo "<script>alert('Username $username sudah terdaftar!');</script>";
                    continue;
                }


                $stmtUser  = $conn->prepare("INSERT INTO users (id, username, password, role, nama) VALUES (?, ?, ?, ?, ?)");
                $stmtUser ->bind_param("issss", $user_id, $username, $password, $role, $nama);
                $stmtUser ->execute();


                $stmtSiswa = $conn->prepare("INSERT INTO siswa (user_id, nama_siswa, id_kelas, id_jurusan) VALUES (?, ?, ?, ?)");
                $stmtSiswa->bind_param("isii", $user_id, $nama_siswa, $kelas, $jurusan);
                $stmtSiswa->execute();
            }


            echo "<script>alert('Data siswa berhasil diimpor!');</script>";
        } catch (Exception $e) {
            echo "<script>alert('Terjadi kesalahan: " . $e->getMessage() . "');</script>";
        }
    } else {
        echo "<script>alert('Silakan pilih file Excel!');</script>";
    }
}


// Add new user
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_user'])) {
    $user_id = intval($_POST["user_id"]);
    $username = $_POST["username"];
    $password = $_POST["password"];
    $role = $_POST["role"];
    $nama = $_POST["nama"];


    try {
        $conn->begin_transaction();


        // Check if ID already exists
        $stmtCheckId = $conn->prepare("SELECT id FROM users WHERE id = ?");
        $stmtCheckId->bind_param("i", $user_id);
        $stmtCheckId->execute();
        $stmtCheckId->store_result();


        if ($stmtCheckId->num_rows > 0) {
            echo "<script>alert('ID $user_id sudah terdaftar!');</script>";
            exit();
        }


        $stmtUser  = $conn->prepare("INSERT INTO users (id, username, password, role, nama) VALUES (?, ?, ?, ?, ?)");
        $stmtUser ->bind_param("issss", $user_id, $username, $password, $role, $nama);
        $stmtUser ->execute();


        if ($role === "guru") {
            $namaGuru = $_POST["namaGuru"];
            $kelas = $_POST["id_kelas"];
            $mata_pelajaran = $_POST["id_pelajaran"];
            $stmtGuru = $conn->prepare("INSERT INTO guru (user_id, nama_guru, id_kelas, id_pelajaran) VALUES (?, ?, ?, ?)");
            $stmtGuru->bind_param("isii", $user_id, $namaGuru, $kelas, $mata_pelajaran);
            $stmtGuru->execute();
        }


        if ($role === "siswa") {
            $namaSiswa = $_POST["namaSiswa"];
            $kelas = $_POST["id_kelas_siswa"];
            $jurusan = $_POST["id_jurusan"];
            $stmtSiswa = $conn->prepare("INSERT INTO siswa (user_id, nama_siswa, id_kelas, id_jurusan) VALUES (?, ?, ?, ?)");
            $stmtSiswa->bind_param("isii", $user_id, $namaSiswa, $kelas, $jurusan);
            $stmtSiswa->execute();
        }


        if ($role === "kepala sekolah") {
            $jabatan = $_POST["jabatan"];
            $stmtKepsek = $conn->prepare("INSERT INTO kepala_sekolah (user_id, nama_kepala_sekolah, jabatan) VALUES (?, ?, ?)");
            $stmtKepsek->bind_param("iss", $user_id, $nama, $jabatan);
            $stmtKepsek->execute();
        }


        if ($role === "staff") {
            $bagian = $_POST["bagian"];
            $stmtStaff = $conn->prepare("INSERT INTO staff (user_id, bagian) VALUES (?, ?)");
            $stmtStaff->bind_param("is", $user_id, $bagian);
            $stmtStaff->execute();
        }


        $conn->commit();
        echo "<script>alert('User  berhasil ditambahkan!'); window.location='manage_users.php';</script>";
    } catch (Exception $e) {
        $conn->rollback();
        echo "<script>alert('Gagal menyimpan data: " . $e->getMessage() . "');</script>";
    }
}


// Edit user
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit_user'])) {
    $id = intval($_POST['id']);
    $username = $_POST['username'];
    $nama = $_POST['nama'];
    $role = $_POST['role'];


    try {
        $conn->begin_transaction();


        $stmt = $conn->prepare("UPDATE users SET username = ?, nama = ?, role = ? WHERE id = ?");
        $stmt->bind_param("sssi", $username, $nama, $role, $id);
        $stmt->execute();


        // Delete old data in guru and siswa for update
        $conn->query("DELETE FROM guru WHERE user_id = $id");
        $conn->query("DELETE FROM siswa WHERE user_id = $id");


        // Save new data based on role
        if ($role === "guru") {
            $namaGuru = $_POST["namaGuru"];
            $kelas = $_POST["id_kelas"];
            $mata_pelajaran = $_POST["id_pelajaran"];
            $stmtGuru = $conn->prepare("INSERT INTO guru (user_id, nama_guru, id_kelas, id_pelajaran) VALUES (?, ?, ?, ?)");
            $stmtGuru->bind_param("isii", $id, $namaGuru, $kelas, $mata_pelajaran);
            $stmtGuru->execute();
        }


        if ($role === "siswa") {
            $namaSiswa = $_POST["namaSiswa"];
            $kelas = $_POST["id_kelas_siswa"];
            $jurusan = $_POST["id_jurusan"];
            $stmtSiswa = $conn->prepare("INSERT INTO siswa (user_id, nama_siswa, id_kelas, id_jurusan) VALUES (?, ?, ?, ?)");
            $stmtSiswa->bind_param("isii", $id, $namaSiswa, $kelas, $jurusan);
            $stmtSiswa->execute();
        }


        $conn->commit();
        echo "<script>alert('User  berhasil diupdate!'); window.location='manage_users.php';</script>";
    } catch (Exception $e) {
        $conn->rollback();
        echo "<script>alert('Gagal update data: " . $e->getMessage() . "');</script>";
    }
}


// Fetch user data for editing
$editUser  = null;
if (isset($_GET['edit'])) {
    $editId = intval($_GET['edit']);
    $resultEdit = $conn->query("SELECT * FROM users WHERE id = $editId");
    $editUser  = $resultEdit->fetch_assoc();


    if ($editUser ) {
        if ($editUser ['role'] === 'guru') {
            $guru = $conn->query("SELECT * FROM guru WHERE user_id = $editId")->fetch_assoc();
        }
        if ($editUser ['role'] === 'siswa') {
            $siswa = $conn->query("SELECT * FROM siswa WHERE user_id = $editId")->fetch_assoc();
        }
    }
}


// Pagination setup
$limit = 10; // Number of users per page
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $limit;


// Fetch user data with pagination and search
$search = isset($_GET['search']) ? $_GET['search'] : '';
$searchQuery = $search ? "WHERE users.username LIKE '%$search%' OR users.nama LIKE '%$search%'" : '';


$result_users = mysqli_query($conn, "
SELECT 
    users.id, 
    users.username, 
    users.password, 
    users.role, 
    users.nama,
    COALESCE(kls_siswa.nama_kelas, kls_guru.nama_kelas, '-') AS nama_kelas
FROM users
LEFT JOIN siswa ON users.id = siswa.user_id
LEFT JOIN kelas AS kls_siswa ON siswa.id_kelas = kls_siswa.id
LEFT JOIN guru ON users.id = guru.user_id
LEFT JOIN kelas AS kls_guru ON guru.id_kelas = kls_guru.id
$searchQuery
ORDER BY users.id DESC
LIMIT $limit OFFSET $offset
");


// Count total users per role
$total_users = [];
$roles = ['admin', 'guru', 'siswa', 'kepala sekolah', 'staff'];
foreach ($roles as $role) {
    $stmtCount = $conn->prepare("SELECT COUNT(*) FROM users WHERE role = ?");
    $stmtCount->bind_param("s", $role);
    $stmtCount->execute();
    $stmtCount->bind_result($count);
    $stmtCount->fetch();
    $total_users[$role] = $count;
    $stmtCount->close();
}


// Total users for pagination
$total_result = $conn->query("SELECT COUNT(*) as total FROM users $searchQuery");
$total_row = $total_result->fetch_assoc();
$total_users_count = $total_row['total'];
$total_result->free();
$total_pages = ceil($total_users_count / $limit);


// Fetch class, major, and subject data for forms
$kelasList = $conn->query("SELECT * FROM kelas");
$jurusanList = $conn->query("SELECT * FROM jurusan");
$pelajaranList = $conn->query("SELECT * FROM mata_pelajaran");


// Add Exam
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_exam'])) {
    $id_ujian = intval($_POST["id_ujian"]);
    $id_guru = intval($_POST["id_guru"]);
    $id_jurusan = intval($_POST["id_jurusan"]);
    $id_kelas = intval($_POST["id_kelas"]);
    $id_pelajaran = intval($_POST["id_pelajaran"]);
    $nama_ujian = $_POST["nama_ujian"];
    $waktu_mulai = $_POST["waktu_mulai"];
    $waktu_selesai = $_POST["waktu_selesai"];


    try {
        $conn->begin_transaction();


        $stmtExam = $conn->prepare("INSERT INTO ujian (id_ujian, id_guru, id_jurusan, id_kelas, id_pelajaran, nama_ujian, waktu_mulai, waktu_selesai) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmtExam->bind_param("iiisssss", $id_ujian, $id_guru, $id_jurusan, $id_kelas, $id_pelajaran, $nama_ujian, $waktu_mulai, $waktu_selesai);
        $stmtExam->execute();


        $conn->commit();
        echo "<script>alert('Ujian berhasil ditambahkan!'); window.location='manage_users.php';</script>";
    } catch (Exception $e) {
        $conn->rollback();
        echo "<script>alert('Gagal menambahkan ujian: " . $e->getMessage() . "');</script>";
    }
}


// Edit Exam
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit_exam'])) {
    $id_ujian = intval($_POST['id_ujian']);
    $id_guru = intval($_POST['id_guru']);
    $id_jurusan = intval($_POST['id_jurusan']);
    $id_kelas = intval($_POST['id_kelas']);
    $id_pelajaran = intval($_POST['id_pelajaran']);
    $nama_ujian = $_POST["nama_ujian"];
    $waktu_mulai = $_POST["waktu_mulai"];
    $waktu_selesai = $_POST["waktu_selesai"];


    try {
        $conn->begin_transaction();


        $stmtExam = $conn->prepare("UPDATE ujian SET id_guru = ?, id_jurusan = ?, id_kelas = ?, id_pelajaran = ?, nama_ujian = ?, waktu_mulai = ?, waktu_selesai = ? WHERE id_ujian = ?");
        $stmtExam->bind_param("iiisssss", $id_guru, $id_jurusan, $id_kelas, $id_pelajaran, $nama_ujian, $waktu_mulai, $waktu_selesai, $id_ujian);
        $stmtExam->execute();


        $conn->commit();
        echo "<script>alert('Ujian berhasil diupdate!'); window.location='manage_users.php';</script>";
    } catch (Exception $e) {
        $conn->rollback();
        echo "<script>alert('Gagal mengupdate ujian: " . $e->getMessage() . "');</script>";
    }
}


// Fetch exam data for editing
$editExam = null;
if (isset($_GET['edit_exam'])) {
    $editExamId = intval($_GET['edit_exam']);
    $resultEditExam = $conn->query("SELECT * FROM ujian WHERE id_ujian = $editExamId");
    $editExam = $resultEditExam->fetch_assoc();
}


// Fetch exam data for display
$result_exams = mysqli_query($conn, "SELECT * FROM ujian");


?>


<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <title>Manage Users</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        /* Sidebar awal hidden (width 0) */
        @media (max-width: 768px) {
            #sidebar {
                display: none;
            }
        }
        #sidebar {
            width: 250px;
            min-height: 100vh;
        }
        .table thead th {
    background-color: black;
    color: white;
    text-align: center;
}

    </style>
</head>
<body class="bg-light">


<!-- Navbar -->
<nav class="navbar navbar-dark bg-dark">
    <div class="container-fluid">
        <button class="btn btn-outline-light me-2" id="toggleSidebarBtn">☰</button>
        <span class="navbar-brand mb-0 h1">Manajemen User</span>
        <a href="?logout=true" class="btn btn-danger">Logout</a>
    </div>
</nav>


<div class="d-flex">
    <!-- Sidebar -->
    <div id="sidebar" class="bg-dark text-white p-3" style="width: 250px; flex-shrink: 0;">
        <h4>Admin</h4>
        <ul class="nav flex-column">
            <li class="nav-item"><a href="dashboard.php" class="nav-link text-white">Dashboard</a></li>
            <li class="nav-item"><a href="manage_users.php" class="nav-link text-white">Manajemen Pengguna</a></li>
            <li class="nav-item"><a href="manage_kelas.php" class="nav-link text-white">Manajemen Kelas</a></li>
            <li class="nav-item"><a href="manage_pelajaran.php" class="nav-link text-white">Manajemen Pelajaran</a></li>
            <li class="nav-item"><a href="jadwal_ujian.php" class="nav-link text-white">Jadwal Ujian</a></li>
            <li class="nav-item">
                    
                </li>
            
        </ul>
    </div>


    <main class="flex-grow-1 p-4">
        <h2>Manajemen Pengguna</h2>
        <hr />

<!-- Total User Count -->
<!-- Total User Count -->
<h4 class="mt-4">Total User Count</h4>
<div style="max-width: 700px; margin: auto;">
<canvas id="userChart" width="300" height="200"></canvas></div>


<script>
    const ctx = document.getElementById('userChart').getContext('2d');
    const userChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?= json_encode(array_map('ucfirst', array_keys($total_users))) ?>,
            datasets: [{
                label: 'Jumlah Pengguna',
                data: <?= json_encode(array_values($total_users)) ?>,
                backgroundColor: [
                    '#0d6efd', // blue
                    '#198754', // green
                    '#ffc107', // yellow
                    '#dc3545'  // red
                ],
                borderColor: '#00000033',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false },
                title: {
                    display: true,
                    text: 'Jumlah Pengguna per Role'
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            }
        }
    });
</script>


<hr />

        <!-- Search Form -->
        <form method="GET" class="mb-4">
            <div class="input-group">
                <input type="text" name="search" class="form-control" placeholder="Cari pengguna..." value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search'])                : '' ?>" />
                <button class="btn btn-primary" type="submit">Cari</button>
            </div>
        </form>

        <!-- User Table -->
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Nama</th>
                    <th>Role</th>
                    <th>Kelas</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (mysqli_num_rows($result_users) > 0): ?>
                    <?php while($row = mysqli_fetch_assoc($result_users)): ?>
                    <tr>
                        <td><?= $row['id'] ?></td>
                        <td><?= htmlspecialchars($row['username']) ?></td>
                        <td><?= htmlspecialchars($row['nama']) ?></td>
                        <td><?= htmlspecialchars($row['role']) ?></td>
                        <td><?= htmlspecialchars($row['nama_kelas']) ?></td>
                        <td>
                            <a href="?edit=<?= $row['id'] ?>" class="btn btn-sm btn-warning">Edit</a>
                            <a href="?delete=<?= $row['id'] ?>" onclick="return confirm('Yakin hapus user ini?')" class="btn btn-sm btn-danger">Hapus</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="text-center">Tidak ada pengguna ditemukan.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <hr />

        <!-- Pagination -->
        <nav aria-label="Page navigation">
            <ul class="pagination">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>

      

        <!-- Add/Edit User Form -->
        <h4><?= $editUser  ? "Edit User ID: " . $editUser ['id'] : "Tambah User Baru" ?></h4>
        <form method="POST" id="formUser ">
            <?php if ($editUser ): ?>
                <input type="hidden" name="id" value="<?= $editUser ['id'] ?>" />
            <?php else: ?>
                <div class="mb-3">
                    <label>ID User</label>
                    <input type="number" name="user_id" class="form-control" required />
                </div>
            <?php endif; ?>
            <div class="mb-3">
                <label>Username</label>
                <input type="text" name="username" class="form-control" required
                    value="<?= $editUser  ? htmlspecialchars($editUser ['username']) : '' ?>" />
            </div>

            <?php if ($editUser): ?>
    <div class="mb-3">
        <label>Password (kosongkan jika tidak ingin mengganti)</label>
        <input type="password" name="password" class="form-control" />
    </div>
<?php else: ?>
    <div class="mb-3">
        <label>Password</label>
        <input type="password" name="password" class="form-control" required />
    </div>
<?php endif; ?>


            <div class="mb-3">
                <label>Nama Lengkap</label>
                <input type="text" name="nama" class="form-control" required
                    value="<?= $editUser  ? htmlspecialchars($editUser ['nama']) : '' ?>" />
            </div>

            <div class="mb-3">
                <label>Role</label>
                <select name="role" id="roleSelect" class="form-select" required>
                    <option value="">-- Pilih Role --</option>
                    <?php 
                        $roles = ['admin', 'guru', 'siswa', 'kepala sekolah', 'staff'];
                        foreach ($roles as $roleOption) {
                            $selected = ($editUser  && $editUser ['role'] == $roleOption) ? "selected" : "";
                            echo "<option value='$roleOption' $selected>$roleOption</option>";
                        }
                    ?>
                </select>
            </div>

            <!-- Additional Forms for Roles -->
            <div id="formGuru" style="display: none;">
                <div class="mb-3">
                    <label>Nama Guru</label>
                    <input type="text" name="namaGuru" class="form-control" 
                        value="<?= ($editUser  && $editUser ['role'] == 'guru' && isset($guru)) ? htmlspecialchars($guru['nama_guru']) : '' ?>" />
                </div>
                <div class="mb-3">
                    <label>Kelas (Guru)</label>
                    <select name="id_kelas" class="form-select">
                        <option value="">-- Pilih Kelas --</option>
                        <?php foreach ($kelasList as $kelas): ?>
                        <option value="<?= $kelas['id'] ?>" 
                            <?= ($editUser  && isset($guru) && $guru['id_kelas'] == $kelas['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($kelas['nama_kelas']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label>Mata Pelajaran</label>
                    <select name="id_pelajaran" class="form-select">
                        <option value="">-- Pilih Pelajaran --</option>
                        <?php foreach ($pelajaranList as $mata_pelajaran): ?>
                        <option value="<?= $mata_pelajaran['id'] ?>" 
                            <?= ($editUser  && isset($guru) && $guru['id_pelajaran'] == $mata_pelajaran['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($mata_pelajaran['nama_pelajaran']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div id="formSiswa" style="display: none;">
                <div class="mb-3">
                    <label>Nama Siswa</label>
                    <input type="text" name="namaSiswa" class="form-control" 
                        value="<?= ($editUser  && $editUser ['role'] == 'siswa' && isset($siswa)) ? htmlspecialchars($siswa['nama_siswa']) : '' ?>" />
                </div>
                <div class="mb-3">
                    <label>Kelas (Siswa)</label>
                    <select name="id_kelas_siswa" class="form-select">
                        <option value="">-- Pilih Kelas --</option>
                        <?php foreach ($kelasList as $kelas): ?>
                        <option value="<?= $kelas['id'] ?>" 
                            <?= ($editUser  && isset($siswa) && $siswa['id_kelas'] == $kelas['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($kelas['nama_kelas']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label>Jurusan</label>
                    <select name="id_jurusan" class="form-select">
                        <option value="">-- Pilih Jurusan --</option>
                        <?php foreach ($jurusanList as $jurusan): ?>
                        <option value="<?= $jurusan['id_jurusan'] ?>" 
                            <?= ($editUser  && isset($siswa) && $siswa['id_jurusan'] == $jurusan['id_jurusan']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($jurusan['nama_jurusan']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div id="formKepalaSekolah" class="hidden">
                <div class="mb-3">
                    <label>Jabatan</label>
                    <input type="text" name="jabatan" class="form-control" />
                </div>
            </div>

            <div id="formStaff" class="hidden">
                <div class="mb-3">
                    <label>Bagian</label>
                    <input type="text" name="bagian" class="form-control" />
                </div>
            </div>

            <button type="submit" class="btn btn-primary" 
                name="<?= $editUser  ? 'edit_user' : 'add_user' ?>">
                <?= $editUser  ? 'Simpan Perubahan' : 'Tambah User' ?>
            </button>
            <?php if ($editUser ): ?>
                <a href="manage_users.php" class="btn btn-secondary">Batal</a>
            <?php endif; ?>
        </form>

        <hr />
        
        <!-- Import Excel -->
        <h4>Import Data Siswa dari Excel</h4>
        <form method="POST" enctype="multipart/form-data" class="mb-4">
            <input type="file" name="excel_file" accept=".xls,.xlsx" required />
            <button type="submit" name="import_excel" class="btn btn-success">Import</button>
        </form>

        <hr />

        <!-- Add/Edit Exam Form -->
        <h4><?= $editExam ? "Edit Ujian ID: " . $editExam['id_ujian'] : "Tambah Ujian" ?></h4>
        <form method="POST" class="mb-4">
            <div class="mb-3">
                <label>ID Ujian</label>
                <input type="number" name="id_ujian" class="form-control" required 
                    value="<?= $editExam ? htmlspecialchars($editExam['id_ujian']) : '' ?>" />
            </div>
            <div class="mb-3">
                <label>ID Guru</label>
                <select name="id_guru" class="form-select" required>
                    <option value="">-- Pilih Guru --</option>
                    <?php 
                    // Fetch teachers for dropdown
                    $result_guru = $conn->query("SELECT id, nama_guru FROM guru");
                    while ($guru = $result_guru->fetch_assoc()): ?>
                        <option value="<?= $guru['id'] ?>" 
                            <?= $editExam && $editExam['id_guru'] == $guru['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($guru['nama_guru']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="mb-3">
                <label>ID Jurusan</label>
                <select name="id_jurusan" class="form-select" required>
                    <option value="">-- Pilih Jurusan --</option>
                    <?php foreach ($jurusanList as $jurusan): ?>
                        <option value="<?= $jurusan['id_jurusan'] ?>" 
                            <?= $editExam && $editExam['id_jurusan'] == $jurusan['id_jurusan'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($jurusan['nama_jurusan']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label>ID Kelas</label>
                <select name="id_kelas" class="form-select" required>
                    <option value="">-- Pilih Kelas --</option>
                    <?php foreach ($kelasList as $kelas): ?>
                        <option value="<?= $kelas['id'] ?>" 
                            <?= $editExam && $editExam['id_kelas'] == $kelas['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($kelas['nama_kelas']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label>ID Pelajaran</label>
                <select name="id_pelajaran" class="form-select" required>
                    <option value="">-- Pilih Pelajaran --</option>
                    <?php foreach ($pelajaranList as $mata_pelajaran): ?>
                        <option value="<?= $mata_pelajaran['id'] ?>" 
                            <?= $editExam && $editExam['id_pelajaran'] == $mata_pelajaran['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($mata_pelajaran['nama_pelajaran']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label>Nama Ujian</label>
                <input type="text" name="nama_ujian" class="form-control" required 
                    value="<?= $editExam ? htmlspecialchars($editExam['nama_ujian']) : '' ?>" />
            </div>
            <div class="mb-3">
                <label>Waktu Mulai</label>
                <input type="datetime-local" name="waktu_mulai" class="form-control" required 
                    value="<?= $editExam ? htmlspecialchars($editExam['waktu_mulai']) : '' ?>" />
            </div>
            <div class="mb-3">
                <label>Waktu Selesai</label>
                <input type="datetime-local" name="waktu_selesai" class="form-control" required 
                    value="<?= $editExam ? htmlspecialchars($editExam['waktu_selesai']) : '' ?>" />
            </div>
            <button type="submit" class="btn btn-success" name="<?= $editExam ? 'edit_exam' : 'add_exam' ?>">
                <?= $editExam ? 'Simpan Perubahan' : 'Tambah Ujian' ?>
            </button>
        </form>

        <hr />

        <!-- Exam Table -->
        <h4>Daftar Ujian</h4>
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>ID Ujian</th>
                    <th>ID Guru</th>
                    <th>ID Jurusan</th>
                    <th>ID Kelas</th>
                    <th>ID Pelajaran</th>
                    <th>Nama Ujian</th>
                    <th>Waktu Mulai</th>
                    <th>Waktu Selesai</th>
                    <th>Aksi</th>
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
                    <td>
                        <a href="?edit_exam=<?= $exam['id_ujian'] ?>" class="btn btn-sm btn-warning">Edit</a>
                        <a href="?delete_exam=<?= $exam['id_ujian'] ?>" onclick="return confirm('Yakin hapus ujian ini?')" class="btn btn-sm btn-danger">Hapus</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </main>
</div>

<script>
    function toggleForms() {
        const role = document.getElementById('roleSelect').value;
        document.getElementById('formGuru').style.display = role === 'guru' ? 'block' : 'none';
        document.getElementById('formSiswa').style.display = role === 'siswa' ? 'block' : 'none';
        document.getElementById('formKepalaSekolah').style.display = role === 'kepala sekolah' ? 'block' : 'none';
        document.getElementById('formStaff').style.display = role === 'staff' ? 'block' : 'none';
    }

    document.getElementById('roleSelect').addEventListener('change', toggleForms);
    window.onload = toggleForms;

    document.getElementById("toggleSidebarBtn").addEventListener("click", function () {
        const sidebar = document.getElementById("sidebar");
        if (sidebar.style.display === "none" || sidebar.classList.contains("d-none")) {
            sidebar.style.display = "block";
            sidebar.classList.remove("d-none");
        } else {
            sidebar.style.display = "none";
            sidebar.classList.add("d-none");
        }
    });
</script>
</body>
</html>
