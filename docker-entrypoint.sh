#!/bin/sh
set -e

echo "===== Running migrations ====="

php spark migrate --all --no-interaction

echo "===== Existing tables ====="

php spark db:table || true

echo "===== Starting Apache ====="

exec apache2-foreground
