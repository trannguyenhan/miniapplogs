#!/bin/bash
set -e

echo ""
echo "╔══════════════════════════════════════╗"
echo "║      Mini App Logs – Startup         ║"
echo "╚══════════════════════════════════════╝"
echo ""

cd /var/www/html

# ─── 0. Xoá file cache có thể bị kẹt từ lúc build image ──────────────────────
rm -f bootstrap/cache/*.php

# ─── 1. .env file (cần tồn tại dù rỗng cho một số artisan commands) ──────────
[ -f .env ] || touch .env

# ─── 2. APP_KEY ────────────────────────────────────────────────────────────────
if [ -z "${APP_KEY}" ]; then
    APP_KEY_FILE="/var/www/html/storage/.app_key"

    if [ -f "${APP_KEY_FILE}" ]; then
        APP_KEY=$(cat "${APP_KEY_FILE}")
        export APP_KEY
        echo "==> Loaded APP_KEY from storage/.app_key"
    else
        echo "==> APP_KEY is missing. Generating and persisting to storage/.app_key..."
        APP_KEY=$(php artisan key:generate --show --no-interaction)
        export APP_KEY
        printf "%s" "${APP_KEY}" > "${APP_KEY_FILE}"
        chmod 600 "${APP_KEY_FILE}"
    fi
fi

# ─── 2.5 Ensure framework directories exist ────────────────────────────────────
mkdir -p /var/www/html/storage/framework/sessions /var/www/html/storage/framework/views /var/www/html/storage/framework/cache /var/www/html/storage/logs
chown -R www-data:www-data /var/www/html/storage

# ─── 3. Xóa cache cũ ───────────────────────────────────────────────────────────
echo "==> Clearing old cache..."
php artisan config:clear  --no-interaction 2>/dev/null || true
php artisan cache:clear   --no-interaction 2>/dev/null || true
php artisan route:clear   --no-interaction 2>/dev/null || true
php artisan view:clear    --no-interaction 2>/dev/null || true
php artisan event:clear   --no-interaction 2>/dev/null || true

# ─── 4. Database setup ─────────────────────────────────────────────────────────
if [ "${DB_CONNECTION:-sqlite}" = "sqlite" ]; then
    # SQLite: tạo thư mục và file nếu chưa có
    SQLITE_PATH="${DB_DATABASE:-/var/www/html/storage/database/app.sqlite}"
    SQLITE_DIR=$(dirname "$SQLITE_PATH")
    mkdir -p "$SQLITE_DIR"
    [ -f "$SQLITE_PATH" ] || touch "$SQLITE_PATH"
    chown www-data:www-data "$SQLITE_PATH" "$SQLITE_DIR"
    echo "==> Using SQLite: $SQLITE_PATH"
else
    # MySQL: chờ sẵn sàng
    echo "==> Waiting for MySQL at ${DB_HOST:-db}:${DB_PORT:-3306}..."
    MAX_TRIES=30
    COUNT=0
    until mysqladmin ping -h"${DB_HOST:-db}" -P"${DB_PORT:-3306}" \
          -u"${DB_USERNAME:-root}" -p"${DB_PASSWORD}" --silent 2>/dev/null; do
        COUNT=$((COUNT + 1))
        [ "$COUNT" -ge "$MAX_TRIES" ] && { echo "    ✗ MySQL not responding – continuing..."; break; }
        echo "    ... ($COUNT/${MAX_TRIES})"
        sleep 1
    done
    echo "    ✓ MySQL ready"
fi

# ─── 5. Migrations ─────────────────────────────────────────────────────────────
echo "==> Running migrations..."
php artisan migrate --force --no-interaction

# ─── 6. Seed lần đầu nếu chưa có user ─────────────────────────────────────────
echo "==> Checking initial seed..."
USER_COUNT=$(php artisan tinker --execute="echo \App\Models\User::count();" 2>/dev/null | tail -1 | tr -d '[:space:]')
if [ "${USER_COUNT}" = "0" ] || [ -z "${USER_COUNT}" ]; then
    echo "    -> Seeding default admin user..."
    php artisan db:seed --force --no-interaction 2>/dev/null || true
fi

# ─── 7. Cache config/routes/views từ env runtime ───────────────────────────────
echo "==> Caching application..."
php artisan config:cache  --no-interaction
php artisan route:cache   --no-interaction
php artisan view:cache    --no-interaction
php artisan event:cache   --no-interaction

# ─── 8. Storage link ───────────────────────────────────────────────────────────
php artisan storage:link --no-interaction 2>/dev/null || true

# ─── 9. Permissions ────────────────────────────────────────────────────────────
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

echo ""
echo "==> ✓ App ready! Starting Nginx + PHP-FPM..."
echo ""
exec supervisord -n -c /etc/supervisor/conf.d/supervisord.conf
