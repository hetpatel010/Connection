#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "${ROOT}"

ENV_FILE="${ROOT}/.env"
BACKUP_FILE="${ENV_FILE}.web-backup"

if [[ ! -f "${ENV_FILE}" ]]; then
    cp "${ROOT}/.env.example" "${ENV_FILE}"
    php artisan key:generate --force
fi

if [[ ! -f "${BACKUP_FILE}" ]]; then
    cp "${ENV_FILE}" "${BACKUP_FILE}"
    echo "Backed up ${ENV_FILE} to ${BACKUP_FILE} before NativePHP build."
fi

php "${ROOT}/scripts/prepare-nativephp-env.php" "${ENV_FILE}"

echo "Prepared ${ENV_FILE} for NativePHP desktop build."
