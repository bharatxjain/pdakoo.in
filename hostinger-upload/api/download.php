<?php
// download.php - Handle file downloads with purchase verification

// Ensure CORS headers are set
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Project ID and filename are passed from index.php
if (!isset($projectId) || !$projectId || !isset($filename) || !$filename) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Project ID and filename required']);
    exit;
}

// Get user ID from query params
$userId = $_GET['uid'] ?? null;

if (!$userId) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'User ID required']);
    exit;
}

error_log("Download attempt - Project: $projectId, File: $filename, User: $userId");

// Security: Prevent directory traversal and decode filename
$filename = basename(urldecode($filename));
$uploadsDir = __DIR__ . '/uploads/projects/' . $projectId . '/files';
$filePath = $uploadsDir . '/' . $filename;

error_log("Looking for file at: $filePath");
error_log("Directory exists: " . (is_dir($uploadsDir) ? "yes" : "no"));
error_log("File exists: " . (file_exists($filePath) ? "yes" : "no"));

// Check if file exists
if (!file_exists($filePath) || !is_file($filePath)) {
    error_log("File not found: $filePath");
    
    // Try to list files in directory for debugging
    if (is_dir($uploadsDir)) {
        $files = @scandir($uploadsDir);
        error_log("Files in directory: " . json_encode($files));
        
        // Try case-insensitive search
        if ($files) {
            foreach ($files as $file) {
                if (strtolower($file) === strtolower($filename)) {
                    $filePath = $uploadsDir . '/' . $file;
                    error_log("Found file with different case: $filePath");
                    if (file_exists($filePath) && is_file($filePath)) {
                        break;
                    }
                }
            }
        }
    } else {
        error_log("Directory does not exist: $uploadsDir");
    }
    
    // If still not found, return error
    if (!file_exists($filePath) || !is_file($filePath)) {
        http_response_code(404);
        header('Content-Type: application/json');
        echo json_encode([
            'error' => 'File not found',
            'requested_file' => $filename,
            'expected_path' => $filePath,
            'directory_exists' => is_dir($uploadsDir),
            'directory_contents' => is_dir($uploadsDir) ? @scandir($uploadsDir) : null
        ]);
        exit;
    }
}

// For a complete implementation, verify purchase here
// For now, we'll allow download (add Firebase purchase check later)

// Get original filename from query param or use stored
$originalName = $_GET['name'] ?? $filename;
$originalName = basename(urldecode($originalName)); // Sanitize

error_log("Downloading file: $originalName from $filePath");

// Verify file is readable
if (!is_readable($filePath)) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'File is not readable']);
    error_log("File not readable: $filePath");
    exit;
}

// Get file size
$fileSize = filesize($filePath);
if ($fileSize === false) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unable to determine file size']);
    error_log("Unable to get file size: $filePath");
    exit;
}

error_log("File size: $fileSize bytes");

// Send file
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $originalName . '"');
header('Content-Length: ' . $fileSize);
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Read and output the file
$handle = fopen($filePath, 'rb');
if (!$handle) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unable to open file']);
    error_log("Unable to open file: $filePath");
    exit;
}

while (!feof($handle)) {
    echo fread($handle, 8192);
}
fclose($handle);
exit;
?>

