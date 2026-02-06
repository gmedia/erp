#!/bin/sh
set -e

# Run optimizations
echo "Running artisan optimize..."
php artisan optimize

# Run migrations (careful with production, but as requested)
if [ "$RUN_MIGRATIONS" = "true" ]; then
    echo "Running artisan migrate --seed..."
    php artisan migrate --seed --force
fi

# Execute the main command
exec "$@"
