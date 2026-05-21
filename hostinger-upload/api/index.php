<?php
// Pdakoo File API - PHP Backend

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS, PUT');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle OPTIONS requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Create MAIN uploads directory
$mainUploadsDir = __DIR__ . '/uploads';
if (!is_dir($mainUploadsDir)) {
    @mkdir($mainUploadsDir, 0777, true);
}
@chmod($mainUploadsDir, 0777);

// Create projects subdirectory
$projectsDir = $mainUploadsDir . '/projects';
if (!is_dir($projectsDir)) {
    @mkdir($projectsDir, 0777, true);
}
@chmod($projectsDir, 0777);

// Get request path
$request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$request_method = $_SERVER['REQUEST_METHOD'];

// Check for ?route= query parameter first (for when mod_rewrite fails)
if (isset($_GET['route'])) {
    $path = trim($_GET['route'], '/');
} else {
    // Remove /api prefix if present
    $path = str_replace('/api/', '', $request_uri);
    $path = trim($path, '/');
}

// ---------------------
// Health Check
// ---------------------
if ($path === 'health' || $path === 'health/') {
    http_response_code(200);
    echo json_encode([
        'status' => 'OK',
        'message' => 'Pdakoo File Upload API (PHP) is running',
        'timestamp' => date('c'),
        'version' => '1.1',
        'uploads_dir' => is_writable($mainUploadsDir) ? 'writable' : 'not_writable'
    ]);
    exit;
}

// ---------------------
// Upload Files
// POST /api/projects/{projectId}/upload
// ---------------------
if (preg_match('#^projects/([^/]+)/upload/?$#', $path, $matches) && $request_method === 'POST') {
    $projectId = $matches[1];
    include __DIR__ . '/upload.php';
    exit;
}

// ---------------------
// List Files
// GET /api/projects/{projectId}/files
// ---------------------
if (preg_match('#^projects/([^/]+)/files/?$#', $path, $matches) && $request_method === 'GET') {
    $projectId = $matches[1];
    include __DIR__ . '/list-files.php';
    exit;
}

// ---------------------
// Download File
// GET /api/projects/{projectId}/files/{filename}
// ---------------------
if (preg_match('#^projects/([^/]+)/files/(.+)$#', $path, $matches) && $request_method === 'GET') {
    $projectId = $matches[1];
    $filename = $matches[2];
    include __DIR__ . '/download.php';
    exit;
}

// ---------------------
// Delete File
// DELETE /api/projects/{projectId}/files/{filename}
// ---------------------
if (preg_match('#^projects/([^/]+)/files/(.+)$#', $path, $matches) && $request_method === 'DELETE') {
    $projectId = $matches[1];
    $filename = $matches[2];
    include __DIR__ . '/delete-file.php';
    exit;
}

// ---------------------
// Send Enrollment Email
// POST /api/enrollment/send-enrollment-email
// ---------------------
if (preg_match('#^enrollment/send-enrollment-email/?$#', $path) && $request_method === 'POST') {
    include __DIR__ . '/send-enrollment-email.php';
    exit;
}

// ---------------------
// Verify Email Service
// GET /api/enrollment/verify-email-service
// ---------------------
if (preg_match('#^enrollment/verify-email-service/?$#', $path)) {
    include __DIR__ . '/send-enrollment-email.php';
    exit;
}

// ---------------------
// Test Email Endpoint
// POST /api/enrollment/test-email
// ---------------------
if (preg_match('#^enrollment/test-email/?$#', $path) && $request_method === 'POST') {
    include __DIR__ . '/send-enrollment-email.php';
    exit;
}

// ---------------------
// Send Email Verification
// POST /api/verification/send
// ---------------------
if (preg_match('#^verification/send/?$#', $path) && $request_method === 'POST') {
    include __DIR__ . '/email-verification.php';
    exit;
}

// ---------------------
// Verify Email Token
// GET /api/verification/verify?token=xxx
// ---------------------
if (preg_match('#^verification/verify/?$#', $path) && $request_method === 'GET') {
    include __DIR__ . '/email-verification.php';
    exit;
}

// ---------------------
// Get Image
// GET /api/projects/{projectId}/image/{filename}
// ---------------------
if (preg_match('#^projects/([^/]+)/image/(.+)$#', $path, $matches) && $request_method === 'GET') {
    $projectId = $matches[1];
    $imageFilename = $matches[2];
    include __DIR__ . '/image.php';
    exit;
}

// ---------------------
// Get Image (Direct path)
// GET /api/uploads/projects/{projectId}/cover/{filename}
// ---------------------
if (preg_match('#^uploads/projects/([^/]+)/cover/(.+)$#', $path, $matches) && $request_method === 'GET') {
    $projectId = $matches[1];
    $imageFilename = $matches[2];
    include __DIR__ . '/image.php';
    exit;
}

// ---------------------
// Fix Missing Files Diagnostic
// GET /api/fix-missing-files
// ---------------------
if (preg_match('#^fix-missing-files/?$#', $path) && $request_method === 'GET') {
    include __DIR__ . '/fix-missing-files.php';
    exit;
}

// ---------------------
// Contact Form
// POST /api/contact-form
// ---------------------
if (preg_match('#^contact-form/?$#', $path) && $request_method === 'POST') {
    include __DIR__ . '/contact-form.php';
    exit;
}

// ---------------------
// 404 - Not Found
// ---------------------
http_response_code(404);
echo json_encode([
    'error' => 'Not found',
    'path' => $path,
    'method' => $request_method,
    'allowed_endpoints' => [
        'GET /api/health',
        'POST /api/projects/{id}/upload',
        'GET /api/projects/{id}/files',
        'GET /api/projects/{id}/files/{filename}',
        'DELETE /api/projects/{id}/files/{filename}',
        'GET /api/projects/{id}/image/{filename}',
        'GET /api/uploads/projects/{id}/cover/{filename}',
        'POST /api/enrollment/send-enrollment-email',
        'GET /api/enrollment/verify-email-service',
        'POST /api/enrollment/test-email',
        'POST /api/contact-form',
        'GET /api/fix-missing-files'
    ]
]);
?>
