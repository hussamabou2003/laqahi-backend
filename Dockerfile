FROM php:8.2-cli

# تثبيت الحزم الأساسية
RUN apt-get update && apt-get install -y libzip-dev zip unzip git

# تثبيت إضافات PHP
RUN docker-php-ext-install pdo_mysql zip

# تثبيت Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY . .

# تثبيت الاعتماديات
RUN composer install --no-dev --optimize-autoloader

# إعطاء الصلاحيات
RUN chmod -R 777 storage bootstrap/cache

EXPOSE 8000

# تشغيل التهجير ثم السيرفر
CMD php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=${PORT:-8000}
