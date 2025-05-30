<?php
session_start();
include 'config/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST["username"];
    $password = $_POST["password"];
    $role = $_POST["role"];

    $query = "SELECT * FROM users WHERE username='$username' AND password='$password' AND role='$role' LIMIT 1";
    $result = mysqli_query($conn, $query);

    function getDataSiswa($id){
        global $conn;
        $querysiswa = "SELECT * FROM siswa WHERE user_id = '$id' LIMIT 1";
        $result = mysqli_query($conn, $querysiswa);

        if ($row = mysqli_fetch_assoc($result)) {
            $_SESSION['id_kelas'] = $row['id_kelas'];
            $_SESSION['id_jurusan'] = $row['id_jurusan'];
        }
    }

    if (mysqli_num_rows($result) > 0) {
        if ($row = mysqli_fetch_assoc($result)) {
            $_SESSION['username'] = $row['username'];
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['role'] = $row['role'];

            if ($role == 'siswa') {
                getDataSiswa($row['id']);
                header("Location: siswa/pilih_ujian.php");
                exit;
            } elseif (in_array($role, ['admin', 'guru', 'staff', 'kepala sekolah'])) {
                header("Location: $role/dashboard.php");
                exit;
            } else {
                $error = "Role tidak dikenali!";
            }
        } else {
            $error = "Data pengguna tidak ditemukan.";
        }
    } else {
        $error = "Login gagal! Username atau password salah.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: url('assets/background.jpg') no-repeat center center fixed;
            background-size: cover;
            margin: 0;
            padding: 0;
        }
        .navbar-brand img {
            height: 40px;
        }
        .page-title {
            font-size: 1.8rem;
            font-weight: bold;
            text-align: center;
            margin-top: 30px;
            color: blue;
            text-shadow: 1px 1px 3px rgba(0,0,0,0.5);
        }
        .login-container {
            min-height: calc(100vh - 56px);
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 30px 15px;
        }
        .card {
            width: 100%;
            max-width: 400px;
            background-color: rgba(255,255,255,0.95);
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 0 15px rgba(0,0,0,0.2);
        }
        .card-title {
            text-align: center;
            font-weight: bold;
            font-size: 1.5rem;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-light bg-white shadow-sm">
    <div class="container">
        <a class="navbar-brand" href="#">
            <img src="asset/logo.png" alt="Logo Sekolah">
        </a>
    </div>
</nav>

<!-- Judul Halaman -->
<div class="container">
    <div class="page-title">Sistem Ujian Online</div>
</div>

<!-- Konten Login -->
<div class="login-container">
    <div class="card">
        <div class="card-title">Login</div>

        <?php if (!empty($error)) : ?>
            <div class="alert alert-danger"><?= $error; ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-control" placeholder="Username" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" placeholder="Password" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Role</label>
                <select name="role" class="form-select" required>
                    <option value="admin">Admin</option>
                    <option value="guru">Guru</option>
                    <option value="siswa">Siswa</option>
                    <option value="staff">Staff</option>
                    <option value="kepala sekolah">Kepala Sekolah</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary w-100">Login</button>
        </form>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
