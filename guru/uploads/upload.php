<?php
// Set response header to JSON
header('Content-Type: application/json');

// Allowed file extensions and MIME types
$allowedExtensions = array('jpg', 'jpeg', 'png', 'mp3', 'wav');
$allowedMimeTypes = array(
    'image/jpeg',
    'image/png',
    'audio/mpeg', // MP3
    'audio/mp3',
    'audio/wav',
    'audio/x-wav',
    'audio/wave',
    'audio/vnd.wave'
);

// Ensure the request is a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(array('error' => 'Invalid request method.'));
    exit;
}

// Check if a file was uploaded
if (!isset($_FILES['file']) || $_FILES['file']['error'] === UPLOAD_ERR_NO_FILE) {
    echo json_encode(array('error' => 'No file uploaded.'));
    exit;
}

$file = $_FILES['file'];

// Check for upload errors
if ($file['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(array('error' => 'File upload error. Code: ' . $file['error']));
    exit;
}

// Validate file extension
$fileName = $file['name'];
$fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
if (!in_array($fileExtension, $allowedExtensions)) {
    echo json_encode(array('error' => 'Invalid file extension. Only JPG, JPEG, PNG, MP3, WAV are allowed.'));
    exit;
}

// Validate MIME type
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mimeType = $finfo->file($file['tmp_name']);
if (!in_array($mimeType, $allowedMimeTypes)) {
    echo json_encode(array('error' => 'Invalid file type. Only image and audio files are allowed.'));
    exit;
}

// Additional check for image files to ensure they are valid images
if (in_array($fileExtension, array('jpg', 'jpeg', 'png'))) {
    $imageInfo = getimagesize($file['tmp_name']);
    if ($imageInfo === false) {
        echo json_encode(array('error' => 'The uploaded file is not a valid image.'));
        exit;
    }
}

// Define upload directory path
$uploadDir = __DIR__ . '/uploads/';

// Create the uploads directory if it doesn't exist
if (!is_dir($uploadDir)) {
    if (!mkdir($uploadDir, 0755, true)) {
        echo json_encode(array('error' => 'Failed to create upload directory.'));
        exit;
    }
}

// Generate a unique file name to avoid conflicts
$uniqueName = uniqid('', true) . '.' . $fileExtension;
$targetFilePath = $uploadDir . $uniqueName;

// Move the uploaded file to the target directory
if (!move_uploaded_file($file['tmp_name'], $targetFilePath)) {
    echo json_encode(array('error' => 'Failed to save the uploaded file.'));
    exit;
}

// Build the URL of the uploaded file (assuming upload.php is in project root)
$fileUrl = 'uploads/' . $uniqueName;

// Return the file URL in JSON response
echo json_encode(array('url' => $fileUrl));
exit;
?>
