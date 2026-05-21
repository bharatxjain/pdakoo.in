<?php
/**
 * Directory Initialization Script
 * Run this once to create all required directories on Hostinger
 * Access: https://api.pdakoo.in/init-directories.php
 */

header('Content-Type: application/json; charset=utf-8');

$results = [
    'timestamp' => date('Y-m-d H:i:s'),
    'server_path' => __DIR__,
    'directories_created' => [],
    'directories_failed' => [],
    'errors' => []
];

function createDirectory($path, &$results) {
    if (is_dir($path)) {
        $results['directories_created'][$path] = 'Already exists';
        return true;
    }

    $created = @mkdir($path, 0777, true);
    if ($created) {
        @chmod($path, 0777);
        $results['directories_created'][$path] = 'Created successfully';
        return true;
    } else {
        $results['directories_failed'][$path] = 'Failed to create';
        $error = error_get_last();
        if ($error) {
            $results['errors'][] = $error['message'];
        }
        return false;
    }
}

try {
    // Create base uploads directory
    $baseUploadsDir = __DIR__ . '/uploads';
    createDirectory($baseUploadsDir, $results);

    // Create projects directory
    $projectsDir = $baseUploadsDir . '/projects';
    createDirectory($projectsDir, $results);

    // Verify/create common project directories for testing
    $testProject = $projectsDir . '/test-project';
    createDirectory($testProject, $results);
    createDirectory($testProject . '/files', $results);
    createDirectory($testProject . '/cover', $results);

    // Test write permissions
    $results['permission_tests'] = [];
    
    $testFile = $baseUploadsDir . '/.write-test';
    if (@file_put_contents($testFile, 'test')) {
        @unlink($testFile);
        $results['permission_tests'][$baseUploadsDir] = 'Writable ✓';
    } else {
        $results['permission_tests'][$baseUploadsDir] = 'NOT WRITABLE ✗';
        $results['errors'][] = "Uploads directory is not writable. Contact hosting support.";
    }

    $testFile = $projectsDir . '/.write-test';
    if (@file_put_contents($testFile, 'test')) {
        @unlink($testFile);
        $results['permission_tests'][$projectsDir] = 'Writable ✓';
    } else {
        $results['permission_tests'][$projectsDir] = 'NOT WRITABLE ✗';
        $results['errors'][] = "Projects directory is not writable. Contact hosting support.";
    }

    // Final status check
    $results['final_status'] = [
        'uploads_dir_exists' => is_dir($baseUploadsDir),
        'uploads_dir_writable' => is_writable($baseUploadsDir),
        'projects_dir_exists' => is_dir($projectsDir),
        'projects_dir_writable' => is_writable($projectsDir),
    ];

    $allReady = (
        is_dir($baseUploadsDir) && 
        is_writable($baseUploadsDir) && 
        is_dir($projectsDir) && 
        is_writable($projectsDir)
    );

    $results['status'] = $allReady ? 'READY FOR UPLOADS ✓' : 'NEEDS ATTENTION ✗';
    $results['message'] = $allReady 
        ? 'All directories created and permissions verified. Ready for file uploads!' 
        : 'Some directories could not be created or are not writable. Check errors above.';

} catch (Exception $e) {
    $results['status'] = 'ERROR';
    $results['message'] = $e->getMessage();
    $results['errors'][] = $e->getMessage();
}

http_response_code(200);
echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

?>
