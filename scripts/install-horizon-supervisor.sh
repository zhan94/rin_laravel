#!/usr/bin/env bash

set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
APP_DIR="${APP_DIR:-/var/www/ski2/current}"
PHP_BIN="${PHP_BIN:-/usr/bin/php}"
RUN_USER="${RUN_USER:-www-data}"
LOG_FILE="${LOG_FILE:-/var/log/supervisor/ski2-horizon.log}"
CONF_PATH="${CONF_PATH:-/etc/supervisor/conf.d/ski2-horizon.conf}"
TEMPLATE="${ROOT_DIR}/deploy/supervisor/ski2-horizon.conf.template"

if [[ ! -f "${TEMPLATE}" ]]; then
  echo "Missing supervisor template: ${TEMPLATE}" >&2
  exit 1
fi

TMP_CONF="$(mktemp)"

sed \
  -e "s|__APP_DIR__|${APP_DIR}|g" \
  -e "s|__PHP_BIN__|${PHP_BIN}|g" \
  -e "s|__RUN_USER__|${RUN_USER}|g" \
  -e "s|__LOG_FILE__|${LOG_FILE}|g" \
  "${TEMPLATE}" > "${TMP_CONF}"

echo "Writing Supervisor config to ${CONF_PATH} (sudo required)"
sudo mkdir -p "$(dirname "${CONF_PATH}")"
sudo cp "${TMP_CONF}" "${CONF_PATH}"
rm -f "${TMP_CONF}"

echo "Reloading Supervisor"
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl restart ski2-horizon
sudo supervisorctl status ski2-horizon
