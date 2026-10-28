#!/bin/sh
set -eu

if [ -z "${APP_KEY:-}" ]; then
  echo "APP_KEY is required. Configure it in Render before starting the service." >&2
  exit 1
fi
case "$APP_KEY" in
  base64:*) ;;
  *) export APP_KEY="base64:$APP_KEY" ;;
esac

export DB_CONNECTION="${DB_CONNECTION:-sqlite}"
export DB_DATABASE="${DB_DATABASE:-/var/www/html/database/database.sqlite}"
mkdir -p "$(dirname "$DB_DATABASE")" storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
touch "$DB_DATABASE"
chown -R www-data:www-data storage bootstrap/cache database "$(dirname "$DB_DATABASE")"
chmod 664 "$DB_DATABASE"

php artisan config:cache
php artisan migrate --force

PORT="${PORT:-10000}"
sed -ri "s/^Listen [0-9]+/Listen $PORT/" /etc/apache2/ports.conf
sed -ri "s/<VirtualHost \*:[0-9]+>/<VirtualHost *:$PORT>/" /etc/apache2/sites-enabled/000-default.conf

exec apache2-foreground