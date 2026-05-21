<?php
// image.php - Serve project feature images

// CORS headers for images
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, HEAD, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Project ID and filename are passed from index.php
if (!isset($projectId) || !$projectId || !isset($imageFilename) || !$imageFilename) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Project ID and filename required']);
    exit;
}

error_log("Image request - Project: $projectId, File: $imageFilename");

// Prevent directory traversal and decode filename
$imageFilename = basename(urldecode($imageFilename));
$coverDir = __DIR__ . '/uploads/projects/' . $projectId . '/cover';
$imagePath = $coverDir . '/' . $imageFilename;

error_log("Looking for image at: $imagePath");
error_log("Directory exists: " . (is_dir($coverDir) ? "yes" : "no"));
error_log("File exists: " . (file_exists($imagePath) ? "yes" : "no"));

// Check if file exists
if (!file_exists($imagePath) || !is_file($imagePath)) {
    error_log("Image not found: $imagePath");
    
    // Try to list files in directory for debugging
    if (is_dir($coverDir)) {
        $files = @scandir($coverDir);
        error_log("Files in directory: " . json_encode($files));
        
        // Try case-insensitive search
        if ($files) {
            foreach ($files as $file) {
                if (strtolower($file) === strtolower($imageFilename)) {
                    $imagePath = $coverDir . '/' . $file;
                    error_log("Found image with different case: $imagePath");
                    if (file_exists($imagePath) && is_file($imagePath)) {
                        break;
                    }
                }
            }
        }
    } else {
        error_log("Directory does not exist: $coverDir");
    }
    
    // If still not found, return 404
    if (!file_exists($imagePath) || !is_file($imagePath)) {
        http_response_code(404);
        header('Content-Type: application/json');
        echo json_encode([
            'error' => 'Image not found',
            'requested_file' => $imageFilename,
            'expected_path' => $imagePath
        ]);
        exit;
    }
}

// Check if file is readable
if (!is_readable($imagePath)) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Image is not readable']);
    error_log("Image not readable: $imagePath");
    exit;
}

// Get file info
$fileSize = filesize($imagePath);
if ($fileSize === false) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Could not get file size']);
    exit;
}

// Determine content type based on file extension
$ext = strtolower(pathinfo($imagePath, PATHINFO_EXTENSION));
$contentTypes = [
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png' => 'image/png',
    'gif' => 'image/gif',
    'webp' => 'image/webp',
    'svg' => 'image/svg+xml',
    'ico' => 'image/x-icon'
];

$contentType = $contentTypes[$ext] ?? 'application/octet-stream';

// Set image headers
header('Content-Type: ' . $contentType);
header('Content-Length: ' . $fileSize);
header('Cache-Control: public, max-age=31536000'); // 1 year
header('Expires: ' . gmdate('D, d M Y H:i:s T', time() + 31536000));
header('X-Content-Type-Options: nosniff');

// Handle HEAD requests
if ($_SERVER['REQUEST_METHOD'] === 'HEAD') {
    exit;
}

// Stream the image
error_log("Serving image: $imagePath");
readfile($imagePath);
exit;
