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
    if (isset($_SESSION['message']) && $_SESSION['message'] === 'Jawaban berhasil disimpan!') {
        $pesan_sukses = $_SESSION['message'];
        unset($_SESSION['message'], $_SESSION['message_type']);
    } else {
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
                <strong>Anda tidak bisa mengerjakan ulang</strong>
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

// --- PROSES SIMPAN JAWABAN DAN NILAI ---
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

    // Redirect ke GET untuk menghindari refresh POST
    header("Location: exam.php?id_ujian=$id_ujian");
    exit();
}

// --- BUAT SEED ACAK YANG KONSISTEN PER SISWA PER UJIAN ---
$seed = crc32($siswa_id . '_' . $id_ujian);

// --- AMBIL SOAL UJIAN UNTUK DITAMPILKAN ---
$limit = 10; // Jumlah soal per halaman
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $limit;

// Gunakan urutan acak yang konsisten berdasarkan seed
$result_soal = mysqli_query($conn, "
    SELECT * FROM soal 
    WHERE id_ujian = $id_ujian 
    ORDER BY RAND($seed)
    LIMIT $limit OFFSET $offset
");
if (!$result_soal) {
    die("Query soal error: " . mysqli_error($conn));
}

// Hitung total soal untuk pagination
$total_result = mysqli_query($conn, "SELECT COUNT(*) as total FROM soal WHERE id_ujian = $id_ujian");
$total_row = mysqli_fetch_assoc($total_result);
$total_questions_count = $total_row['total'];
$total_pages = ceil($total_questions_count / $limit);

// Hitung nomor soal awal untuk halaman saat ini
$nomor_soal_awal = ($page - 1) * $limit + 1;
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
        .question-card { 
            background: #fff; 
            padding: 20px; 
            border-radius: 8px; 
            box-shadow: 0 4px 6px rgba(0,0,0,0.1); 
            margin-bottom: 20px; 
        }
        .btn-submit { 
            background-color: #4caf50; 
            color: white; 
            font-weight: bold; 
            width: 100%; 
            padding: 12px; 
            border-radius: 5px; 
        }
        .info-soal { 
            background-color: #e3f2fd; 
            padding: 15px; 
            border-radius: 8px; 
            margin-bottom: 20px; 
            text-align: center;
            border-left: 4px solid #2196f3;
        }
        .navigasi-sederhana {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin: 30px 0;
            text-align: center;
        }
        .btn-nav {
            margin: 0 10px;
            min-width: 120px;
        }
    </style>
    <script>
    let peringatanDiberikan = false;
    let sudahDikirim = false;

    document.addEventListener("visibilitychange", function () {
        if (document.hidden && !sudahDikirim) {
            if (!peringatanDiberikan) {
                peringatanDiberikan = true;
                alert("PERINGATAN: Jangan berpindah tab saat ujian berlangsung!");
            } else {
                sudahDikirim = true;
                alert("Anda keluar tab lagi. Jawaban akan dikirim otomatis.");
                document.querySelector("form").submit();
            }
        }
    });
    </script>
</head>
<body>
<div class="container">

    <?php if ($pesan_sukses): ?>
        <div class="alert alert-success text-center">
            <h4>✅ <?= htmlspecialchars($pesan_sukses) ?></h4>
            <?php if (isset($_SESSION['nilai_pg'])): ?>
                <p class="mb-0">Nilai Anda: <strong><?= htmlspecialchars($_SESSION['nilai_pg']) ?>/100</strong></p>
                <?php unset($_SESSION['nilai_pg']); ?>
            <?php endif; ?>
        </div>
        <div class="text-center mb-4">
            <a href="pilih_ujian.php" class="btn btn-primary btn-lg">Kembali ke Pilihan Ujian</a>
        </div>
    <?php else: ?>
        <h2 class="text-center mb-4">📝 Jawab Soal Ujian</h2>
        
        <!-- Info Soal -->
        <div class="info-soal">
            <h5 class="mb-2">📊 Halaman <?= $page ?> dari <?= $total_pages ?></h5>
            <p class="mb-0">
                Soal nomor <strong><?= $nomor_soal_awal ?></strong> 
                sampai <strong><?= min($nomor_soal_awal + $limit - 1, $total_questions_count) ?></strong> 
                dari total <strong><?= $total_questions_count ?></strong> soal
            </p>
        </div>

        <form method="POST" novalidate id="formUjian">
            <input type="hidden" name="id_ujian" value="<?= htmlspecialchars($id_ujian) ?>" />

            <?php 
            $no = $nomor_soal_awal;
            while ($soal = mysqli_fetch_assoc($result_soal)): 
            ?>
                <div class="question-card">
                    <div class="mb-3">
                        <span class="badge bg-primary me-2">Soal <?= $no ?></span>
                        <strong><?= html_entity_decode($soal['pertanyaan']) ?></strong>
                    </div>
                    <div class="mt-3">
                        <?php if ($soal['tipe'] === 'pg'): ?>
                            <?php foreach (['A','B','C','D'] as $opt): ?>
                                <?php if (!empty($soal["opsi_" . strtolower($opt)])): ?>
                                    <div class="form-check mb-2">
                                        <input type="radio" 
                                               name="jawaban[<?= $soal['id_soal'] ?>]" 
                                               value="<?= $opt ?>" 
                                               class="form-check-input" 
                                               id="opsi<?= $opt . $soal['id_soal'] ?>" 
                                               required />
                                        <label class="form-check-label" for="opsi<?= $opt . $soal['id_soal'] ?>">
                                            <strong><?= $opt ?>.</strong> <?= htmlspecialchars($soal["opsi_" . strtolower($opt)]) ?>
                                        </label>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <textarea name="jawaban[<?= $soal['id_soal'] ?>]" 
                                      rows="4" 
                                      class="form-control" 
                                      placeholder="Tulis jawaban Anda di sini..." 
                                      required></textarea>
                        <?php endif; ?>
                    </div>
                </div>
                <?php $no++; ?>
            <?php endwhile; ?>

            <!-- Tombol kirim hanya muncul di halaman terakhir -->
            <?php if ($page == $total_pages): ?>
                <div class="alert alert-warning text-center mb-3">
                    <strong>⚠️ Halaman Terakhir!</strong><br>
                    Pastikan semua jawaban sudah benar sebelum mengirim.
                </div>
                <div class="text-center mt-4">
                    <button type="submit" name="submit_jawaban" class="btn btn-submit btn-lg" onclick="return konfirmasiKirim()">
                        🚀 Kirim Semua Jawaban
                    </button>
                </div>
            <?php endif; ?>
        </form>

        <!-- NAVIGASI SEDERHANA -->
        <div class="navigasi-sederhana">
            <div class="row align-items-center">
                <div class="col-4 text-start">
                    <?php if ($page > 1): ?>
                        <a href="?id_ujian=<?= $id_ujian ?>&page=<?= $page - 1 ?>" class="btn btn-outline-primary btn-nav">
                            ← Sebelumnya
                        </a>
                    <?php endif; ?>
                </div>
                
                <div class="col-4 text-center">
                    <span class="badge bg-secondary fs-6 px-3 py-2">
                        <?= $page ?> / <?= $total_pages ?>
                    </span>
                </div>
                
                <div class="col-4 text-end">
                    <?php if ($page < $total_pages): ?>
                        <a href="?id_ujian=<?= $id_ujian ?>&page=<?= $page + 1 ?>" class="btn btn-outline-primary btn-nav">
                            Selanjutnya →
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <script>
    // Kunci localStorage unik untuk ujian ini
    const kunciPenyimpanan = 'jawaban_ujian_<?= $id_ujian ?>_<?= $siswa_id ?>';

    document.addEventListener('DOMContentLoaded', () => {
        // Muat jawaban yang tersimpan dari localStorage
        const jawabanTersimpan = JSON.parse(localStorage.getItem(kunciPenyimpanan)) || {};
        
        // Kembalikan pilihan radio button
        for (const [idSoal, jawaban] of Object.entries(jawabanTersimpan)) {
            const input = document.querySelector(`[name="jawaban[${idSoal}]"][value="${jawaban}"]`);
            if (input && input.type === 'radio') {
                input.checked = true;
            }
            
            // Kembalikan nilai textarea
            const textarea = document.querySelector(`[name="jawaban[${idSoal}]"]`);
            if (textarea && textarea.tagName === "TEXTAREA") {
                textarea.value = jawaban;
            }
        }

        // Simpan jawaban ke localStorage saat ada perubahan
        document.querySelectorAll('input[type="radio"]').forEach(input => {
            input.addEventListener('change', () => {
                const jawaban = JSON.parse(localStorage.getItem(kunciPenyimpanan)) || {};
                const idSoal = input.name.match(/\[(\d+)\]/)[1];
                jawaban[idSoal] = input.value;
                localStorage.setItem(kunciPenyimpanan, JSON.stringify(jawaban));
                
                tampilkanNotifikasiTersimpan();
            });
        });

        document.querySelectorAll('textarea').forEach(input => {
            input.addEventListener('input', () => {
                const jawaban = JSON.parse(localStorage.getItem(kunciPenyimpanan)) || {};
                const idSoal = input.name.match(/\[(\d+)\]/)[1];
                jawaban[idSoal] = input.value;
                localStorage.setItem(kunciPenyimpanan, JSON.stringify(jawaban));
                
                tampilkanNotifikasiTersimpan();
            });
        });
    });

    // Fungsi konfirmasi sebelum mengirim
    function konfirmasiKirim() {
        return confirm('🤔 Apakah Anda yakin ingin mengirim semua jawaban?\n\n⚠️ Setelah dikirim, Anda tidak dapat mengubah jawaban lagi.');
    }

    // Fungsi notifikasi jawaban tersimpan
    function tampilkanNotifikasiTersimpan() {
        const notifikasiLama = document.querySelector('.notifikasi-tersimpan');
        if (notifikasiLama) {
            notifikasiLama.remove();
        }
        
        const notifikasi = document.createElement('div');
        notifikasi.className = 'alert alert-success notifikasi-tersimpan position-fixed';
        notifikasi.style.cssText = 'top: 20px; right: 20px; z-index: 9999; opacity: 0.9; font-size: 14px;';
        notifikasi.innerHTML = '✅ Tersimpan';
        document.body.appendChild(notifikasi);
        
        setTimeout(() => {
            if (notifikasi.parentNode) {
                notifikasi.remove();
            }
        }, 1500);
    }

    // Handle pengiriman form
    document.getElementById('formUjian').addEventListener('submit', function(e) {
        const semuaJawaban = JSON.parse(localStorage.getItem(kunciPenyimpanan)) || {};
        
        document.querySelectorAll('.jawaban-tersembunyi').forEach(el => el.remove());
        
        for (const [idSoal, jawaban] of Object.entries(semuaJawaban)) {
            const inputTersembunyi = document.createElement('input');
            inputTersembunyi.type = 'hidden';
            inputTersembunyi.name = `jawaban[${idSoal}]`;
            inputTersembunyi.value = jawaban;
            inputTersembunyi.className = 'jawaban-tersembunyi';
            this.appendChild(inputTersembunyi);
        }
        
        if (e.target.querySelector('[name="submit_jawaban"]')) {
            localStorage.removeItem(kunciPenyimpanan);
        }
    });
    </script>

</div>
</body>
</html>