<?php
// delete-file.php - Delete file (admin only)

// Ensure CORS headers are set
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

// Project ID and filename are passed from index.php
if (!isset($projectId) || !$projectId || !isset($filename) || !$filename) {
    http_response_code(400);
    echo json_encode(['error' => 'Project ID and filename required']);
    exit;
}

if (!$projectId || !$filename) {
    http_response_code(400);
    echo json_encode(['error' => 'Project ID and filename required']);
    exit;
}

// Get admin UID from request
$body = json_decode(file_get_contents('php://input'), true);
$adminUid = $body['adminUid'] ?? null;

if (!$adminUid) {
    http_response_code(400);
    echo json_encode(['error' => 'Admin UID required']);
    exit;
}

// For security, verify admin role via Firebase (add later)

// Security: Prevent directory traversal
$filename = basename($filename);
$uploadsDir = __DIR__ . '/uploads/projects/' . $projectId . '/files';
$filePath = $uploadsDir . '/' . $filename;

// Check if file exists
if (!file_exists($filePath) || !is_file($filePath)) {
    http_response_code(404);
    echo json_encode(['error' => 'File not found']);
    exit;
}

// Delete file
if (unlink($filePath)) {
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'File deleted successfully',
        'file' => $filename
    ]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to delete file']);
}
?>
