<?php
session_start();
require '../config/db.php';

if (!isset($_SESSION["role"]) || $_SESSION["role"] != "guru") {
    header("Location: ../index.php");
    exit();
}

$user_id = $_SESSION["user_id"];
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

// Tambah Soal
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_soal'])) {
    $id_kelas = $_POST["id_kelas"];
    $id_ujian = $_POST["id_ujian"];
    $id_pelajaran = $_POST["id_pelajaran"];
    $pertanyaan = $_POST["pertanyaan"];
    $opsi_a = $_POST["opsi_a"];
    $opsi_b = $_POST["opsi_b"];
    $opsi_c = $_POST["opsi_c"];
    $opsi_d = $_POST["opsi_d"];
    $jawaban = $_POST["jawaban"];

    if (isset($_POST['edit_id_soal'])) {
        $edit_id = $_POST['edit_id_soal'];
        $query = "UPDATE soal SET id_ujian='$id_ujian', id_kelas='$id_kelas', id_pelajaran='$id_pelajaran',
                  pertanyaan='$pertanyaan', opsi_a='$opsi_a', opsi_b='$opsi_b', opsi_c='$opsi_c', opsi_d='$opsi_d', jawaban='$jawaban'
                  WHERE id_soal='$edit_id' AND id_guru='$idGuru'";
        mysqli_query($conn, $query);
    } else {
        $query = "INSERT INTO soal (id_ujian, id_guru, id_kelas, id_pelajaran, pertanyaan, opsi_a, opsi_b, opsi_c, opsi_d, jawaban)
                  VALUES ('$id_ujian', '$idGuru', '$id_kelas', '$id_pelajaran', '$pertanyaan', '$opsi_a', '$opsi_b', '$opsi_c', '$opsi_d', '$jawaban')";
        mysqli_query($conn, $query);
    }

    header("Location: create_exam.php");
    exit();
}

// Hapus
if (isset($_GET['hapus'])) {
    $hapus_id = $_GET['hapus'];
    mysqli_query($conn, "DELETE FROM soal WHERE id_soal = $hapus_id AND id_guru = $idGuru");
    header("Location: create_exam.php");
    exit();
}

// Ambil semua soal guru
$result_soal = mysqli_query($conn, "SELECT * FROM soal WHERE id_guru = $idGuru");

// Edit mode
$edit_data = null;
if (isset($_GET['edit'])) {
    $edit_id = $_GET['edit'];
    $edit_query = mysqli_query($conn, "SELECT * FROM soal WHERE id_soal = $edit_id AND id_guru = $idGuru");
    $edit_data = mysqli_fetch_assoc($edit_query);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Buat Soal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.snow.css" rel="stylesheet">
    <style>
        .ql-toolbar .ql-audio::before {
            content: '🔊';
        }
    </style>
</head>
<body>
<div class="container mt-5">
    <h2><?= $edit_data ? 'Edit Soal' : 'Buat Soal Ujian' ?></h2>
    <form method="POST" onsubmit="return submitForm()">
        <input type="hidden" name="pertanyaan" id="pertanyaan">
        <?php if ($edit_data): ?>
            <input type="hidden" name="edit_id_soal" value="<?= $edit_data['id_soal']; ?>">
        <?php endif; ?>

        <div class="mb-3">
            <label>Kelas</label>
            <select name="id_kelas" class="form-select" required>
                <?php while ($row = mysqli_fetch_assoc($result_kelas)) { ?>
                    <option value="<?= $row['id'] ?>" <?= $edit_data && $edit_data['id_kelas'] == $row['id'] ? 'selected' : '' ?>>
                        <?= $row['nama_kelas'] ?>
                    </option>
                <?php } ?>
            </select>
        </div>

        <div class="mb-3">
            <label>Mata Pelajaran</label>
            <select name="id_pelajaran" class="form-select" required>
                <?php mysqli_data_seek($result_pelajaran, 0); while ($row = mysqli_fetch_assoc($result_pelajaran)) { ?>
                    <option value="<?= $row['id'] ?>" <?= $edit_data && $edit_data['id_pelajaran'] == $row['id'] ? 'selected' : '' ?>>
                        <?= $row['nama_pelajaran'] ?>
                    </option>
                <?php } ?>
            </select>
        </div>

        <div class="mb-3">
            <label>Ujian</label>
            <select name="id_ujian" class="form-select" required>
                <?php mysqli_data_seek($result_ujian, 0); while ($row = mysqli_fetch_assoc($result_ujian)) { ?>
                    <option value="<?= $row['id_ujian'] ?>" <?= $edit_data && $edit_data['id_ujian'] == $row['id_ujian'] ? 'selected' : '' ?>>
                        <?= $row['nama_ujian'] ?>
                    </option>
                <?php } ?>
            </select>
        </div>

        <div class="mb-3">
            <label>Pertanyaan</label>
            <div id="editor" style="height: 200px;"><?= $edit_data ? $edit_data['pertanyaan'] : '' ?></div>
        </div>

        <div class="mb-3">
            <label>Opsi A</label>
            <input name="opsi_a" class="form-control" value="<?= $edit_data['opsi_a'] ?? '' ?>" required>
        </div>
        <div class="mb-3">
            <label>Opsi B</label>
            <input name="opsi_b" class="form-control" value="<?= $edit_data['opsi_b'] ?? '' ?>" required>
        </div>
        <div class="mb-3">
            <label>Opsi C</label>
            <input name="opsi_c" class="form-control" value="<?= $edit_data['opsi_c'] ?? '' ?>" required>
        </div>
        <div class="mb-3">
            <label>Opsi D</label>
            <input name="opsi_d" class="form-control" value="<?= $edit_data['opsi_d'] ?? '' ?>" required>
        </div>

        <div class="mb-3">
            <label>Jawaban Benar</label>
            <select name="jawaban" class="form-select" required>
                <?php foreach (['A', 'B', 'C', 'D'] as $opt): ?>
                    <option value="<?= $opt ?>" <?= $edit_data && $edit_data['jawaban'] == $opt ? 'selected' : '' ?>><?= $opt ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <button name="submit_soal" class="btn btn-primary"><?= $edit_data ? 'Update' : 'Tambah' ?></button>
    </form>

    <h3 class="mt-5">Daftar Soal</h3>
    <ul class="list-group mt-3">
        <?php while ($soal = mysqli_fetch_assoc($result_soal)) { ?>
            <li class="list-group-item">
                <div><?= html_entity_decode($soal['pertanyaan']) ?></div>
                <div>A. <?= $soal['opsi_a'] ?></div>
                <div>B. <?= $soal['opsi_b'] ?></div>
                <div>C. <?= $soal['opsi_c'] ?></div>
                <div>D. <?= $soal['opsi_d'] ?></div>
                <div><strong>Jawaban:</strong> <?= $soal['jawaban'] ?></div>
                <a href="?edit=<?= $soal['id_soal'] ?>" class="btn btn-warning btn-sm mt-2">Edit</a>
                <a href="?hapus=<?= $soal['id_soal'] ?>" class="btn btn-danger btn-sm mt-2" onclick="return confirm('Yakin?')">Hapus</a>
            </li>
        <?php } ?>
    </ul>
</div>

<script src="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.min.js"></script>
<script>
    const quill = new Quill('#editor', {
        theme: 'snow',
        modules: {
            toolbar: [
                ['bold', 'italic', 'underline'],
                ['image'],
                ['clean']
            ]
        }
    });

    function submitForm() {
        const isi = quill.root.innerHTML.trim();
        if (!isi || isi === '<p><br></p>') {
            alert('Pertanyaan kosong');
            return false;
        }
        document.getElementById('pertanyaan').value = isi;
        return true;
    }

    quill.getModule('toolbar').addHandler('image', () => {
        const input = document.createElement('input');
        input.type = 'file';
        input.accept = 'image/*,audio/*';
        input.click();

        input.onchange = async () => {
            const file = input.files[0];
            const formData = new FormData();
            formData.append('file', file);

            try {
                const res = await fetch('upload.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();
                if (data.success) {
                    const range = quill.getSelection();
                    if (file.type.startsWith('image/')) {
                        quill.insertEmbed(range.index, 'image', data.url);
                    } else if (file.type.startsWith('audio/')) {
                        quill.insertEmbed(range.index, 'audio', data.url);
                    }
                } else {
                    alert('Upload gagal');
                }
            } catch (e) {
                alert('Upload error: ' + e.message);
            }
        };
    });

    // Register audio blot
    const BlockEmbed = Quill.import('blots/block/embed');
    class AudioBlot extends BlockEmbed {
        static create(value) {
            const node = super.create();
            node.setAttribute('controls', '');
            node.setAttribute('src', value);
            return node;
        }

        static value(node) {
            return node.getAttribute('src');
        }
    }
    AudioBlot.blotName = 'audio';
    AudioBlot.tagName = 'audio';
    Quill.register(AudioBlot);
</script>
</body>
</html>
