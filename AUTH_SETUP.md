# BloodMate Authentication System Setup Guide

## Overview
This authentication system provides:
- User registration with automatic email credential delivery
- User login with secure authentication
- Admin login on the same page
- Unified authentication interface

## Files Created/Modified

### New Files:
1. `auth.html` - Unified authentication page with registration, login, and admin tabs
2. `api/register-user.php` - User registration API with email functionality
3. `api/login-user.php` - User login API
4. `js/auth.js` - JavaScript for authentication page
5. `database/add_users_table.sql` - SQL script to add users table

### Modified Files:
1. `database/bloodmate_schema.sql` - Added users table to schema
2. `index.html` - Added Login/Register link to navigation

## Setup Instructions

### Step 1: Database Setup

Run the SQL script to add the users table to your existing database:

```bash
mysql -u root -p bloodmate < database/add_users_table.sql
```

Or manually execute the SQL in `database/add_users_table.sql` using your MySQL client.

### Step 2: Email Configuration

The registration system uses PHP's `mail()` function to send credentials. For production use, you may need to configure your server's email settings:

1. **For Local Development:**
   - Install a local mail server (like Postfix) or use a service like MailHog
   - Configure PHP's php.ini to use the mail server

2. **For Production:**
   - Configure your server's SMTP settings
   - Consider using a library like PHPMailer for better email handling
   - Update the `sendCredentialsEmail()` function in `api/register-user.php`

### Step 3: File Permissions

Ensure proper permissions for the API files:

```bash
chmod 644 api/register-user.php
chmod 644 api/login-user.php
chmod 644 api/admin-login.php
```

### Step 4: Test the System

1. **Access the Authentication Page:**
   - Navigate to `http://localhost/BloodMate/auth.html`
   - Or click "Login/Register" from the main page

2. **Test User Registration:**
   - Fill in the registration form (name, email, phone)
   - Submit the form
   - Check your email for credentials (username and password)
   - Note: If email fails, credentials will be displayed on screen

3. **Test User Login:**
   - Switch to the Login tab
   - Enter the username and password from the email
   - Submit to login
   - Successful login redirects to `index.html`

4. **Test Admin Login:**
   - Switch to the Admin tab
   - Use default admin credentials:
     - Username: `admin`
     - Password: `admin123`
   - Submit to login
   - Successful login redirects to `admin/dashboard.html`

## Default Credentials

### Admin Account:
- **Username:** admin
- **Password:** admin123
- **Role:** Super Admin

⚠️ **Important:** Change the default admin password in production!

## Security Notes

1. **Password Storage:** All passwords are hashed using PHP's `password_hash()` with BCRYPT algorithm
2. **Token System:** Simple base64-encoded tokens are used (upgrade to JWT for production)
3. **Session Management:** Tokens are stored in localStorage with 24-hour expiration
4. **Email Security:** Credentials are sent via email (consider using password reset links instead)

## Customization Options

### Change Email Template:
Edit the `sendCredentialsEmail()` function in `api/register-user.php` to customize the email content and styling.

### Modify Password Requirements:
Update the `generateRandomPassword()` function in `api/register-user.php` to change password complexity.

### Add Email Verification:
The system currently auto-verifies users. To add manual verification:
1. Set `is_verified = FALSE` by default in the registration
2. Add a verification token system
3. Create an email verification endpoint

### Add Remember Me Functionality:
Extend the token expiration time and implement persistent cookies.

## Troubleshooting

### Email Not Sending:
- Check PHP error logs
- Verify mail server configuration
- Test with a simple PHP mail script
- Check spam/junk folders

### Database Connection Error:
- Verify database credentials in `config/database.php`
- Ensure MySQL server is running
- Check database name matches

### Login Not Working:
- Verify the users table was created successfully
- Check that passwords are being hashed correctly
- Ensure JavaScript is loading properly
- Check browser console for errors

### Admin Login Fails:
- Verify admin_users table exists and has the default admin
- Check that the admin account is active (`is_active = 1`)
- Verify password hash matches

## File Structure Reference

```
BloodMate/
├── auth.html                          # New unified auth page
├── index.html                         # Modified: added auth link
├── api/
│   ├── register-user.php              # New: user registration API
│   ├── login-user.php                 # New: user login API
│   └── admin-login.php                # Existing: admin login API
├── js/
│   ├── auth.js                        # New: auth page JavaScript
│   └── admin-login.js                 # Existing: admin login JavaScript
├── database/
│   ├── bloodmate_schema.sql           # Modified: added users table
│   └── add_users_table.sql            # New: migration script
└── config/
    └── database.php                   # Existing: database configuration
```

## Next Steps

1. Apply the database changes
2. Test the registration and login flow
3. Configure email for production
4. Change default admin password
5. Consider implementing additional security features (2FA, password reset, etc.)

## Support

If you encounter any issues:
1. Check browser console for JavaScript errors
2. Check PHP error logs for server-side issues
3. Verify database connectivity
4. Test email functionality separately
