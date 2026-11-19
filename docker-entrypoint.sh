#!/usr/bin/env bash
set -e

PORT="${PORT:-8080}"
echo "[entrypoint] Booting SINAR-CONNECT on PORT=${PORT}"

# Ensure only mpm_prefork is loaded (prevents AH00534: More than one MPM loaded)
rm -f /etc/apache2/mods-enabled/mpm_*.load /etc/apache2/mods-enabled/mpm_*.conf
a2enmod mpm_prefork >/dev/null 2>&1 || true

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

# Run pending migrations safely (auto-mark baseline db_tk_final migrations if dump was pre-imported)
if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
    php -r '
        require "/var/www/html/vendor/autoload.php";
        $app = require "/var/www/html/bootstrap/app.php";
        $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
        if (Illuminate\Support\Facades\Schema::hasTable("audit_log") && Illuminate\Support\Facades\Schema::hasTable("siswa")) {
            if (! Illuminate\Support\Facades\Schema::hasTable("migrations")) {
                Illuminate\Support\Facades\Schema::create("migrations", function ($t) {
                    $t->increments("id");
                    $t->string("migration");
                    $t->integer("batch");
                });
            }
            $existing = Illuminate\Support\Facades\DB::table("migrations")->pluck("migration")->all();
            foreach (glob("/var/www/html/database/migrations/0000_01_*.php") as $file) {
                $name = basename($file, ".php");
                if (! in_array($name, $existing, true)) {
                    Illuminate\Support\Facades\DB::table("migrations")->insert(["migration" => $name, "batch" => 1]);
                }
            }
        }
    ' || true
    php artisan migrate --force || echo "[entrypoint] Migration skipped or already satisfied."
fi

apache2ctl -t
exec apache2-foreground
