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
php artisan config:clear >/dev/null 2>&1 || true
php artisan route:clear >/dev/null 2>&1 || true
php artisan view:clear >/dev/null 2>&1 || true

kangoo-restore-database

if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
  php artisan migrate --force
fi

if [ "${RUN_SEEDERS:-false}" = "true" ]; then
  php artisan db:seed --force
fi

php artisan db:seed --class=ThemeSettingsSeeder --force >/dev/null 2>&1 || true

php artisan config:cache

exec "$@"
