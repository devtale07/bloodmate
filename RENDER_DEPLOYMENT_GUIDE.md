# BloodMate Render Deployment Guide

## Issue: Database Connection Error on Render

The error you're experiencing is because the database is not properly configured on Render. BloodMate requires a MySQL database to function, and the old API endpoints were not using the updated configuration system.

## Solution Steps

### 1. Set Up MySQL Database on Render

**Option A: Use Render's Managed PostgreSQL (Recommended)**
Render offers free PostgreSQL databases. We'll need to adapt the code to use PostgreSQL instead of MySQL.

**Option B: Use External MySQL Service**
Use an external MySQL service like PlanetScale, Railway, or AWS RDS.

**Option C: Use Render with MySQL Docker**
Deploy MySQL as a separate service on Render.

### 2. Update Environment Variables on Render

Go to your Render dashboard and add these environment variables:

```bash
# Database Configuration (Replace with your actual database credentials)
DB_HOST=your-database-host
DB_NAME=bloodmate
DB_USER=your-database-user
DB_PASSWORD=your-database-password
DB_PORT=3306

# Application Configuration
APP_NAME=BloodMate
APP_URL=https://bloodmate-p39s.onrender.com
APP_ENV=production
DEBUG=false

# Email Configuration (Optional - for production)
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USER=your-email@gmail.com
SMTP_PASSWORD=your-app-password
SMTP_FROM=noreply@bloodmate.com
SMTP_FROM_NAME=BloodMate

# OpenAI API Configuration (Optional - for AI chatbot)
OPENAI_API_KEY=your-openai-api-key
OPENAI_MODEL=gpt-3.5-turbo

# JWT Authentication Secret (Generate a secure random string)
JWT_SECRET=generate-a-very-long-random-secret-key-min-32-characters

# Security Configuration
CORS_ALLOWED_ORIGINS=https://bloodmate-p39s.onrender.com
RATE_LIMIT_REQUESTS=100
RATE_LIMIT_PERIOD=60

# Admin Configuration
ADMIN_EMAIL=admin@bloodmate.com
ADMIN_USERNAME=admin
```

### 3. Initialize Database Schema

Once your database is set up, you need to import the schema:

```bash
# Connect to your database
mysql -h your-host -u your-user -p your-database

# Import the schema
SOURCE database/bloodmate_schema.sql;
```

Or use the database management interface provided by your hosting service to run the SQL from `database/bloodmate_schema.sql`.

### 4. Quick Fix: Use SQLite for Testing (Temporary)

If you want to get it working quickly for testing, we can modify the code to use SQLite (file-based database) which doesn't require a separate database server.

**Steps:**
1. I'll create a SQLite adapter
2. Update the database configuration to use SQLite
3. This will work immediately on Render without external database setup

### 5. Recommended: Use Render PostgreSQL (Best for Production)

Render provides free PostgreSQL databases. Here's how to set it up:

1. **Create PostgreSQL Database on Render**
   - Go to Render Dashboard → New → PostgreSQL
   - Choose a name (e.g., `bloodmate-db`)
   - Select the free tier
   - Create the database

2. **Get Database Credentials**
   - Render will provide:
     - Internal Database URL
     - External Database URL
     - Username
     - Password
     - Database Name

3. **Update Environment Variables**
   Add these to your Render web service:
   ```bash
   DB_HOST=your-postgres-host.render.com
   DB_NAME=bloodmate
   DB_USER=your-username
   DB_PASSWORD=your-password
   DB_PORT=5433
   ```

4. **Update Database Driver**
   I'll need to modify the database configuration to support PostgreSQL.

## Immediate Action Required

**To fix the current error, you need to:**

1. **Set up a database** (MySQL or PostgreSQL)
2. **Configure environment variables** on Render with database credentials
3. **Import the database schema** from `database/bloodmate_schema.sql`

## Which Option Do You Prefer?

1. **Quick Fix**: I can modify the code to use SQLite (works immediately, no database setup needed)
2. **Production Ready**: I can help you set up PostgreSQL on Render (recommended for production)
3. **External Database**: You provide MySQL credentials from another service

Let me know which option you'd like to proceed with, and I'll implement the necessary changes.

## Updated Files

I've already updated these API endpoints to use the new middleware system:
- ✅ `api/admin-login.php` - Now uses new Auth and Security middleware
- ✅ `api/login-user.php` - Now uses new Auth and Security middleware  
- ✅ `api/submit-request.php` - Now uses new middleware
- ✅ `api/register-donor.php` - Already updated earlier

These endpoints now have:
- Better error handling
- Input sanitization
- Rate limiting
- Security logging
- JWT authentication
- Proper database connection checks

## Next Steps

Once you choose a database option, I will:
1. Update the database configuration to support your chosen database
2. Create a database initialization script
3. Provide deployment instructions
4. Test the admin login functionality

Please let me know which database option you prefer, and I'll proceed with the implementation.
