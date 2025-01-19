#!/bin/sh

set -e

if [ ! -f .env ]; then
  cp .env.example .env
fi

php artisan key:generate

php artisan migrate --force

php artisan config:cache
php artisan route:cache
php artisan view:cache

exec "$@"
