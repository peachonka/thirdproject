# ./services/php-web/Dockerfile
FROM php:8.2-apache

# Устанавливаем системные зависимости и расширения PHP
RUN apt-get update && apt-get install -y \
    git curl libpng-dev libonig-dev libxml2-dev zip unzip libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql mbstring exif pcntl bcmath gd \
    && a2enmod rewrite

# Устанавливаем Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Указываем рабочую директорию
WORKDIR /var/www/html

# Копируем только то, что нужно для установки зависимостей (оптимизация кэша Docker)
COPY composer.json composer.lock ./

# Устанавливаем зависимости (если они есть)
RUN if [ -f composer.json ]; then composer install --no-interaction --no-dev --no-scripts; fi

# Копируем всё остальное
COPY . .

# Устанавливаем права
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Стандартная команда Apache
CMD ["apache2-foreground"]