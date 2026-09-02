#!/usr/bin/env sh
set -eu

cd /var/www/html

mkdir -p \
  bootstrap/cache \
  storage/app/public \
  storage/framework/cache/data \
  storage/framework/sessions \
  storage/framework/views \
  storage/logs

chown -R www-data:www-data storage bootstrap/cache

if [ "${APP_KEY:-}" = "" ]; then
  php artisan key:generate --force --no-interaction
fi

php artisan storage:link --force >/dev/null 2>&1 || true
php artisan optimize:clear >/dev/null 2>&1 || true
php artisan config:clear >/dev/null 2>&1 || true
php artisan route:clear >/dev/null 2>&1 || true
php artisan view:clear >/dev/null 2>&1 || true

kangoo-restore-database

php artisan migrate --force >/dev/null 2>&1 || true

if [ "${RUN_SEEDERS:-false}" = "true" ]; then
  php artisan db:seed --force
fi

php artisan migrate --force --path=database/migrations/2024_01_01_000000_create_theme_settings_table.php >/dev/null 2>&1 || true
php artisan db:seed --class=ThemeSettingsSeeder --force >/dev/null 2>&1 || true

php artisan config:cache

exec "$@"
