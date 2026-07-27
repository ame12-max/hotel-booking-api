#!/bin/sh
set -e

echo "Running CodeIgniter migrations..."

php spark migrate --all --no-interaction

echo "Starting Apache..."

exec apache2-foreground