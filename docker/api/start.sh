#!/bin/bash

# Iniciar PHP-FPM en background
php-fpm -D

# Iniciar Nginx en foreground
nginx -g "daemon off;"