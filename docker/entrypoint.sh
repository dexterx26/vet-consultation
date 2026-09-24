#!/bin/sh
set -e

# Default PORT if not provided by Render (Render passes PORT e.g. 10000 or 80)
export PORT="${PORT:-80}"

echo ">>> Configuring Nginx for port ${PORT}..."
envsubst '${PORT}' < /etc/nginx/templates/default.conf.template > /etc/nginx/http.d/default.conf

# Ensure storage directories exist and have proper permissions
mkdir -p /var/www/html/storage/framework/cache/data \
         /var/www/html/storage/framework/sessions \
         /var/www/html/storage/framework/views \
         /var/www/html/storage/logs \
         /var/www/html/storage/app/public \
         /var/www/html/bootstrap/cache

chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Generate APP_KEY if missing
if [ -z "$APP_KEY" ]; then
    echo ">>> Notice: APP_KEY is empty. Generating temporary application key..."
    php artisan key:generate --force
fi

# Link public storage
echo ">>> Linking storage..."
php artisan storage:link --force || true

# Run database migrations
if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
    echo ">>> Running database migrations..."
    php artisan migrate --force || echo ">>> Migration warning: Please check database connection."
fi

# Run database seeder if requested
if [ "${RUN_SEEDER:-false}" = "true" ]; then
    echo ">>> Running database seeders..."
    php artisan db:seed --force || echo ">>> Seeder warning: Seed command completed with warnings."
fi

# Optimize Laravel caching for production
if [ "${APP_ENV:-production}" = "production" ]; then
    echo ">>> Caching Laravel configuration, routes, and views..."
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
fi

# Optionally disable background services if configured via env
if [ "${ENABLE_REVERB:-true}" = "false" ]; then
    echo ">>> Disabling Reverb WebSocket server..."
    sed -i '/\[program:reverb\]/,/priority=15/d' /etc/supervisor/conf.d/supervisord.conf
fi

if [ "${ENABLE_QUEUE_WORKER:-true}" = "false" ]; then
    echo ">>> Disabling queue worker..."
    sed -i '/\[program:queue-worker\]/,/priority=20/d' /etc/supervisor/conf.d/supervisord.conf
fi

echo ">>> Starting Supervisor (Nginx, PHP-FPM, Reverb, Queue)..."
exec supervisord -c /etc/supervisor/conf.d/supervisord.conf
