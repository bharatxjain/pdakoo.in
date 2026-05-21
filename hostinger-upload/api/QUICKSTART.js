// ============================================
// PDAKOO BACKEND - QUICK START
// ============================================
// This file helps you get started quickly

const fs = require('fs');
const path = require('path');

console.log(`
╔════════════════════════════════════════════════════════╗
║     PDAKOO BACKEND - QUICK START GUIDE                 ║
╚════════════════════════════════════════════════════════╝

📂 PROJECT STRUCTURE
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

backend/
├── server.js              ← Main application
├── package.json           ← Dependencies
├── index.js              ← Entry point for Hostinger
├── .env                  ← Your configuration (CREATE THIS)
├── .env.example          ← Template for .env
├── .env.production       ← Production defaults
│
├── DEPLOYMENT.md         ← Full deployment guide
├── setup.sh              ← Linux/Mac setup
├── setup.bat             ← Windows setup
├── deploy.sh             ← Hostinger deployment
├── QUICKSTART.js         ← This file
│
└── uploads/              ← File storage (created at runtime)
    └── projects/


🚀 GETTING STARTED IN 3 STEPS
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

STEP 1: Install Dependencies
────────────────────────────
$ npm install

STEP 2: Create Configuration
────────────────────────────
Copy .env.example to .env and add your Firebase credentials:

$ cp .env.example .env
$ nano .env  (or edit in your editor)

You need:
  FIREBASE_SERVICE_ACCOUNT = Your Firebase service account JSON
  FIREBASE_DATABASE_URL = Your Firebase database URL
  API_BASE_URL = Your domain URL

STEP 3: Start the Server
──────────────────────
$ npm start

Or for development with auto-reload:
$ npm run dev

✅ Server will start on PORT 3001


🔗 API ENDPOINTS
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Health Check:
  GET /api/health

Upload Files:
  POST /api/projects/{projectId}/upload
  Body: multipart/form-data
    - files: File[]
    - projectId: string
    - adminUid: string

Download File:
  GET /api/projects/{projectId}/files/{filename}?uid={userId}

List Files:
  GET /api/projects/{projectId}/files

Delete File:
  DELETE /api/projects/{projectId}/files/{filename}
  Body: { adminUid: string }


🌍 HOSTINGER DEPLOYMENT
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

1. Upload to Hostinger via FTP:
   - Connect to your Hostinger FTP
   - Upload all files to: ~/public_html/api
   - Ensure .env file is uploaded (keep secret!)

2. Create Node.js App in cPanel:
   - Go to: cPanel → Node.js Selector (or Node.js Manager)
   - Click: Create Application
   - Set:
     • Node.js Version: 18.x or latest
     • Application Mode: production
     • Application Root: ~/public_html/api
     • Application Startup File: server.js
   - Click: Create

3. Configure Environment:
   - Via SSH or cPanel Terminal:
   - cd ~/public_html/api
   - Edit .env with your Firebase credentials

4. Start Application:
   - In cPanel Node.js Manager
   - Click your app
   - Click: Start/Restart

5. Test:
   - Visit: https://your-domain.com/api/health
   - Should show: {"status":"OK",...}


📋 ENVIRONMENT VARIABLES
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

PORT=3001
  ↳ Server port (auto-assigned on Hostinger)

NODE_ENV=production
  ↳ Must be "production" for security

FIREBASE_SERVICE_ACCOUNT={...}
  ↳ Firebase admin SDK key (JSON string)
  ↳ Get from: Firebase Console → Settings → Service Accounts

FIREBASE_DATABASE_URL=https://...
  ↳ Your Firestore database URL

API_BASE_URL=https://your-domain.com/api
  ↳ Frontend will use this to connect


🔐 SECURITY CHECKLIST
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

✅ .env file added to .gitignore (don't commit)
✅ HTTPS only in production
✅ Firebase credentials kept secure
✅ File uploads validated
✅ File types whitelisted
✅ Purchase verification required
✅ Admin auth for uploads
✅ CORS configured


📚 DOCUMENTATION
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Full docs available in:
  ├── DEPLOYMENT.md (step-by-step guide)
  ├── server.js (code comments)
  └── ../__HOSTINGER_FILE_UPLOAD_GUIDE.md (complete reference)


❓ TROUBLESHOOTING
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Error: "Module not found"
  → Run: npm install

Error: "FIREBASE_SERVICE_ACCOUNT is invalid"
  → Check: Your Firebase credentials in .env
  → Fix: Copy complete JSON from Firebase Console

Error: "Upload failed - Permission denied"
  → Check: Directory permissions: chmod 755 uploads/
  → Fix: Via SSH in cPanel Terminal

Error: "API connection refused"
  → Check: Node.js app is started in cPanel
  → Check: Port 3001 is assigned (may be different)
  → Check: Domain DNS is configured


💡 TIPS
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

• Keep .env secure - never commit to Git
• Monitor /uploads/ directory size
• Regular backups via cPanel
• Check logs in cPanel Terminal: tail -f ~/logs/nodejs.log
• For large files, increase Node.js memory in cPanel


✨ YOU'RE READY!
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Next: Read DEPLOYMENT.md for detailed instructions
Questions? Check: server.js comments or HOSTINGER_FILE_UPLOAD_GUIDE.md

Happy uploading! 🚀

`);

// Quick version check
console.log('📦 Installed Packages:');
const packageJson = JSON.parse(fs.readFileSync(path.join(__dirname, 'package.json'), 'utf8'));
Object.entries(packageJson.dependencies).forEach(([pkg, version]) => {
  console.log(`   ✓ ${pkg}: ${version}`);
});

console.log('\n✅ Backend is ready for deployment!\n');
