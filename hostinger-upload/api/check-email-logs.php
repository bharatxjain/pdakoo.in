<?php
/**
 * Debug Log Viewer - Check Contact Form Email Issues
 * Shows contact-form.php debug logs to diagnose email problems
 */

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Debug Logs - Pdakoo API</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 30px 20px;
            color: white;
            text-align: center;
        }
        h1 { font-size: 24px; font-weight: 800; }
        .status-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-top: 15px;
        }
        .status-item {
            background: rgba(255,255,255,0.2);
            padding: 10px;
            border-radius: 6px;
            font-size: 13px;
        }
        .content {
            padding: 30px;
        }
        .log-viewer {
            background: #1e293b;
            color: #e2e8f0;
            padding: 20px;
            border-radius: 8px;
            font-family: 'Monaco', 'Courier New', monospace;
            font-size: 13px;
            line-height: 1.6;
            max-height: 500px;
            overflow-y: auto;
            whitespace: pre-wrap;
            word-wrap: break-word;
        }
        .empty { color: #94a3b8; font-style: italic; }
        .error { color: #ef4444; }
        .success { color: #10b981; }
        .warning { color: #f59e0b; }
        .info { color: #3b82f6; }
        .controls {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        button {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
        }
        .btn-refresh {
            background: #667eea;
            color: white;
        }
        .btn-refresh:hover {
            background: #5568d3;
        }
        .btn-clear {
            background: #ef4444;
            color: white;
        }
        .btn-clear:hover {
            background: #dc2626;
        }
        .info-box {
            background: #f0f9ff;
            border-left: 4px solid #3b82f6;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        .info-box strong { color: #1e40af; }
        .troubleshooting {
            background: #fefce8;
            border-left: 4px solid #eab308;
            padding: 15px;
            border-radius: 6px;
            margin-top: 20px;
            font-size: 14px;
        }
        .troubleshooting h3 {
            color: #ca8a04;
            margin-bottom: 10px;
        }
        .troubleshooting ul {
            margin-left: 20px;
        }
        .troubleshooting li {
            margin-bottom: 8px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📊 Email Debug Dashboard</h1>
            <div class="status-info">
                <div class="status-item">
                    <strong>API Path:</strong> /api/contact-form.php
                </div>
                <div class="status-item">
                    <strong>Log File:</strong> contact-form-debug.log
                </div>
            </div>
        </div>
        
        <div class="content">
            <div class="info-box">
                <strong>ℹ️ How to use this:</strong> This page shows debug logs from contact form submissions. Use it to troubleshoot why emails aren't being sent. Each contact form submission creates detailed logs about whether emails were successfully sent or failed.
            </div>
            
            <div class="controls">
                <button class="btn-refresh" onclick="location.reload()">🔄 Refresh Logs</button>
                <button class="btn-clear" onclick="clearLogs()">🗑️ Clear Logs</button>
            </div>
            
            <h2 style="margin-bottom: 15px; font-size: 18px;">Debug Output</h2>
            <div class="log-viewer" id="logContent">
                <span class="empty">Loading debug logs...</span>
            </div>
            
            <div class="troubleshooting">
                <h3>🔧 Troubleshooting FAQ</h3>
                <ul>
                    <li><strong>If you see "✓ Email sent successfully":</strong> Emails are sending from PHP, but may be blocked by spam filters. Check your help@pdakoo.in spam/junk folder.</li>
                    <li><strong>If you see "✗ Email FAILED":</strong> PHP's mail() function failed. The server may not have mail() enabled or mailbox configured properly.</li>
                    <li><strong>If you see no logs:</strong> Contact forms haven't been submitted yet or logs file hasn't been created.</li>
                    <li><strong>To test:</strong> Submit a contact form on https://pdakoo.in/contact and then refresh this page.</li>
                    <li><strong>Check email:</strong> Look in help@pdakoo.in inbox AND spam/junk folders for test emails.</li>
                </ul>
            </div>
        </div>
    </div>

    <script>
        // Load logs on page load
        window.addEventListener('load', loadLogs);
        
        function loadLogs() {
            fetch('contact-form.php?debug=1')
                .then(response => response.text())
                .then(content => {
                    const logContent = document.getElementById('logContent');
                    if (content.trim() === 'No debug log found yet.' || !content.trim()) {
                        logContent.innerHTML = '<span class="empty">No debug logs yet. Submit a contact form to generate logs.</span>';
                    } else {
                        // Format log content with colors
                        let formatted = content
                            .replace(/✓/g, '<span class="success">✓</span>')
                            .replace(/✗/g, '<span class="error">✗</span>')
                            .replace(/ERROR:/g, '<span class="error">ERROR:</span>')
                            .replace(/WARNING:/g, '<span class="warning">WARNING:</span>')
                            .replace(/\[([^\]]+)\]/g, '<span class="info">[$1]</span>');
                        logContent.textContent = content; // Keep original for readability
                    }
                })
                .catch(error => {
                    document.getElementById('logContent').innerHTML = 
                        '<span class="error">Error loading debug logs: ' + error.message + '</span>';
                });
        }
        
        function clearLogs() {
            if (confirm('Clear all debug logs? This cannot be undone.')) {
                fetch('contact-form.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ clearLogs: true })
                })
                .then(() => {
                    document.getElementById('logContent').innerHTML = 
                        '<span class="success">✓ Logs cleared successfully</span>';
                    setTimeout(loadLogs, 1000);
                })
                .catch(error => console.error('Error:', error));
            }
        }
    </script>
</body>
</html>
