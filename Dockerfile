# Use official PHP 8.1 Apache image
FROM php:8.1-apache

# Install PostgreSQL extension
RUN apt-get update && apt-get install -y \
    libpq-dev \
    postgresql-client \
    && docker-php-ext-install pdo pdo_pgsql pgsql

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . /var/www/html/

# Set permissions
RUN chown -R www-data:www-data /var/www/html
RUN chmod -R 755 /var/www/html

# Verify PostgreSQL extensions are installed
RUN php -m | grep -i pgsql

# Expose port 80
EXPOSE 80

# Start Apache
CMD ["apache2-foreground"]
