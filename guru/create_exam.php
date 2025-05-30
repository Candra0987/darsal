<?php
    session_start();
    require '../vendor/autoload.php';
    require '../config/db.php';



    // Check user role
    if (!isset($_SESSION["role"]) || $_SESSION["role"] != "guru") {
        header("Location: ../index.php");
        exit();
    }



    $user_id = $_SESSION["user_id"];



    // Fetch data from the database
    $result_kelas = mysqli_query($conn, "SELECT * FROM kelas");
    $result_pelajaran = mysqli_query($conn, "SELECT mp.id, mp.nama_pelajaran
        FROM guru g
        JOIN mata_pelajaran mp ON g.id_pelajaran = mp.id
        WHERE g.user_id = $user_id");
    $result_ujian = mysqli_query($conn, "SELECT * FROM ujian");
    $result_guru = mysqli_query($conn, "SELECT * FROM guru WHERE user_id = $user_id");



    if ($row = mysqli_fetch_assoc($result_guru)) {
        $_SESSION['id_guru'] = $row['id'];
    }
    $idGuru = $_SESSION['id_guru'];



    // Handle form submission
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_soal'])) {
        $id_kelas = mysqli_real_escape_string($conn, $_POST["id_kelas"]);
        $id_ujian = mysqli_real_escape_string($conn, $_POST["id_ujian"]);
        $id_pelajaran = mysqli_real_escape_string($conn, $_POST["id_pelajaran"]);
        $pertanyaan = mysqli_real_escape_string($conn, $_POST["pertanyaan"]);
        $opsi_a = mysqli_real_escape_string($conn, $_POST["opsi_a"] ?? null);
        $opsi_b = mysqli_real_escape_string($conn, $_POST["opsi_b"] ?? null);
        $opsi_c = mysqli_real_escape_string($conn, $_POST["opsi_c"] ?? null);
        $opsi_d = mysqli_real_escape_string($conn, $_POST["opsi_d"] ?? null);
        $jawaban = mysqli_real_escape_string($conn, $_POST["jawaban"] ?? null);
        $tipe = mysqli_real_escape_string($conn, $_POST["tipe"]);



        // Cek apakah soal yang sama sudah ada
        $cek_duplikat_query = "SELECT * FROM soal 
            WHERE pertanyaan = '$pertanyaan' 
            AND id_ujian = '$id_ujian' 
            AND id_kelas = '$id_kelas' 
            AND id_pelajaran = '$id_pelajaran' 
            AND id_guru = '$idGuru'";



        $cek_duplikat_result = mysqli_query($conn, $cek_duplikat_query);



        // Jika duplikat ditemukan (dan bukan soal yang sedang diedit)
        if (mysqli_num_rows($cek_duplikat_result) > 0 && !isset($_POST['edit_id_soal'])) {
            $_SESSION['message'] = 'Soal sudah pernah ditambahkan sebelumnya!';
            $_SESSION['message_type'] = 'warning';
            header("Location: create_exam.php");
            exit();
        }



        if (isset($_POST['edit_id_soal'])) {
            $edit_id = mysqli_real_escape_string($conn, $_POST['edit_id_soal']);
            $query = "UPDATE soal SET id_ujian='$id_ujian', id_kelas='$id_kelas', id_pelajaran='$id_pelajaran',
                    pertanyaan='$pertanyaan', opsi_a='$opsi_a', opsi_b='$opsi_b', opsi_c='$opsi_c', opsi_d='$opsi_d', 
                    jawaban='$jawaban', tipe='$tipe'
                    WHERE id_soal='$edit_id' AND id_guru='$idGuru'";
        } else {
            $query = "INSERT INTO soal (id_ujian, id_guru, id_kelas, id_pelajaran, pertanyaan, opsi_a, opsi_b, opsi_c, opsi_d, jawaban, tipe)
                    VALUES ('$id_ujian', '$idGuru', '$id_kelas', '$id_pelajaran', '$pertanyaan', '$opsi_a', '$opsi_b', '$opsi_c', '$opsi_d', '$jawaban', '$tipe')";
        }



        if (mysqli_query($conn, $query)) {
            $_SESSION['message'] = isset($edit_id) ? 'Soal berhasil diperbarui!' : 'Soal berhasil ditambahkan!';
            $_SESSION['message_type'] = 'success';
        } else {
            $_SESSION['message'] = 'Gagal menyimpan soal!';
            $_SESSION['message_type'] = 'danger';
        }



        header("Location: create_exam.php");
        exit();
    }



    // Handle deletion
    if (isset($_GET['hapus'])) {
        $hapus_id = mysqli_real_escape_string($conn, $_GET['hapus']);
        mysqli_query($conn, "DELETE FROM soal WHERE id_soal = $hapus_id AND id_guru = $idGuru");
        $_SESSION['message'] = 'Soal berhasil dihapus!';
        $_SESSION['message_type'] = 'success';
        header("Location: create_exam.php");
        exit();
    }



    // Pagination setup
    $results_per_page = 10; // Number of results per page
    $result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM soal WHERE id_guru = $idGuru");
    $row = mysqli_fetch_assoc($result);
    $total_results = $row['total'];
    $total_pages = ceil($total_results / $results_per_page);
    $current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $start_limit = ($current_page - 1) * $results_per_page;



    // Fetch all questions with pagination
    $result_soal = mysqli_query($conn, "SELECT * FROM soal WHERE id_guru = $idGuru LIMIT $start_limit, $results_per_page");
    $edit_data = null;



    if (isset($_GET['edit'])) {
        $edit_id = mysqli_real_escape_string($conn, $_GET['edit']);
        $edit_query = mysqli_query($conn, "SELECT * FROM soal WHERE id_soal = $edit_id AND id_guru = $idGuru");
        $edit_data = mysqli_fetch_assoc($edit_query);
    }



    // Count questions
    $jumlah_pg = $jumlah_essay = 0;
    $soal_pg = $soal_essay = [];
    mysqli_data_seek($result_soal, 0);



    while ($s = mysqli_fetch_assoc($result_soal)) {
        if ($s['tipe'] === 'pg') {
            $jumlah_pg++;
            $soal_pg[] = $s;
        } else {
            $jumlah_essay++;
            $soal_essay[] = $s;
        }
    }



    $bobot_pg = $jumlah_pg > 0 ? 40 / $jumlah_pg : 0;
    $bobot_essay = $jumlah_essay > 0 ? 60 / $jumlah_essay : 0;
    ?>



    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <title>Buat Soal Ujian</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.snow.css" rel="stylesheet" />
        <style>
            #editor img {
                max-width: 100%;
                height: auto;
                display: block;
                margin: 10px 0;
            }



            .card-text img {
                max-width: 100%;
                height: auto;
                display: block;
                margin: 10px 0;
            }



            .pagination a {
                padding: 8px 16px;
                margin: 0 4px;
                text-decoration: none;
                border: 1px solid #007bff;
                color: #007bff;
            }



            .pagination a:hover {
                background-color: #007bff;
                color: white;
            }
        </style>
    </head>
    <body>
    <div class="container mt-5">
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-<?= $_SESSION['message_type'] ?> alert-dismissible fade show">
                <?= $_SESSION['message'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['message'], $_SESSION['message_type']); ?>
        <?php endif; ?>



        <h2><?= $edit_data ? 'Edit Soal' : 'Buat Soal Ujian' ?></h2>
        <form method="POST" onsubmit="return submitForm()" class="border p-4 bg-light rounded mb-5">
            <input type="hidden" name="pertanyaan" id="pertanyaan">
            <?php if ($edit_data): ?>
                <input type="hidden" name="edit_id_soal" value="<?= $edit_data['id_soal'] ?>">
            <?php endif; ?>



            <div class="mb-3">
                <label class="form-label">Pilih Kelas:</label>
                <select name="id_kelas" class="form-select" required>
                    <?php mysqli_data_seek($result_kelas, 0); while ($row = mysqli_fetch_assoc($result_kelas)) { ?>
                        <option value="<?= $row['id']; ?>" <?= $edit_data && $edit_data['id_kelas'] == $row['id'] ? 'selected' : '' ?>>
                            <?= $row['nama_kelas']; ?>
                        </option>
                    <?php } ?>
                </select>
            </div>



            <div class="mb-3">
                <label class="form-label">Pilih Mata Pelajaran:</label>
                <select name="id_pelajaran" class="form-select" required>
                    <?php mysqli_data_seek($result_pelajaran, 0); while ($row = mysqli_fetch_assoc($result_pelajaran)) { ?>
                        <option value="<?= $row['id']; ?>" <?= $edit_data && $edit_data['id_pelajaran'] == $row['id'] ? 'selected' : '' ?>>
                            <?= $row['nama_pelajaran']; ?>
                        </option>
                    <?php } ?>
                </select>
            </div>



            <div class="mb-3">
                <label class="form-label">Pilih Ujian:</label>
                <select name="id_ujian" class="form-select" required>
                    <?php mysqli_data_seek($result_ujian, 0); while ($row = mysqli_fetch_assoc($result_ujian)) { ?>
                        <option value="<?= $row['id_ujian']; ?>" <?= $edit_data && $edit_data['id_ujian'] == $row['id_ujian'] ? 'selected' : '' ?>>
                            <?= $row['nama_ujian']; ?>
                        </option>
                    <?php } ?>
                </select>
            </div>



            <div class="mb-3">
                <label class="form-label">Tipe Soal:</label>
                <select name="tipe" class="form-select" required onchange="toggleOpsi(this.value)">
                    <option value="pg" <?= $edit_data && $edit_data['tipe'] == 'pg' ? 'selected' : '' ?>>Pilihan Ganda</option>
                    <option value="essay" <?= $edit_data && $edit_data['tipe'] == 'essay' ? 'selected' : '' ?>>Essay</option>
                </select>
            </div>



            <div class="mb-3">
                <label>Pertanyaan:</label>
                <div id="editor" style="height: 200px;"><?= $edit_data ? $edit_data['pertanyaan'] : '' ?></div>
            </div>



            <div id="opsi_pg" style="display: <?= !$edit_data || $edit_data['tipe'] == 'pg' ? 'block' : 'none' ?>;">
                <div class="mb-3"><label>Opsi A:</label><input type="text" name="opsi_a" class="form-control" value="<?= $edit_data['opsi_a'] ?? '' ?>"></div>
                <div class="mb-3"><label>Opsi B:</label><input type="text" name="opsi_b" class="form-control" value="<?= $edit_data['opsi_b'] ?? '' ?>"></div>
                <div class="mb-3"><label>Opsi C:</label><input type="text" name="opsi_c" class="form-control" value="<?= $edit_data['opsi_c'] ?? '' ?>"></div>
                <div class="mb-3"><label>Opsi D:</label><input type="text" name="opsi_d" class="form-control" value="<?= $edit_data['opsi_d'] ?? '' ?>"></div>
                <div class="mb-3">
                    <label>Jawaban Benar:</label>
                    <select name="jawaban" class="form-select">
                        <option value="A" <?= $edit_data && $edit_data['jawaban'] == 'A' ? 'selected' : '' ?>>A</option>
                        <option value="B" <?= $edit_data && $edit_data['jawaban'] == 'B' ? 'selected' : '' ?>>B</option>
                        <option value="C" <?= $edit_data && $edit_data['jawaban'] == 'C' ? 'selected' : '' ?>>C</option>
                        <option value="D" <?= $edit_data && $edit_data['jawaban'] == 'D' ? 'selected' : '' ?>>D</option>
                    </select>
                </div>
            </div>



            <div class="mb-3 text-center">
                <button type="submit" name="submit_soal" class="btn btn-primary"><?= $edit_data ? 'Perbarui Soal' : 'Tambah Soal' ?></button>
            </div>
        </form>



        <form action="upload_soal.php" method="POST" enctype="multipart/form-data" class="mt-4 border p-3 bg-white">
            <h4>Import Soal dari Word</h4>
            <div class="mb-3">
                <label>File Word (.docx):</label>
                <input type="file" name="file_docx" accept=".docx" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>Tipe Soal:</label>
                <select name="tipe" class="form-select" required>
                    <option value="pg">Pilihan Ganda</option>
                    <option value="essay">Essay</option>
                </select>
            </div>
            <div class="mb-3">
                <label>Pilih Ujian:</label>
                <select name="id_ujian" class="form-select" required>
                    <?php mysqli_data_seek($result_ujian, 0); while ($row = mysqli_fetch_assoc($result_ujian)) { ?>
                        <option value="<?= $row['id_ujian']; ?>"><?= $row['nama_ujian']; ?></option>
                    <?php } ?>
                </select>
            </div>
            <div class="mb-3">
                <label>Pilih Kelas:</label>
                <select name="id_kelas" class="form-select" required>
                    <?php mysqli_data_seek($result_kelas, 0); while ($row = mysqli_fetch_assoc($result_kelas)) { ?>
                        <option value="<?= $row['id']; ?>"><?= $row['nama_kelas']; ?></option>
                    <?php } ?>
                </select>
            </div>
            <div class="mb-3">
                <label>Pilih Pelajaran:</label>
                <select name="id_pelajaran" class="form-select" required>
                    <?php mysqli_data_seek($result_pelajaran, 0); while ($row = mysqli_fetch_assoc($result_pelajaran)) { ?>
                        <option value="<?= $row['id']; ?>"><?= $row['nama_pelajaran']; ?></option>
                    <?php } ?>
                </select>
            </div>
            <button type="submit" class="btn btn-success">Import Soal</button>
        </form>



        <hr class="my-4">
        <h4>Daftar Soal</h4>
        <div class="row">
            <?php if (!empty($soal_pg) || !empty($soal_essay)): ?>
                <?php 
                $nomor = $start_limit + 1; // Initialize question number
                foreach (array_merge($soal_pg, $soal_essay) as $soal): ?>
                    <div class="col-12 mb-3">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Soal <?= strtoupper($soal['tipe']) ?> #<?= $nomor ?></h5>
                                <p class="card-text"><?= $soal['pertanyaan'] ?></p>
                                <?php if ($soal['tipe'] === 'pg'): ?>
                                    <ul class="list-group list-group-flush mb-3">
                                        <li class="list-group-item"><strong>Opsi A:</strong> <?= htmlspecialchars($soal['opsi_a']) ?></li>
                                        <li class="list-group-item"><strong>Opsi B:</strong> <?= htmlspecialchars($soal['opsi_b']) ?></li>
                                        <li class="list-group-item"><strong>Opsi C:</strong> <?= htmlspecialchars($soal['opsi_c']) ?></li>
                                        <li class="list-group-item"><strong>Opsi D:</strong> <?= htmlspecialchars($soal['opsi_d']) ?></li>
                                        <li class="list-group-item"><strong>Jawaban Benar:</strong> <?= htmlspecialchars($soal['jawaban']) ?></li>
                                    </ul>
                                <?php else: ?>
                                    <p><em>Soal Essay</em></p>
                                <?php endif; ?>
                                <a href="?edit=<?= $soal['id_soal'] ?>" class="btn btn-warning btn-sm">Edit</a>
                                <a href="?hapus=<?= $soal['id_soal'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Yakin ingin menghapus soal ini?')">Hapus</a>
                            </div>
                        </div>
                    </div>
                    <?php $nomor++; ?>
                <?php endforeach; ?>
            <?php else: ?>
                <p>Belum ada soal yang dibuat.</p>
            <?php endif; ?>
        </div>



        <!-- Pagination controls -->
        <div class="pagination">
            <?php for ($page = 1; $page <= $total_pages; $page++): ?>
                <a href="create_exam.php?page=<?= $page ?>" <?= $page == $current_page ? 'class="active"' : '' ?>><?= $page ?></a>
            <?php endfor; ?>
        </div>
        <a href="dashboard.php" class="btn btn-secondary mt-4">Kembali ke Dashboard</a>
    </div>



    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.min.js"></script>



    <script>
        // Inisialisasi Quill Editor
        var quill = new Quill('#editor', {
            theme: 'snow',
            modules: {
                toolbar: [
                    ['bold', 'italic', 'underline', 'strike'],
                    ['link', 'image', 'audio'],
                    [{ 'list': 'ordered' }, { 'list': 'bullet' }],
                    [{ 'header': [1, 2, 3, false] }],
                    ['clean']
                ]
            }
        });



        // Submit isi Quill ke input hidden sebelum form dikirim
        function submitForm() {
            var pertanyaanContent = document.querySelector('input[name=pertanyaan]');
            pertanyaanContent.value = quill.root.innerHTML;
            return true; // lanjutkan submit
        }



        // Toggle opsi PG saat tipe soal berubah
        function toggleOpsi(tipe) {
            var opsiPG = document.getElementById('opsi_pg');
            opsiPG.style.display = tipe === 'pg' ? 'block' : 'none';
        }



        // Set konten editor saat mode edit
        <?php if ($edit_data): ?>
        quill.root.innerHTML = <?= json_encode($edit_data['pertanyaan']) ?>;
        <?php endif; ?>
    </script>
    </body>
    </html>