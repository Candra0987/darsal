<?php
$targetDir = "../uploads/upload";
if (!file_exists($targetDir)) {
    mkdir($targetDir, 0777, true);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['file'])) {
    $file = $_FILES['file'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'mp3', 'wav', 'ogg'];

    if (!in_array($ext, $allowed)) {
        echo json_encode(['success' => false, 'error' => 'Tipe file tidak diperbolehkan.']);
        exit;
    }

    $newName = uniqid() . '.' . $ext;
    $targetFile = $targetDir . $newName;

    if (move_uploaded_file($file['tmp_name'], $targetFile)) {
        echo json_encode([
            'success' => true,
            'url' => $targetFile
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Gagal upload file.']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Tidak ada file yang diupload.']);
}
?>
