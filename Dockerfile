# Dockerfile optimisé pour votre projet
FROM php:7.4-fpm-alpine

# Mettre à jour les dépôts et installer les dépendances nécessaires
RUN apk update && apk upgrade && \
    apk add --no-cache --virtual .build-deps \
        autoconf \
        g++ \
        gcc \
        make \
        pkgconfig \
        \
        # Dépendances générales
        bash \
        curl \
        git \
        zip \
        unzip \
        \
        # Dépendances pour PHP extensions (libxml2-dev pour dom/xml, etc.)
        libxml2-dev \
    && \
    \
    # Installer les extensions PHP de base
    docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_mysql \
        ctype \
        json \
        dom \
        xml \
    && \
    \
    # Installer et activer Xdebug 3.1.6 (compatible PHP 7.4)
    # Important : Spécifier la version exacte pour PHP 7.4
    pecl install xdebug-3.1.6 \
    && docker-php-ext-enable xdebug \
    && \
    \
    # Nettoyage : supprimer les dépendances de compilation pour réduire la taille de l'image
    apk del .build-deps

# Définir le répertoire de travail
WORKDIR /var/www/html

# Créer le répertoire logs avec les permissions adéquates
RUN mkdir -p /var/www/html/logs && chmod -R 777 /var/www/html/logs

# Exposer le port FPM
EXPOSE 9000

CMD ["php-fpm"]