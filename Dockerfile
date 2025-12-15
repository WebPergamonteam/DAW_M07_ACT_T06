FROM php:8.1-apache

# Instalar extensiones PHP necesarias
RUN docker-php-ext-install mysqli && \
    docker-php-ext-enable mysqli

# Habilitar mod_rewrite
RUN a2enmod rewrite

# Configurar Apache
RUN sed -i 's|/var/www/html|/var/www/html|g' /etc/apache2/sites-available/000-default.conf

# Copiar archivos
COPY . /var/www/html/

# Establecer permisos
RUN chown -R www-data:www-data /var/www/html

WORKDIR /var/www/html

