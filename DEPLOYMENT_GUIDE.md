# BloodMate Production Deployment Guide

## Overview
This guide provides step-by-step instructions for deploying BloodMate to a production environment for real-world use.

---

## Table of Contents
1. [Prerequisites](#prerequisites)
2. [Server Requirements](#server-requirements)
3. [Environment Setup](#environment-setup)
4. [Database Configuration](#database-configuration)
5. [SSL/HTTPS Setup](#sslhttps-setup)
6. [Docker Deployment](#docker-deployment)
7. [Manual Deployment](#manual-deployment)
8. [Email Configuration](#email-configuration)
9. [AI Chatbot Setup](#ai-chatbot-setup)
10. [Security Hardening](#security-hardening)
11. [Monitoring and Maintenance](#monitoring-and-maintenance)
12. [Troubleshooting](#troubleshooting)

---

## Prerequisites

### Required Software
- **Docker & Docker Compose** (for containerized deployment)
- **Git** (for version control)
- **Domain name** (for production deployment)
- **SSL Certificate** (Let's Encrypt recommended)
- **SMTP Server** (for email functionality)
- **OpenAI API Key** (for AI chatbot)

### Server Requirements
- **Minimum**: 2 CPU cores, 4GB RAM, 40GB storage
- **Recommended**: 4 CPU cores, 8GB RAM, 100GB storage
- **Operating System**: Ubuntu 22.04 LTS or similar
- **Web Server**: Apache/Nginx (included in Docker setup)

---

## Environment Setup

### 1. Clone the Repository
```bash
git clone <your-repository-url> BloodMate
cd BloodMate
```

### 2. Configure Environment Variables
```bash
# Copy the example environment file
cp .env.example .env

# Edit the .env file with your actual values
nano .env
```

### 3. Update .env Configuration
```bash
# Database Configuration
DB_HOST=mysql
DB_NAME=bloodmate
DB_USER=bloodmate_user
DB_PASSWORD=your_strong_password_here
DB_PORT=3306

# Application Configuration
APP_NAME=BloodMate
APP_URL=https://yourdomain.com
APP_ENV=production
DEBUG=false

# Email Configuration (SMTP)
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USER=your-email@gmail.com
SMTP_PASSWORD=your-app-password
SMTP_FROM=noreply@bloodmate.com
SMTP_FROM_NAME=BloodMate

# OpenAI API Configuration
OPENAI_API_KEY=sk-your-openai-api-key-here
OPENAI_MODEL=gpt-3.5-turbo

# JWT Authentication Secret
JWT_SECRET=generate-a-very-long-random-secret-key-min-32-characters

# Security Configuration
CORS_ALLOWED_ORIGINS=https://yourdomain.com,https://www.yourdomain.com
RATE_LIMIT_REQUESTS=100
RATE_LIMIT_PERIOD=60

# Admin Configuration
ADMIN_EMAIL=admin@bloodmate.com
ADMIN_USERNAME=admin
```

### 4. Generate Secure Secrets
```bash
# Generate JWT secret
openssl rand -base64 32

# Generate database password
openssl rand -base64 16
```

---

## Database Configuration

### 1. Initialize Database Schema
```bash
# The database schema will be automatically initialized when you start the Docker containers
# The schema file is located at: database/bloodmate_schema.sql
```

### 2. Manual Database Setup (if not using Docker)
```bash
# Create database
mysql -u root -p
CREATE DATABASE bloodmate;
CREATE USER 'bloodmate_user'@'localhost' IDENTIFIED BY 'your_password';
GRANT ALL PRIVILEGES ON bloodmate.* TO 'bloodmate_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;

# Import schema
mysql -u bloodmate_user -p bloodmate < database/bloodmate_schema.sql
```

### 3. Update Default Admin Password
```bash
# The default admin password is 'admin123' - CHANGE THIS IMMEDIATELY
# Connect to database and update:
mysql -u bloodmate_user -p bloodmate
UPDATE admin_users SET password_hash = '$2y$10$your-new-hash-here' WHERE username = 'admin';
```

---

## SSL/HTTPS Setup

### Option 1: Let's Encrypt (Recommended)
```bash
# Install Certbot
sudo apt-get install certbot python3-certbot-nginx

# Obtain SSL certificate
sudo certbot --nginx -d yourdomain.com -d www.yourdomain.com

# Auto-renewal is configured automatically
sudo certbot renew --dry-run
```

### Option 2: Commercial SSL Certificate
1. Purchase SSL certificate from provider
2. Upload certificate files to `nginx/ssl/` directory:
   - `fullchain.pem` (certificate + chain)
   - `privkey.pem` (private key)
   - `chain.pem` (intermediate chain)

### Option 3: Self-Signed (Development Only)
```bash
# Generate self-signed certificate
openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
  -keyout nginx/ssl/privkey.pem \
  -out nginx/ssl/fullchain.pem
```

---

## Docker Deployment

### 1. Build and Start Containers
```bash
# Build and start all services
docker-compose up -d --build

# Check container status
docker-compose ps

# View logs
docker-compose logs -f
```

### 2. Service Health Checks
```bash
# Check web application
curl http://localhost/health.php

# Check database connection
docker exec bloodmate-mysql mysql -u bloodmate_user -p bloodmate -e "SELECT 1"

# Check Redis
docker exec bloodmate-redis redis-cli ping
```

### 3. Access Services
- **Web Application**: http://localhost or https://yourdomain.com
- **phpMyAdmin** (development): http://localhost:8080
- **MySQL**: localhost:3306
- **Redis**: localhost:6379

### 4. Docker Production Commands
```bash
# Stop all services
docker-compose down

# Stop and remove volumes (WARNING: deletes data)
docker-compose down -v

# Update application
git pull
docker-compose up -d --build

# View resource usage
docker stats
```

---

## Manual Deployment

### 1. Install PHP and Dependencies
```bash
sudo apt-get update
sudo apt-get install php8.2 php8.2-fpm php8.2-mysql php8.2-mbstring \
  php8.2-xml php8.2-curl php8.2-zip php8.2-bcmath php8.2-gd

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

### 2. Install Apache Web Server
```bash
sudo apt-get install apache2 libapache2-mod-php8.2

# Enable required modules
sudo a2enmod rewrite
sudo a2enmod ssl
sudo systemctl restart apache2
```

### 3. Configure Apache Virtual Host
```apache
<VirtualHost *:80>
    ServerName yourdomain.com
    DocumentRoot /var/www/html/BloodMate
    
    <Directory /var/www html/BloodMate>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/bloodmate_error.log
    CustomLog ${APACHE_LOG_DIR}/bloodmate_access.log combined
</VirtualHost>

# SSL Virtual Host
<VirtualHost *:443>
    ServerName yourdomain.com
    DocumentRoot /var/www/html/BloodMate
    
    SSLEngine on
    SSLCertificateFile /etc/ssl/certs/fullchain.pem
    SSLCertificateKeyFile /etc/ssl/private/privkey.pem
    
    <Directory /var/www/html/BloodMate>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

### 4. Set File Permissions
```bash
sudo chown -R www-data:www-data /var/www/html/BloodMate
sudo chmod -R 755 /var/www/html/BloodMate
sudo chmod -R 777 /var/www/html/BloodMate/logs
sudo chmod -R 777 /var/www/html/BloodMate/cache
sudo chmod -R 777 /var/www/html/BloodMate/uploads
```

### 5. Install PHP Dependencies
```bash
cd /var/www/html/BloodMate
composer install --no-dev --optimize-autoloader
```

---

## Email Configuration

### 1. Gmail SMTP Setup
```bash
# Enable 2-factor authentication on your Google account
# Generate an App Password: Google Account → Security → App Passwords
# Use the App Password in SMTP_PASSWORD
```

### 2. SendGrid Setup
```bash
# Create SendGrid account
# Generate API key
# Update .env:
SMTP_HOST=smtp.sendgrid.net
SMTP_PORT=587
SMTP_USER=apikey
SMTP_PASSWORD=your-sendgrid-api-key
```

### 3. Custom SMTP Server
```bash
# Update .env with your SMTP server details
SMTP_HOST=your-smtp-server.com
SMTP_PORT=587
SMTP_USER=your-username
SMTP_PASSWORD=your-password
```

### 4. Test Email Configuration
```bash
# Create test script: test-email.php
<?php
require_once 'config/EmailService.php';
$emailService = new EmailService();
$result = $emailService->sendEmail('test@example.com', 'Test Email', 'This is a test');
var_dump($result);
?>
```

---

## AI Chatbot Setup

### 1. OpenAI API Setup
```bash
# Create OpenAI account: https://platform.openai.com/
# Generate API key: https://platform.openai.com/api-keys
# Add API key to .env:
OPENAI_API_KEY=sk-your-actual-api-key-here
```

### 2. Update Frontend Chatbot
```javascript
// Update the chatbot JavaScript in index.html to use the real API
async function sendMessage() {
    const input = document.getElementById('chatbotInput');
    const message = input.value.trim();
    if (!message) return;

    addMessage(message, 'user');
    input.value = '';

    showTypingIndicator();

    try {
        const response = await fetch('/api/chatbot.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                message: message,
                history: getConversationHistory()
            })
        });

        const data = await response.json();
        
        removeTypingIndicator();
        
        if (data.success) {
            addMessage(data.response, 'bot');
        } else {
            addMessage('Sorry, I encountered an error. Please try again.', 'bot');
        }
    } catch (error) {
        removeTypingIndicator();
        addMessage('Sorry, I encountered an error. Please try again.', 'bot');
    }
}
```

### 3. Test Chatbot
```bash
# Test the chatbot API endpoint
curl -X POST http://localhost/api/chatbot.php \
  -H "Content-Type: application/json" \
  -d '{"message":"Hello, can I donate blood?"}'
```

---

## Security Hardening

### 1. Firewall Configuration
```bash
# Configure UFW firewall
sudo ufw allow 22/tcp    # SSH
sudo ufw allow 80/tcp    # HTTP
sudo ufw allow 443/tcp   # HTTPS
sudo ufw enable

# Block direct MySQL access from outside
sudo ufw deny 3306/tcp
```

### 2. Fail2Ban Setup
```bash
sudo apt-get install fail2ban

# Create jail configuration
sudo nano /etc/fail2ban/jail.local
```

### 3. Regular Security Updates
```bash
# Enable automatic security updates
sudo apt-get install unattended-upgrades
sudo dpkg-reconfigure -plow unattended-upgrades
```

### 4. File Security
```bash
# Protect sensitive files
sudo chmod 600 .env
sudo chown root:root .env

# Protect configuration directories
sudo chmod 750 config/
sudo chown www-data:www-data config/
```

### 5. Database Security
```bash
# Remove test databases
mysql -u root -p -e "DROP DATABASE IF EXISTS test;"

# Remove anonymous users
mysql -u root -p -e "DELETE FROM mysql.user WHERE User='';"

# Remove remote root access
mysql -u root -p -e "DELETE FROM mysql.user WHERE User='root' AND Host NOT IN ('localhost', '127.0.0.1', '::1');"
```

---

## Monitoring and Maintenance

### 1. Database Backups
```bash
# Make backup script executable
chmod +x scripts/backup-database.sh

# Add to crontab for daily backups
crontab -e

# Add this line for daily backup at 2 AM
0 2 * * * /path/to/BloodMate/scripts/backup-database.sh
```

### 2. Log Rotation
```bash
# Create logrotate configuration
sudo nano /etc/logrotate.d/bloodmate

/var/www/html/BloodMate/logs/*.log {
    daily
    missingok
    rotate 30
    compress
    delaycompress
    notifempty
    create 0640 www-data www-data
    sharedscripts
}
```

### 3. System Monitoring
```bash
# Install monitoring tools
sudo apt-get install htop iotop nethogs

# Monitor Docker containers
docker stats

# View application logs
docker-compose logs -f web
```

### 4. Performance Monitoring
```bash
# Monitor PHP-FPM
sudo tail -f /var/log/php8.2-fpm.log

# Monitor Apache
sudo tail -f /var/log/apache2/access.log
sudo tail -f /var/log/apache2/error.log

# Monitor MySQL
sudo tail -f /var/log/mysql/error.log
```

### 5. Health Checks
```bash
# Create health check script
#!/bin/bash
# health-check.sh

# Check web server
curl -f http://localhost/health.php || exit 1

# Check database
docker exec bloodmate-mysql mysqladmin ping -h localhost || exit 1

# Check Redis
docker exec bloodmate-redis redis-cli ping || exit 1

echo "All services healthy"
```

---

## Troubleshooting

### Common Issues

#### 1. Database Connection Failed
```bash
# Check if MySQL container is running
docker-compose ps mysql

# Check MySQL logs
docker-compose logs mysql

# Test connection
docker exec bloodmate-mysql mysql -u bloodmate_user -p bloodmate
```

#### 2. Email Not Sending
```bash
# Check SMTP configuration in .env
# Test SMTP connection
telnet smtp.gmail.com 587

# Check email logs
tail -f logs/error.log
```

#### 3. Chatbot Not Responding
```bash
# Check OpenAI API key in .env
# Test API endpoint
curl -X POST http://localhost/api/chatbot.php \
  -H "Content-Type: application/json" \
  -d '{"message":"test"}'

# Check chatbot logs
tail -f logs/error.log
```

#### 4. SSL Certificate Issues
```bash
# Renew Let's Encrypt certificate
sudo certbot renew

# Check certificate expiration
sudo certbot certificates

# Test SSL configuration
openssl s_client -connect yourdomain.com:443
```

#### 5. High Memory Usage
```bash
# Check container resource usage
docker stats

# Limit container resources in docker-compose.yml:
services:
  web:
    deploy:
      resources:
        limits:
          memory: 512M
```

### Emergency Procedures

#### 1. Restore Database from Backup
```bash
# Stop application
docker-compose down

# Restore database
docker run --rm -v bloodmate-mysql-data:/var/lib/mysql \
  -v /path/to/backups:/backups \
  mysql:8.0 sh -c \
  'exec mysql -h mysql -u bloodmate_user -p${MYSQL_PASSWORD} bloodmate < /backups/latest_backup.sql'

# Restart application
docker-compose up -d
```

#### 2. Emergency Rollback
```bash
# Rollback to previous Git commit
git log
git checkout <previous-commit-hash>

# Restart services
docker-compose down
Docker-compose up -d --build
```

#### 3. Disable Application in Emergency
```bash
# Create maintenance page
echo "System Maintenance - We'll be back soon" > maintenance.html

# Update Apache/Nginx to serve maintenance page
# Or stop Docker containers
docker-compose stop web
```

---

## Post-Deployment Checklist

- [ ] Environment variables configured correctly
- [ ] Database schema imported successfully
- [ ] SSL/HTTPS certificate installed and working
- [ ] Email service tested and working
- [ ] AI chatbot API key configured and tested
- [ ] Admin password changed from default
- [ ] Firewall configured properly
- [ ] Database backup script scheduled
- [ ] Log rotation configured
- [ ] Monitoring tools installed
- [ ] Health checks configured
- [ ] Security headers verified
- [ ] Rate limiting tested
- [ ] CORS configuration verified
- [ ] File permissions set correctly
- [ ] Error logging working
- [ ] All API endpoints tested
- [ ] Frontend functionality tested
- [ ] Mobile responsiveness verified
- [ ] Performance baseline established

---

## Support and Resources

### Documentation
- [BloodMate README](README.md)
- [API Documentation](API_DOCUMENTATION.md)
- [Database Schema](database/bloodmate_schema.sql)

### External Resources
- [PHP Documentation](https://www.php.net/docs.php)
- [MySQL Documentation](https://dev.mysql.com/doc/)
- [Docker Documentation](https://docs.docker.com/)
- [OpenAI API Documentation](https://platform.openai.com/docs)

### Emergency Contact
- Email: admin@bloodmate.com
- Phone: +91 123 456 7890

---

**Document Version:** 1.0  
**Last Updated:** July 16, 2026  
**Compatible with:** BloodMate v2.0 Production
