#!/bin/bash
set -e
export PROXY=http://scnkt47u2f:007dbce8@10.1.4.13:3120
export BASE=/root/private_data/sun/tools

echo "=== 1. 创建目录结构 ==="
mkdir -p $BASE/php $BASE/composer $BASE/mysql /root/private_data/sun/mysql-data

echo "=== 2. 下载 Composer ==="
curl -x $PROXY -sSL https://getcomposer.org/download/latest-stable/composer.phar -o $BASE/composer/composer.phar
chmod +x $BASE/composer/composer.phar
echo "Composer OK"

echo "=== 3. 下载 PHP 8.2 CLI ==="
curl -x $PROXY -sSL https://dl.static-php.dev/static/php-src-8.2/cli-latest/linux-x86_64/php -o $BASE/php/php
chmod +x $BASE/php/php
$BASE/php/php -v 2>&1 | head -3

echo "=== 4. 下载 PHP 8.2 FPM ==="
curl -x $PROXY -sSL https://dl.static-php.dev/static/php-src-8.2/fpm-latest/linux-x86_64/php-fpm -o $BASE/php/php-fpm
chmod +x $BASE/php/php-fpm
echo "PHP-FPM OK"

echo "=== 5. 下载 PHP 扩展 ==="
for ext in curl mbstring pdo pdo_mysql mysqli gd zip openssl fileinfo; do
    curl -x $PROXY -sSL "https://dl.static-php.dev/static/php-src-8.2/cli-latest/linux-x86_64/extensions/${ext}.so" -o "$BASE/php/ext-${ext}.so" 2>&1 || echo "  skip: $ext"
done
echo "=== extensions OK ==="

echo "=== 6. 下载 MySQL 8.0 ==="
cd /tmp
curl -x $PROXY -sSL https://dev.mysql.com/get/Downloads/MySQL-8.0/mysql-8.0.36-linux-glibc2.17-x86_64-minimal.tar.xz -o mysql.tar.xz
tar -xf mysql.tar.xz
mv mysql-8.0.36-linux-glibc2.17-x86_64-minimal/* $BASE/mysql/
rm -rf mysql-8.0.36-linux-glibc2.17-x86_64-minimal mysql.tar.xz
echo "MySQL OK"

echo "=== ALL DONE ==="
$BASE/php/php -v 2>&1 | head -1
$BASE/mysql/bin/mysqld --version 2>&1
