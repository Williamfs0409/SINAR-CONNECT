#!/usr/bin/env bash
set -e

PORT="${PORT:-8080}"
echo "[entrypoint] Booting SINAR-CONNECT on PORT=${PORT}"

# Bind Apache to Railway's $PORT (and also port 80 when PORT != 80 to avoid Target Port 80/8080 mismatch)
if [ "${PORT}" = "80" ]; then
    printf "Listen 80\n" > /etc/apache2/ports.conf
    sed -ri "s/<VirtualHost [^>]+>/<VirtualHost *:80>/g" /etc/apache2/sites-available/000-default.conf
else
    printf "Listen %s\nListen 80\n" "${PORT}" > /etc/apache2/ports.conf
    sed -ri "s/<VirtualHost [^>]+>/<VirtualHost *:${PORT} *:80>/g" /etc/apache2/sites-available/000-default.conf
fi

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

apache2ctl -t
exec apache2-foreground
