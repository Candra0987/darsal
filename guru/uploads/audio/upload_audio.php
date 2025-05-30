<?php
// upload_audio.php

// Atur direktori tujuan
$targetDir = "../guru/uploads/audio/upload.audio.php";
if (!is_dir($targetDir)) {
    mkdir($targetDir, 0777, true); // Buat folder jika belum ada
}

// Periksa apakah file dikirim via POST
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_FILES["file"])) {
        $file = $_FILES["file"];
        $allowedTypes = ["audio/mpeg", "audio/mp3", "audio/wav", "audio/ogg", "audio/webm"];
        $fileType = mime_content_type($file["tmp_name"]);

        // Validasi tipe file
        if (!in_array($fileType, $allowedTypes)) {
            http_response_code(400);
            echo json_encode(["error" => "Format audio tidak diizinkan."]);
            exit;
        }

        // Generate nama unik
        $fileName = uniqid("audio_") . "_" . basename($file["name"]);
        $targetFile = $targetDir . $fileName;

        // Pindahkan file
        if (move_uploaded_file($file["tmp_name"], $targetFile)) {
            echo json_encode(["url" => $targetFile]);
        } else {
            http_response_code(500);
            echo json_encode(["error" => "Gagal mengunggah file audio."]);
        }
    } else {
        http_response_code(400);
        echo json_encode(["error" => "Tidak ada file yang dikirim."]);
    }
} else {
    http_response_code(405);
    echo json_encode(["error" => "Metode tidak diizinkan."]);
}
