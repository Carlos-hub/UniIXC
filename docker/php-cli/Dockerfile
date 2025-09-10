# Dockerfile para PHP CLI
# Configura um ambiente PHP para execução de comandos via linha de comando (CLI)
# Usar uma imagem base leve e específica para a versão do Debian
FROM debian:12-slim

# Argumento para definir qual versão do PHP será instalada
ARG PHP_VERSION=7.4
ENV PHP_VERSION=${PHP_VERSION}

# Evitar prompts interativos durante a instalação
ENV DEBIAN_FRONTEND=noninteractive

# Instalar dependências básicas e repositório Sury para versões PHP
RUN apt-get update \
    && apt-get install -y \
      curl \
      git \
      unzip \
    && rm -rf /tmp/* /var/tmp/*

# Comando para instalar o php-cli
RUN curl -sSLo sury.sh https://packages.sury.org/php/README.txt \
  && chmod +x sury.sh \
  && sh sury.sh \
  && apt-get install --no-install-recommends --no-install-suggests -y \
      php${PHP_VERSION}-cli \
      php${PHP_VERSION}-mbstring \
      php${PHP_VERSION}-memcached \
      php${PHP_VERSION}-pdo \
      php${PHP_VERSION}-pgsql \
      php${PHP_VERSION}-xdebug \
      php${PHP_VERSION}-xml \
      php${PHP_VERSION}-zip \
      php${PHP_VERSION}-apcu \
  && apt-get clean  \
  && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

# Comando para instalar o composer utilizando a imagem oficial do composer no docker hub
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copia configurações de desenvolvimento para o PHP
COPY dev.ini /etc/php/${PHP_VERSION}/mods-available/dev.ini
COPY apcu.ini /etc/php/${PHP_VERSION}/mods-available/apcu.ini

# Ativa o módulo de configurações de desenvolvimento
RUN phpenmod dev apcu

# Criar e configurar um usuário não root
RUN groupadd --gid 1000 ixcsoft && \
    useradd --uid 1000 --gid ixcsoft --shell /bin/bash --create-home ixcsoft

# Define o diretório de inicio de execução
WORKDIR /opt/project

# Define o usuário padrão para execução dos scripts
USER ixcsoft