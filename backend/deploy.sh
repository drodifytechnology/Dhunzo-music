#!/usr/bin/env bash
# ==============================================================================
# Production Deployment Script for BlackHole / Dhunzo Music & Lyrics API
# ==============================================================================
set -e

echo "🚀 Starting Production Deployment..."

# 1. Maintenance Mode
php artisan down || true

# 2. Install production dependencies
echo "📦 Installing composer dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction

# 3. Run database migrations
echo "🗄️ Running database migrations..."
php artisan migrate --force

# 4. Cache Configurations, Routes, and Events
echo "⚡ Caching configurations & routes..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 5. Clear old application cache
php artisan cache:clear

# 6. Bring application back online
php artisan up

echo "✅ Production Deployment Completed Successfully!"
