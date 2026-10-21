FROM php:8.4-fpm-alpine

# Set working directory
WORKDIR /var/www/html

# Install system dependencies needed by git/composer
RUN apk add --no-cache \
    git \
    curl \
    zip \
    unzip

# Install PHP extensions using official installer helper (resolves PECL issues in PHP 8.4 Alpine)
COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions \
    pdo_pgsql \
    pgsql \
    bcmath \
    pcntl \
    opcache \
    zip \
    redis

# Install Composer from official Composer image
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Configure custom php.ini settings for API performance
RUN { \
    echo 'upload_max_filesize = 10M;'; \
    echo 'post_max_size = 12M;'; \
    echo 'memory_limit = 256M;'; \
    echo 'opcache.enable = 1;'; \
    echo 'opcache.enable_cli = 1;'; \
    echo 'opcache.validate_timestamps = 1;'; \
    echo 'opcache.revalidate_freq = 0;'; \
} > /usr/local/etc/php/conf.d/custom.ini

# Copy application code
COPY . /var/www/html

# Install Composer production dependencies
RUN composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev

# Ensure www-data has correct permissions on working directory
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Expose PHP-FPM port
EXPOSE 9000

# Run as non-root user for security
USER www-data

CMD ["php-fpm"]

