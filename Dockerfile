FROM php:8.2-cli

RUN apt-get update && apt-get install -y \
    git unzip zip libsqlite3-dev sqlite3 \
    && docker-php-ext-install pdo pdo_sqlite \
    && curl -OL https://squizlabs.github.io/PHP_CodeSniffer/phpcs.phar \
    && mv phpcs.phar /usr/local/bin/phpcs \
    && chmod +x /usr/local/bin/phpcs

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY . .

RUN composer install
RUN composer require --dev phpstan/phpstan

ENV PATH="/root/.composer/vendor/bin:${PATH}"

EXPOSE 8000

CMD ["php", "-S", "0.0.0.0:8000", "-t", "public"]
