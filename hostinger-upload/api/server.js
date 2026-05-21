// ============================================
// PDAKOO - File Upload API for Hostinger
// ============================================

const express = require('express');
const multer = require('multer');
const path = require('path');
const fs = require('fs');
const cors = require('cors');
const dotenv = require('dotenv');
const admin = require('firebase-admin');

dotenv.config();

const app = express();

// CORS Configuration
app.use(cors({
  origin: [
    'http://localhost:4176',
    'http://localhost:5173',
    'https://pdakoo.in',
    'https://www.pdakoo.in',
    'https://api.pdakoo.in',
    'https://pdakoo.com',
    'https://www.pdakoo.com',
  ],
  credentials: true
}));

app.use(express.json());
app.use(express.static('public'));
app.use('/uploads', express.static(path.join(__dirname, 'uploads')));

// Initialize Firebase Admin
const serviceAccount = process.env.FIREBASE_SERVICE_ACCOUNT ? 
  JSON.parse(process.env.FIREBASE_SERVICE_ACCOUNT) : 
  require('./firebase-service-account.json');

admin.initializeApp({
  credential: admin.credential.cert(serviceAccount),
  databaseURL: process.env.FIREBASE_DATABASE_URL
});

const db = admin.firestore();
const bucket = admin.storage().bucket();

// Create uploads directory if it doesn't exist
const uploadsDir = path.join(__dirname, 'uploads');
const projectuploadsDir = path.join(uploadsDir, 'projects');

if (!fs.existsSync(uploadsDir)) {
  fs.mkdirSync(uploadsDir, { recursive: true });
}
if (!fs.existsSync(projectuploadsDir)) {
  fs.mkdirSync(projectuploadsDir, { recursive: true });
}

// Configure multer for file uploads
const storage = multer.diskStorage({
  destination: (req, file, cb) => {
    const projectId = req.params.projectId;
    if (!projectId) {
      return cb(new Error('Invalid project ID'));
    }

    const projectDir = path.join(projectuploadsDir, projectId);
    const subDir = file.fieldname === 'coverImage' ? 'cover' : 'files';
    const targetDir = path.join(projectDir, subDir);

    if (!fs.existsSync(targetDir)) {
      fs.mkdirSync(targetDir, { recursive: true });
    }

    cb(null, targetDir);
  },
  filename: (req, file, cb) => {
    const uniqueName = `${Date.now()}_${file.originalname}`;
    cb(null, uniqueName);
  }
});

const fileFilter = (req, file, cb) => {
  const allowedMimes = [
    'application/pdf',
    'application/zip',
    'text/csv',
    'application/json',
    'text/plain',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'image/jpeg',
    'image/png',
    'image/webp',
    'image/jpg'
  ];

  const allowedExt = [
    '.pdf',
    '.zip',
    '.csv',
    '.json',
    '.txt',
    '.doc',
    '.docx',
    '.jpg',
    '.jpeg',
    '.png',
    '.webp'
  ];
  const fileExt = path.extname(file.originalname).toLowerCase();

  if (allowedMimes.includes(file.mimetype) || allowedExt.includes(fileExt)) {
    cb(null, true);
  } else {
    cb(new Error(`Unsupported file type: ${file.originalname}`), false);
  }
};

const upload = multer({
  storage,
  fileFilter,
  limits: {
    fileSize: 200 * 1024 * 1024 // 200MB
  }
});

// ============================================
// API ENDPOINTS
// ============================================

/**
 * Health Check
 */
app.get('/api/health', (req, res) => {
  res.json({ status: 'OK', message: 'Pdakoo File Upload API is running' });
});

/**
 * Upload Files Endpoint
 * POST /api/projects/:projectId/upload
 */
app.post('/api/projects/:projectId/upload', async (req, res) => {
  try {
    const { projectId } = req.params;
    if (!projectId) {
      return res.status(400).json({ error: 'Invalid project ID' });
    }

    upload.fields([
      { name: 'files', maxCount: 20 },
      { name: 'coverImage', maxCount: 1 }
    ])(req, res, async (err) => {
      if (err) {
        return res.status(400).json({ error: err.message || 'File upload failed' });
      }

      const uploadedFilesRaw = (req.files && req.files.files) || [];
      const coverImageRaw = req.files && req.files.coverImage ? req.files.coverImage[0] : null;
      const { adminUid } = req.body;

      if (!adminUid) {
        uploadedFilesRaw.forEach(file => fs.existsSync(file.path) && fs.unlinkSync(file.path));
        if (coverImageRaw && fs.existsSync(coverImageRaw.path)) {
          fs.unlinkSync(coverImageRaw.path);
        }
        return res.status(401).json({ error: 'Unauthorized: Missing admin UID' });
      }

      // Verify admin status in Firebase
      const userDoc = await db.collection('users').doc(adminUid).get();
      if (!userDoc.exists || userDoc.data().role !== 'admin') {
        uploadedFilesRaw.forEach(file => fs.existsSync(file.path) && fs.unlinkSync(file.path));
        if (coverImageRaw && fs.existsSync(coverImageRaw.path)) {
          fs.unlinkSync(coverImageRaw.path);
        }
        return res.status(403).json({ error: 'Forbidden: User is not an admin' });
      }

      if (!uploadedFilesRaw.length && !coverImageRaw) {
        return res.status(400).json({ error: 'No files uploaded' });
      }

      try {
        const uploadedFiles = uploadedFilesRaw.map(file => ({
          name: file.originalname,
          filename: file.filename,
          size: file.size,
          type: file.mimetype,
          path: `/uploads/projects/${projectId}/files/${file.filename}`,
          uploadedAt: new Date().toISOString(),
          uploadedBy: adminUid
        }));

        const coverImage = coverImageRaw ? {
          name: coverImageRaw.originalname,
          filename: coverImageRaw.filename,
          size: coverImageRaw.size,
          type: coverImageRaw.mimetype,
          path: `/uploads/projects/${projectId}/cover/${coverImageRaw.filename}`,
          uploadedAt: new Date().toISOString(),
          uploadedBy: adminUid
        } : null;

        // Save file metadata to Firestore
        const projectRef = db.collection('projects').doc(projectId);
        await projectRef.update({
          files: admin.firestore.FieldValue.arrayUnion(...uploadedFiles),
          projectFiles: admin.firestore.FieldValue.arrayUnion(...uploadedFiles),
          ...(coverImage ? { coverImage: coverImage.path } : {}),
          updatedAt: admin.firestore.FieldValue.serverTimestamp()
        });

        res.json({
          success: true,
          message: `${uploadedFiles.length} file(s) uploaded successfully`,
          files: uploadedFiles,
          coverImage
        });
      } catch (error) {
        console.error('Error saving file metadata:', error);
        res.status(500).json({ error: 'Failed to save file metadata' });
      }
    });
  } catch (error) {
    console.error('Upload error:', error);
    res.status(500).json({ error: error.message });
  }
});

/**
 * Download File Endpoint
 * GET /api/projects/:projectId/files/:filename
 */
app.get('/api/projects/:projectId/files/:filename', async (req, res) => {
  try {
    const { projectId, filename } = req.params;
    const { uid } = req.query;

    if (!uid) {
      return res.status(401).json({ error: 'Unauthorized: Missing user ID' });
    }

    // Verify user has purchased the project
    const purchaseSnapshot = await db.collection('purchases')
      .where('userId', '==', uid)
      .where('projectId', '==', projectId)
      .where('status', '==', 'completed')
      .get();

    if (purchaseSnapshot.empty) {
      return res.status(403).json({ error: 'User has not purchased this project' });
    }

    const filePath = path.join(__dirname, 'uploads', 'projects', projectId, 'files', filename);

    // Security: Prevent directory traversal
    if (!filePath.startsWith(path.join(__dirname, 'uploads'))) {
      return res.status(403).json({ error: 'Invalid file path' });
    }

    // Check if file exists
    if (!fs.existsSync(filePath)) {
      return res.status(404).json({ error: 'File not found' });
    }

    // Track download in Firestore
    try {
      await db.collection('downloads').add({
        userId: uid,
        projectId,
        filename,
        fileSize: fs.statSync(filePath).size,
        downloadedAt: admin.firestore.FieldValue.serverTimestamp()
      });
    } catch (error) {
      console.warn('Failed to track download:', error);
      // Don't fail the download if tracking fails
    }

    // Send file
    res.download(filePath, filename);
  } catch (error) {
    console.error('Download error:', error);
    res.status(500).json({ error: error.message });
  }
});

/**
 * List Project Files Endpoint
 * GET /api/projects/:projectId/files
 */
app.get('/api/projects/:projectId/files', async (req, res) => {
  try {
    const { projectId } = req.params;

    const projectDoc = await db.collection('projects').doc(projectId).get();
    if (!projectDoc.exists) {
      return res.status(404).json({ error: 'Project not found' });
    }

    const files = projectDoc.data().files || projectDoc.data().projectFiles || [];
    res.json({ files });
  } catch (error) {
    console.error('List files error:', error);
    res.status(500).json({ error: error.message });
  }
});

/**
 * Delete File Endpoint
 * DELETE /api/projects/:projectId/files/:filename
 */
app.delete('/api/projects/:projectId/files/:filename', async (req, res) => {
  try {
    const { projectId, filename } = req.params;
    const { adminUid } = req.body;

    if (!adminUid) {
      return res.status(401).json({ error: 'Unauthorized: Missing admin UID' });
    }

    // Verify admin status
    const userDoc = await db.collection('users').doc(adminUid).get();
    if (!userDoc.exists || userDoc.data().role !== 'admin') {
      return res.status(403).json({ error: 'Forbidden: User is not an admin' });
    }

    const filePath = path.join(__dirname, 'uploads', 'projects', projectId, 'files', filename);

    // Security: Prevent directory traversal
    if (!filePath.startsWith(path.join(__dirname, 'uploads'))) {
      return res.status(403).json({ error: 'Invalid file path' });
    }

    // Delete file from disk
    if (fs.existsSync(filePath)) {
      fs.unlinkSync(filePath);
    }

    // Update Firestore
    const projectRef = db.collection('projects').doc(projectId);
    const projectDoc = await projectRef.get();
    const projectData = projectDoc.data() || {};
    const files = projectData.files || projectData.projectFiles || [];
    const target = files.find(f => f.filename === filename);

    if (target) {
      await projectRef.update({
        files: admin.firestore.FieldValue.arrayRemove(target),
        projectFiles: admin.firestore.FieldValue.arrayRemove(target),
        updatedAt: admin.firestore.FieldValue.serverTimestamp()
      });
    }

    res.json({ success: true, message: 'File deleted' });
  } catch (error) {
    console.error('Delete error:', error);
    res.status(500).json({ error: error.message });
  }
});

// Error handler
app.use((err, req, res, next) => {
  console.error(err);
  res.status(500).json({ error: err.message || 'Internal server error' });
});

// Start server
const PORT = process.env.PORT || 3001;
app.listen(PORT, () => {
  console.log(`\n✅ Pdakoo File Upload API running on port ${PORT}`);
  console.log(`📁 Uploads directory: ${uploadsDir}`);
  console.log(`🔒 Admin authentication enabled\n`);
});
