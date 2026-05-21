# Pdakoo Backend API - README

Complete Node.js Express API for handling file uploads on Hostinger.

## 🚀 Quick Start

### Local Development
```bash
npm install
cp .env.example .env
# Edit .env with your Firebase credentials
npm run dev
```

### Production (Hostinger)
```bash
npm install --production
npm start
```

## 📁 Files

```
backend/
├── server.js              Main Express server
├── index.js              Entry point for Hostinger
├── package.json          Dependencies
├── .env.example          Configuration template
├── .env.production       Production defaults
├── QUICKSTART.js         Quick start guide
├── DEPLOYMENT.md         Detailed deployment guide
├── setup.sh              Linux/Mac setup script
├── setup.bat             Windows setup script
├── deploy.sh             Hostinger deployment helper
└── uploads/              File storage (auto-created)
```

## 🔧 Configuration

### Create .env file
```bash
cp .env.example .env
```

Edit `.env` with your values:
```
PORT=3001
NODE_ENV=production
FIREBASE_SERVICE_ACCOUNT={...}  # Your Firebase service account JSON
FIREBASE_DATABASE_URL=https://codepdakoo.firebaseio.com
API_BASE_URL=https://your-domain.com/api
```

### Get Firebase Credentials
1. Go to https://console.firebase.google.com
2. Select project: **codepdakoo**
3. Click ⚙️ **Settings** → **Service Accounts**
4. Click **Generate New Private Key**
5. Copy the entire JSON and paste into `.env`

## 🌐 API Endpoints

### Health Check
```
GET /api/health
```
Returns: `{status: "OK"}`

### Upload Files
```
POST /api/projects/{projectId}/upload
Content-Type: multipart/form-data

Body:
- files: File[] (up to 20 files, 200MB max each)
- projectId: string
- adminUid: string
```

### Download File
```
GET /api/projects/{projectId}/files/{filename}?uid={userId}

Query Params:
- uid: User's Firebase UID (auto-verified purchase)
```

### List Files
```
GET /api/projects/{projectId}/files
```

### Delete File
```
DELETE /api/projects/{projectId}/files/{filename}

Body:
- adminUid: string
```

## 🏗️ Hostinger Deployment

### Step 1: Upload Files
- Via FTP: Upload to `/public_html/api`
- Keep `.env` file secure

### Step 2: Create Node.js App
1. cPanel → Node.js Manager (or Selector)
2. Click **Create Application**
3. Set:
   - Node.js Version: **18.x**
   - Application Root: **api**
   - Startup File: **server.js**
4. Click **Create**

### Step 3: Configure
Via cPanel Terminal:
```bash
cd ~/public_html/api
npm install --production
nano .env  # Add your Firebase credentials
```

### Step 4: Start
In cPanel Node.js Manager, click **Start**

### Step 5: Test
```bash
curl https://your-domain.com/api/health
```

Expected: `{"status":"OK",...}`

## 📚 Documentation

- **DEPLOYMENT.md** - Step-by-step guide
- **QUICKSTART.js** - Quick reference
- **../HOSTINGER_FILE_UPLOAD_GUIDE.md** - Complete technical guide

## 🔐 Security Features

✅ Admin verification  
✅ Purchase verification  
✅ File type whitelist  
✅ Directory traversal protection  
✅ Size limits (200MB per file)  
✅ CORS configured  
✅ Rate limiting ready  

## ⚙️ Scripts

```bash
npm start              # Start production server
npm run dev            # Start with auto-reload (nodemon)
npm install            # Install dependencies
npm install --production  # Install prod dependencies only
```

## 📊 Monitoring

### Check Disk Usage
```bash
du -sh ~/uploads/
```

### View Logs
```bash
tail -f ~/logs/nodejs.log
# or
pm2 logs
```

### Recent Uploads
```bash
ls -lt ~/uploads/projects/**/* | head -20
```

## 🆘 Troubleshooting

| Problem | Solution |
|---------|----------|
| Module not found | Run `npm install` |
| Firebase connection fails | Check `.env` credentials |
| Upload fails | Check file size & type |
| 404 on API | Ensure Node.js app is started |
| Permission denied | Run `chmod 755 uploads/` |

## 📞 Support

For issues:
1. Check logs in cPanel
2. Verify Firebase setup
3. See DEPLOYMENT.md
4. Check ../HOSTINGER_FILE_UPLOAD_GUIDE.md

## 📄 License

MIT - Pdakoo Platform
