#!/bin/bash
# Quick Hostinger Deployment Script

echo "🚀 Pdakoo Backend - Hostinger Deployment"
echo ""

# Check if we're in the right directory
if [ ! -f "server.js" ]; then
    echo "❌ Error: server.js not found. Please run this script from backend directory"
    exit 1
fi

# Create uploads directory
mkdir -p uploads/projects
echo "✅ Created uploads directory"

# Install dependencies
echo "📦 Installing Node dependencies..."
npm install --production
echo "✅ Dependencies installed"

# Set permissions
chmod 755 uploads logs 2>/dev/null
echo "✅ Permissions set"

# Display next steps
echo ""
echo "================================================"
echo "✨ DEPLOYMENT READY!"
echo "================================================"
echo ""
echo "📋 Next Steps:"
echo ""
echo "1. UPLOAD TO HOSTINGER:"
echo "   - Upload all files to ~/public_html/api"
echo "   - Use FTP or cPanel File Manager"
echo ""
echo "2. CREATE NODE.JS APP IN cPANEL:"
echo "   - Go to: cPanel → Node.js Manager"
echo "   - Click: Create Application"
echo "   - Set Node.js Version: 18.x"
echo "   - Set Application Root: api"
echo "   - Set Startup File: server.js"
echo ""
echo "3. CONFIGURE ENVIRONMENT:"
echo "   - In cPanel Terminal:"
echo "   - cd ~/public_html/api"
echo "   - nano .env"
echo "   - Add Firebase service account JSON"
echo "   - Save (Ctrl+X, Y, Enter)"
echo ""
echo "4. START THE APP:"
echo "   - In cPanel Node.js Manager"
echo "   - Click the app name"
echo "   - Click: Start"
echo ""
echo "5. TEST API:"
echo "   - Visit: https://your-domain.com/api/health"
echo "   - Should return: {\"status\":\"OK\",\"message\":\"...\"}"
echo ""
echo "📚 Full docs: See DEPLOYMENT.md"
echo ""
