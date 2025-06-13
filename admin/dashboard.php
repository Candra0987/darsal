<?php
session_start();
if (!isset($_SESSION["role"]) || $_SESSION["role"] != "admin") {
    header("Location: ../index.php");
    exit();
}

// Get user info from session if available
$username = $_SESSION['username'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin</title>
    
    <!-- Bootstrap 5.3 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #0d6efd;
            --danger-color: #dc3545;
            --warning-color: #ffc107;
            --success-color: #198754;
            --dark-color: #212529;
            --light-color: #f8f9fa;
            --border-color: #dee2e6;
            --text-primary: #212529;
            --text-secondary: #6c757d;
            --shadow-sm: 0 .125rem .25rem rgba(0,0,0,.075);
            --shadow-md: 0 .5rem 1rem rgba(0,0,0,.15);
        }

        * {
            font-family: 'Inter', sans-serif;
        }

        body {
            background-color: var(--light-color);
            color: var(--text-primary);
        }

        /* Navbar */
        .navbar {
            box-shadow: var(--shadow-sm);
        }

        .navbar-brand {
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .navbar-brand-icon {
            width: 32px;
            height: 32px;
            background: var(--primary-color);
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }

        /* Main Content */
        .main-content {
            padding: 2rem 0;
        }

        /* Welcome Card */
        .welcome-card {
            background: white;
            border-radius: 12px;
            border: 1px solid var(--border-color);
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-sm);
        }

        .welcome-title {
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: var(--text-primary);
        }

        .welcome-subtitle {
            color: var(--text-secondary);
            margin-bottom: 1.5rem;
        }

        /* Menu Cards */
        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.5rem;
        }

        .menu-card {
            background: white;
            border-radius: 12px;
            border: 1px solid var(--border-color);
            padding: 1.5rem;
            transition: all 0.2s ease;
            text-decoration: none;
            color: inherit;
            box-shadow: var(--shadow-sm);
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .menu-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-md);
            border-color: var(--primary-color);
        }

        .menu-icon {
            width: 48px;
            height: 48px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
            margin-bottom: 1rem;
        }

        .menu-icon.primary { background: var(--primary-color); }
        .menu-icon.danger { background: var(--danger-color); }
        .menu-icon.warning { background: var(--warning-color); }
        .menu-icon.success { background: var(--success-color); }

        .menu-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--text-primary);
        }

        .menu-description {
            color: var(--text-secondary);
            font-size: 0.875rem;
            margin-bottom: 1rem;
            flex-grow: 1;
        }

        /* Footer */
        .footer {
            text-align: center;
            padding: 1.5rem 0;
            color: var(--text-secondary);
            font-size: 0.875rem;
            border-top: 1px solid var(--border-color);
            margin-top: 2rem;
        }

        /* Toggle Button */
        .version-toggle {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 1000;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: var(--shadow-md);
            transition: all 0.2s ease;
        }

        .version-toggle:hover {
            transform: scale(1.1);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .menu-grid {
                grid-template-columns: 1fr;
            }
            
            .welcome-title {
                font-size: 1.5rem;
            }
        }

        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .fade-in-up {
            animation: fadeInUp 0.5s ease-out forwards;
        }

        .delay-1 { animation-delay: 0.1s; }
        .delay-2 { animation-delay: 0.2s; }
        .delay-3 { animation-delay: 0.3s; }
        .delay-4 { animation-delay: 0.4s; }
    </style>
</head>
<body>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="#">
                <div class="navbar-brand-icon">
                    <i class="bi bi-mortarboard-fill"></i>
                </div>
                Admin Panel
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    
                    <li class="nav-item">
                        <a class="nav-link" href="../index.php">
                            <i class="bi bi-box-arrow-right me-1"></i>
                            Logout
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Toggle Button -->
    <a href="dashboard.php" class="version-toggle" title="Lihat Versi Original">
        <i class="bi bi-arrow-left"></i>
    </a>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container">
            
            <!-- Welcome Card -->
            <div class="welcome-card fade-in-up">
                <h2 class="welcome-title">Dashboard Admin</h2>
                <p class="welcome-subtitle">Selamat datang, Admin! Kelola sistem ujian online dengan mudah.</p>
            </div>

            <!-- Menu Cards -->
            <div class="menu-grid">
                <!-- Kelola Pengguna -->
                <a href="manage_users.php" class="menu-card fade-in-up delay-1">
                    <div class="menu-icon primary">
                        <i class="bi bi-people-fill"></i>
                    </div>
                    <h3 class="menu-title">Kelola Pengguna</h3>
                    <p class="menu-description">Tambah, edit, dan hapus pengguna sistem. Atur hak akses dan informasi pengguna.</p>
                    <div class="d-flex align-items-center">
                        <span class="badge bg-primary me-2">Akses</span>
                        <i class="bi bi-arrow-right ms-auto"></i>
                    </div>
                </a>

                <!-- Kelola Kelas -->
                <a href="manage_kelas.php" class="menu-card fade-in-up delay-2">
                    <div class="menu-icon danger">
                        <i class="bi bi-building"></i>
                    </div>
                    <h3 class="menu-title">Kelola Kelas</h3>
                    <p class="menu-description">Atur kelas dan pembagian siswa. Kelola informasi kelas dan wali kelas.</p>
                    <div class="d-flex align-items-center">
                        <span class="badge bg-danger me-2">Akses</span>
                        <i class="bi bi-arrow-right ms-auto"></i>
                    </div>
                </a>

                <!-- Kelola Pelajaran -->
                <a href="manage_pelajaran.php" class="menu-card fade-in-up delay-3">
                    <div class="menu-icon warning">
                        <i class="bi bi-book-fill"></i>
                    </div>
                    <h3 class="menu-title">Kelola Pelajaran</h3>
                    <p class="menu-description">Tambah dan atur mata pelajaran. Kelola kurikulum dan materi pembelajaran.</p>
                    <div class="d-flex align-items-center">
                        <span class="badge bg-warning text-dark me-2">Akses</span>
                        <i class="bi bi-arrow-right ms-auto"></i>
                    </div>
                </a>

                <!-- Jadwal Ujian -->
                <a href="jadwal_ujian.php" class="menu-card fade-in-up delay-4">
                    <div class="menu-icon primary">
                        <i class="bi bi-calendar-event"></i>
                    </div>
                    <h3 class="menu-title">Jadwal Ujian</h3>
                    <p class="menu-description">Buat dan kelola jadwal ujian, Atur waktu, durasi, dan peserta ujian.</p>
                    <div class="d-flex align-items-center">
                        <span class="badge bg-primary me-2">Akses</span>
                        <i class="bi bi-arrow-right ms-auto"></i>
                    </div>
                </a>
            </div>

            <!-- Footer -->
            <div class="footer">
                <p>Sistem Ujian online &copy; <?= date('Y') ?> </p>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
