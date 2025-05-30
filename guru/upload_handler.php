<?php
$targetDir = "../uploads/";
if (!file_exists($targetDir)) mkdir($targetDir, 0755, true);

if ($_FILES['file']['error'] === UPLOAD_ERR_OK) {
    $ext = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);
    $newName = uniqid() . '.' . $ext;
    $uploadPath = $targetDir . $newName;

    if (move_uploaded_file($_FILES['file']['tmp_name'], $uploadPath)) {
        echo json_encode(['url' => "../uploads/$newName"]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Upload gagal!']);
    }
} else {
    http_response_code(400);
    echo json_encode(['error' => 'File error!']);
}
?>
