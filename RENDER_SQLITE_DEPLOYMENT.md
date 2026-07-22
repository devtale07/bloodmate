# BloodMate Render Deployment with SQLite - Quick Start Guide

## Overview
This guide will help you deploy BloodMate to Render using SQLite database. SQLite is a file-based database that doesn't require a separate database server, making it perfect for quick deployment.

## What's Been Done
✅ SQLite database adapter created
✅ SQLite schema converted from MySQL
✅ Configuration updated to support SQLite
✅ Environment variables configured for SQLite
✅ API endpoints updated with security middleware

## Deployment Steps

### 1. Update Your Render Environment Variables

Go to your Render dashboard → Your BloodMate service → Environment

Add/Update these environment variables:

```bash
DB_TYPE=sqlite
DB_HOST=database/bloodmate.sqlite
DB_NAME=bloodmate
DB_USER=
DB_PASSWORD=
DB_PORT=
APP_NAME=BloodMate
APP_URL=https://bloodmate-p39s.onrender.com
APP_ENV=production
DEBUG=false
JWT_SECRET=generate-a-secure-random-string-here
CORS_ALLOWED_ORIGINS=https://bloodmate-p39s.onrender.com
```

**Important:** Generate a secure JWT secret using:
```bash
openssl rand -base64 32
```

### 2. Deploy Updated Files to Render

Push these updated files to your Git repository (Render will auto-deploy):

**Files to commit:**
- `config/database.php` - Updated with SQLite support
- `config/Config.php` - Added DB_TYPE configuration
- `database/bloodmate_sqlite.sql` - SQLite schema
- `.env` - Updated SQLite configuration
- `api/admin-login.php` - Updated with middleware
- `api/login-user.php` - Updated with middleware
- `api/submit-request.php` - Updated with middleware
- `api/register-donor.php` - Updated with middleware

### 3. Commit and Push

```bash
git add .
git commit -m "Add SQLite support for Render deployment"
git push origin main
```

Render will automatically detect the changes and redeploy.

### 4. Verify Deployment

After deployment completes:

1. **Check Health Endpoint**
   ```
   https://bloodmate-p39s.onrender.com/health.php
   ```
   Should return: `{"status":"healthy",...}`

2. **Test Admin Login**
   - Go to: https://bloodmate-p39s.onrender.com/admin/login.html
   - Username: `admin`
   - Password: `admin123`
   - Should login successfully

### 5. Important Notes

**SQLite Limitations:**
- SQLite is file-based, not suitable for high-traffic production
- Database file is stored in `database/bloodmate.sqlite`
- If Render restarts, data persists (file is in repository)
- For production, consider migrating to PostgreSQL

**Security:**
- Change the default admin password immediately after first login
- Update JWT_SECRET to a secure random string
- Keep DEBUG=false in production

**Data Persistence:**
- The SQLite database file will be created automatically on first run
- It's stored in the `database/` directory
- Consider backing up the `.sqlite` file regularly

## Troubleshooting

### Issue: Database connection error
**Solution:** Ensure `DB_TYPE=sqlite` is set in Render environment variables

### Issue: Admin login still fails
**Solution:** 
1. Check Render logs for specific error
2. Verify environment variables are set correctly
3. Ensure all updated files are deployed

### Issue: Database not initializing
**Solution:** The database auto-initializes on first connection. If it fails, check file permissions in Render logs.

## Next Steps (Optional)

For production deployment, consider:
1. Migrating to Render PostgreSQL (free tier available)
2. Setting up automated backups
3. Configuring email service for notifications
4. Adding OpenAI API key for AI chatbot

## Files Changed Summary

- `config/database.php` - Added SQLite connection logic
- `config/Config.php` - Added DB_TYPE configuration
- `database/bloodmate_sqlite.sql` - New SQLite schema file
- `.env` - Updated for SQLite configuration
- `api/admin-login.php` - Security middleware integration
- `api/login-user.php` - Security middleware integration
- `api/submit-request.php` - Security middleware integration
- `api/register-donor.php` - Security middleware integration

## Default Admin Credentials

- **Username:** admin
- **Password:** admin123
- **Email:** admin@bloodmate.com

⚠️ **CHANGE THIS IMMEDIATELY AFTER FIRST LOGIN**

## Support

If you encounter issues:
1. Check Render service logs
2. Verify environment variables
3. Ensure all files are committed and pushed
4. Test health endpoint first
