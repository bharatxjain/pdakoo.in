# Pdakoo

Pdakoo is a learning platform for students who want practical, production-style academic projects instead of generic course material. The site focuses on high-quality project templates and complete project experiences for college submissions, viva preparation, and placement readiness.

## Overview

This repository contains the deployed Pdakoo web experience and the related upload API used for project file management.

The platform is designed to help students:

- browse curated academic projects by category
- view project details with clear positioning and SEO-friendly pages
- access downloadable project assets and documentation
- handle secure admin-managed uploads for project files
- support a production deployment on Hostinger

## Live Surface

- Website: https://pdakoo.in
- API base: `/hostinger-upload/api`

## Key Features

- Premium academic project showcase for tech students
- Category-driven browsing for different project types
- Responsive landing pages and project listing pages
- SEO metadata, social sharing tags, and favicon branding
- Razorpay checkout integration on the frontend
- File upload, listing, download, and delete APIs for project assets
- Firebase-backed admin and purchase verification in the API layer

## Repository Layout

```text
.
├── index.html
├── assets/
│   ├── index-08DaxAjZ.js
│   └── index-DYHXCKjr.css
├── favicon.svg
├── logo.svg
├── _redirects
└── hostinger-upload/
	└── api/
		├── server.js
		├── index.js
		├── package.json
		├── README.md
		├── README_PHP.md
		├── upload.php
		├── download.php
		├── list-files.php
		├── delete-file.php
		└── uploads/
```

## Frontend Highlights

The frontend is a static production build with:

- custom metadata for title, description, Open Graph, and Twitter cards
- bundled JavaScript and CSS assets
- Razorpay checkout script inclusion
- a polished landing page built for direct deployment

## Backend Highlights

The `hostinger-upload/api` folder includes a Node.js upload API and supporting PHP files for Hostinger-based hosting setups.

Node.js API capabilities:

- `GET /api/health`
- `POST /api/projects/:projectId/upload`
- `GET /api/projects/:projectId/files/:filename`
- `GET /api/projects/:projectId/files`
- `DELETE /api/projects/:projectId/files/:filename`

The API uses:

- Express for routing
- Multer for file uploads
- Firebase Admin for Firestore and storage access
- CORS for controlled cross-origin access
- `.env` configuration for environment-specific values

## Local Development

### Frontend preview

If you only need to preview the static site locally, serve the repository root with any static server.

Example:

```bash
python3 -m http.server 8000
```

Then open `http://localhost:8000`.

### Backend API

To run the Node API in `hostinger-upload/api`:

```bash
cd hostinger-upload/api
npm install
cp .env.example .env
npm start
```

You must provide a valid Firebase service account JSON in `FIREBASE_SERVICE_ACCOUNT` before the API can fully operate.

## Deployment Notes

The repository is structured for Hostinger deployment:

- upload the frontend files to the public web root
- deploy the API under `/api`
- keep Firebase credentials in a secure `.env` file
- ensure uploads directories are writable by the host

## Project Goals

Pdakoo is built to make project discovery and delivery feel more professional than a typical college-project catalog. The emphasis is on presentation quality, practical usefulness, and a clean path from browsing to download.

## License

MIT
