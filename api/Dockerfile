FROM php:8.4-fpm-alpine

# Set working directory
WORKDIR /var/www/html

# Install system dependencies & libraries required for PostGIS/PostgreSQL, Redis, and ZIP
RUN apk update && apk add --no-cache \
    postgresql-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    curl \
    linux-headers \
    $PHPIZE_DEPS

# Install and configure PHP extensions
RUN docker-php-ext-install -j$(nproc) \
    pdo_pgsql \
    pgsql \
    bcmath \
    pcntl \
    opcache \
    zip

# Install and enable Redis extension via PECL
RUN pecl install redis && \
    docker-php-ext-enable redis

# Clean up dev dependencies to keep image lean
RUN apk del $PHPIZE_DEPS && rm -rf /var/cache/apk/*

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

