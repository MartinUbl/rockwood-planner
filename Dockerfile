FROM composer:2 AS vendor

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install \
	--no-dev \
	--prefer-dist \
	--no-interaction \
	--no-progress \
	--no-scripts \
	--optimize-autoloader

COPY . .
RUN composer dump-autoload --no-dev --optimize


FROM php:8.3-apache

ENV APACHE_DOCUMENT_ROOT=/var/www/html/www

RUN apt-get update \
	&& apt-get install -y --no-install-recommends unzip \
	&& docker-php-ext-install pdo_mysql \
	&& a2enmod rewrite deflate headers \
	&& sed -ri -e "s!/var/www/html!${APACHE_DOCUMENT_ROOT}!g" /etc/apache2/sites-available/*.conf /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf \
	&& sed -ri 's/^Listen 80$/Listen 127.0.0.1:8080/' /etc/apache2/ports.conf \
	&& sed -ri 's/<VirtualHost \*:80>/<VirtualHost 127.0.0.1:8080>/' /etc/apache2/sites-available/000-default.conf \
	&& printf '%s\n' '<Directory /var/www/html/www>' 'AllowOverride All' 'Require all granted' '</Directory>' > /etc/apache2/conf-available/rockwood.conf \
	&& a2enconf rockwood \
	&& rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

COPY --from=vendor /app .
COPY docker/entrypoint.sh /usr/local/bin/rockwood-entrypoint

RUN chmod +x /usr/local/bin/rockwood-entrypoint \
	&& mkdir -p temp/sessions log www/uploads/issues \
	&& chown -R www-data:www-data temp log www/uploads

EXPOSE 8080

ENTRYPOINT ["rockwood-entrypoint"]
CMD ["apache2-foreground"]
