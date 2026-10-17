#!/bin/sh
# Bootstraps the dev container on first start: dependencies, .env, key, migrations, demo data.
set -e

cd /var/www/html

if [ "${CONTAINER_ROLE:-app}" = "app" ]; then
    [ -f .env ] || cp .env.example .env
    [ -d vendor ] || composer install --no-interaction --prefer-dist
    grep -q '^APP_KEY=base64' .env || php artisan key:generate --force

    php artisan migrate --force

    if [ "$(php artisan tinker --execute 'echo \App\Models\User::count();' 2>/dev/null)" = "0" ]; then
        php artisan db:seed --force
    fi

    php artisan storage:link >/dev/null 2>&1 || true
    touch /tmp/ready
fi

exec "$@"
