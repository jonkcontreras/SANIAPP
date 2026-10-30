# Imagen oficial de PHP con Apache para ejecutar SaniApp.
FROM php:8.2-apache

# Render espera que el servicio HTTP escuche en el puerto 10000.
COPY docker/saniapp-security.conf /etc/apache2/conf-available/saniapp-security.conf

RUN docker-php-ext-install pdo_mysql \
    && a2enmod rewrite \
    && sed -ri 's/Listen 80/Listen 10000/' /etc/apache2/ports.conf \
    && sed -ri 's/<VirtualHost \*:80>/<VirtualHost *:10000>/' /etc/apache2/sites-available/000-default.conf \
    && a2enconf saniapp-security

ENV PORT=10000
WORKDIR /var/www/html
COPY . /var/www/html/

RUN chown -R www-data:www-data /var/www/html \
    && find /var/www/html -type d -exec chmod 755 {} \; \
    && find /var/www/html -type f -exec chmod 644 {} \;

EXPOSE 10000
CMD ["apache2-foreground"]
