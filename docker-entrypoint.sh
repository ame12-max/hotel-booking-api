#!/bin/sh
set -e

echo "===== Shell Environment ====="
env | grep database || true

echo "===== PHP getenv() ====="
php -r 'echo "HOST=" . getenv("database.default.hostname") . PHP_EOL;'
php -r 'echo "DB=" . getenv("database.default.database") . PHP_EOL;'
php -r 'echo "USER=" . getenv("database.default.username") . PHP_EOL;'
php -r 'echo "PORT=" . getenv("database.default.port") . PHP_EOL;'

echo "===== Testing database connection ====="
php spark db:table

echo "===== Running migrations ====="
php spark migrate --all --no-interaction

echo "===== Starting Apache ====="
exec apache2-foreground
