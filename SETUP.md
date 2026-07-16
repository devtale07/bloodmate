# BloodMate Setup Guide

## Quick Start

### 1. Database Setup

1. Create MySQL database:
```sql
CREATE DATABASE bloodmate;
```

2. Import schema:
```bash
mysql -u root -p bloodmate < database/bloodmate_schema.sql
```

3. Update `config/database.php` with your credentials:
```php
private $host = 'localhost';
private $db_name = 'bloodmate';
private $username = 'your_username';
private $password = 'your_password';
```

### 2. Web Server Setup

**For XAMPP/WAMP:**
1. Copy BloodMate folder to `htdocs/`
2. Start Apache and MySQL
3. Visit `http://localhost/BloodMate/`

**For LAMP/LEMP:**
1. Copy files to `/var/www/html/bloodmate/`
2. Set proper permissions:
```bash
sudo chown -R www-data:www-data /var/www/html/bloodmate/
sudo chmod -R 755 /var/www/html/bloodmate/
```

### 3. Default Admin Access
- URL: `http://localhost/BloodMate/admin/login.html`
- Username: `admin`
- Password: `admin123`

**⚠️ IMPORTANT: Change default password immediately!**

## Testing the System

### Test Donor Registration
1. Go to "Become a Donor"
2. Fill form with test data
3. Check database `donors` table

### Test Blood Request
1. Go to "Request Blood"
2. Submit urgent request
3. Check `recipient_requests` table

### Test Admin Panel
1. Login to admin dashboard
2. View statistics
3. Approve pending requests
4. Add blood stock

## Troubleshooting

### Common Issues

**Database Connection Error:**
- Check MySQL service is running
- Verify credentials in `config/database.php`
- Ensure database exists

**Permission Denied:**
- Check file permissions
- Ensure web server can write to logs

**API Not Working:**
- Check mod_rewrite is enabled
- Verify .htaccess file is present
- Check PHP error logs

### Error Logs
Check these locations for errors:
- PHP error log
- Apache/Nginx error log
- Browser console (F12)

## Production Deployment

### Security Checklist
- [ ] Change default admin password
- [ ] Update database credentials
- [ ] Enable HTTPS
- [ ] Configure proper file permissions
- [ ] Set up regular backups
- [ ] Enable error logging
- [ ] Configure email notifications

### Performance Optimization
- Enable gzip compression
- Set up caching headers
- Optimize database queries
- Use CDN for static assets

## Maintenance

### Regular Tasks
- Backup database weekly
- Monitor disk space
- Check error logs
- Update blood inventory
- Review pending requests

### Database Maintenance
```sql
-- Clean old requests (older than 1 year)
DELETE FROM recipient_requests WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 YEAR);

-- Clean old messages (older than 6 months)
DELETE FROM contact_messages WHERE created_at < DATE_SUB(NOW(), INTERVAL 6 MONTH);
```
