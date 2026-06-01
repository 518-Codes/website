#!/usr/bin/env sh
# Bootstraps and serves the Laravel app inside the `app` container.
# Idempotent: safe to re-run on `docker compose up`.
set -e

cd /var/www/html

if [ ! -f .env ]; then
    cp .env.example .env
fi

composer install --no-interaction

# Generate an app key only if one is not already set.
if ! grep -q '^APP_KEY=.\+' .env; then
    php artisan key:generate --no-interaction
fi

# SQLite database lives in a bind-mounted file.
touch database/database.sqlite

php artisan migrate --force --no-interaction
php artisan db:seed --force --no-interaction

php artisan serve --host=0.0.0.0 --port=8000
