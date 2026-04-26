#!/usr/bin/env bash
set -euo pipefail

APP_DIR="${APP_DIR:-/var/www/printaqui}"
PHP_BIN="${PHP_BIN:-php}"
COMPOSER_BIN="${COMPOSER_BIN:-composer}"
NPM_BIN="${NPM_BIN:-npm}"

cd "$APP_DIR"

echo "==> Pull latest code"
git pull --ff-only origin main

echo "==> Install PHP dependencies"
"$COMPOSER_BIN" install --no-dev --optimize-autoloader --no-interaction

echo "==> Install and build frontend assets"
"$NPM_BIN" ci
"$NPM_BIN" run build

echo "==> Laravel maintenance tasks"
"$PHP_BIN" artisan migrate --force
"$PHP_BIN" artisan storage:link || true
"$PHP_BIN" artisan config:cache
"$PHP_BIN" artisan route:cache
"$PHP_BIN" artisan view:cache

echo "==> Permissions"
chmod -R ug+rw storage bootstrap/cache

echo "==> Done"
