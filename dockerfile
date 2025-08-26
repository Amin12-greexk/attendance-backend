# Stage 1: Build stage dengan PHP dan Composer
FROM composer:2 as vendor

WORKDIR /app
COPY composer.json composer.lock ./
# Hanya install dependensi, ini akan dicache jika tidak ada perubahan
RUN composer install --no-dev --no-interaction --no-plugins --no-scripts --prefer-dist

# Stage 2: Production stage dengan PHP-FPM
FROM php:8.2-fpm-alpine

# Set working directory
WORKDIR /var/www/html

# Install ekstensi PHP yang dibutuhkan Laravel
RUN docker-php-ext-install pdo pdo_mysql

# Copy dependensi dari build stage
COPY --from=vendor /app/vendor /var/www/html/vendor

# Copy seluruh file aplikasi
COPY . .

# Set kepemilikan file agar bisa ditulis oleh web server
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Expose port 9000 untuk PHP-FPM
EXPOSE 9000

# Perintah default untuk menjalankan PHP-FPM
CMD ["php-fpm"]