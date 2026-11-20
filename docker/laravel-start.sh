#!/bin/sh
set -eu

if [ "${APP_ENV:-}" != local ] || [ "${DB_CONNECTION:-}" != mysql ] ||
    [ "${DB_HOST:-}" != mysql ] || [ -n "${DB_URL:-}" ] ||
    [ -z "${DB_DATABASE:-}" ] || [ "${DB_DATABASE:-}" != "${DB_DEVELOPMENT_DATABASE:-}" ]; then
    printf '%s\n' 'Expected the designated local MySQL database for the isolated Compose project.' >&2
    exit 2
fi

APP_KEY="$(cat /run/rin/app-key)"
DB_PASSWORD="$(cat /run/rin/db-password)"
export APP_KEY DB_PASSWORD

php artisan migrate --seed --seeder=DemoDatabaseSeeder --force --no-interaction
cd /app/public
exec php -d display_errors=Off -S 0.0.0.0:8080 /app/vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php
