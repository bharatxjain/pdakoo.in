<?php
/**
 * Pdakoo Email Notification API - PHP Backend
 * Handles enrollment email notifications
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle OPTIONS requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Load environment variables
function loadEnv() {
    $envFile = __DIR__ . '/.env';
    if (file_exists($envFile)) {
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) continue;
            list($name, $value) = explode('=', $line, 2);
            $_ENV[trim($name)] = trim($value);
        }
    }
}
loadEnv();

// Get SMTP configuration from environment
$smtp_host = $_ENV['SMTP_HOST'] ?? 'smtp.hostinger.com';
$smtp_port = $_ENV['SMTP_PORT'] ?? 587;
$smtp_user = $_ENV['SMTP_USER'] ?? 'contact@pdakoo.in';
$smtp_pass = $_ENV['SMTP_PASS'] ?? '';
$smtp_from = $_ENV['SMTP_FROM'] ?? 'contact@pdakoo.in';
$admin_email = $_ENV['ADMIN_EMAIL'] ?? 'contact@pdakoo.in';

/**
 * Send email using PHP mail() with SMTP
 */
function sendEmail($to, $subject, $htmlBody, $textBody) {
    global $smtp_from;
    
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: Pdakoo <{$smtp_from}>\r\n";
    $headers .= "Reply-To: {$smtp_from}\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();
    
    $success = mail($to, $subject, $htmlBody, $headers);
    
    return $success;
}

/**
 * Generate user enrollment email HTML
 */
function getUserEnrollmentHTML($studentName, $courseName, $enrollmentDate, $courseUrl) {
    return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="format-detection" content="telephone=no">
    <title>Enrollment Confirmation - Pdakoo</title>
    <style>
        @media only screen and (max-width: 600px) {
            .email-container { width: 100% !important; }
            .header-logo { width: 80px !important; height: 80px !important; }
            .header-title { font-size: 24px !important; }
            .content-padding { padding: 24px !important; }
            .button { padding: 12px 24px !important; font-size: 14px !important; }
            .info-box { padding: 16px !important; }
            .footer-text { font-size: 11px !important; }
        }
    </style>
</head>
<body style="margin:0;padding:0;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;background-color:#0f172a;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#0f172a;padding:20px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color:#1e293b;border-radius:16px;overflow:hidden;">
                    <!-- Header -->
                    <tr>
                        <td style="background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);padding:40px 20px;text-align:center;">
                            <img src="https://pdakoo.in/logo.svg" alt="Pdakoo Logo" class="header-logo" style="width:120px;height:120px;margin:0 auto;display:block;border-radius:16px;background:rgba(255,255,255,0.15);padding:10px;filter:brightness(1.2) saturate(1.1);" />
                            <h1 class="header-title" style="color:#ffffff;margin:20px 0 0;font-size:28px;font-weight:800;letter-spacing:2px;">PDAKOO</h1>
                            <p style="color:#ffffff;margin:10px 0 0;font-size:14px;letter-spacing:1px;font-weight:500;">Premium Code Projects</p>
                        </td>
                    </tr>
                    
                    <!-- Content -->
                    <tr>
                        <td class="content-padding" style="padding:40px;">
                            <h2 style="color:#ffffff;margin:0 0 20px;font-size:24px;">🎉 Enrollment Confirmed!</h2>
                            <p style="color:#cbd5e1;font-size:16px;line-height:1.6;margin:0 0 20px;">
                                Hi <strong style="color:#ffffff;">{$studentName}</strong>,
                            </p>
                            <p style="color:#cbd5e1;font-size:16px;line-height:1.6;margin:0 0 30px;">
                                Congratulations! You've successfully enrolled in:
                            </p>
                            
                            <!-- Course Box -->
                            <div style="background-color:#334155;padding:24px;border-radius:12px;border-left:4px solid #667eea;margin-bottom:30px;">
                                <h3 style="color:#ffffff;margin:0 0 10px;font-size:20px;">{$courseName}</h3>
                                <p style="color:#94a3b8;font-size:14px;margin:0;">
                                    Enrolled on: {$enrollmentDate}
                                </p>
                            </div>
                            
                            <p style="color:#cbd5e1;font-size:16px;line-height:1.6;margin:0 0 30px;">
                                You can now access your project files and start building amazing things!
                            </p>
                            
                            <!-- CTA Button -->
                            <div style="text-align:center;margin-bottom:30px;">
                                <a href="{$courseUrl}" class="button" style="display:inline-block;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);color:#ffffff;text-decoration:none;padding:14px 32px;border-radius:8px;font-weight:700;font-size:16px;min-width:200px;">
                                    View Project
                                </a>
                            </div>
                            
                            <hr style="border:none;border-top:1px solid #334155;margin:30px 0;">
                            
                            <p style="color:#94a3b8;font-size:14px;line-height:1.6;margin:0;">
                                Need help? Contact our support team at <a href="mailto:help@pdakoo.in" style="color:#667eea;text-decoration:none;font-weight:600;">help@pdakoo.in</a>
                            </p>
                        </td>
                    </tr>
                    
                    <!-- Footer -->
                    <tr>
                        <td style="background-color:#0f172a;padding:30px 20px;text-align:center;">
                            <p class="footer-text" style="color:#64748b;font-size:12px;margin:0 0 10px;">
                                © 2026 Pdakoo. All rights reserved.
                            </p>
                            <p class="footer-text" style="color:#64748b;font-size:12px;margin:0 0 10px;">
                                <a href="https://pdakoo.in" style="color:#667eea;text-decoration:none;margin:0 8px;">Visit Website</a> | 
                                <a href="mailto:help@pdakoo.in" style="color:#667eea;text-decoration:none;margin:0 8px;">Contact Support</a>
                            </p>
                            <p class="footer-text" style="color:#64748b;font-size:11px;margin:0;">
                                Email us at <a href="mailto:help@pdakoo.in" style="color:#667eea;text-decoration:none;">help@pdakoo.in</a>
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
}

/**
 * Generate admin notification email HTML
 */
function getAdminEnrollmentHTML($studentName, $studentEmail, $courseName, $enrollmentDate, $amount) {
    $amountText = $amount > 0 ? "₹{$amount}" : "FREE";
    return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="format-detection" content="telephone=no">
    <title>New Enrollment - Pdakoo Admin</title>
    <style>
        @media only screen and (max-width: 600px) {
            .email-container { width: 100% !important; }
            .admin-logo { width: 60px !important; height: 60px !important; }
            .content-padding { padding: 24px !important; }
            .info-table td { display: block !important; width: 100% !important; padding: 4px 0 !important; }
            .footer-text { font-size: 11px !important; }
        }
    </style>
</head>
<body style="margin:0;padding:0;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;background-color:#0f172a;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#0f172a;padding:20px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" class="email-container" style="background-color:#1e293b;border-radius:16px;overflow:hidden;max-width:600px;">
                    <!-- Header -->
                    <tr>
                        <td style="background:linear-gradient(135deg,#f59e0b 0%,#d97706 100%);padding:30px 20px;text-align:center;">
                            <img src="https://pdakoo.in/logo.svg" alt="Pdakoo Logo" class="admin-logo" style="width:80px;height:80px;margin:0 auto 15px;display:block;border-radius:12px;background:rgba(255,255,255,0.15);padding:6px;filter:brightness(1.2) saturate(1.1);" />
                            <h1 style="color:#ffffff;margin:0;font-size:24px;font-weight:800;letter-spacing:1px;">📊 NEW ENROLLMENT</h1>
                        </td>
                    </tr>
                    
                    <!-- Content -->
                    <tr>
                        <td class="content-padding" style="padding:40px;">
                            <h2 style="color:#ffffff;margin:0 0 20px;font-size:20px;">Student Enrolled</h2>
                            
                            <div style="background-color:#334155;padding:24px;border-radius:12px;margin-bottom:20px;">
                                <table width="100%" cellpadding="8" cellspacing="0" class="info-table">\n                                    <tr>
                                        <td style="color:#94a3b8;font-size:14px;width:120px;">Student:</td>
                                        <td style="color:#ffffff;font-size:14px;font-weight:600;">{$studentName}</td>
                                    </tr>
                                    <tr>
                                        <td style="color:#94a3b8;font-size:14px;">Email:</td>
                                        <td style="color:#ffffff;font-size:14px;">{$studentEmail}</td>
                                    </tr>
                                    <tr>
                                        <td style="color:#94a3b8;font-size:14px;">Project:</td>
                                        <td style="color:#ffffff;font-size:14px;font-weight:600;">{$courseName}</td>
                                    </tr>
                                    <tr>
                                        <td style="color:#94a3b8;font-size:14px;">Amount:</td>
                                        <td style="color:#10b981;font-size:16px;font-weight:700;">{$amountText}</td>
                                    </tr>
                                    <tr>
                                        <td style="color:#94a3b8;font-size:14px;">Date:</td>
                                        <td style="color:#ffffff;font-size:14px;">{$enrollmentDate}</td>
                                    </tr>
                                </table>
                            </div>
                        </td>
                    </tr>
                    
                    <!-- Footer -->
                    <tr>
                        <td style="background-color:#0f172a;padding:20px;text-align:center;">
                            <p class="footer-text" style="color:#64748b;font-size:12px;margin:0 0 8px;">
                                Pdakoo Admin Notification System
                            </p>
                            <p class="footer-text" style="color:#64748b;font-size:11px;margin:0;">
                                <a href="https://pdakoo.in" style="color:#667eea;text-decoration:none;margin:0 8px;">Website</a> |
                                <a href="mailto:help@pdakoo.in" style="color:#667eea;text-decoration:none;margin:0 8px;">Support</a>
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
}

// Handle POST request for sending enrollment emails
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validate required fields
    if (!isset($input['studentName']) || !isset($input['studentEmail']) || !isset($input['courseName'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Missing required fields: studentName, studentEmail, courseName'
        ]);
        exit;
    }
    
    $studentName = $input['studentName'];
    $studentEmail = $input['studentEmail'];
    $courseName = $input['courseName'];
    $enrollmentDate = $input['enrollmentDate'] ?? date('F j, Y');
    $courseUrl = $input['courseUrl'] ?? 'https://pdakoo.in';
    $amount = $input['amount'] ?? 0;
    
    // Validate email
    if (!filter_var($studentEmail, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid email address'
        ]);
        exit;
    }
    
    $results = [
        'user' => false,
        'admin' => false
    ];
    
    // Send user email
    $userSubject = "Welcome to {$courseName} - Pdakoo";
    $userHTML = getUserEnrollmentHTML($studentName, $courseName, $enrollmentDate, $courseUrl);
    $results['user'] = sendEmail($studentEmail, $userSubject, $userHTML, strip_tags($userHTML));
    
    // Send admin notification
    $adminSubject = "New Enrollment: {$courseName} - {$studentName}";
    $adminHTML = getAdminEnrollmentHTML($studentName, $studentEmail, $courseName, $enrollmentDate, $amount);
    $results['admin'] = sendEmail($admin_email, $adminSubject, $adminHTML, strip_tags($adminHTML));
    
    $overallSuccess = $results['user'] || $results['admin'];
    
    http_response_code($overallSuccess ? 200 : 500);
    echo json_encode([
        'success' => $overallSuccess,
        'message' => $overallSuccess ? 'Enrollment emails sent successfully' : 'Failed to send enrollment emails',
        'emails' => [
            'user' => $results['user'] ? 'sent' : 'failed',
            'admin' => $results['admin'] ? 'sent' : 'failed'
        ]
    ]);
    exit;
}

// Handle GET request for verification
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Check if mail function is available
    $mailAvailable = function_exists('mail');
    
    http_response_code(200);
    echo json_encode([
        'verified' => $mailAvailable,
        'message' => $mailAvailable ? 'Email service is ready' : 'PHP mail() function not available',
        'smtp_configured' => !empty($smtp_user) && !empty($smtp_pass)
    ]);
    exit;
}

// Method not allowed
http_response_code(405);
echo json_encode([
    'success' => false,
    'message' => 'Method not allowed. Use POST to send emails or GET to verify service.'
]);
?>
