#!/bin/bash
set -e
mkdir -p /var/tmp/cache/nginx
PGPASSWORD=$DB_PASSWORD pg_dump -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USER" -d "$DB_NAME" | gzip > /var/tmp/backup.sql.gz
php -d memory_limit=512M /var/www/html/src/deploy.php
php-fpm -D
nginx -g 'daemon off;'