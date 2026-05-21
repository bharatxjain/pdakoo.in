<?php
/**
 * Diagnose and Fix Missing Project Files
 * Identifies projects with file metadata but missing physical files
 */

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

try {
    $action = $_GET['action'] ?? 'diagnose';
    $uploadsDir = __DIR__ . '/uploads/projects';
    
    if (!is_dir($uploadsDir)) {
        echo json_encode([
            'error' => 'Uploads directory does not exist',
            'path' => $uploadsDir,
            'suggestion' => 'Create the directory and upload files via admin panel'
        ]);
        exit;
    }
    
    // Scan all project directories
    $projects = scandir($uploadsDir);
    $results = [];
    $totalProjects = 0;
    $totalDirectories = 0;
    $totalFiles = 0;
    
    foreach ($projects as $projectId) {
        if ($projectId === '.' || $projectId === '..') continue;
        
        $projectPath = "$uploadsDir/$projectId";
        if (!is_dir($projectPath)) continue;
        
        $totalProjects++;
        $filesDir = "$projectPath/files";
        $coverDir = "$projectPath/cover";
        
        $filesList = [];
        $coverImages = [];
        $fileCount = 0;
        
        // List files
        if (is_dir($filesDir)) {
            $totalDirectories++;
            $items = scandir($filesDir);
            foreach ($items as $item) {
                if ($item === '.' || $item === '..') continue;
                if (is_file("$filesDir/$item")) {
                    $fileCount++;
                    $totalFiles++;
                    $filesList[] = [
                        'filename' => $item,
                        'size' => filesize("$filesDir/$item"),
                        'path' => "/uploads/projects/$projectId/files/$item",
                        'exists' => true
                    ];
                }
            }
        }
        
        // List cover images
        if (is_dir($coverDir)) {
            $items = scandir($coverDir);
            foreach ($items as $item) {
                if ($item === '.' || $item === '..') continue;
                if (is_file("$coverDir/$item")) {
                    $coverImages[] = [
                        'filename' => $item,
                        'size' => filesize("$coverDir/$item"),
                        'path' => "/uploads/projects/$projectId/cover/$item"
                    ];
                }
            }
        }
        
        $results[] = [
            'projectId' => $projectId,
            'filesDirectory' => [
                'exists' => is_dir($filesDir),
                'writable' => is_dir($filesDir) && is_writable($filesDir),
                'fileCount' => $fileCount,
                'files' => $filesList
            ],
            'coverDirectory' => [
                'exists' => is_dir($coverDir),
                'coverImages' => $coverImages
            ],
            'apiEndpoint' => "/api/projects/$projectId/files"
        ];
    }
    
    echo json_encode([
        'success' => true,
        'action' => $action,
        'summary' => [
            'totalProjectDirectories' => $totalProjects,
            'totalDirectoriesWithFiles' => $totalDirectories,
            'totalFilesFound' => $totalFiles,
            'uploadsPath' => $uploadsDir,
            'timestamp' => date('Y-m-d H:i:s')
        ],
        'projects' => $results,
        'instructions' => [
            'createDirectory' => 'mkdir -p /uploads/projects/{projectId}/files/',
            'uploadFiles' => 'POST /api/projects/{projectId}/upload',
            'checkFiles' => 'GET /api/projects/{projectId}/files',
            'downloadFile' => 'GET /api/projects/{projectId}/files/{filename}?uid={userId}'
        ]
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    error_log("Fix missing files error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to diagnose missing files',
        'message' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}
