<?php
// upload-images.php - Handle project image uploads (feature image + screenshots)

header('Content-Type: application/json');

error_log("Image upload request received");

// Extract project ID from URL
preg_match('/\/api\/projects\/([^\/]+)\/images/', $_SERVER['REQUEST_URI'], $matches);
$projectId = $matches[1] ?? null;

if (!$projectId) {
    http_response_code(400);
    echo json_encode(['error' => 'Project ID required']);
    exit;
}

$adminUid = $_POST['adminUid'] ?? null;

$maxFileSize = 5 * 1024 * 1024; // 5MB
$allowedMime = ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'image/avif'];
$allowedExt = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'avif'];

// Use /public_html/uploads for public images
$publicUploadsBase = dirname(__DIR__) . '/uploads';
$projectImagesDir = $publicUploadsBase . '/project-images/' . $projectId;

if (!is_dir($projectImagesDir)) {
    $created = @mkdir($projectImagesDir, 0777, true);
    if (!$created) {
        error_log("Failed to create project images dir: $projectImagesDir");
        http_response_code(500);
        echo json_encode(['error' => 'Failed to create image directory']);
        exit;
    }
    @chmod($projectImagesDir, 0777);
}

if (!is_writable($projectImagesDir)) {
    error_log("Project images dir not writable: $projectImagesDir");
    http_response_code(403);
    echo json_encode(['error' => 'Image directory not writable']);
    exit;
}

function save_image_file($fileName, $tmpName, $fileSize, $fileType, $projectImagesDir, $projectId, $maxFileSize, $allowedMime, $allowedExt, $adminUid, $prefix) {
    if ($fileSize > $maxFileSize) {
        return ['error' => 'File too large (max 5MB)'];
    }

    $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExt) && !in_array($fileType, $allowedMime)) {
        return ['error' => 'Invalid image type'];
    }

    $safeBase = preg_replace('/[^a-zA-Z0-9._-]/', '_', pathinfo($fileName, PATHINFO_FILENAME));
    $safeBase = preg_replace('/_+/', '_', $safeBase);
    $timeStamp = time();
    $finalName = $prefix . '_' . $timeStamp . '_' . $safeBase . '.' . $ext;
    $targetPath = $projectImagesDir . '/' . $finalName;

    if (!move_uploaded_file($tmpName, $targetPath)) {
        return ['error' => 'Failed to move uploaded file'];
    }

    @chmod($targetPath, 0644);

    $publicPath = '/uploads/project-images/' . $projectId . '/' . $finalName;

    return [
        'name' => $fileName,
        'filename' => $finalName,
        'size' => $fileSize,
        'type' => $fileType ?: $ext,
        'path' => $publicPath,
        'url' => $publicPath,
        'uploadedAt' => date('c'),
        'uploadedBy' => $adminUid
    ];
}

$featureImageMeta = null;
$screenshotMetas = [];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['featureImage'])) {
        $file = $_FILES['featureImage'];
        if ($file['error'] === UPLOAD_ERR_OK) {
            $result = save_image_file(
                $file['name'],
                $file['tmp_name'],
                $file['size'],
                $file['type'],
                $projectImagesDir,
                $projectId,
                $maxFileSize,
                $allowedMime,
                $allowedExt,
                $adminUid,
                'feature'
            );
            if (isset($result['error'])) {
                $errors[] = 'Feature image: ' . $result['error'];
            } else {
                $featureImageMeta = $result;
            }
        } else {
            $errors[] = 'Feature image upload error: ' . $file['error'];
        }
    }

    if (isset($_FILES['screenshots'])) {
        $files = $_FILES['screenshots'];
        $count = is_array($files['name']) ? count($files['name']) : 1;

        for ($i = 0; $i < $count; $i++) {
            $name = is_array($files['name']) ? $files['name'][$i] : $files['name'];
            $tmp = is_array($files['tmp_name']) ? $files['tmp_name'][$i] : $files['tmp_name'];
            $size = is_array($files['size']) ? $files['size'][$i] : $files['size'];
            $type = is_array($files['type']) ? $files['type'][$i] : $files['type'];
            $err = is_array($files['error']) ? $files['error'][$i] : $files['error'];

            if ($err !== UPLOAD_ERR_OK) {
                $errors[] = 'Screenshot upload error: ' . $err;
                continue;
            }

            $result = save_image_file(
                $name,
                $tmp,
                $size,
                $type,
                $projectImagesDir,
                $projectId,
                $maxFileSize,
                $allowedMime,
                $allowedExt,
                $adminUid,
                'screenshot'
            );

            if (isset($result['error'])) {
                $errors[] = 'Screenshot ' . ($i + 1) . ': ' . $result['error'];
            } else {
                $screenshotMetas[] = $result;
            }
        }
    }

    if (!$featureImageMeta && count($screenshotMetas) === 0) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'No images uploaded',
            'errors' => $errors
        ]);
        exit;
    }

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'featureImage' => $featureImageMeta,
        'screenshots' => $screenshotMetas,
        'errors' => $errors,
        'projectId' => $projectId
    ]);
    exit;
}

http_response_code(400);
echo json_encode(['error' => 'Invalid request method']);
?>
