FROM php:8.0-apache

# เปิด extensions ที่จำเป็น
RUN apt-get update && apt-get install -y libpq-dev && \
    docker-php-ext-install pdo pdo_pgsql pgsql && \
    a2enmod rewrite

# copy โค้ดทั้งหมดไปไว้ใน web root
COPY . /var/www/html/

# ตั้งค่า Apache
RUN chown -R www-data:www-data /var/www/html && \
    chmod -R 755 /var/www/html

EXPOSE 80