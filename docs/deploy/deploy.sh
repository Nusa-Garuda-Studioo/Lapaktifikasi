#!/usr/bin/env bash
# ==============================================================================
# LAPAKTIFIKASI - PRODUCTION ZERO-DOWNTIME DEPLOYMENT SCRIPT
# Recommended location: /var/www/lapaktifikasi/deploy.sh
# Usage: ./deploy.sh
# ==============================================================================

set -e

echo "🚀 [1/8] Starting Lapaktifikasi Production Deployment..."

# 1. Enter maintenance mode with friendly message (or bypass secret)
php artisan down --secret="lapaktifikasi-deploy-bypass" --render="errors::503" || true

# 2. Pull latest code from main branch
echo "📦 [2/8] Fetching latest Git updates..."
git fetch origin main
git reset --hard origin/main

# 3. Install composer dependencies without dev packages & generate authoritative classmap
echo "⚡ [3/8] Optimizing Composer dependencies & autoloader..."
composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader --classmap-authoritative
composer dump-autoload -o

# 4. Build frontend assets with Vite (ES2020 target & hashed bundles)
echo "🎨 [4/8] Building production frontend assets (Vite)..."
npm ci --prefer-offline --no-audit
npm run build

# 5. Run database migrations safely
echo "🗄️ [5/8] Running database migrations..."
php artisan migrate --force

# 6. Clear stale caches and pre-compile production caches
echo "🧹 [6/8] Compiling Laravel production caches..."
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
php artisan optimize

# 7. Restart background queues & reload PHP-FPM for OPcache refresh
echo "🔄 [7/8] Restarting Queue workers & reloading PHP-FPM..."
php artisan queue:restart

# Reload PHP-FPM to invalidate OPcache memory cleanly
if systemctl is-active --quiet php8.3-fpm; then
    sudo systemctl reload php8.3-fpm
elif systemctl is-active --quiet php8.2-fpm; then
    sudo systemctl reload php8.2-fpm
elif systemctl is-active --quiet php-fpm; then
    sudo systemctl reload php-fpm
fi

# Reload Nginx / Apache
if systemctl is-active --quiet nginx; then
    sudo systemctl reload nginx
elif systemctl is-active --quiet apache2; then
    sudo systemctl reload apache2
fi

# 8. Bring application back online
echo "✨ [8/8] Bringing application back online..."
php artisan up

echo "✅ [SUCCESS] Lapaktifikasi successfully deployed and optimized!"
