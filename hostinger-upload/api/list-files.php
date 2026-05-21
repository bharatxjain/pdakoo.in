<?php
// list-files.php - List all files for a project

// Ensure CORS headers are set
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

// Project ID is passed from index.php
if (!isset($projectId) || !$projectId) {
    http_response_code(400);
    echo json_encode(['error' => 'Project ID required']);
    exit;
}

if (!$projectId) {
    http_response_code(400);
    echo json_encode(['error' => 'Project ID required']);
    exit;
}

$uploadsDir = __DIR__ . '/uploads/projects/' . $projectId . '/files';

if (!is_dir($uploadsDir)) {
    http_response_code(200);
    echo json_encode([
        'projectId' => $projectId,
        'files' => []
    ]);
    exit;
}

$files = [];
$items = scandir($uploadsDir);

foreach ($items as $item) {
    if ($item === '.' || $item === '..') {
        continue;
    }

    $filePath = $uploadsDir . '/' . $item;
    
    if (is_file($filePath)) {
        $ext = strtolower(pathinfo($item, PATHINFO_EXTENSION));
        $displayName = preg_replace('/^\d+_/', '', $item);
        $files[] = [
            'name' => $displayName,
            'filename' => $item,
            'size' => filesize($filePath),
            'type' => $ext,
            'path' => '/uploads/projects/' . $projectId . '/files/' . $item,
            'uploadedAt' => date('c', filemtime($filePath)),
            'downloadUrl' => '/api/projects/' . $projectId . '/files/' . urlencode($item)
        ];
    }
}

http_response_code(200);
echo json_encode([
    'projectId' => $projectId,
    'files' => $files,
    'count' => count($files)
]);
?>
