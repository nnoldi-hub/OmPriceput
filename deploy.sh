#!/usr/bin/env bash
#
# Deploy OmPriceput on a live server.
#
# Usage:
#   ./deploy.sh            # deploy the master branch
#   ./deploy.sh develop    # deploy another branch
#
set -euo pipefail

BRANCH="${1:-master}"
APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$APP_DIR"

if [ ! -f artisan ]; then
    echo "Eroare: artisan nu a fost gasit in $APP_DIR. Ruleaza scriptul din radacina proiectului." >&2
    exit 1
fi

echo "==> Deploy OmPriceput (branch: $BRANCH)"

php artisan down --retry=5 || true
trap 'php artisan up || true' EXIT

echo "==> Git pull"
git pull origin "$BRANCH"

echo "==> Composer install"
composer install --no-dev --optimize-autoloader --no-interaction

echo "==> Migrari"
php artisan migrate --force

echo "==> Reface cache-urile"
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "==> Restart cozi"
php artisan queue:restart || true

echo "==> Deploy finalizat"
