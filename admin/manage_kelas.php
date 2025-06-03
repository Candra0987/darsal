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

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_user'])) {
    $username = $_POST["username"];
    $password = $_POST["password"];
    $role = $_POST["role"];

    try {
        $conn->begin_transaction();

        $stmtUser = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
        $stmtUser->bind_param("sss", $username, $password, $role);
        $stmtUser->execute();
        $user_id = $stmtUser->insert_id;

        if ($role === "guru" || $role === "semua guru") {
            $namaGuru = $_POST["namaGuru"];
            $kelas = $_POST["id_kelas"];
            $pelajaran = $_POST["id_pelajaran"];
            $stmtGuru = $conn->prepare("INSERT INTO guru (user_id, nama_guru, id_kelas, id_pelajaran) VALUES (?, ?, ?, ?)");
            $stmtGuru->bind_param("isii", $user_id, $namaGuru, $kelas, $pelajaran);
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

        $conn->commit();
        echo "<script>alert('User berhasil ditambahkan!');</script>";
    } catch (Exception $e) {
        $conn->rollback();
        echo "<script>alert('Gagal menyimpan data: " . $e->getMessage() . "');</script>";
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_kelas'])) {
    $nama_kelas = $_POST["nama_kelas"];
    $query = "INSERT INTO kelas (nama_kelas) VALUES ('$nama_kelas')";
    mysqli_query($conn, $query);
    echo "<script>alert('Kelas berhasil ditambahkan!');</script>";
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_pelajaran'])) {
    $nama_pelajaran = $_POST["nama_pelajaran"];
    $query = "INSERT INTO mata_pelajaran (nama_pelajaran) VALUES ('$nama_pelajaran')";
    mysqli_query($conn, $query);
    echo "<script>alert('Mata pelajaran berhasil ditambahkan!');</script>";
}

if (isset($_GET["delete_user"])) {
    $id = $_GET["delete_user"];
    $query = "DELETE FROM users WHERE id='$id'";
    mysqli_query($conn, $query);
}
if (isset($_GET["delete_kelas"])) {
    $id = $_GET["delete_kelas"];
    $query = "DELETE FROM kelas WHERE id='$id'";
    mysqli_query($conn, $query);
}
if (isset($_GET["delete_pelajaran"])) {
    $id = $_GET["delete_pelajaran"];
    $query = "DELETE FROM mata_pelajaran WHERE id='$id'";
    mysqli_query($conn, $query);
}

$result_users = mysqli_query($conn, "
    SELECT 
        users.id, 
        users.username, 
        users.role, 
        COALESCE(kelas.nama_kelas, '-') AS nama_kelas
    FROM users
    LEFT JOIN siswa ON users.id = siswa.user_id
    LEFT JOIN guru ON users.id = guru.user_id
    LEFT JOIN kelas ON kelas.id = COALESCE(siswa.id_kelas, guru.id_kelas)
");

$result_kelas = mysqli_query($conn, "SELECT * FROM kelas");
$result_pelajaran = mysqli_query($conn, "SELECT * FROM mata_pelajaran");
$result_jurusan = mysqli_query($conn, "SELECT * FROM jurusan");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manajemen Kelas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @media (max-width: 768px) {
            #sidebar {
                display: none;
            }
        }
        #sidebar {
            width: 250px;
            min-height: 100vh;
        }
        .navbar {
            position: sticky;
            top: 0;
            z-index: 1030;
        }
    </style>
</head>
<body class="bg-light">

<!-- Navbar -->
<nav class="navbar navbar-dark bg-dark">
    <div class="container-fluid">
        <button class="btn btn-outline-light me-2" id="toggleSidebarBtn">☰</button>
        <span class="navbar-brand mb-0 h1">Manajemen kelas</span>
        <a href="?logout=true" class="btn btn-danger">Logout</a>
    </div>
</nav>

<div class="d-flex">
    <!-- Sidebar -->
    <div id="sidebar" class="bg-dark text-white p-3">
        <h4>Menu</h4>
        <ul class="nav flex-column">
            <li class="nav-item"><a href="dashboard.php" class="nav-link text-white">Dashboard</a></li>
            <li class="nav-item"><a href="manage_users.php" class="nav-link text-white">Manajemen Pengguna</a></li>
            <li class="nav-item"><a href="manage_kelas.php" class="nav-link text-white">Manajemen Kelas</a></li>
            <li class="nav-item"><a href="manage_pelajaran.php" class="nav-link text-white">Manajemen Pelajaran</a></li>
            <li class="nav-item"><a href="jadwal_ujian.php" class="nav-link text-white">Jadwal Ujian</a></li>

            <li class="nav-item"><a href="?logout=true" class="nav-link text-white">Logout</a></li>
        </ul>
    </div>

    <!-- Konten Utama -->
    <div class="flex-grow-1 p-4">
        
        <hr>
        <h2>Manajemen Kelas</h2>
        <form method="POST" class="mb-4">
            <div class="mb-3">
                <input type="text" name="nama_kelas" class="form-control" placeholder="Nama Kelas" required>
            </div>
            <button type="submit" name="add_kelas" class="btn btn-success">Tambah Kelas</button>
        </form>

        <h3>Daftar Kelas</h3>
        <table class="table table-bordered">
            <thead class="table-light">
            <tr><th>ID</th><th>Nama</th><th>Aksi</th></tr>
            </thead>
            <tbody>
            <?php mysqli_data_seek($result_kelas, 0); while ($row = mysqli_fetch_assoc($result_kelas)) { ?>
            <tr>
                <td><?= $row["id"] ?></td>
                <td><?= $row["nama_kelas"] ?></td>
                <td><a href="manage_users.php?delete_kelas=<?= $row["id"] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Hapus kelas ini?')">Hapus</a></td>
            </tr>
            <?php } ?>
            </tbody>
        </table>

      

<script>
function tampilkanFormBerdasarkanRole() {
    const role = document.getElementById("roleSelect").value;
    document.getElementById("formGuru").style.display = (role === "guru" || role === "semua guru") ? "block" : "none";
    document.getElementById("formSiswa").style.display = (role === "siswa") ? "block" : "none";
}

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
