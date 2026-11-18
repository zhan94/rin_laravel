#!/usr/bin/env bash

set -euo pipefail

REPO_DIR="${REPO_DIR:-$(pwd)}"
DEPLOY_ROOT="${DEPLOY_ROOT:-${REPO_DIR}/.deploy}"
RELEASE_ID="${RELEASE_ID:-$(date +%Y%m%d%H%M%S)}"
KEEP_RELEASES="${KEEP_RELEASES:-5}"

RELEASES_DIR="${DEPLOY_ROOT}/releases"
SHARED_DIR="${DEPLOY_ROOT}/shared"
CURRENT_LINK="${DEPLOY_ROOT}/current"
RELEASE_DIR="${RELEASES_DIR}/${RELEASE_ID}"

require_command() {
    if ! command -v "$1" >/dev/null 2>&1; then
        echo "Missing required command: $1" >&2
        exit 1
    fi
}

require_command rsync
require_command php
require_command composer
require_command npm

mkdir -p "${RELEASES_DIR}" "${SHARED_DIR}/storage"

if [[ ! -f "${SHARED_DIR}/.env" ]]; then
    echo "Expected shared env file at ${SHARED_DIR}/.env" >&2
    exit 1
fi

echo "[deploy] creating release ${RELEASE_ID}"
mkdir -p "${RELEASE_DIR}"

rsync -a --delete \
    --exclude ".git" \
    --exclude ".github" \
    --exclude "node_modules" \
    --exclude "vendor" \
    --exclude "storage" \
    --exclude ".env" \
    "${REPO_DIR}/" "${RELEASE_DIR}/"

ln -sfn "${SHARED_DIR}/.env" "${RELEASE_DIR}/.env"
rm -rf "${RELEASE_DIR}/storage"
ln -sfn "${SHARED_DIR}/storage" "${RELEASE_DIR}/storage"

pushd "${RELEASE_DIR}" >/dev/null

echo "[deploy] installing PHP dependencies"
composer install --no-interaction --no-dev --prefer-dist --optimize-autoloader

echo "[deploy] installing JS dependencies and building assets"
npm ci --no-audit --no-fund
npm run build

echo "[deploy] running database migrations"
php artisan migrate --force

echo "[deploy] warming caches"
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "[deploy] switching current symlink"
ln -sfn "${RELEASE_DIR}" "${CURRENT_LINK}"

if php artisan horizon:status >/dev/null 2>&1; then
    php artisan horizon:terminate || true
fi
php artisan queue:restart || true

popd >/dev/null

echo "[deploy] cleaning old releases"
mapfile -t RELEASE_PATHS < <(find "${RELEASES_DIR}" -mindepth 1 -maxdepth 1 -type d | sort)
RELEASE_COUNT="${#RELEASE_PATHS[@]}"

if (( RELEASE_COUNT > KEEP_RELEASES )); then
    REMOVE_COUNT=$(( RELEASE_COUNT - KEEP_RELEASES ))
    for (( i=0; i<REMOVE_COUNT; i++ )); do
        rm -rf "${RELEASE_PATHS[$i]}"
    done
fi

echo "[deploy] finished: ${RELEASE_DIR}"
