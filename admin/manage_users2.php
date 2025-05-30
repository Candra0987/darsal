<?php
session_start();
include '../config/db.php';

// Cek apakah user adalah admin
if (!isset($_SESSION["role"]) || $_SESSION["role"] != "admin") {
    header("Location: ../index.php");
    exit();
}

// Tambah User
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_user'])) {
    $username = $_POST["username"];
    $password = $_POST["password"];
    $role = $_POST["role"];

    try {
        $conn->begin_transaction();

        // Simpan ke tabel users
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

// Tambah Kelas
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_kelas'])) {
    $nama_kelas = $_POST["nama_kelas"];
    $query = "INSERT INTO kelas (nama_kelas) VALUES ('$nama_kelas')";
    mysqli_query($conn, $query);
    echo "<script>alert('Kelas berhasil ditambahkan!');</script>";
}

// Tambah Mata Pelajaran
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_pelajaran'])) {
    $nama_pelajaran = $_POST["nama_pelajaran"];
    $query = "INSERT INTO mata_pelajaran (nama_pelajaran) VALUES ('$nama_pelajaran')";
    mysqli_query($conn, $query);
    echo "<script>alert('Mata pelajaran berhasil ditambahkan!');</script>";
}

// Hapus
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

// Ambil data
$result_users = mysqli_query($conn, "SELECT * FROM users");
$result_kelas = mysqli_query($conn, "SELECT * FROM kelas");
$result_pelajaran = mysqli_query($conn, "SELECT * FROM mata_pelajaran");
$result_jurusan = mysqli_query($conn, "SELECT * FROM jurusan");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manajemen Pengguna</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light p-4">
<div class="container">
    <h2 class="mb-4">Form Tambah User</h2>
    <form method="POST" class="mb-5">
        <div class="mb-3">
            <input type="text" name="username" class="form-control" placeholder="Username" required>
        </div>
        <div class="mb-3">
            <input type="password" name="password" class="form-control" placeholder="Password" required>
        </div>
        <div class="mb-3">
            <select name="role" id="roleSelect" class="form-select" required onchange="tampilkanFormBerdasarkanRole()">
                <option value="">-- Pilih Role --</option>
                <option value="admin">Admin</option>
                <option value="guru">Guru</option>
                <option value="siswa">Siswa</option>
                <option value="semua guru">Semua Guru</option>
            </select>
        </div>

        <div id="formGuru" style="display: none;">
            <div class="mb-3">
                <input type="text" name="namaGuru" class="form-control" placeholder="Nama Guru">
            </div>
            <div class="mb-3">
                <label>Kelas:</label>
                <select name="id_kelas" class="form-select">
                    <option value="">-- Pilih Kelas --</option>
                    <?php while($row = mysqli_fetch_assoc($result_kelas)) {
                        echo "<option value='{$row['id']}'>{$row['nama_kelas']}</option>";
                    } ?>
                </select>
            </div>
            <div class="mb-3">
                <label>Pelajaran:</label>
                <select name="id_pelajaran" class="form-select">
                    <option value="">-- Pilih Pelajaran --</option>
                    <?php while($row = mysqli_fetch_assoc($result_pelajaran)) {
                        echo "<option value='{$row['id']}'>{$row['nama_pelajaran']}</option>";
                    } ?>
                </select>
            </div>
        </div>

        <div id="formSiswa" style="display: none;">
            <div class="mb-3">
                <input type="text" name="namaSiswa" class="form-control" placeholder="Nama Siswa">
            </div>
            <div class="mb-3">
                <label>Kelas:</label>
                <select name="id_kelas_siswa" class="form-select">
                    <option value="">-- Pilih Kelas --</option>
                    <?php mysqli_data_seek($result_kelas, 0); while($row = mysqli_fetch_assoc($result_kelas)) {
                        echo "<option value='{$row['id']}'>{$row['nama_kelas']}</option>";
                    } ?>
                </select>
            </div>
            <div class="mb-3">
                <label>Jurusan:</label>
                <select name="id_jurusan" class="form-select">
                    <option value="">-- Pilih Jurusan --</option>
                    <?php while($row = mysqli_fetch_assoc($result_jurusan)) {
                        echo "<option value='{$row['id_jurusan']}'>{$row['nama_jurusan']}</option>";
                    } ?>
                </select>
            </div>
        </div>

        <button type="submit" name="add_user" class="btn btn-primary">Tambah User</button>
    </form>

    <script>
    function tampilkanFormBerdasarkanRole() {
        const role = document.getElementById("roleSelect").value;
        document.getElementById("formGuru").style.display = (role === "guru" || role === "semua guru") ? "block" : "none";
        document.getElementById("formSiswa").style.display = (role === "siswa") ? "block" : "none";
    }
    </script>

    <h3>Daftar Pengguna</h3>
    <table class="table table-bordered">
        <thead class="table-light">
        <tr>
            <th>ID</th>
            <th>Username</th>
            <th>Role</th>
            <th>Aksi</th>
        </tr>
        </thead>
        <tbody>
        <?php mysqli_data_seek($result_users, 0); while ($row = mysqli_fetch_assoc($result_users)) { ?>
        <tr>
            <td><?= $row["id"] ?></td>
            <td><?= $row["username"] ?></td>
            <td><?= $row["role"] ?></td>
            <td><a href="manage_users.php?delete_user=<?= $row["id"] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Hapus user ini?')">Hapus</a></td>
        </tr>
        <?php } ?>
        </tbody>
    </table>

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

    <hr>
    <h2>Manajemen Pelajaran</h2>
    <form method="POST" class="mb-4">
        <div class="mb-3">
            <input type="text" name="nama_pelajaran" class="form-control" placeholder="Nama Pelajaran" required>
        </div>
        <button type="submit" name="add_pelajaran" class="btn btn-success">Tambah Pelajaran</button>
    </form>

    <h3>Daftar Pelajaran</h3>
    <table class="table table-bordered">
        <thead class="table-light">
        <tr><th>ID</th><th>Nama</th><th>Aksi</th></tr>
        </thead>
        <tbody>
        <?php mysqli_data_seek($result_pelajaran, 0); while ($row = mysqli_fetch_assoc($result_pelajaran)) { ?>
        <tr>
            <td><?= $row["id"] ?></td>
            <td><?= $row["nama_pelajaran"] ?></td>
            <td><a href="manage_users.php?delete_pelajaran=<?= $row["id"] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Hapus pelajaran ini?')">Hapus</a></td>
        </tr>
        <?php } ?>
        </tbody>
    </table>

    <a href="dashboard.php" class="btn btn-secondary mt-4">Kembali ke Dashboard</a>
</div>
</body>
</html>