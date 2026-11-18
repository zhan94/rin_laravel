#!/usr/bin/env bash

set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "${ROOT_DIR}"

echo "[health] project: ${ROOT_DIR}"

if [[ -x "/opt/homebrew/bin/php" ]]; then
  PHP_BIN="/opt/homebrew/bin/php"
else
  PHP_BIN="$(command -v php)"
fi

PHP_VERSION_RAW="$("${PHP_BIN}" -r 'echo PHP_VERSION;')"
echo "[health] php: ${PHP_VERSION_RAW}"

"${PHP_BIN}" -r 'exit(version_compare(PHP_VERSION, "8.5.0", ">=") ? 0 : 1);' || {
  echo "[health][error] PHP 8.5+ is required."
  exit 1
}

if command -v redis-cli >/dev/null 2>&1; then
  REDIS_PING="$(redis-cli ping || true)"
  echo "[health] redis ping: ${REDIS_PING:-failed}"
  if [[ "${REDIS_PING}" != "PONG" ]]; then
    echo "[health][error] Redis is not reachable."
    exit 1
  fi
else
  echo "[health][error] redis-cli is not installed."
  exit 1
fi

QUEUE_DRIVER="$("${PHP_BIN}" artisan tinker --execute='echo config("queue.default");' 2>/dev/null || true)"
CACHE_DRIVER="$("${PHP_BIN}" artisan tinker --execute='echo config("cache.default");' 2>/dev/null || true)"
SESSION_DRIVER="$("${PHP_BIN}" artisan tinker --execute='echo config("session.driver");' 2>/dev/null || true)"
QUEUE_REDIS_CONNECTION="$("${PHP_BIN}" artisan tinker --execute='echo (string) data_get(config("queue.connections.redis"), "connection");' 2>/dev/null || true)"
CACHE_REDIS_CONNECTION="$("${PHP_BIN}" artisan tinker --execute='echo (string) data_get(config("cache.stores.redis"), "connection");' 2>/dev/null || true)"
SESSION_CONNECTION="$("${PHP_BIN}" artisan tinker --execute='echo (string) config("session.connection");' 2>/dev/null || true)"
HORIZON_REDIS_CONNECTION="$("${PHP_BIN}" artisan tinker --execute='echo (string) config("horizon.use");' 2>/dev/null || true)"
echo "[health] queue driver: ${QUEUE_DRIVER}"
echo "[health] cache driver: ${CACHE_DRIVER}"
echo "[health] session driver: ${SESSION_DRIVER}"
echo "[health] queue redis connection: ${QUEUE_REDIS_CONNECTION}"
echo "[health] cache redis connection: ${CACHE_REDIS_CONNECTION}"
echo "[health] session connection: ${SESSION_CONNECTION}"
echo "[health] horizon redis connection: ${HORIZON_REDIS_CONNECTION}"

if [[ "${QUEUE_DRIVER}" != "redis" ]]; then
  echo "[health][error] queue driver is not redis."
  exit 1
fi

if [[ "${CACHE_DRIVER}" != "redis" ]]; then
  echo "[health][error] cache driver is not redis."
  exit 1
fi

if [[ "${QUEUE_REDIS_CONNECTION}" == "" ]]; then
  echo "[health][error] queue redis connection is not configured."
  exit 1
fi

if [[ "${CACHE_REDIS_CONNECTION}" == "" ]]; then
  echo "[health][error] cache redis connection is not configured."
  exit 1
fi

if [[ "${SESSION_DRIVER}" == "redis" && "${SESSION_CONNECTION}" == "" ]]; then
  echo "[health][error] session redis connection is not configured."
  exit 1
fi

if [[ "${HORIZON_REDIS_CONNECTION}" == "" ]]; then
  echo "[health][error] horizon redis connection is not configured."
  exit 1
fi

if "${PHP_BIN}" artisan horizon:status >/tmp/ski2_horizon_status.log 2>&1; then
  echo "[health] horizon: active"
else
  echo "[health][warn] horizon is inactive."
  echo "[health][warn] start it with: ${PHP_BIN} artisan horizon"
fi

echo "[health] ok"
