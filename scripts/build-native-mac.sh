#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "${ROOT}"

export CSC_IDENTITY_AUTO_DISCOVERY=false

BUILDER="${ROOT}/nativephp/electron/electron-builder.mjs"

if [[ -f "${BUILDER}" ]] && ! grep -q "NATIVEPHP_MAC_IDENTITY" "${BUILDER}"; then
    perl -0pi -e 's/mac: \{\n        entitlementsInherit:/mac: {\n        identity: process.env.NATIVEPHP_MAC_IDENTITY === '\''sign'\'' ? undefined : null,\n        hardenedRuntime: process.env.NATIVEPHP_MAC_IDENTITY === '\''sign'\'',\n        entitlementsInherit:/' "${BUILDER}"
fi

ARCH="$(uname -m)"
TARGET_ARCH="${1:-auto}"

if [[ "${TARGET_ARCH}" == "auto" ]]; then
    if [[ "${ARCH}" == "arm64" ]]; then
        TARGET_ARCH="arm64"
    else
        TARGET_ARCH="x64"
    fi
fi

echo "Building NativePHP macOS app (${TARGET_ARCH})..."

php artisan native:build mac "${TARGET_ARCH}" --no-interaction

PHP_BIN="${ROOT}/nativephp/electron/dist/mac-${TARGET_ARCH}/Billing SaaS.app/Contents/Resources/build/php/php"
if [[ -f "${PHP_BIN}" ]]; then
    chmod +x "${PHP_BIN}"
    echo "Ensured PHP binary is executable in app bundle."
fi

echo ""
echo "Build output:"
ls -lh "${ROOT}/nativephp/electron/dist/"*.dmg "${ROOT}/nativephp/electron/dist/"*.zip 2>/dev/null || true
