#!/bin/sh
set -e

echo "===== Testing database connection ====="

php spark db:table

echo "===== Running migrations ====="

php spark migrate --all --no-interaction

echo "===== Starting Apache ====="

exec apache2-foreground
