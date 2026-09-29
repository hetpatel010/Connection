#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "${ROOT}"

ENV_FILE="${ROOT}/.env"
BACKUP_FILE="${ENV_FILE}.web-backup"

if [[ -f "${BACKUP_FILE}" ]]; then
    cp "${BACKUP_FILE}" "${ENV_FILE}"
    rm "${BACKUP_FILE}"
    echo "Restored ${ENV_FILE} from web development backup."
else
    php "${ROOT}/scripts/restore-local-env.php" "${ENV_FILE}"
    echo "Restored ${ENV_FILE} for local web development."
fi

php artisan optimize:clear --no-interaction

echo "Cleared cached config/routes/views for local development."
