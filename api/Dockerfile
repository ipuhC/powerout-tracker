FROM serversideup/php:8.4-fpm

# Set working directory
WORKDIR /var/www/html

# Copy application files with correct ownership for www-data
COPY --chown=www-data:www-data . /var/www/html

# Switch to unprivileged user
USER www-data

# Install Composer production dependencies
RUN composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev

# Expose PHP-FPM port
EXPOSE 9000
