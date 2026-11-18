#!/usr/bin/env bash

set -euo pipefail

PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
TEST_DB_CONNECTION="${TEST_DB_CONNECTION:-mysql}"
TEST_DB_HOST="${TEST_DB_HOST:-127.0.0.1}"
TEST_DB_PORT="${TEST_DB_PORT:-3306}"
TEST_DB_DATABASE="${TEST_DB_DATABASE:-ski2_test}"
TEST_DB_USERNAME="${TEST_DB_USERNAME:-root}"
TEST_DB_PASSWORD="${TEST_DB_PASSWORD:-}"

cd "${PROJECT_ROOT}"

MYSQL_ARGS=(
  --host="${TEST_DB_HOST}"
  --port="${TEST_DB_PORT}"
  --user="${TEST_DB_USERNAME}"
)

if [[ -n "${TEST_DB_PASSWORD}" ]]; then
  MYSQL_ARGS+=(--password="${TEST_DB_PASSWORD}")
fi

mysql "${MYSQL_ARGS[@]}" \
  --execute="CREATE DATABASE IF NOT EXISTS \`${TEST_DB_DATABASE}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

APP_ENV=testing \
DB_CONNECTION="${TEST_DB_CONNECTION}" \
DB_HOST="${TEST_DB_HOST}" \
DB_PORT="${TEST_DB_PORT}" \
DB_DATABASE="${TEST_DB_DATABASE}" \
DB_USERNAME="${TEST_DB_USERNAME}" \
DB_PASSWORD="${TEST_DB_PASSWORD}" \
php artisan migrate:fresh --seed --seeder=Database\\Seeders\\TestDatabaseSeeder

printf 'Test database reset: mysql://%s@%s:%s/%s\n' "${TEST_DB_USERNAME}" "${TEST_DB_HOST}" "${TEST_DB_PORT}" "${TEST_DB_DATABASE}"
