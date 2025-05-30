<?php
session_start();

// Cek apakah user login dan role-nya guru
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "guru") {
    http_response_code(403);
    exit("Akses ditolak.");
}

// Ambil jenis template dari parameter GET
$type = $_GET['type'] ?? '';

switch ($type) {
    case 'pg':
        $file_path = "../guru/assets/template/TEMPLATE_FIX/pg.docx";
        $file_name = "template_pg.docx";
        break;
    case 'essay':
        $file_path = "../guru/assets/template/TEMPLATE_FIX/ESSAY2.docx";
        $file_name = "template_ESSAY.docx";
        break;
    default:
        exit("Jenis template tidak valid.");
}

if (file_exists($file_path)) {
    header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
    header("Content-Disposition: attachment; filename=\"$file_name\"");
    header('Content-Length: ' . filesize($file_path));
    readfile($file_path);
    exit;
} else {
    exit("File template tidak ditemukan.");
}
?>
