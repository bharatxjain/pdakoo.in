<?php
// upload.php - Handle file uploads

// Ensure CORS headers are set
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

error_log("Upload request received");

// Project ID is passed from index.php
if (!isset($projectId) || !$projectId) {
    http_response_code(400);
    echo json_encode(['error' => 'Project ID required']);
    exit;
}

// Get admin UID from request (from form data, not JSON)
$adminUid = $_POST['adminUid'] ?? null;

if (!$adminUid) {
    http_response_code(400);
    echo json_encode(['error' => 'Admin UID required']);
    exit;
}

error_log("Upload for project: $projectId");

// Create base uploads directory if needed
$baseUploadsDir = __DIR__ . '/uploads';
if (!is_dir($baseUploadsDir)) {
    $created = @mkdir($baseUploadsDir, 0777, true);
    if (!$created) {
        error_log("Failed to create base uploads dir: $baseUploadsDir");
    }
    @chmod($baseUploadsDir, 0777);
}

// Create project uploads directory
$projectDir = __DIR__ . '/uploads/projects/' . $projectId;
$filesDir = $projectDir . '/files';
$coverDir = $projectDir . '/cover';

if (!is_dir($filesDir)) {
    $created = @mkdir($filesDir, 0777, true);
    if (!$created) {
        error_log("Failed to create project files dir: $filesDir");
        http_response_code(500);
        echo json_encode(['error' => 'Failed to create upload directory', 'debug' => "Path: $filesDir"]);
        exit;
    }
    @chmod($filesDir, 0777);
}

if (!is_dir($coverDir)) {
    $created = @mkdir($coverDir, 0777, true);
    if (!$created) {
        error_log("Failed to create project cover dir: $coverDir");
        http_response_code(500);
        echo json_encode(['error' => 'Failed to create cover directory', 'debug' => "Path: $coverDir"]);
        exit;
    }
    @chmod($coverDir, 0777);
}

// Verify directory is writable
if (!is_writable($filesDir)) {
    error_log("Upload directory not writable: $filesDir");
    http_response_code(403);
    echo json_encode(['error' => 'Upload directory not writable', 'debug' => "Permissions issue on: $filesDir"]);
    exit;
}

// Handle file uploads
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_FILES['files']) && !isset($_FILES['coverImage'])) {
        error_log("No files provided in POST request");
        http_response_code(400);
        echo json_encode(['error' => 'No files provided']);
        exit;
    }

    $uploadedFiles = [];
    $coverImage = null;
    $errors = [];
    // Allow all common file types
    $allowedExtensions = [
        'pdf', 'zip', 'rar', '7z', 'tar', 'gz',
        'csv', 'json', 'txt', 'xml',
        'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx',
        'jpg', 'jpeg', 'png', 'webp', 'gif', 'svg', 'bmp',
        'mp4', 'avi', 'mov', 'wmv', 'flv', 'mkv',
        'mp3', 'wav', 'ogg', 'flac',
        'sql', 'db', 'sqlite',
        'html', 'css', 'js', 'ts', 'jsx', 'tsx',
        'py', 'java', 'cpp', 'c', 'h', 'php', 'rb', 'go', 'rs',
        'md', 'markdown', 'rst'
    ];
    $allowedMime = [
        // Documents
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        // Archives
        'application/zip',
        'application/x-rar-compressed',
        'application/x-7z-compressed',
        'application/x-tar',
        'application/gzip',
        // Data
        'text/csv',
        'application/json',
        'text/plain',
        'application/xml',
        'text/xml',
        // Images
        'image/jpeg',
        'image/png',
        'image/webp',
        'image/gif',
        'image/svg+xml',
        'image/bmp',
        'image/jpg',
        // Video
        'video/mp4',
        'video/x-msvideo',
        'video/quicktime',
        'video/x-ms-wmv',
        'video/x-flv',
        'video/x-matroska',
        // Audio
        'audio/mpeg',
        'audio/wav',
        'audio/ogg',
        'audio/flac',
        // Database
        'application/sql',
        'application/x-sqlite3',
        // Code files
        'text/html',
        'text/css',
        'application/javascript',
        'text/javascript',
        'application/typescript',
        'text/x-python',
        'text/x-java-source',
        'text/x-c',
        'text/x-php',
        'text/markdown',
        // Generic
        'application/octet-stream'
    ];
    $maxFileSize = 200 * 1024 * 1024; // 200MB
    $maxImageSize = 5 * 1024 * 1024; // 5MB

    $files = $_FILES['files'] ?? null;
    
    // Handle both single and multiple files
    $fileCount = is_array($files['name']) ? count($files['name']) : 1;

    error_log("Processing $fileCount file(s)");

    if ($files) {
        for ($i = 0; $i < $fileCount; $i++) {
            $fileName = is_array($files['name']) ? $files['name'][$i] : $files['name'];
            $fileTmp = is_array($files['tmp_name']) ? $files['tmp_name'][$i] : $files['tmp_name'];
            $fileSize = is_array($files['size']) ? $files['size'][$i] : $files['size'];
            $fileError = is_array($files['error']) ? $files['error'][$i] : $files['error'];
            $fileType = is_array($files['type']) ? $files['type'][$i] : $files['type'];

            error_log("File $i: $fileName (size: $fileSize, error: $fileError)");

            // Check for upload errors
            if ($fileError !== UPLOAD_ERR_OK) {
                $errors[] = "$fileName: Upload error " . $fileError;
                continue;
            }

            // Validate file size
            if ($fileSize > $maxFileSize) {
                $errors[] = "$fileName: File too large (max 200MB)";
                continue;
            }

            // Get file extension
            $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            
            if (!in_array($ext, $allowedExtensions) && !in_array($fileType, $allowedMime)) {
                $errors[] = "$fileName: Invalid file type (allowed: " . implode(', ', $allowedExtensions) . ")";
                continue;
            }

            // Sanitize filename - keep it readable
            $baseName = pathinfo($fileName, PATHINFO_FILENAME);
            $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $baseName);
            $safeName = preg_replace('/_+/', '_', $safeName); // Remove multiple underscores
            $timeStamp = time();
            $finalName = $timeStamp . '_' . $safeName . '.' . $ext;
            $targetPath = $filesDir . '/' . $finalName;

            error_log("Moving file to: $targetPath");

            // Move uploaded file
            if (move_uploaded_file($fileTmp, $targetPath)) {
                @chmod($targetPath, 0644);
                
                $uploadedFiles[] = [
                    'name' => $fileName,
                    'filename' => $finalName,
                    'originalName' => $fileName,
                    'size' => $fileSize,
                    'type' => $fileType ?: $ext,
                    'path' => '/uploads/projects/' . $projectId . '/files/' . $finalName,
                    'uploadedAt' => date('c'),
                    'uploadedBy' => $adminUid
                ];
                error_log("File uploaded successfully: $finalName");
            } else {
                $errors[] = "$fileName: Failed to move uploaded file to $targetPath";
                error_log("Failed to move: $fileTmp -> $targetPath");
            }
        }
    }

    // Handle cover image upload
    if (isset($_FILES['coverImage'])) {
        $cover = $_FILES['coverImage'];
        $coverName = $cover['name'];
        $coverTmp = $cover['tmp_name'];
        $coverSize = $cover['size'];
        $coverError = $cover['error'];
        $coverType = $cover['type'];

        if ($coverError === UPLOAD_ERR_OK) {
            if ($coverSize > $maxImageSize) {
                $errors[] = "$coverName: Cover image too large (max 5MB)";
            } else {
                $ext = strtolower(pathinfo($coverName, PATHINFO_EXTENSION));
                if (!in_array($ext, $allowedExtensions) && !in_array($coverType, $allowedMime)) {
                    $errors[] = "$coverName: Invalid cover image type";
                } else {
                    $baseName = pathinfo($coverName, PATHINFO_FILENAME);
                    $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $baseName);
                    $safeName = preg_replace('/_+/', '_', $safeName);
                    $timeStamp = time();
                    $finalName = $timeStamp . '_' . $safeName . '.' . $ext;
                    $targetPath = $coverDir . '/' . $finalName;

                    if (move_uploaded_file($coverTmp, $targetPath)) {
                        @chmod($targetPath, 0644);
                        $coverImage = [
                            'name' => $coverName,
                            'filename' => $finalName,
                            'size' => $coverSize,
                            'type' => $coverType ?: $ext,
                            'path' => '/uploads/projects/' . $projectId . '/cover/' . $finalName,
                            'uploadedAt' => date('c'),
                            'uploadedBy' => $adminUid
                        ];
                    } else {
                        $errors[] = "$coverName: Failed to move cover image";
                    }
                }
            }
        } else {
            $errors[] = "$coverName: Cover image upload error " . $coverError;
        }
    }

    // Return response
    http_response_code(200);
    echo json_encode([
        'success' => count($uploadedFiles) > 0 || $coverImage,
        'message' => (count($uploadedFiles) > 0 || $coverImage) ? 'Upload completed' : 'No files uploaded',
        'uploadedCount' => count($uploadedFiles),
        'files' => $uploadedFiles,
        'coverImage' => $coverImage,
        'errors' => $errors,
        'projectId' => $projectId
    ]);
    exit;
}

http_response_code(400);
echo json_encode(['error' => 'Invalid request method']);
?>
