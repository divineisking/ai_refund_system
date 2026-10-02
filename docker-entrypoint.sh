#!/bin/sh
set -e

echo "=== AI Refund System Container Initialization ==="

# 1. Ensure .env exists
if [ ! -f /var/www/.env ]; then
    echo "Creating .env from .env.example..."
    cp /var/www/.env.example /var/www/.env
fi

# 2. Ensure application key
if ! grep -q "APP_KEY=base64:" /var/www/.env; then
    echo "Generating application encryption key..."
    php artisan key:generate --force
fi

# 3. Ensure database directory & SQLite file exist if using SQLite
if [ "$DB_CONNECTION" = "sqlite" ] || [ -z "$DB_CONNECTION" ]; then
    mkdir -p /var/www/database
    touch /var/www/database/database.sqlite
fi

# 4. Run database migrations
echo "Running database migrations..."
php artisan migrate --force

# 5. Seed database if empty or explicitly needed
echo "Seeding database with 15 customer profiles and order histories..."
php artisan db:seed --force

# 6. Verify seed record count invariant
echo "Verifying database record assertions..."
php artisan tinker --execute="
\$c = \App\Models\Customer::count();
\$o = \App\Models\Order::count();
if (\$c < 15 || \$o < 15) {
    echo 'Assertion failure: Found ' . \$c . ' customers and ' . \$o . ' orders.' . PHP_EOL;
    exit(1);
}
echo 'Initialization verified: ' . \$c . ' customers, ' . \$o . ' orders ready.' . PHP_EOL;
"

echo "=== Container Initialization Complete. Starting Service ==="

# Execute passed command (e.g. php artisan serve or php-fpm)
exec "$@"
