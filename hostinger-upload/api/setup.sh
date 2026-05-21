#!/bin/bash
# Pdakoo Backend Installation Script for Hostinger
# Run this after uploading files to Hostinger

echo "================================================"
echo "Pdakoo File Upload API - Hostinger Setup"
echo "================================================"

# Install dependencies
echo "📦 Installing dependencies..."
npm install

# Create necessary directories
echo "📁 Creating directories..."
mkdir -p uploads/projects
mkdir -p logs

# Set permissions
echo "🔒 Setting permissions..."
chmod 755 uploads
chmod 755 logs

# Create .env file if it doesn't exist
if [ ! -f .env ]; then
  echo "⚠️  Creating .env file..."
  cp .env.example .env
  echo ""
  echo "📝 IMPORTANT: Edit .env file with your Firebase credentials"
  echo "   1. Go to: https://console.firebase.google.com/project/codepdakoo/settings/serviceaccounts"
  echo "   2. Generate new private key"
  echo "   3. Paste the JSON into .env FIREBASE_SERVICE_ACCOUNT"
else
  echo "✅ .env file already exists"
fi

echo ""
echo "================================================"
echo "✅ Setup complete!"
echo "================================================"
echo ""
echo "Next steps:"
echo "1. Edit .env file with your Firebase service account"
echo "2. In cPanel Node.js Manager, set startup file to: server.js"
echo "3. Click Start/Restart"
echo "4. Test: curl http://localhost:3001/api/health"
echo ""
