FROM php:8.0-apache

# เปิด extensions ที่จำเป็น
RUN apt-get update && apt-get install -y libpq-dev && \
    docker-php-ext-install pdo pdo_pgsql pgsql && \
    a2enmod rewrite

# เพิ่มลิมิตขนาดไฟล์อัปโหลด/POST (ค่า default ของ PHP คือ 8M ซึ่งเล็กเกินไปสำหรับ
# ฟอร์มที่แนบรูปหลายรูป — เพิ่มเป็น 25M/30M แทน)
RUN { \
        echo 'upload_max_filesize = 25M'; \
        echo 'post_max_size = 30M'; \
        echo 'max_file_uploads = 10'; \
        echo 'memory_limit = 256M'; \
        echo 'max_execution_time = 120'; \
    } > /usr/local/etc/php/conf.d/uploads.ini

# copy โค้ดทั้งหมดไปไว้ใน web root
COPY . /var/www/html/

# ตั้งค่า Apache
RUN chown -R www-data:www-data /var/www/html && \
    chmod -R 755 /var/www/html

EXPOSE 80