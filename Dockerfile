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

# Instalar o Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Copiar o código do projeto para o container
COPY . /var/www/html

# Definir o diretório de trabalho
WORKDIR /var/www/html

# Configurar o Apache para servir a pasta public do Laravel
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Habilita o módulo rewrite do Apache
RUN a2enmod rewrite

# Configurar o ServerName para evitar avisos
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Instalar dependências do Laravel
RUN composer install --optimize-autoloader --no-dev

# Configurar permissões
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
RUN chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Gerar caches do Laravel
RUN php artisan config:cache && \
    php artisan route:cache && \
    php artisan view:cache


# Expor a porta 80
EXPOSE 80

# Comando para iniciar o servidor Apache
CMD ["apache2-foreground"]
