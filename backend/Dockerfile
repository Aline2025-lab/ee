# Utilise l'image officielle PHP avec FPM (FastCGI Process Manager)
FROM php:8.2-fpm 

# Installer les dépendances système et extensions PHP nécessaires pour Laravel
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

# Copier les fichiers composer depuis le dossier backend
COPY composer.json composer.lock ./
# Installer les dépendances PHP via Composer
RUN composer install --no-dev --optimize-autoloader

# Copier le reste des fichiers de l’application
COPY . .

# Générer la clé d’application Laravel
RUN php artisan key:generate

# Exécuter les migrations forcées
RUN php artisan migrate --force
# Définir le répertoire de travail
WORKDIR /app










# Exposer le port 8000 pour l’application Laravel
EXPOSE 8000

# Commande pour démarrer le serveur Laravel en écoute sur toutes les interfaces
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
