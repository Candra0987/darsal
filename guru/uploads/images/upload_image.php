<?php
// upload_image.php
session_start();
require '../config/db.php';
// Atur direktori tujuan
$targetDir = "../guru/uploads//images/";
if (!is_dir($targetDir)) {
    mkdir($targetDir, 0777, true); // Buat folder jika belum ada
}

// Periksa apakah file dikirim via POST
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_FILES["file"])) {
        $file = $_FILES["file"];
        $allowedTypes = ["image/jpeg", "image/png", "image/gif", "image/webp"];
        $fileType = mime_content_type($file["tmp_name"]);

        // Validasi tipe file
        if (!in_array($fileType, $allowedTypes)) {
            http_response_code(400);
            echo json_encode(["error" => "Format gambar tidak diizinkan."]);
            exit;
        }

        // Generate nama unik
        $fileName = uniqid("img_") . "_" . basename($file["name"]);
        $targetFile = $targetDir . $fileName;

        // Pindahkan file
        if (move_uploaded_file($file["tmp_name"], $targetFile)) {
            echo json_encode(["url" => $targetFile]);
        } else {
            http_response_code(500);
            echo json_encode(["error" => "Gagal mengunggah gambar."]);
        }
    } else {
        http_response_code(400);
        echo json_encode(["error" => "Tidak ada file yang dikirim."]);
    }
} else {
    http_response_code(405);
    echo json_encode(["error" => "Metode tidak diizinkan."]);
}
