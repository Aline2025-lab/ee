FROM php:8.2-fpm 

# Installer les dépendances système et extensions PHP nécessaires
RUN apt-get update && apt-get install -y \
    git \
    zip \
    unzip \
    libzip-dev \
    libonig-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libcurl4-openssl-dev \
    && docker-php-ext-install pdo_mysql mbstring zip exif pcntl bcmath gd curl

# Installer Composer globalement
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Définir le répertoire de travail
WORKDIR /app

# Copier tous les fichiers du projet (y compris artisan, etc.)
COPY . .

# Installer les dépendances PHP via Composer
RUN composer install --no-dev --optimize-autoloader

# Générer la clé d’application Laravel
RUN php artisan key:generate

# Exécuter les migrations
# RUN php artisan migrate --force

# Exposer le port
EXPOSE 8000

# Lancer le serveur Laravel
CMD ["sh", "-c", "php artisan serve --host=0.0.0.0 --port=${PORT}"]

