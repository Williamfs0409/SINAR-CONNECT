#!/usr/bin/env bash
set -e

PORT="${PORT:-8080}"

# Bind Apache to Railway's dynamic $PORT
sed -ri "s/Listen [0-9]+/Listen ${PORT}/g" /etc/apache2/ports.conf
sed -ri "s/<VirtualHost \*:[0-9]+>/<VirtualHost *:${PORT}>/g" /etc/apache2/sites-available/000-default.conf

# Ensure writable storage directories (especially when Railway Volume is mounted)
mkdir -p \
    /var/www/html/storage/app/private \
    /var/www/html/storage/app/public \
    /var/www/html/storage/framework/cache/data \
    /var/www/html/storage/framework/sessions \
    /var/www/html/storage/framework/views \
    /var/www/html/storage/logs \
    /var/www/html/bootstrap/cache

chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Rebuild runtime caches (skip route:cache because routes/web.php uses Closure routes)
php artisan config:clear || true
php artisan config:cache || true
php artisan view:cache || true

# Run pending migrations safely (skip without crashing if existing dump lacks migrations table)
if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
    php artisan migrate --force || echo "[entrypoint] Migration skipped or already satisfied."
fi

exec apache2-foreground
