#!/usr/bin/env bash

set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
APP_DIR="${APP_DIR:-${ROOT_DIR}}"
PHP_BIN="${PHP_BIN:-/opt/homebrew/bin/php}"
LABEL="${LABEL:-com.ski2.horizon}"
LOG_DIR="${LOG_DIR:-${APP_DIR}/storage/logs}"
AGENT_DIR="${HOME}/Library/LaunchAgents"
TARGET_PLIST="${AGENT_DIR}/${LABEL}.plist"
TEMPLATE="${ROOT_DIR}/deploy/launchd/com.ski2.horizon.plist"

if [[ ! -x "${PHP_BIN}" ]]; then
  echo "PHP binary is not executable: ${PHP_BIN}" >&2
  exit 1
fi

if [[ ! -f "${TEMPLATE}" ]]; then
  echo "Missing launchd template: ${TEMPLATE}" >&2
  exit 1
fi

mkdir -p "${AGENT_DIR}" "${LOG_DIR}"

sed \
  -e "s|__LABEL__|${LABEL}|g" \
  -e "s|__PHP_BIN__|${PHP_BIN}|g" \
  -e "s|__APP_DIR__|${APP_DIR}|g" \
  -e "s|__LOG_DIR__|${LOG_DIR}|g" \
  "${TEMPLATE}" > "${TARGET_PLIST}"

launchctl bootout "gui/$(id -u)" "${TARGET_PLIST}" >/dev/null 2>&1 || true
launchctl bootstrap "gui/$(id -u)" "${TARGET_PLIST}"
launchctl enable "gui/$(id -u)/${LABEL}" || true
launchctl kickstart -k "gui/$(id -u)/${LABEL}" || true

echo "Installed and started launchd service:"
echo "  ${TARGET_PLIST}"
launchctl print "gui/$(id -u)/${LABEL}" | sed -n '1,30p'
