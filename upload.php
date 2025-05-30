<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $targetDir = "uploads/";
    if (!is_dir($targetDir)) mkdir($targetDir, 0755, true);

    $fileName = basename($_FILES["file"]["name"]);
    $targetFile = $targetDir . time() . "_" . $fileName;

    if (move_uploaded_file($_FILES["file"]["tmp_name"], $targetFile)) {
        echo json_encode(['success' => true, 'url' => $targetFile]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Upload gagal!']);
    }
}
?>
