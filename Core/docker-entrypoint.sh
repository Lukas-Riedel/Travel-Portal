#!/bin/bash
set -e
php /var/www/html/src/deploy.php
php-fpm -D
nginx -g 'daemon off;'