# 🚀 Pdakoo PHP API - Ready to Deploy

This is a PHP-based backend for file uploads and downloads. No npm install needed!

---

## 📁 What's Inside

```
api/
├── index.php           ← Main router
├── upload.php          ← File upload handler
├── download.php        ← File download handler
├── list-files.php      ← List project files
├── delete-file.php     ← Delete files (admin)
├── .htaccess          ← URL routing
└── uploads/           ← File storage (auto-created)
    └── projects/
        └── {projectId}/
            └── [uploaded files]
```

---

## 🎯 API ENDPOINTS

### Health Check
```
GET /api/health
```
Response: `{"status":"OK",...}`

### Upload Files
```
POST /api/projects/{projectId}/upload
Content-Type: multipart/form-data

Body:
- files: File[] (multiple files)
- adminUid: string
```

### Download File
```
GET /api/projects/{projectId}/files/{filename}?uid={userId}
```
Returns: Downloaded file

### List Files
```
GET /api/projects/{projectId}/files
```
Response: JSON array of files

### Delete File
```
DELETE /api/projects/{projectId}/files/{filename}
Content-Type: application/json

Body:
- adminUid: string
```

---

## ✅ THAT'S IT!

No install needed. Just upload to Hostinger and it works immediately.

---

## 🔒 Security Notes

- Files stored outside public_html (better security)
- Filenames sanitized to prevent injection
- PHP execution disabled in uploads directory
- Size limits enforced (200MB per file)
- Add Firebase verification for production

---

## 📝 Next Steps

1. Upload entire folder to `/public_html/api/`
2. Test: `https://pdakoo.in/api/health`
3. Upload files through admin panel
4. Download files through project page

**That's all!** 🎉
