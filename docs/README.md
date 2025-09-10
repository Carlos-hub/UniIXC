# Documentação do Projeto PHP-OO

## Visão Geral

Este projeto implementa uma aplicação PHP moderna com arquitetura orientada a objetos, utilizando Docker, PostgreSQL com replicação, HAProxy, Nginx, PHP-DI (Dependency Injection) e Doctrine DBAL para gerenciamento de conexões de banco de dados.

## Arquitetura do Sistema

- **Docker & Docker Compose**: Containerização de todos os serviços
- **PostgreSQL**: Banco principal com replicação (Primary/Slave)
- **HAProxy**: Load balancer para banco de dados
- **Nginx**: Servidor web e load balancer para PHP-FPM
- **PHP-FPM**: Múltiplas versões (7.4, 8.0, 8.1, 8.2, 8.3, 8.4)
- **Memcached**: Cache de sessões PHP
- **PHP-DI**: Container de injeção de dependências
- **Doctrine DBAL**: Gerenciamento avançado de conexões de banco

## Guias de Execução

⚠️ **IMPORTANTE**: Execute os guias na sequência indicada abaixo para garantir o funcionamento correto do sistema.

### 1. Health Checks dos Serviços
**Arquivo**: `HEALTH_CHECKS_GUIDE.md`

Este guia configura e testa os health checks de todos os serviços Docker, garantindo que:
- Todos os containers estejam funcionando corretamente
- As dependências entre serviços sejam respeitadas
- O sistema esteja pronto para receber requisições

**Pré-requisitos**: Docker e Docker Compose instalados
**Tempo estimado**: 5-10 minutos

### 2. Testes de Funcionamento
**Arquivo**: `TESTING_GUIDE.md`

Este guia executa testes completos da infraestrutura, incluindo:
- Subida dos containers
- Testes de conectividade
- Validação de endpoints
- Verificação de logs e status

**Pré-requisitos**: Health checks executados com sucesso
**Tempo estimado**: 10-15 minutos

### 3. Instalação e Configuração do PHP-DI
**Arquivo**: `PHP-DI_INSTALLATION_GUIDE.md`

Este guia implementa o sistema de injeção de dependências, incluindo:
- Instalação do PHP-DI via Composer
- Configuração do autoload PSR-4
- Criação da estrutura de serviços
- Implementação do padrão Singleton
- Configuração do APCu para cache

**Pré-requisitos**: Testes de funcionamento executados com sucesso
**Tempo estimado**: 15-20 minutos

### 4. Gerenciamento de Conexões de Banco de Dados
**Arquivo**: `DATABASE_CONNECTION_MANAGER_GUIDE.md`

Este guia implementa o sistema avançado de gerenciamento de conexões usando Doctrine DBAL, incluindo:
- Instalação do Doctrine DBAL
- Implementação do padrão Singleton para conexões
- Lazy Loading de conexões
- Gerenciamento de conexões Primary/Slave
- Integração com PHP-DI
- Funcionalidades avançadas (Query Builder, Schema Manager)

**Pré-requisitos**: PHP-DI instalado e configurado
**Tempo estimado**: 20-25 minutos

## Sequência de Execução Recomendada

```bash
# 1. Health Checks
cd docs
cat HEALTH_CHECKS_GUIDE.md
# Execute os comandos do guia

# 2. Testes de Funcionamento
cat TESTING_GUIDE.md
# Execute os comandos do guia

# 3. PHP-DI
cat PHP-DI_INSTALLATION_GUIDE.md
# Execute os comandos do guia

# 4. Database Connection Manager
cat DATABASE_CONNECTION_MANAGER_GUIDE.md
# Execute os comandos do guia
```

## Estrutura do Projeto

```
project_phpoo_final/
├── docker/                    # Configurações Docker
│   ├── nginx/                # Configuração Nginx
│   ├── php-cli/              # Imagem PHP CLI
│   ├── php-fpm/              # Imagem PHP-FPM
│   └── postgres/             # Scripts PostgreSQL
├── docs/                     # Documentação
│   ├── README.md             # Este arquivo
│   ├── HEALTH_CHECKS_GUIDE.md
│   ├── TESTING_GUIDE.md
│   ├── PHP-DI_INSTALLATION_GUIDE.md
│   └── DATABASE_CONNECTION_MANAGER_GUIDE.md
├── public/                   # Arquivos públicos
│   ├── index.php
│   ├── test_database.php
│   └── test_connection_manager.php
├── src/                      # Código fonte
│   ├── App.php              # Singleton do container DI
│   ├── Bootstrap/           # Service definitions
│   ├── Database/            # Gerenciamento de conexões
│   └── Service/             # Serviços da aplicação
├── docker-compose.yml       # Orquestração dos containers
└── composer.json            # Dependências PHP
```

## Comandos Úteis

### Gerenciamento de Containers
```bash
# Subir todos os serviços
docker compose up -d

# Ver status dos containers
docker compose ps

# Ver logs de um serviço
docker compose logs <service_name>

# Parar todos os serviços
docker compose down
```

### Testes Rápidos
```bash
# Testar endpoint principal
curl http://localhost:8080/

# Testar banco de dados
curl http://localhost:8080/test_database.php

# Testar gerenciador de conexões
curl http://localhost:8080/test_connection_manager.php
```

### Desenvolvimento
```bash
# Instalar dependências
docker compose run --rm php-cli-74 composer install

# Regenerar autoload
docker compose run --rm php-cli-74 composer dump-autoload

# Executar comandos PHP
docker compose run --rm php-cli-74 php <script.php>
```

## Troubleshooting

### Problemas Comuns

1. **Containers não sobem**: Verifique se as portas 8080 e 5432 estão livres
2. **Erro de conexão com banco**: Execute o guia de Health Checks
3. **Erro de autoload**: Execute `composer dump-autoload`
4. **Cache desatualizado**: Limpe o diretório `cache/` e reinicie os containers

### Logs Importantes

```bash
# Logs do Nginx
docker compose logs nginx_lb

# Logs do PHP-FPM
docker compose logs php-fpm-74

# Logs do PostgreSQL
docker compose logs postgres_primary

# Logs do HAProxy
docker compose logs haproxy
```

## Suporte

Para dúvidas ou problemas:
1. Verifique os logs dos containers
2. Execute os guias na sequência correta
3. Consulte a documentação específica de cada guia
4. Verifique se todos os pré-requisitos foram atendidos

---

**Última atualização**: Setembro 2024  
**Versão**: 1.0.0  
**Autor**: Franklin Liz
