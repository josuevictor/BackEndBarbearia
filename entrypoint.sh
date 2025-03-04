#!/bin/sh

# Limpar o cache do Laravel
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Gerar caches do Laravel (opcional, apenas para produção)
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Iniciar o Apache
exec apache2-foreground
