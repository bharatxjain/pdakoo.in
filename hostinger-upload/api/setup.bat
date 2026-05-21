@echo off
REM Pdakoo Backend Installation Script for Windows/Hostinger
REM Run this in Command Prompt or PowerShell

echo.
echo ================================================
echo Pdakoo File Upload API - Hostinger Setup
echo ================================================
echo.

REM Install dependencies
echo 📦 Installing dependencies...
call npm install

REM Create necessary directories
echo 📁 Creating directories...
if not exist "uploads" mkdir uploads
if not exist "uploads\projects" mkdir uploads\projects
if not exist "logs" mkdir logs

REM Create .env file if it doesn't exist
if not exist ".env" (
  echo ⚠️  Creating .env file...
  copy .env.example .env
  echo.
  echo 📝 IMPORTANT: Edit .env file with your Firebase credentials
  echo    1. Go to: https://console.firebase.google.com/project/codepdakoo/settings/serviceaccounts
  echo    2. Generate new private key
  echo    3. Paste the JSON into .env FIREBASE_SERVICE_ACCOUNT
) else (
  echo ✅ .env file already exists
)

echo.
echo ================================================
echo ✅ Setup complete!
echo ================================================
echo.
echo Next steps:
echo 1. Edit .env file with your Firebase service account
echo 2. In cPanel Node.js Manager, set startup file to: server.js
echo 3. Click Start/Restart
echo 4. Test: curl http://localhost:3001/api/health
echo.
pause
