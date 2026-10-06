FROM php:8.3-apache

# pdo_mysql for Aiven MySQL; rewrite/headers for .htaccess
RUN docker-php-ext-install pdo_mysql \
    && a2enmod rewrite headers \
    && mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && echo "expose_php = Off" > "$PHP_INI_DIR/conf.d/app.ini"

# Serve only public/ so src/, bin/ and config files are never web-accessible
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf \
    && echo "ServerName localhost" >> /etc/apache2/apache2.conf \
    && echo "ServerTokens Prod\nServerSignature Off" >> /etc/apache2/apache2.conf

WORKDIR /var/www/html
COPY . .
RUN chmod +x docker/entrypoint.sh

EXPOSE 10000
CMD ["docker/entrypoint.sh"]
