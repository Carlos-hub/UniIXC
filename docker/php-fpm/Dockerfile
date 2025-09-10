# Dockerfile para PHP-FPM
# Configura um servidor PHP FastCGI Process Manager para processar requisições PHP
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

# Comando para instalar o php-fpm
RUN curl -sSLo sury.sh https://packages.sury.org/php/README.txt \
  && chmod +x sury.sh \
  && sh sury.sh \
  && apt-get install --no-install-recommends --no-install-suggests -y \
      php${PHP_VERSION}-fpm \
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

# Ativa o módulo de configurações de desenvolvimento
RUN phpenmod dev

# Criar e configurar um usuário não root
RUN groupadd --gid 1000 ixcsoft && \
    useradd --uid 1000 --gid ixcsoft --shell /bin/bash --create-home ixcsoft

# Define o diretório de inicio de execução
WORKDIR /opt/project

# Configurar PHP-FPM para usar porta TCP em vez de socket Unix
# Converte de socket (/run/php/php-fpm.sock) para porta (0.0.0.0:9000)
# Altera o usuário padrão de www-data para ixcsoft
RUN sed -i "s|/run/php/php${PHP_VERSION}-fpm.sock|0.0.0.0:9000|" /etc/php/${PHP_VERSION}/fpm/pool.d/www.conf \
  && sed -i 's|www-data|ixcsoft|' /etc/php/${PHP_VERSION}/fpm/pool.d/www.conf \
  && sed -i 's|www-data|ixcsoft|' /etc/php/${PHP_VERSION}/fpm/pool.d/www.conf \
  && sed -i '/^user = /d' /etc/php/${PHP_VERSION}/fpm/pool.d/www.conf \
  && sed -i '/^group = /d' /etc/php/${PHP_VERSION}/fpm/pool.d/www.conf

# Expor a porta 9000 para comunicação FastCGI
EXPOSE 9000

# Criar script de entrypoint para inicialização do PHP-FPM
# Define permissões adequadas para logs e arquivos PID
RUN echo '#!/bin/sh' > /entrypoint.sh \
    && echo "/usr/sbin/php-fpm${PHP_VERSION} -F" >> /entrypoint.sh \
    && chmod +x /entrypoint.sh \
    && touch /var/log/php${PHP_VERSION}-fpm.log \
    && chown ixcsoft:ixcsoft /var/log/php${PHP_VERSION}-fpm.log \
    && touch /run/php/php${PHP_VERSION}-fpm.pid \
    && chown ixcsoft:ixcsoft /run/php/php${PHP_VERSION}-fpm.pid

# Add php_oo.ini
COPY php_oo.ini /etc/php/${PHP_VERSION}/mods-available/php_oo.ini
COPY apcu.ini /etc/php/${PHP_VERSION}/mods-available/apcu.ini
RUN phpenmod php_oo
RUN phpenmod dev apcu

# Define o script de entrypoint para inicialização do PHP-FPM em foreground
ENTRYPOINT ["/entrypoint.sh"]

# Define o usuário padrão para execução dos scripts
USER ixcsoft