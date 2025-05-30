<?php
session_start();
require '../config/db.php';


// Pastikan pengguna adalah siswa
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "siswa") {
    header("Location: ../index.php");
    exit();
}


$siswa_id = $_SESSION["user_id"];


// Validasi id_ujian
if (!isset($_GET['id_ujian'])) {
    echo "Ujian tidak ditemukan.";
    exit();
}


$id_ujian = intval($_GET['id_ujian']);
if ($id_ujian <= 0) {
    echo "ID ujian tidak valid.";
    exit();
}


// Reset nilai PG di session jika metode request GET
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_SESSION['nilai_pg'])) {
        unset($_SESSION['nilai_pg']);
    }
}


$pesan_sukses = "";


// --- CEK APAKAH SISWA SUDAH MENGERJAKAN UJIAN INI ---
$qCheck = mysqli_query($conn, "SELECT nilai_pg FROM hasil_ujian WHERE id_siswa = '$siswa_id' AND id_ujian = '$id_ujian'");
if (!$qCheck) {
    die("Query cek hasil ujian error: " . mysqli_error($conn));
}


$dataCheck = mysqli_fetch_assoc($qCheck);


// Jika sudah pernah mengerjakan ujian
if ($dataCheck) {
    // Jika pesan submit sukses masih ada di session (baru saja submit)
    if (isset($_SESSION['message']) && $_SESSION['message'] === 'Jawaban berhasil disimpan!') {
        $pesan_sukses = $_SESSION['message'];
        unset($_SESSION['message'], $_SESSION['message_type']);
    } else {
        // Sudah mengerjakan dan tidak baru submit -> tampilkan pesan larangan
        echo "<!DOCTYPE html>
        <html lang='id'>
        <head>
            <meta charset='UTF-8'>
            <title>Ujian Sudah Dikerjakan</title>
            <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css' rel='stylesheet'>
        </head>
        <body>
        <div class='container mt-5'>
            <div class='alert alert-warning text-center'>
                Anda telah mengerjakan ujian ini <br>
                <strong></strong> Anda tidak bisa mengerjakan ulang
            </div>
            <div class='text-center'>
                <a href='pilih_ujian.php' class='btn btn-primary'>Kembali ke Pilihan Ujian</a>
            </div>
        </div>
        </body>
        </html>";
        exit();
    }
}


// if ($_SERVER["REQUEST_METHOD"] === "POST"){
//     echo "<script>alert('submit di jalankan')</script>";

//     die;
// }

// --- PROSES SIMPAN JAWABAN DAN NILAI ---
// if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['submit_jawaban'])) {
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nilai_pg = 0;
    $jumlah_pg = 0;


    if (isset($_POST['jawaban']) && is_array($_POST['jawaban'])) {
        foreach ($_POST['jawaban'] as $id_soal => $jawaban) {
            $id_soal = intval($id_soal);
            $jawaban = mysqli_real_escape_string($conn, $jawaban);


            // Ambil data soal untuk cek tipe dan jawaban benar
            $qSoal = mysqli_query($conn, "SELECT tipe, jawaban FROM soal WHERE id_soal = $id_soal");
            if (!$qSoal) {
                die("Query soal error: " . mysqli_error($conn));
            }
            $dataSoal = mysqli_fetch_assoc($qSoal);


            $skor = "NULL";
            if ($dataSoal['tipe'] === 'pg') {
                $jumlah_pg++;
                if (strtoupper($jawaban) === strtoupper($dataSoal['jawaban'])) {
                    $nilai_pg++;
                    $skor = 100;
                } else {
                    $skor = 0;
                }
            }


            // Simpan jawaban (insert/update)
            $query = "INSERT INTO jawaban (id_soal, id_siswa, id_ujian, jawaban, skor)
                      VALUES ('$id_soal', '$siswa_id', '$id_ujian', '$jawaban', $skor)
                      ON DUPLICATE KEY UPDATE jawaban = '$jawaban', skor = $skor";
            if (!mysqli_query($conn, $query)) {
                die("Gagal menyimpan jawaban: " . mysqli_error($conn));
            }
        }
    }


    // Hitung nilai akhir PG (persentase)
    $nilai_pg_final = ($jumlah_pg > 0) ? round(($nilai_pg / $jumlah_pg) * 100, 2) : 0;


    // Simpan nilai PG ke tabel hasil_ujian
    $queryNilai = "INSERT INTO hasil_ujian (id_siswa, id_ujian, nilai_pg) VALUES ('$siswa_id', '$id_ujian', '$nilai_pg_final')";
    if (!mysqli_query($conn, $queryNilai)) {
        die("Gagal menyimpan nilai ujian: " . mysqli_error($conn));
    }


    // Simpan nilai PG ke session untuk ditampilkan
    $_SESSION['nilai_pg'] = $nilai_pg_final;


    // Pesan sukses
    $_SESSION['message'] = 'Jawaban berhasil disimpan!';
    $_SESSION['message_type'] = 'success';


    // Redirect ke GET untuk menghindari refresh POST dan supaya pesan muncul
    header("Location: exam.php?id_ujian=$id_ujian");
    exit();
}


// --- AMBIL SOAL UJIAN UNTUK DITAMPILKAN ---
$limit = 10; // Number of questions per page
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $limit;


// Fetch random questions with pagination
$result_soal = mysqli_query($conn, "
    SELECT * FROM soal 
    WHERE id_ujian = $id_ujian 
    ORDER BY RAND() 
    LIMIT $limit OFFSET $offset
");
if (!$result_soal) {
    die("Query soal error: " . mysqli_error($conn));
}



// Count total questions for pagination
$total_result = mysqli_query($conn, "SELECT COUNT(*) as total FROM soal WHERE id_ujian = $id_ujian");
$total_row = mysqli_fetch_assoc($total_result);
$total_questions_count = $total_row['total'];
$total_pages = ceil($total_questions_count / $limit);
?>


<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <title>Jawab Soal Ujian</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <style>
        body { background-color: #f4f7fc; }
        .container { margin-top: 50px; max-width: 800px; }
        .question-card { background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .btn-submit { background-color: #4caf50; color: white; font-weight: bold; width: 100%; padding: 12px; border-radius: 5px; }
    </style>
    <script>
    let warningGiven = false;
    let isSubmitted = false;


    document.addEventListener("visibilitychange", function () {

        console.log("ini jalan");

        if (document.hidden && !isSubmitted) {
            console.log("masuk kondisi 1");
            if (!warningGiven) {
                console.log("masuk kondisi 2");
                warningGiven = true;
                alert("PERINGATAN: Jangan berpindah tab saat ujian berlangsung!");
            } else {
                console.log("masuk kondisi 3");
                isSubmitted = true;
                alert("Anda keluar tab lagi. Jawaban dikirim otomatis.");
                document.querySelector("form").submit(); // Submit the form
            }
        }
        console.log("masuk kondisi 4");
    });
    </script>
    
</head>
<body>
<div class="container">


    <?php if ($pesan_sukses): ?>
        <div class="alert alert-success text-center">
            <?= htmlspecialchars($pesan_sukses) ?>
        </div>
        <div class="text-center mb-4">
            <a href="pilih_ujian.php" class="btn btn-primary">Kembali ke Pilihan Ujian</a>
        </div>
    <?php else: ?>
        <h2 class="text-center mb-4">Jawab Soal Ujian</h2>


        <form method="POST" novalidate>
            <input type="hidden" name="id_ujian" value="<?= htmlspecialchars($id_ujian) ?>" />


            <?php $no = 1; while ($soal = mysqli_fetch_assoc($result_soal)): ?>
                <div class="question-card">
                    <div><strong>#<?= $no++ ?>.</strong> <?= html_entity_decode($soal['pertanyaan']) ?></div>
                    <div class="mt-3">
                        <?php if ($soal['tipe'] === 'pg'): ?>
                            <?php foreach (['A','B','C','D'] as $opt): ?>
                                <?php if (!empty($soal["opsi_" . strtolower($opt)])): ?>
                                    <div class="form-check">
                                        <input type="radio" 
                                               name="jawaban[<?= $soal['id_soal'] ?>]" 
                                               value="<?= $opt ?>" 
                                               class="form-check-input" 
                                               id="opsi<?= $opt . $soal['id_soal'] ?>" 
                                               required />
                                        <label class="form-check-label" for="opsi<?= $opt . $soal['id_soal'] ?>">
                                            <?= htmlspecialchars($soal["opsi_" . strtolower($opt)]) ?>
                                        </label>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <textarea name="jawaban[<?= $soal['id_soal'] ?>]" rows="4" class="form-control" placeholder="Jawaban Anda..." required></textarea>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endwhile; ?>


            <?php if ($page == $total_pages): ?>
    <div class="text-center mt-4">
        <button type="submit" name="submit_jawaban" class="btn btn-submit">Kirim Jawaban</button>
    </div>
<?php endif; ?>



        <?php if (isset($_SESSION['nilai_pg'])): ?>
            <div class="mt-4 alert alert-info text-center">
                Nilai pilihan ganda sementara Anda: <strong><?= htmlspecialchars($_SESSION['nilai_pg']) ?>/100</strong>
            </div>
            <?php unset($_SESSION['nilai_pg']); ?>
        <?php endif; ?>


        <!-- Pagination -->
        <nav aria-label="Page navigation">
            <ul class="pagination">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                        <a class="page-link" href="?id_ujian=<?= $id_ujian ?>&page=<?= $i ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
    <?php endif; ?>


    <div class="text-center mt-4">
    <?php if ($page > 1): ?>
        <a href="exam.php?id_ujian=<?= $id_ujian ?>&page=<?= $page - 1 ?>" class="btn btn-secondary">Sebelumnya</a>
    <?php endif; ?>

    <?php if ($page < $total_pages): ?>
        <a href="exam.php?id_ujian=<?= $id_ujian ?>&page=<?= $page + 1 ?>" class="btn btn-primary">Selanjutnya</a>
    <?php endif; ?>
</div>




    <script>
document.addEventListener('DOMContentLoaded', () => {
    // Load jawaban dari localStorage ke form
    const savedAnswers = JSON.parse(localStorage.getItem('jawaban_ujian')) || {};
    for (const [idSoal, jawaban] of Object.entries(savedAnswers)) {
        const input = document.querySelector(`[name="jawaban[${idSoal}]"][value="${jawaban}"]`);
        if (input) input.checked = true;

        const textarea = document.querySelector(`[name="jawaban[${idSoal}]"]`);
        if (textarea && textarea.tagName === "TEXTAREA") textarea.value = jawaban;
    }

    // Simpan ke localStorage setiap ada perubahan
    document.querySelectorAll('input[type="radio"]').forEach(input => {
        input.addEventListener('change', () => {
            const answers = JSON.parse(localStorage.getItem('jawaban_ujian')) || {};
            answers[input.name.match(/\d+/)[0]] = input.value;
            localStorage.setItem('jawaban_ujian', JSON.stringify(answers));
        });
    });

    document.querySelectorAll('textarea').forEach(input => {
        input.addEventListener('input', () => {
            const answers = JSON.parse(localStorage.getItem('jawaban_ujian')) || {};
            answers[input.name.match(/\d+/)[0]] = input.value;
            localStorage.setItem('jawaban_ujian', JSON.stringify(answers));
        });
    });

    // Saat form submit, isi semua jawaban dari localStorage
    document.querySelector("form").addEventListener("submit", function (e) {
        const answers = JSON.parse(localStorage.getItem('jawaban_ujian')) || {};
        for (const [id, val] of Object.entries(answers)) {
            let input = document.createElement("input");
            input.type = "hidden";
            input.name = `jawaban[${id}]`;
            input.value = val;
            this.appendChild(input);
        }

        localStorage.removeItem('jawaban_ujian');
    });
});
</script>
<script>form.addEventListener('submit', function(e) {
    if (currentPage < totalPages) {
        e.preventDefault(); // mencegah kirim form
        alert("Selesaikan semua soal terlebih dahulu.");
        return false;
    }

    // Kosongkan input hidden sebelumnya
    document.querySelectorAll('.hidden-jawaban').forEach(el => el.remove());

    // Masukkan semua jawaban dari localStorage
    for (let i = 0; i < localStorage.length; i++) {
        const key = localStorage.key(i);
        if (key.startsWith('jawaban_soal_')) {
            const id_soal = key.replace('jawaban_soal_', '');
            const jawaban = localStorage.getItem(key);

            const inputHidden = document.createElement('input');
            inputHidden.type = 'hidden';
            inputHidden.name = `jawaban[${id_soal}]`;
            inputHidden.value = jawaban;
            inputHidden.classList.add('hidden-jawaban');

            form.appendChild(inputHidden);
        }
    }
});
</script>


</div>
</body>
</html>

