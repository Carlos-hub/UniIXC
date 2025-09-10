# Dockerfile para Load Balancer Nginx
# Configura um proxy reverso para distribuir requisições entre múltiplos serviços nginx+php-fpm
FROM nginx

COPY default.conf /etc/nginx/conf.d/default.conf

# Criar e configurar um usuário não root
RUN groupadd --gid 1000 ixcsoft && \
    useradd --uid 1000 --gid ixcsoft --shell /bin/bash --create-home ixcsoft

# Remover qualquer diretiva 'user' existente do nginx.conf
RUN sed -i '/^user/d' /etc/nginx/nginx.conf

# Criar diretórios necessários e definir permissões
RUN mkdir -p /var/cache/nginx/client_temp \
    /var/cache/nginx/proxy_temp \
    /var/cache/nginx/fastcgi_temp \
    /var/cache/nginx/uwsgi_temp \
    /var/cache/nginx/scgi_temp \
    && chown -R ixcsoft:ixcsoft /var/cache/nginx \
    && chown -R ixcsoft:ixcsoft /var/log/nginx \
    && touch /var/run/nginx.pid \
    && chown ixcsoft:ixcsoft /var/run/nginx.pid \
    && chown -R ixcsoft:ixcsoft /etc/nginx/conf.d

# Expor porta 8080
EXPOSE 8080

# Executar como usuário não-root
USER ixcsoft

CMD ["nginx", "-g", "daemon off;"]