# Dockerfile
FROM php:8.2-apache

# Install extensions
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    && docker-php-ext-install pdo_mysql mysqli \
    && a2enmod rewrite headers

# Set working directory
WORKDIR /var/www/html

# Copy project files
COPY . .

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Apache config for pretty URLs (optional)
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Expose port (Railway automatically detects this)
EXPOSE 80

# Start Apache in foreground
CMD ["apache2-foreground"]
