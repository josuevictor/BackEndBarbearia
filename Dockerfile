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

# Instalar dependências do Laravel
RUN composer install --optimize-autoloader --no-dev

# Configurar permissões
RUN chown -R www-data:www-data /var/www/html
RUN chmod -R 755 /var/www/html/storage /var/www/html/bootstrap/cache

# Configurar o Apache para usar o diretório public/ como raiz
RUN sed -i 's#/var/www/html#/var/www/html/public#g' /etc/apache2/sites-available/000-default.conf
RUN sed -i 's#/var/www/html#/var/www/html/public#g' /etc/apache2/apache2.conf
RUN a2enmod rewrite

# Configurar o Apache para escutar na porta do Render
RUN echo "Listen $PORT" > /etc/apache2/ports.conf
RUN sed -i 's/80/$PORT/g' /etc/apache2/sites-available/000-default.conf /etc/apache2/apache2.conf

# Gerar caches do Laravel
RUN php artisan config:cache && \
    php artisan route:cache && \
    php artisan view:cache

# Comando para iniciar o servidor Apache
CMD ["sh", "-c", "sed -i 's/80/$PORT/g' /etc/apache2/sites-available/000-default.conf /etc/apache2/apache2.conf && apache2-foreground"]
