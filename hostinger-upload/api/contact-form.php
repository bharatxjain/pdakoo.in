<?php
/**
 * Contact Form Email Handler - PHP Backend
 * Sends contact form submissions to support team
 * WITH DEBUG LOGGING
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Enable error logging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
$logFile = __DIR__ . '/contact-form-debug.log';
ini_set('error_log', $logFile);

// Log function with timestamp
function debugLog($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
}

debugLog("=== Contact Form Request Started ===");
debugLog("Method: " . $_SERVER['REQUEST_METHOD']);

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
    } else {
        debugLog("WARNING: No .env file found at $envFile");
    }
}
loadEnv();

$smtp_from = $_ENV['SMTP_FROM'] ?? 'contact@pdakoo.in';
$support_email = 'help@pdakoo.in';

debugLog("SMTP_FROM: $smtp_from");
debugLog("Support email: $support_email");

/**
 * Send email via direct SMTP connection
 */
function sendEmailViaSMTP($to, $subject, $htmlBody, $replyTo = null) {
    $smtp_host = 'smtp.hostinger.com';
    $smtp_port = 587;
    $smtp_user = 'contact@pdakoo.in';
    $smtp_pass = '@Pdakoo@2004';
    $smtp_from = 'contact@pdakoo.in';
    
    debugLog("--- Sending email to: $to ---");
    debugLog("Subject: $subject");
    
    try {
        // Connect to SMTP server
        $fp = @fsockopen($smtp_host, $smtp_port, $errno, $errstr, 30);
        
        if (!$fp) {
            debugLog("✗ FAILED: Cannot connect to $smtp_host:$smtp_port - $errstr ($errno)");
            return false;
        }
        
        debugLog("✓ Connected to SMTP server");
        
        // Read initial response
        $response = fgets($fp, 1024);
        if (strpos($response, '220') === false) {
            debugLog("✗ FAILED: Invalid SMTP response");
            fclose($fp);
            return false;
        }
        
        // Send EHLO
        fputs($fp, "EHLO pdakoo.in\r\n");
        $response = fgets($fp, 1024);
        while (strpos($response, '-') === 3) {
            $response = fgets($fp, 1024);
        }
        
        // Start TLS
        fputs($fp, "STARTTLS\r\n");
        $response = fgets($fp, 1024);
        
        if (strpos($response, '220') === 0) {
            stream_context_set_option($fp, 'ssl', 'verify_peer', false);
            stream_context_set_option($fp, 'ssl', 'verify_peer_name', false);
            stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            debugLog("✓ TLS enabled");
            
            // Re-EHLO after TLS
            fputs($fp, "EHLO pdakoo.in\r\n");
            $response = fgets($fp, 1024);
            while (strpos($response, '-') === 3) {
                $response = fgets($fp, 1024);
            }
        }
        
        // Authenticate
        fputs($fp, "AUTH LOGIN\r\n");
        fgets($fp, 1024);
        
        fputs($fp, base64_encode($smtp_user) . "\r\n");
        fgets($fp, 1024);
        
        fputs($fp, base64_encode($smtp_pass) . "\r\n");
        $response = fgets($fp, 1024);
        
        if (strpos($response, '235') === false) {
            debugLog("✗ FAILED: Authentication failed");
            fclose($fp);
            return false;
        }
        
        debugLog("✓ Authentication successful");
        
        // Send MAIL FROM
        fputs($fp, "MAIL FROM: <$smtp_from>\r\n");
        fgets($fp, 1024);
        
        // Send RCPT TO
        fputs($fp, "RCPT TO: <$to>\r\n");
        $response = fgets($fp, 1024);
        
        if (strpos($response, '250') === false) {
            debugLog("✗ FAILED: Recipient not accepted");
            fclose($fp);
            return false;
        }
        
        // Send DATA
        fputs($fp, "DATA\r\n");
        $response = fgets($fp, 1024);
        
        if (strpos($response, '354') === false) {
            debugLog("✗ FAILED: Server not ready for data");
            fclose($fp);
            return false;
        }
        
        // Build email
        $headers = "From: Pdakoo Contact <$smtp_from>\r\n";
        $headers .= "To: <$to>\r\n";
        $headers .= "Subject: $subject\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        if ($replyTo) {
            $headers .= "Reply-To: <$replyTo>\r\n";
        }
        $headers .= "Date: " . date('r') . "\r\n";
        
        $message = $headers . "\r\n" . $htmlBody;
        fputs($fp, $message . "\r\n.\r\n");
        $response = fgets($fp, 1024);
        
        if (strpos($response, '250') === false) {
            debugLog("✗ FAILED: Message not accepted");
            fclose($fp);
            return false;
        }
        
        fputs($fp, "QUIT\r\n");
        fclose($fp);
        
        debugLog("✓ Email sent successfully to $to");
        return true;
        
    } catch (Exception $e) {
        debugLog("✗ EXCEPTION: " . $e->getMessage());
        return false;
    }
}

/**
 * Generate support notification email HTML
 */
function getSupportEmailHTML($name, $email, $subject, $message, $submittedAt) {
    $timestamp = date('F j, Y \a\t g:i A', strtotime($submittedAt));
    $escapedMessage = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
    $escapedMessage = nl2br($escapedMessage);
    
    return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Contact Form Submission - Pdakoo</title>
    <style>
        @media only screen and (max-width: 600px) {
            .email-container { width: 100% !important; }
            .content-padding { padding: 24px !important; }
            .info-box { padding: 16px !important; }
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
                        <td style="background:linear-gradient(135deg,#10b981 0%,#059669 100%);padding:30px 20px;text-align:center;">
                            <h1 style="color:#ffffff;margin:0;font-size:24px;font-weight:800;letter-spacing:1px;">📬 NEW CONTACT SUBMISSION</h1>
                        </td>
                    </tr>
                    
                    <!-- Content -->
                    <tr>
                        <td class="content-padding" style="padding:40px;">
                            <h2 style="color:#ffffff;margin:0 0 20px;font-size:20px;">Contact Message Received</h2>
                            
                            <div class="info-box" style="background-color:#334155;padding:24px;border-radius:12px;margin-bottom:30px;">
                                <table width="100%" cellpadding="8" cellspacing="0">
                                    <tr style="border-bottom:1px solid #475569;">
                                        <td style="color:#94a3b8;font-size:14px;font-weight:600;width:120px;">From:</td>
                                        <td style="color:#ffffff;font-size:14px;"><strong>{$name}</strong> &lt;{$email}&gt;</td>
                                    </tr>
                                    <tr style="border-bottom:1px solid #475569;">
                                        <td style="color:#94a3b8;font-size:14px;font-weight:600;">Subject:</td>
                                        <td style="color:#ffffff;font-size:14px;"><strong>{$subject}</strong></td>
                                    </tr>
                                    <tr>
                                        <td style="color:#94a3b8;font-size:14px;font-weight:600;">Submitted:</td>
                                        <td style="color:#ffffff;font-size:14px;">{$timestamp}</td>
                                    </tr>
                                </table>
                            </div>
                            
                            <h3 style="color:#ffffff;margin:0 0 15px;font-size:16px;font-weight:700;">Message:</h3>
                            <div style="background-color:#334155;padding:24px;border-radius:12px;border-left:4px solid #10b981;margin-bottom:30px;">
                                <p style="color:#cbd5e1;font-size:14px;line-height:1.8;margin:0;white-space:pre-wrap;word-wrap:break-word;">
                                    {$escapedMessage}
                                </p>
                            </div>
                            
                            <div style="background-color:#1e40af/10;border:1px solid #3b82f6;padding:16px;border-radius:8px;margin-bottom:20px;">
                                <p style="color:#60a5fa;font-size:13px;margin:0;font-weight:600;">💡 Quick Action</p>
                                <p style="color:#cbd5e1;font-size:13px;margin:8px 0 0;">
                                    Reply directly to this email or contact the user at <a href="mailto:{$email}" style="color:#60a5fa;text-decoration:none;font-weight:600;">{$email}</a>
                                </p>
                            </div>
                            
                            <hr style="border:none;border-top:1px solid #334155;margin:30px 0;">
                            
                            <p style="color:#94a3b8;font-size:13px;margin:0 0 8px;">
                                <strong style="color:#cbd5e1;">🔍 Contact Details Summary:</strong>
                            </p>
                            <ul style="color:#cbd5e1;font-size:13px;margin:8px 0;padding-left:20px;">
                                <li>Name: {$name}</li>
                                <li>Email: {$email}</li>
                                <li>Subject: {$subject}</li>
                                <li>Submitted: {$timestamp}</li>
                            </ul>
                        </td>
                    </tr>
                    
                    <!-- Footer -->
                    <tr>
                        <td style="background-color:#0f172a;padding:20px;text-align:center;">
                            <p style="color:#64748b;font-size:12px;margin:0 0 8px;">
                                Pdakoo Contact Management System
                            </p>
                            <p style="color:#64748b;font-size:11px;margin:0;">
                                This is an automated notification. Please reply to the user directly at {$email}
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
 * Generate auto-reply email for user
 */
function getUserReplyHTML($name) {
    return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>We've Received Your Message - Pdakoo</title>
</head>
<body style="margin:0;padding:0;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;background-color:#0f172a;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#0f172a;padding:20px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color:#1e293b;border-radius:16px;overflow:hidden;max-width:600px;">
                    <!-- Header -->
                    <tr>
                        <td style="background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);padding:40px 20px;text-align:center;">
                            <img src="https://pdakoo.in/logo.svg" alt="Pdakoo Logo" style="width:100px;height:100px;margin:0 auto;display:block;border-radius:16px;background:rgba(255,255,255,0.15);padding:8px;filter:brightness(1.2) saturate(1.1);" />
                            <h1 style="color:#ffffff;margin:20px 0 0;font-size:28px;font-weight:800;letter-spacing:2px;">PDAKOO</h1>
                            <p style="color:#ffffff;margin:10px 0 0;font-size:14px;font-weight:500;">Premium Code Projects</p>
                        </td>
                    </tr>
                    
                    <!-- Content -->
                    <tr>
                        <td style="padding:40px;">
                            <h2 style="color:#ffffff;margin:0 0 20px;font-size:24px;">We've Received Your Message! ✅</h2>
                            <p style="color:#cbd5e1;font-size:16px;line-height:1.6;margin:0 0 20px;">
                                Hi <strong style="color:#ffffff;">{$name}</strong>,
                            </p>
                            <p style="color:#cbd5e1;font-size:16px;line-height:1.6;margin:0 0 20px;">
                                Thank you for reaching out to Pdakoo! We've received your message and our support team will review it shortly.
                            </p>
                            
                            <div style="background-color:#334155;padding:20px;border-radius:12px;border-left:4px solid #667eea;margin:30px 0;">
                                <p style="color:#cbd5e1;font-size:14px;margin:0;">
                                    <strong style="color:#ffffff;">Expected Response Time:</strong><br>
                                    We typically respond within <strong>24-48 hours</strong>. For urgent matters, please mention it in your message.
                                </p>
                            </div>
                            
                            <p style="color:#cbd5e1;font-size:16px;line-height:1.6;margin:30px 0 0;">
                                Best regards,<br>
                                <strong style="color:#ffffff;">Pdakoo Support Team</strong>
                            </p>
                        </td>
                    </tr>
                    
                    <!-- Footer -->
                    <tr>
                        <td style="background-color:#0f172a;padding:30px 20px;text-align:center;">
                            <p style="color:#64748b;font-size:12px;margin:0 0 10px;">
                                © 2026 Pdakoo. All rights reserved.
                            </p>
                            <p style="color:#64748b;font-size:11px;margin:0;">
                                <a href="https://pdakoo.in" style="color:#667eea;text-decoration:none;margin:0 8px;">Visit Website</a> | 
                                <a href="mailto:help@pdakoo.in" style="color:#667eea;text-decoration:none;margin:0 8px;">Contact Support</a>
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

// Handle POST request for contact form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    debugLog("Processing POST request");
    
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    debugLog("Received input: " . json_encode($input));

    // Validate required fields
    if (!isset($input['name']) || !isset($input['email']) || !isset($input['subject']) || !isset($input['message'])) {
        debugLog("ERROR: Missing required fields");
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Missing required fields: name, email, subject, message'
        ]);
        exit;
    }

    $name = trim($input['name']);
    $email = trim($input['email']);
    $subject = trim($input['subject']);
    $message = trim($input['message']);
    $submittedAt = $input['submittedAt'] ?? date('c');

    debugLog("Processing: name=$name, email=$email, subject=$subject, messageLen=" . strlen($message));

    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        debugLog("ERROR: Invalid email format: $email");
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid email address'
        ]);
        exit;
    }

    // Validate message length
    if (strlen($message) < 5 || strlen($message) > 5000) {
        debugLog("ERROR: Message length invalid: " . strlen($message) . " characters (min 5, max 5000)");
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Message must be at least 5 characters'
        ]);
        exit;
    }

    $results = [
        'support' => false,
        'user_reply' => false
    ];

    // Send notification to support team
    $supportSubject = "[CONTACT] $subject";
    $supportHTML = getSupportEmailHTML($name, $email, $subject, $message, $submittedAt);
    debugLog("Sending support email to: $support_email");
    $results['support'] = sendEmailViaSMTP($support_email, $supportSubject, $supportHTML, $email);

    // Send auto-reply to user
    $userSubject = "We've Received Your Message - Pdakoo";
    $userHTML = getUserReplyHTML($name);
    debugLog("Sending auto-reply to: $email");
    $results['user_reply'] = sendEmailViaSMTP($email, $userSubject, $userHTML, $support_email);

    $overallSuccess = $results['support'] || $results['user_reply'];

    debugLog("Support email result: " . ($results['support'] ? 'SUCCESS' : 'FAILED'));
    debugLog("User reply result: " . ($results['user_reply'] ? 'SUCCESS' : 'FAILED'));
    debugLog("Overall result: " . ($overallSuccess ? 'SUCCESS' : 'FAILED'));
    debugLog("=== Contact Form Request Ended ===\n");

    http_response_code($overallSuccess ? 200 : 500);
    echo json_encode([
        'success' => $overallSuccess,
        'message' => $overallSuccess 
            ? 'Your message has been sent successfully and our team will respond soon!' 
            : 'Failed to process your message. Please try again.',
        'emails' => [
            'support_notified' => $results['support'] ? 'sent' : 'failed',
            'user_reply' => $results['user_reply'] ? 'sent' : 'failed'
        ]
    ]);
    exit;
}

// Handle GET request for verification and debug log access
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $mailAvailable = function_exists('mail');
    
    // Allow viewing debug log if ?debug=1
    if (isset($_GET['debug']) && $_GET['debug'] === '1') {
        if (file_exists($logFile)) {
            header('Content-Type: text/plain');
            echo file_get_contents($logFile);
        } else {
            echo "No debug log found yet.\n";
        }
        exit;
    }
    
    http_response_code(200);
    echo json_encode([
        'verified' => $mailAvailable,
        'message' => $mailAvailable ? 'Contact form email service is ready' : 'PHP mail() function not available',
        'endpoint' => '/contact-form',
        'debug_log' => '/contact-form?debug=1',
        'version' => '1.1'
    ]);
    exit;
}

// Method not allowed
http_response_code(405);
echo json_encode([
    'success' => false,
    'message' => 'Method not allowed. Use POST to submit contact forms or GET to verify service.'
]);
?>


/**
 * Generate support notification email HTML
 */
function getSupportEmailHTML($name, $email, $subject, $message, $submittedAt) {
    $timestamp = date('F j, Y \a\t g:i A', strtotime($submittedAt));
    $escapedMessage = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
    $escapedMessage = nl2br($escapedMessage);
    
    return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Contact Form Submission - Pdakoo</title>
    <style>
        @media only screen and (max-width: 600px) {
            .email-container { width: 100% !important; }
            .content-padding { padding: 24px !important; }
            .info-box { padding: 16px !important; }
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
                        <td style="background:linear-gradient(135deg,#10b981 0%,#059669 100%);padding:30px 20px;text-align:center;">
                            <h1 style="color:#ffffff;margin:0;font-size:24px;font-weight:800;letter-spacing:1px;">📬 NEW CONTACT SUBMISSION</h1>
                        </td>
                    </tr>
                    
                    <!-- Content -->
                    <tr>
                        <td class="content-padding" style="padding:40px;">
                            <h2 style="color:#ffffff;margin:0 0 20px;font-size:20px;">Contact Message Received</h2>
                            
                            <div class="info-box" style="background-color:#334155;padding:24px;border-radius:12px;margin-bottom:30px;">
                                <table width="100%" cellpadding="8" cellspacing="0">
                                    <tr style="border-bottom:1px solid #475569;">
                                        <td style="color:#94a3b8;font-size:14px;font-weight:600;width:120px;">From:</td>
                                        <td style="color:#ffffff;font-size:14px;"><strong>{$name}</strong> &lt;{$email}&gt;</td>
                                    </tr>
                                    <tr style="border-bottom:1px solid #475569;">
                                        <td style="color:#94a3b8;font-size:14px;font-weight:600;">Subject:</td>
                                        <td style="color:#ffffff;font-size:14px;"><strong>{$subject}</strong></td>
                                    </tr>
                                    <tr>
                                        <td style="color:#94a3b8;font-size:14px;font-weight:600;">Submitted:</td>
                                        <td style="color:#ffffff;font-size:14px;">{$timestamp}</td>
                                    </tr>
                                </table>
                            </div>
                            
                            <h3 style="color:#ffffff;margin:0 0 15px;font-size:16px;font-weight:700;">Message:</h3>
                            <div style="background-color:#334155;padding:24px;border-radius:12px;border-left:4px solid #10b981;margin-bottom:30px;">
                                <p style="color:#cbd5e1;font-size:14px;line-height:1.8;margin:0;white-space:pre-wrap;word-wrap:break-word;">
                                    {$escapedMessage}
                                </p>
                            </div>
                            
                            <div style="background-color:#1e40af/10;border:1px solid #3b82f6;padding:16px;border-radius:8px;margin-bottom:20px;">
                                <p style="color:#60a5fa;font-size:13px;margin:0;font-weight:600;">💡 Quick Action</p>
                                <p style="color:#cbd5e1;font-size:13px;margin:8px 0 0;">
                                    Reply directly to this email or contact the user at <a href="mailto:{$email}" style="color:#60a5fa;text-decoration:none;font-weight:600;">{$email}</a>
                                </p>
                            </div>
                            
                            <hr style="border:none;border-top:1px solid #334155;margin:30px 0;">
                            
                            <p style="color:#94a3b8;font-size:13px;margin:0 0 8px;">
                                <strong style="color:#cbd5e1;">🔍 Contact Details Summary:</strong>
                            </p>
                            <ul style="color:#cbd5e1;font-size:13px;margin:8px 0;padding-left:20px;">
                                <li>Name: {$name}</li>
                                <li>Email: {$email}</li>
                                <li>Subject: {$subject}</li>
                                <li>Submitted: {$timestamp}</li>
                            </ul>
                        </td>
                    </tr>
                    
                    <!-- Footer -->
                    <tr>
                        <td style="background-color:#0f172a;padding:20px;text-align:center;">
                            <p style="color:#64748b;font-size:12px;margin:0 0 8px;">
                                Pdakoo Contact Management System
                            </p>
                            <p style="color:#64748b;font-size:11px;margin:0;">
                                This is an automated notification. Please reply to the user directly at {$email}
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
 * Generate auto-reply email for user
 */
function getUserReplyHTML($name) {
    return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>We've Received Your Message - Pdakoo</title>
</head>
<body style="margin:0;padding:0;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;background-color:#0f172a;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#0f172a;padding:20px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color:#1e293b;border-radius:16px;overflow:hidden;max-width:600px;">
                    <!-- Header -->
                    <tr>
                        <td style="background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);padding:40px 20px;text-align:center;">
                            <img src="https://pdakoo.in/logo.svg" alt="Pdakoo Logo" style="width:100px;height:100px;margin:0 auto;display:block;border-radius:16px;background:rgba(255,255,255,0.15);padding:8px;filter:brightness(1.2) saturate(1.1);" />
                            <h1 style="color:#ffffff;margin:20px 0 0;font-size:28px;font-weight:800;letter-spacing:2px;">PDAKOO</h1>
                            <p style="color:#ffffff;margin:10px 0 0;font-size:14px;font-weight:500;">Premium Code Projects</p>
                        </td>
                    </tr>
                    
                    <!-- Content -->
                    <tr>
                        <td style="padding:40px;">
                            <h2 style="color:#ffffff;margin:0 0 20px;font-size:24px;">We've Received Your Message! ✅</h2>
                            <p style="color:#cbd5e1;font-size:16px;line-height:1.6;margin:0 0 20px;">
                                Hi <strong style="color:#ffffff;">{$name}</strong>,
                            </p>
                            <p style="color:#cbd5e1;font-size:16px;line-height:1.6;margin:0 0 20px;">
                                Thank you for reaching out to Pdakoo! We've received your message and our support team will review it shortly.
                            </p>
                            
                            <div style="background-color:#334155;padding:20px;border-radius:12px;border-left:4px solid #667eea;margin:30px 0;">
                                <p style="color:#cbd5e1;font-size:14px;margin:0;">
                                    <strong style="color:#ffffff;">Expected Response Time:</strong><br>
                                    We typically respond within <strong>24-48 hours</strong>. For urgent matters, please mention it in your message.
                                </p>
                            </div>
                            
                            <p style="color:#cbd5e1;font-size:16px;line-height:1.6;margin:30px 0 0;">
                                Best regards,<br>
                                <strong style="color:#ffffff;">Pdakoo Support Team</strong>
                            </p>
                        </td>
                    </tr>
                    
                    <!-- Footer -->
                    <tr>
                        <td style="background-color:#0f172a;padding:30px 20px;text-align:center;">
                            <p style="color:#64748b;font-size:12px;margin:0 0 10px;">
                                © 2026 Pdakoo. All rights reserved.
                            </p>
                            <p style="color:#64748b;font-size:11px;margin:0;">
                                <a href="https://pdakoo.in" style="color:#667eea;text-decoration:none;margin:0 8px;">Visit Website</a> | 
                                <a href="mailto:help@pdakoo.in" style="color:#667eea;text-decoration:none;margin:0 8px;">Contact Support</a>
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

// Handle POST request for contact form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);

    // Validate required fields
    if (!isset($input['name']) || !isset($input['email']) || !isset($input['subject']) || !isset($input['message'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Missing required fields: name, email, subject, message'
        ]);
        exit;
    }

    $name = trim($input['name']);
    $email = trim($input['email']);
    $subject = trim($input['subject']);
    $message = trim($input['message']);
    $submittedAt = $input['submittedAt'] ?? date('c');

    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid email address'
        ]);
        exit;
    }

    // Validate message length
    if (strlen($message) < 5 || strlen($message) > 5000) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Message must be at least 5 characters'
        ]);
        exit;
    }

    $results = [
        'support' => false,
        'user_reply' => false
    ];

    // Send notification to support team
    $supportSubject = "📬 New Contact Form: {$subject}";
    $supportHTML = getSupportEmailHTML($name, $email, $subject, $message, $submittedAt);
    $results['support'] = sendEmail($support_email, $supportSubject, $supportHTML);

    // Send auto-reply to user
    $userSubject = "We've Received Your Message - Pdakoo";
    $userHTML = getUserReplyHTML($name);
    $results['user_reply'] = sendEmail($email, $userSubject, $userHTML);

    $overallSuccess = $results['support'] || $results['user_reply'];

    http_response_code($overallSuccess ? 200 : 500);
    echo json_encode([
        'success' => $overallSuccess,
        'message' => $overallSuccess 
            ? 'Your message has been sent successfully and our team will respond soon!' 
            : 'Failed to process your message. Please try again.',
        'emails' => [
            'support_notified' => $results['support'] ? 'sent' : 'failed',
            'user_reply' => $results['user_reply'] ? 'sent' : 'failed'
        ]
    ]);
    exit;
}

// Handle GET request for verification
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $mailAvailable = function_exists('mail');
    
    http_response_code(200);
    echo json_encode([
        'verified' => $mailAvailable,
        'message' => $mailAvailable ? 'Contact form email service is ready' : 'PHP mail() function not available',
        'endpoint' => '/contact-form',
        'version' => '1.0'
    ]);
    exit;
}

// Method not allowed
http_response_code(405);
echo json_encode([
    'success' => false,
    'message' => 'Method not allowed. Use POST to submit contact forms or GET to verify service.'
]);
?>
