#!/bin/bash
set -e

echo "=========================================================="
echo "  Power Outage Reporting Platform — Automated Setup Script"
echo "=========================================================="

# 1. Copy environment templates if they do not exist
echo "--> Checking environment configuration files..."
if [ ! -f .env ]; then
    echo "  Creating root .env from .env.example..."
    cp .env.example .env
fi

if [ ! -f api/.env ]; then
    echo "  Creating api/.env from api/.env.example..."
    cp api/.env.example api/.env
fi

if [ ! -f client/.env ]; then
    echo "  Creating client/.env from client/.env.example..."
    cp client/.env.example client/.env
fi

# 2. Install Composer dependencies inside container
echo "--> Installing Composer dependencies (backend)..."
docker compose run --rm --user root api composer install --no-interaction --prefer-dist --optimize-autoloader

# 3. Ensure storage and cache write permissions
echo "--> Configuring storage and cache directory permissions..."
docker compose run --rm --user root api chmod -R 777 /var/www/html/storage /var/www/html/bootstrap/cache

# 4. Generate application encryption key
if ! grep -q "^APP_KEY=base64:" api/.env; then
    echo "--> Generating Laravel encryption key (APP_KEY)..."
    docker compose run --rm api php artisan key:generate --force
fi

# 5. Start all containers in background
echo "--> Starting Docker Compose services (7 containers)..."
docker compose up -d

# 6. Run database migrations and seeders
echo "--> Initializing databases, spatial extensions, and seeders..."
docker compose exec -T db psql -U powerout_user -d postgres -c "SELECT 1 FROM pg_database WHERE datname = 'powerout_test'" | grep -q 1 || docker compose exec -T db psql -U powerout_user -d postgres -c "CREATE DATABASE powerout_test;"
docker compose exec -T db psql -U powerout_user -d powerout_test -c "CREATE EXTENSION IF NOT EXISTS postgis;"
docker compose exec api php artisan migrate --seed --force
docker compose exec -e DB_DATABASE=powerout_test api php artisan migrate --force

echo ""
echo "=========================================================="
echo "  All systems operational!"
echo "=========================================================="
echo "  Frontend SPA (React 18 + Vite):  http://localhost:5173"
echo "  Backend API (Nginx + PHP-FPM):   http://localhost:8080"
echo "  Public Outages Endpoint:         http://localhost:8080/api/v1/public/outages"
echo "  Demo Credentials:                demo@powerout.test / password"
echo "  Automated Tests:                 docker compose exec api php artisan test"
echo "=========================================================="
