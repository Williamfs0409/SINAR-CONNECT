#!/usr/bin/env bash
set -e

PORT="${PORT:-8080}"
echo "[entrypoint] Booting SINAR-CONNECT (PHP-FPM + Nginx) on PORT=${PORT}"

# Build Nginx listen directives (IPv4 + IPv6 when available, plus port 80 fallback)
LISTEN_BLOCK="    listen ${PORT} default_server;"
if [ -f /proc/net/if_inet6 ]; then
    LISTEN_BLOCK="${LISTEN_BLOCK}
    listen [::]:${PORT} default_server;"
fi
if [ "${PORT}" != "80" ]; then
    LISTEN_BLOCK="${LISTEN_BLOCK}
    listen 80 default_server;"
    if [ -f /proc/net/if_inet6 ]; then
        LISTEN_BLOCK="${LISTEN_BLOCK}
    listen [::]:80 default_server;"
    fi
fi

cat > /etc/nginx/conf.d/default.conf <<EOF
server {
${LISTEN_BLOCK}
    server_name _;

    root /var/www/html/public;
    index index.php;

    client_max_body_size 25M;
    charset utf-8;

    access_log /dev/stdout;
    error_log /dev/stderr warn;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass 127.0.0.1:9000;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
        fastcgi_read_timeout 60;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
EOF

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

php-fpm -t
php-fpm -D
nginx -t
exec nginx -g "daemon off;"
