#!/bin/sh
set -e

if [ ! -d vendor ]; then
    composer install --no-interaction --prefer-dist --optimize-autoloader
fi

if [ ! -d node_modules ]; then
    npm install
fi

if [ ! -d public/build ]; then
    npm run build
fi

exec "$@"
