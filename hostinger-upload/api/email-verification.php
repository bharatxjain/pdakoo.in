<?php
/**
 * Email Verification System - PHP Backend
 * Sends custom verification emails and handles verification
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

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

$smtp_from = $_ENV['SMTP_FROM'] ?? 'contact@pdakoo.in';

/**
 * Send email using PHP mail()
 */
function sendEmail($to, $subject, $htmlBody) {
    global $smtp_from;
    
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: Pdakoo <{$smtp_from}>\r\n";
    $headers .= "Reply-To: {$smtp_from}\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();
    
    return mail($to, $subject, $htmlBody, $headers);
}

/**
 * Generate verification email HTML
 */
function getVerificationEmailHTML($userName, $verificationLink) {
    return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="format-detection" content="telephone=no">
    <title>Verify Your Email - Pdakoo</title>
    <style>
        @media only screen and (max-width: 600px) {
            .email-container { width: 100% !important; }
            .header-logo { width: 80px !important; height: 80px !important; }
            .header-title { font-size: 24px !important; }
            .content-padding { padding: 24px !important; }
            .verify-button { padding: 12px 24px !important; font-size: 14px !important; }
            .info-box { padding: 16px !important; }
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
                        <td style="background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);padding:40px 20px;text-align:center;">
                            <img src="https://pdakoo.in/logo.svg" alt="Pdakoo Logo" class="header-logo" style="width:120px;height:120px;margin:0 auto;display:block;border-radius:16px;background:rgba(255,255,255,0.15);padding:10px;filter:brightness(1.2) saturate(1.1);" />
                            <h1 class="header-title" style="color:#ffffff;margin:20px 0 0;font-size:28px;font-weight:800;letter-spacing:2px;">PDAKOO</h1>
                            <p style="color:#ffffff;margin:10px 0 0;font-size:14px;letter-spacing:1px;font-weight:500;">Premium Code Projects</p>
                        </td>
                    </tr>
                    
                    <!-- Content -->
                    <tr>
                        <td class="content-padding" style="padding:40px;">
                            <h2 style="color:#ffffff;margin:0 0 20px;font-size:24px;">Welcome to Pdakoo! 🎉</h2>
                            <p style="color:#cbd5e1;font-size:16px;line-height:1.6;margin:0 0 20px;">
                                Hi <strong style="color:#ffffff;">{$userName}</strong>,
                            </p>
                            <p style="color:#cbd5e1;font-size:16px;line-height:1.6;margin:0 0 30px;">
                                Thanks for signing up! Please verify your email address to access all features and start downloading premium code projects.
                            </p>
                            
                            <!-- Verification Box -->
                            <div class="info-box" style="background-color:#334155;padding:24px;border-radius:12px;border-left:4px solid #667eea;margin-bottom:30px;text-align:center;">
                                <p style="color:#94a3b8;font-size:14px;margin:0 0 20px;">
                                    Click the button below to verify your email:
                                </p>
                                <a href="{$verificationLink}" class="verify-button" style="display:inline-block;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);color:#ffffff;text-decoration:none;padding:14px 32px;border-radius:8px;font-weight:700;font-size:16px;min-width:200px;">
                                    Verify Email Address
                                </a>
                                <p style="color:#64748b;font-size:12px;margin:20px 0 0;">
                                    Button not working? Copy and paste this link into your browser:
                                </p>
                                <p style="color:#667eea;font-size:12px;margin:10px 0 0;word-break:break-all;">
                                    {$verificationLink}
                                </p>
                            </div>
                            
                            <hr style="border:none;border-top:1px solid #334155;margin:30px 0;">
                            
                            <p style="color:#94a3b8;font-size:14px;line-height:1.6;margin:0 0 10px;">
                                This link will expire in 24 hours. If you didn't create this account, you can safely ignore this email.
                            </p>
                            <p style="color:#94a3b8;font-size:14px;line-height:1.6;margin:0;">
                                Need help? Contact us at <a href="mailto:help@pdakoo.in" style="color:#667eea;text-decoration:none;font-weight:600;">help@pdakoo.in</a>
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

// ===============================================
// SEND VERIFICATION EMAIL ENDPOINT
// POST /api/verification/send
// ===============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['email']) || !isset($input['name']) || !isset($input['uid'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Missing required fields: email, name, uid'
        ]);
        exit;
    }
    
    $email = filter_var($input['email'], FILTER_VALIDATE_EMAIL);
    if (!$email) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid email address'
        ]);
        exit;
    }
    
    $name = $input['name'];
    $uid = $input['uid'];
    
    // Generate verification token (simple approach - use uid + timestamp + hash)
    $token = base64_encode($uid . '|' . time());
    
    // Create verification link
    $verificationLink = "https://pdakoo.in/verify-email?token=" . urlencode($token);
    
    // Send verification email
    $subject = "Verify your email - Pdakoo";
    $html = getVerificationEmailHTML($name, $verificationLink);
    
    $sent = sendEmail($email, $subject, $html);
    
    if ($sent) {
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Verification email sent successfully',
            'token' => $token // Return token so frontend can store it
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to send verification email'
        ]);
    }
    exit;
}

// ===============================================
// VERIFY TOKEN ENDPOINT
// GET /api/verification/verify?token=xxx
// ===============================================
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (!isset($_GET['token'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Missing verification token'
        ]);
        exit;
    }
    
    $token = $_GET['token'];
    
    // Decode token
    $decoded = base64_decode($token);
    $parts = explode('|', $decoded);
    
    if (count($parts) !== 2) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid verification token'
        ]);
        exit;
    }
    
    list($uid, $timestamp) = $parts;
    
    // Check if token is expired (24 hours)
    $currentTime = time();
    $tokenAge = $currentTime - (int)$timestamp;
    $maxAge = 24 * 60 * 60; // 24 hours
    
    if ($tokenAge > $maxAge) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Verification link expired. Please request a new one.'
        ]);
        exit;
    }
    
    // Token is valid
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Email verified successfully',
        'uid' => $uid
    ]);
    exit;
}

http_response_code(405);
echo json_encode([
    'success' => false,
    'message' => 'Method not allowed'
]);
?>
