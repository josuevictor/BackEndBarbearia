# Usar uma imagem base do PHP com Apache
FROM php:8.2-apache

# Instalar dependências do sistema
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    libonig-dev \
    libxml2-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd pdo_mysql zip mbstring exif pcntl

# Habilitar módulos do Apache necessários
RUN a2enmod rewrite headers

# Instalar o Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Copiar o código do projeto para o container
COPY . /var/www/html

# Definir o diretório de trabalho
WORKDIR /var/www/html/public

# Configurar permissões para o Apache
RUN chown -R www-data:www-data /var/www/html
RUN chmod -R 755 /var/www/html/storage /var/www/html/bootstrap/cache

# Configurar o Apache para permitir o .htaccess
RUN echo "<Directory /var/www/html/public>\n\
    Options Indexes FollowSymLinks\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>" > /etc/apache2/sites-available/000-default.conf

# Instalar dependências do Laravel
RUN composer install --optimize-autoloader --no-dev

# Gerar caches do Laravel
RUN php artisan config:cache && \
    php artisan route:cache && \
    php artisan view:cache

# Expor a porta 80
EXPOSE 10000

# Comando para iniciar o servidor Apache
CMD ["apache2-foreground"]
