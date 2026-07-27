#!/bin/sh
set -e

echo "Waiting for PostgreSQL..."

MAX_RETRIES=30
COUNT=0

until php spark db:table >/dev/null 2>&1
do
  COUNT=$((COUNT + 1))

  if [ "$COUNT" -ge "$MAX_RETRIES" ]; then
    echo "Database is still unavailable after $MAX_RETRIES attempts."
    exit 1
  fi

  echo "Database not ready... retrying in 5 seconds ($COUNT/$MAX_RETRIES)"
  sleep 5
done

echo "Running migrations..."

php spark migrate --all --no-interaction

echo "Starting Apache..."

exec apache2-foreground
