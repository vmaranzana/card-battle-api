FROM php:8-fpm-alpine

# Instalar dependencias necesarias, incluyendo las requeridas por Composer
RUN apk add --no-cache --update \
    curl \
    git \
    unzip

# instalar dependencias
# instalar configuración de php
# preparar montaje para volumen
RUN docker-php-ext-install mysqli pdo pdo_mysql && \
    ln -s /usr/local/etc/php/php.ini-development /usr/local/etc/php/php.ini && \
    rm -rf /var/www/html

# Establecer el directorio de trabajo
WORKDIR /var/www/html

# Descargar e instalar Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Limpiar la caché de Composer (opcional)
RUN composer clear-cache

# Exponer el puerto 80 para el servidor de desarrollo de PHP
EXPOSE 80

# Comando para ejecutar el servidor de desarrollo de PHP
CMD [ "php", "-S", "0.0.0.0:80", "-t", "." ]