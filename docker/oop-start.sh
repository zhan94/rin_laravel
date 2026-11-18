#!/bin/sh
set -eu

if [ ! -f /oop-source/composer.lock ] || [ ! -f /oop-source/src/Database/DemoDataset.php ]; then
    printf '%s\n' 'RIN_OOP_SOURCE must point to the current RIN OOP source checkout.' >&2
    exit 2
fi

cd /app
# Keep the OOP checkout read-only and copy only files required by the app.
tar -C /oop-source --exclude='*.sqlite*' --exclude='*/uploads' --exclude='*/backups' -cf - \
    composer.json composer.lock config database public scripts src templates | tar -xf -
mkdir -p storage/issue_attachments

umask 077
cat > .env <<ENV
APP_ENV=local
APP_URL=http://localhost:${RIN_OOP_PORT:-8080}
DB_HOST=mysql
DB_PORT=3306
DB_NAME=rin_demo
DB_USER=oop_demo
DB_PASS=$(cat /run/rin/db-password)
DB_CHARSET=utf8mb4
WEBAUTHN_RP_ID=localhost
WEBAUTHN_ORIGINS=http://localhost:${RIN_OOP_PORT:-8080}
PASSWORD_ALLOW_LEGACY_PLAINTEXT=0
ENV

composer install --no-dev --prefer-dist --no-scripts --no-interaction
php /runner/oop-seed.php
exec php -S 0.0.0.0:8080 -t public public/index.php
