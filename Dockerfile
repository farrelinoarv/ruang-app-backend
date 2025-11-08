FROM php:8.3-fpm

WORKDIR /var/www

COPY . .

RUN apt-get update && apt-get install -y \
    libpng-dev \
    zip \
    unzip \
    git \
    libzip-dev \
    libicu-dev \
    cron \
    supervisor \
    && docker-php-ext-install pdo pdo_mysql zip intl gd

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && chmod +x /usr/local/bin/composer

# Check Composer installation
RUN ls -lah /usr/local/bin/composer && php /usr/local/bin/composer --version

RUN php /usr/local/bin/composer install --no-interaction --prefer-dist

COPY supervisor/supervisord.conf /etc/supervisor/supervisord.conf
COPY supervisor/php-fpm.conf /etc/supervisor/conf.d/php-fpm.conf
COPY supervisor/laravel-worker.conf /etc/supervisor/conf.d/laravel-worker.conf

RUN ln -sf /usr/share/zoneinfo/Asia/Jakarta /etc/localtime && \
    echo "Asia/Jakarta" > /etc/timezone

COPY cron/laravel-cron /etc/cron.d/laravel-cron

# Fix format Windows (CRLF -> LF)
RUN sed -i 's/\r//g' /etc/cron.d/laravel-cron

RUN chmod 0644 /etc/cron.d/laravel-cron

RUN service cron restart

RUN touch /var/log/cron.log

CMD ["/bin/sh", "-c", "cron && /usr/bin/supervisord -c /etc/supervisor/supervisord.conf"]
