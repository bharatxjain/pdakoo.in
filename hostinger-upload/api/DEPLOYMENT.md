# Pdakoo File Upload API - Hostinger Deployment Guide

## Prerequisites
- Hostinger account with Node.js support
- Firebase service account key
- Domain configured on Hostinger

## Step 1: Prepare Firebase Service Account

1. Go to **Firebase Console** → **Project Settings** → **Service Accounts**
2. Click **Generate New Private Key**
3. Save the JSON file contents
4. Copy the entire JSON and use it for `FIREBASE_SERVICE_ACCOUNT` env variable

## Step 2: Deploy to Hostinger

### Option A: Using cPanel (Recommended)

1. **Connect via FTP or File Manager**
   - Go to cPanel → Files → File Manager
   - Navigate to `public_html` or your domain folder
   - Create new folder: `api`

2. **Upload Backend Files**
   ```
   /api/
   ├── server.js
   ├── package.json
   ├── .env
   └── uploads/ (will be created automatically)
   ```

3. **Install Dependencies**
   - In cPanel, go to **Terminal**
   - Navigate to your API folder
   ```bash
   cd ~/public_html/api
   npm install
   ```

4. **Create .env File**
   ```bash
   cat > .env << EOF
   PORT=3001
   NODE_ENV=production
   FIREBASE_SERVICE_ACCOUNT={paste your firebase JSON here}
   FIREBASE_DATABASE_URL=https://codepdakoo.firebaseio.com
   API_BASE_URL=https://your-domain.com/api
   EOF
   ```

5. **Set Up Node.js App**
   - In cPanel, find **Node.js Manager** (or **App Manager**)
   - Click **Create Application**
   - Set:
     - **Node.js Version**: 18.x
     - **Application Mode**: production
     - **Application Root**: `api`
     - **Application Startup File**: `server.js`
     - **Application URL**: `api.your-domain.com` or `your-domain.com/api`

6. **Start the Application**
   - Click **Start** in Node.js Manager
   - Your API will be live!

### Option B: Using SSH

```bash
# SSH into your Hostinger account
ssh username@your-server-ip

# Navigate to your domain folder
cd ~/public_html

# Clone or create your API folder
mkdir api && cd api

# Copy files (via SCP or Git)
git clone your-repo

# Install dependencies
npm install

# Create .env file
nano .env
# Paste your configuration

# Test locally (optional)
npm start
```

## Step 3: Verify Deployment

Test your API:
```bash
curl https://your-domain.com/api/health
```

Expected response:
```json
{
  "status": "OK",
  "message": "Pdakoo File Upload API is running"
}
```

## Step 4: Update Frontend Code

In your React app, update the API base URL:

```typescript
// src/services/fileUploadService.ts
const API_BASE_URL = 'https://your-domain.com/api'; // or your-domain.com/api
```

## Hostinger Node.js Configuration

If using Hostinger's Node.js Manager:

- **Max File Size**: Set to 200MB (matches multer limit)
- **Memory**: 512MB minimum
- **Workers**: Auto or 2-4 depending on traffic

## Troubleshooting

### Port Issues
If port 3001 is in use, Hostinger's Node.js Manager will auto-assign another port. Check the "Status" tab.

### Permission Denied
Make sure the `uploads` directory is writable:
```bash
chmod 755 uploads
```

### Firebase Connection Issues
1. Verify `FIREBASE_SERVICE_ACCOUNT` is valid JSON
2. Check Firebase project ID matches
3. Ensure Firestore is enabled in Firebase

### File Upload Fails
1. Check file size (max 200MB per file)
2. Verify file type is allowed (ZIP, PDF, CSV, JSON, MD, TXT)
3. Check disk space on Hostinger (`du -sh ~`)

## API Endpoints

### Upload Files
```
POST /api/projects/{projectId}/upload
Content-Type: multipart/form-data

Body:
- files: File[] (array of files)
- projectId: string (from URL)
- adminUid: string (from request body)

Response:
{
  "success": true,
  "files": [
    {
      "name": "source-code.zip",
      "filename": "1234567890_source-code.zip",
      "size": 5242880,
      "path": "/uploads/projects/projectId/1234567890_source-code.zip"
    }
  ]
}
```

### Download File
```
GET /api/projects/{projectId}/files/{filename}?uid={userId}

Response: File binary data
```

### List Files
```
GET /api/projects/{projectId}/files

Response:
{
  "files": [...]
}
```

### Delete File
```
DELETE /api/projects/{projectId}/files/{filename}

Body:
{
  "adminUid": "user123"
}
```

## Security Notes

✅ **Implemented**
- Admin verification via Firebase
- Purchase verification for downloads
- Directory traversal prevention
- File type validation
- CORS configuration
- Password-protected access

⚠️ **Remember**
- Keep `.env` file private (use Hostinger's secure .env file feature)
- Regularly backup uploaded files
- Monitor disk usage
- Enable HTTPS (Hostinger provides free SSL)

## Support

For issues:
1. Check Node.js logs in Hostinger cPanel
2. Verify Firebase connectivity
3. Test with `curl` commands above
4. Check file permissions: `ls -la uploads/`
