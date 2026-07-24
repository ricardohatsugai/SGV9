FROM php:8.2-fpm

# Instala extensões essenciais para banco de dados relacional
RUN docker-php-ext-install pdo pdo_mysql

# Define o diretório de trabalho padrão
WORKDIR /var/www/html
