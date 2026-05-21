<?php
/**
 * Pdakoo API Diagnostics
 * Upload this to /public_html/api/diagnose.php to test your setup
 * Then visit: https://pdakoo.in/api/diagnose.php
 */
?>
<!DOCTYPE html>
<html>
<head>
    <title>Pdakoo API Diagnostics</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #1a1a1a; color: #ccc; }
        .container { max-width: 900px; margin: 0 auto; background: #2a2a2a; padding: 20px; border-radius: 8px; }
        h1 { color: #0891b2; }
        .section { margin: 20px 0; padding: 15px; border-left: 3px solid #0891b2; background: #333; }
        .check { display: flex; align-items: center; margin: 10px 0; }
        .icon { margin-right: 10px; font-weight: bold; }
        .pass { color: #10b981; }
        .fail { color: #ef4444; }
        .warn { color: #f59e0b; }
        code { background: #1a1a1a; padding: 2px 6px; border-radius: 3px; margin: 0 4px; }
        pre { background: #1a1a1a; padding: 15px; border-radius: 5px; overflow-x: auto; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Pdakoo API Diagnostics</h1>
        <?php
        $pass = '<span class="icon pass">✅</span>';
        $fail = '<span class="icon fail">❌</span>';
        $warn = '<span class="icon warn">⚠️</span>';

        echo '<div class="section">';
        echo '<h2>Server Information</h2>';
        
        $checks = [
            'PHP Version' => phpversion(),
            'Server Software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'Operating System' => PHP_OS,
            'Current Directory' => __DIR__,
            'Document Root' => $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown'
        ];
        
        foreach ($checks as $name => $value) {
            echo "<div class='check'>$pass <strong>$name:</strong> <code>$value</code></div>";
        }
        echo '</div>';

        // Check mod_rewrite
        echo '<div class="section">';
        echo '<h2>Apache Modules</h2>';
        
        if (function_exists('apache_get_modules')) {
            $modules = apache_get_modules();
            $modRewrite = in_array('mod_rewrite', $modules);
            $status = $modRewrite ? $pass : $fail;
            echo "<div class='check'>$status <strong>mod_rewrite:</strong> " . ($modRewrite ? "✅ Enabled" : "❌ Disabled - Contact Hostinger support") . "</div>";
        } else {
            echo "<div class='check'>$warn <strong>mod_rewrite:</strong> Cannot detect (but API is running, so likely enabled)</div>";
        }
        echo '</div>';

        // Check file permissions
        echo '<div class="section">';
        echo '<h2>File & Directory Permissions</h2>';

        $dirs_to_check = [
            __DIR__ => 'api/',
            __DIR__ . '/uploads' => 'api/uploads/',
            __DIR__ . '/uploads/projects' => 'api/uploads/projects/',
            dirname(__DIR__) => 'public_html/'
        ];

        foreach ($dirs_to_check as $path => $name) {
            if (is_dir($path)) {
                $perms = substr(sprintf('%o', fileperms($path)), -3);
                $writable = is_writable($path);
                $status = $writable ? $pass : $fail;
                echo "<div class='check'>$status <strong>$name</strong> - Permissions: <code>$perms</code> - Writable: " . ($writable ? "Yes" : "No") . "</div>";
            } else {
                echo "<div class='check'>$fail <strong>$name</strong> - <span class='fail'>Does not exist</span></div>";
            }
        }
        echo '</div>';

        // Check .htaccess presence
        echo '<div class="section">';
        echo '<h2>.htaccess Files</h2>';

        $htaccess_files = [
            __DIR__ . '/.htaccess' => 'api/.htaccess',
            dirname(__DIR__) . '/.htaccess' => 'public_html/.htaccess'
        ];

        foreach ($htaccess_files as $path => $name) {
            if (file_exists($path)) {
                $size = filesize($path);
                echo "<div class='check'>$pass <strong>$name</strong> - Exists <code>$size bytes</code></div>";
            } else {
                echo "<div class='check'>$fail <strong>$name</strong> - <span class='fail'>Missing</span></div>";
            }
        }
        echo '</div>';

        // Test directory creation/writing
        echo '<div class="section">';
        echo '<h2>Write Test</h2>';

        $testFile = __DIR__ . '/uploads/test_' . time() . '.txt';
        $testContent = 'Pdakoo write test - ' . date('Y-m-d H:i:s');
        
        $writeSuccess = @file_put_contents($testFile, $testContent);
        if ($writeSuccess) {
            echo "<div class='check'>$pass <strong>File Write Test:</strong> Successfully wrote test file</div>";
            @unlink($testFile);
        } else {
            echo "<div class='check'>$fail <strong>File Write Test:</strong> <span class='fail'>Failed to write file to uploads directory</span></div>";
        }
        echo '</div>';

        // Check PHP settings
        echo '<div class="section">';
        echo '<h2>PHP Configuration</h2>';

        $settings = [
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'max_execution_time' => ini_get('max_execution_time') . 's',
            'file_uploads' => ini_get('file_uploads') ? 'On' : 'Off',
            'display_errors' => ini_get('display_errors') ? 'On' : 'Off'
        ];

        foreach ($settings as $setting => $value) {
            echo "<div class='check'>$pass <strong>$setting:</strong> <code>$value</code></div>";
        }
        echo '</div>';

        // API endpoints
        echo '<div class="section">';
        echo '<h2>Available Endpoints</h2>';
        echo '<pre>GET  /api/health                      → API status
POST /api/projects/{id}/upload        → Upload files
GET  /api/projects/{id}/files         → List files
GET  /api/projects/{id}/files/{name}  → Download file
DEL  /api/projects/{id}/files/{name}  → Delete file</pre>';
        echo '</div>';

        // Instructions
        echo '<div class="section">';
        echo '<h2>✅ Next Steps</h2>';
        echo '<ol>';
        echo '<li>If all checks are <span class="pass">✅ Pass</span>, your API should be working</li>';
        echo '<li>Go to <code>https://pdakoo.in/admin/projects/new</code> and test file upload</li>';
        echo '<li>If upload fails, check browser console (F12) for detailed error messages</li>';
        echo '<li>If directory permissions show <span class="fail">❌ No</span>, contact Hostinger support</li>';
        echo '<li>After testing, <strong>delete this file</strong> for security: <code>rm /api/diagnose.php</code></li>';
        echo '</ol>';
        echo '</div>';

        // Debug info
        echo '<div class="section">';
        echo '<h2>Debug Information</h2>';
        echo '<pre>';
        echo 'Timestamp: ' . date('Y-m-d H:i:s');
        echo "\n";
        echo 'Request Method: ' . $_SERVER['REQUEST_METHOD'];
        echo "\n";
        echo 'Request URL: ' . $_SERVER['REQUEST_URI'];
        echo "\n";
        echo 'User Agent: ' . $_SERVER['HTTP_USER_AGENT'];
        echo '</pre>';
        echo '</div>';
        ?>
    </div>
</body>
</html>