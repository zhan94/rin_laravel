#!/bin/sh
set -eu

if [ "${APP_ENV:-}" != local ] || [ "${DB_CONNECTION:-}" != mysql ] ||
    [ "${DB_HOST:-}" != mysql ] || [ "${DB_DATABASE:-}" != rin_laravel_demo ] ||
    [ -n "${DB_URL:-}" ]; then
    printf '%s\n' 'Expected the isolated Compose demo database.' >&2
    exit 2
fi

APP_KEY="$(cat /run/rin/app-key)"
DB_PASSWORD="$(cat /run/rin/db-password)"
export APP_KEY DB_PASSWORD

php artisan migrate --force --no-interaction
php artisan db:seed --class=DemoDatabaseSeeder --force --no-interaction
cd /app/public
exec php -d display_errors=Off -S 0.0.0.0:8080 /app/vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php
