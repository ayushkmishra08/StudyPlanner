#!/bin/bash
set -e

# Render and cloud hosts dynamically assign $PORT (defaults to 8000 or 80)
PORT="${PORT:-8000}"

echo "Starting StudyPlanner container on port ${PORT}..."

# Configure Apache to listen on $PORT
sed -i "s/Listen 80/Listen ${PORT}/g" /etc/apache2/ports.conf 2>/dev/null || true
sed -i "s/<VirtualHost \*:80>/<VirtualHost \*:${PORT}>/g" /etc/apache2/sites-available/000-default.conf 2>/dev/null || true

# Allow .htaccess overrides in Apache
sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf 2>/dev/null || true

# Ensure storage directory exists and has write permissions for www-data
mkdir -p /var/www/html/storage
chown -R www-data:www-data /var/www/html/storage
chmod -R 775 /var/www/html/storage

# Run database setup & seeding
php /var/www/html/setup.php || echo "Database setup notice: proceeding to start server."

# Execute Apache in foreground
exec apache2-foreground
