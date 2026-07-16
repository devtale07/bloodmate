# BloodMate Production Dockerfile
# Multi-stage build for optimal image size

# Stage 1: Build stage
FROM composer:2.5 as build

WORKDIR /app

# Copy composer files
COPY composer.json composer.lock* ./

# Install dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Stage 2: Production stage
FROM php:8.2-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install \
    pdo_mysql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Configure Apache
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY --from=build /app/vendor ./vendor
COPY . .

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Create necessary directories
RUN mkdir -p /var/www/html/logs \
    && mkdir -p /var/www/html/cache \
    && mkdir -p /var/www/html/uploads \
    && chown -R www-data:www-data /var/www/html/logs \
    && chown -R www-data:www-data /var/www/html/cache \
    && chown -R www-data:www-data /var/www/html/uploads

# Expose port 80
EXPOSE 80

# Health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=40s --retries=3 \
    CMD php -r "try { \$pdo = new PDO('mysql:host=\$_ENV[DB_HOST];dbname=\$_ENV[DB_NAME]', \$_ENV[DB_USER], \$_ENV[DB_PASSWORD]); echo 'OK'; } catch(Exception \$e) { exit(1); }"

# Start Apache in foreground
CMD ["apache2-foreground"]
