# Usar uma imagem base do PHP com Apache
FROM php:8.2-apache

# Instalar dependências do sistema e extensões do PHP
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    libonig-dev \
    libxml2-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libpq-dev && \
    docker-php-ext-configure gd --with-freetype --with-jpeg && \
    docker-php-ext-install -j$(nproc) gd pdo_mysql pdo_pgsql zip mbstring exif pcntl && \
    apt-get clean && rm -rf /var/lib/apt/lists/*

# Instalar o Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Copiar o código do projeto para o container
COPY . /var/www/html

# Definir o diretório de trabalho
WORKDIR /var/www/html

# Configurar o Apache para servir a pasta public do Laravel
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e "s!/var/www/html!${APACHE_DOCUMENT_ROOT}!g" /etc/apache2/sites-available/*.conf
RUN sed -ri -e "s!/var/www/!${APACHE_DOCUMENT_ROOT}!g" /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Habilitar o módulo rewrite do Apache e definir ServerName
RUN a2enmod rewrite && echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Instalar dependências do Laravel
RUN composer install --optimize-autoloader --no-dev

RUN composer require mercadopago/dx-php


# Configurar permissões
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
RUN chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Copiar o script de entrypoint
COPY entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Expor a porta 80
EXPOSE 80

# Usar o script de entrypoint
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
