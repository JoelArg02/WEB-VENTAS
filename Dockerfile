FROM php:8.2-apache

# Instalar extensiones PHP necesarias
RUN docker-php-ext-install pdo pdo_mysql mysqli

# Habilitar mod_rewrite de Apache
RUN a2enmod rewrite

# Copiar configuración de Apache
COPY apache-config.conf /etc/apache2/sites-available/000-default.conf

# Configurar permisos
RUN chown -R www-data:www-data /var/www/html
RUN chmod -R 755 /var/www/html
