<?php
// check-setup.php - Diagnostic script for Hostinger setup

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$results = [
    'timestamp' => date('Y-m-d H:i:s'),
    'server_path' => __DIR__,
    'checks' => []
];

// Check 1: API directory
$results['checks']['api_directory'] = [
    'path' => __DIR__,
    'exists' => true,
    'writable' => is_writable(__DIR__),
    'status' => is_writable(__DIR__) ? 'OK' : 'NOT WRITABLE'
];

// Check 2: Uploads base directory
$uploadsDir = __DIR__ . '/uploads';
$results['checks']['uploads_directory'] = [
    'path' => $uploadsDir,
    'exists' => is_dir($uploadsDir),
    'writable' => is_dir($uploadsDir) ? is_writable($uploadsDir) : false,
    'status' => (is_dir($uploadsDir) && is_writable($uploadsDir)) ? 'OK' : 'NEEDS CREATION/PERMISSIONS'
];

// Check 3: Projects directory
$projectsDir = $uploadsDir . '/projects';
$results['checks']['projects_directory'] = [
    'path' => $projectsDir,
    'exists' => is_dir($projectsDir),
    'writable' => is_dir($projectsDir) ? is_writable($projectsDir) : false,
    'status' => (is_dir($projectsDir) && is_writable($projectsDir)) ? 'OK' : 'NEEDS CREATION/PERMISSIONS'
];

// Check 4: List existing projects
if (is_dir($projectsDir)) {
    $projects = array_diff(scandir($projectsDir), ['.', '..']);
    $results['existing_projects'] = [];
    
    foreach ($projects as $projectId) {
        $projectPath = $projectsDir . '/' . $projectId;
        if (is_dir($projectPath)) {
            $filesPath = $projectPath . '/files';
            $coverPath = $projectPath . '/cover';
            
            $results['existing_projects'][$projectId] = [
                'files_dir' => [
                    'exists' => is_dir($filesPath),
                    'count' => is_dir($filesPath) ? count(array_diff(scandir($filesPath), ['.', '..'])) : 0,
                    'files' => is_dir($filesPath) ? array_diff(scandir($filesPath), ['.', '..']) : []
                ],
                'cover_dir' => [
                    'exists' => is_dir($coverPath),
                    'count' => is_dir($coverPath) ? count(array_diff(scandir($coverPath), ['.', '..'])) : 0,
                    'files' => is_dir($coverPath) ? array_diff(scandir($coverPath), ['.', '..']) : []
                ]
            ];
        }
    }
} else {
    $results['existing_projects'] = 'Projects directory does not exist';
}

// Check 5: PHP files present
$phpFiles = ['index.php', 'upload.php', 'download.php', 'image.php', 'list-files.php', 'delete-file.php'];
$results['checks']['php_files'] = [];
foreach ($phpFiles as $file) {
    $results['checks']['php_files'][$file] = file_exists(__DIR__ . '/' . $file) ? 'OK' : 'MISSING';
}

// Check 6: Server info
$results['server_info'] = [
    'php_version' => PHP_VERSION,
    'upload_max_filesize' => ini_get('upload_max_filesize'),
    'post_max_size' => ini_get('post_max_size'),
    'memory_limit' => ini_get('memory_limit'),
    'max_execution_time' => ini_get('max_execution_time')
];

// Overall status
$allOk = true;
foreach ($results['checks'] as $check) {
    if (is_array($check) && isset($check['status']) && $check['status'] !== 'OK') {
        $allOk = false;
        break;
    }
}
$results['overall_status'] = $allOk ? 'READY' : 'NEEDS ATTENTION';

echo json_encode($results, JSON_PRETTY_PRINT);
