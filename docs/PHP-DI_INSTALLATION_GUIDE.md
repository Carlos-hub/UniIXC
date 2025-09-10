# Guia de Instalação e Configuração do PHP-DI

Este guia fornece todos os passos necessários para instalar e configurar o PHP-DI no projeto, implementando um container
de dependência com padrão Singleton e estrutura organizada de interfaces e implementações.

## Pré-requisitos

- Docker e Docker Compose instalados
- PHP 7.4 configurado via Docker
- Composer instalado no container

## Passo 1: Instalação do PHP-DI via Composer

### 1.1 Atualizar o composer.json

Execute o comando para adicionar o PHP-DI ao projeto:

```bash
docker compose run --rm php-cli-74 composer require php-di/php-di
```

### 1.2 Verificar a instalação

O comando acima irá:

- Adicionar `php-di/php-di` às dependências no `composer.json`
- Baixar e instalar o PHP-DI e suas dependências
- Atualizar o arquivo `composer.lock`
- Gerar o autoload com as novas classes

### 1.3 Configurar autoload PSR-4 da aplicação

Adicione a configuração do autoload PSR-4 no arquivo `composer.json` para mapear o namespace da aplicação:

```json
{
  "name": "phpoo/app",
  "description": "Projeto do curso de PHP-OO",
  "minimum-stability": "stable",
  "license": "proprietary",
  "authors": [
    {
      "name": "franklin",
      "email": "franklin.liz@ixcsoft.com.br"
    }
  ],
  "require": {
    "php": ">=7.4 <8.5",
    "ext-pdo": "*"
  },
  "autoload": {
    "psr-4": {
      "App\\": "src/"
    }
  }
}
```

Após adicionar a configuração do autoload, execute o comando para regenerar o autoload:

```bash
docker compose run --rm php-cli-74 composer dump-autoload
```

Este passo é essencial para que o Composer possa localizar automaticamente as classes da aplicação no diretório `src/`
através do namespace `App\`.

## Passo 2: Criar Estrutura de Diretórios

### 2.1 Criar os diretórios necessários

Execute o comando para criar a estrutura de diretórios:

```bash
mkdir -p src/Bootstrap/Impl src/Service/Impl cache
```

## Passo 3: Instalar Extensão APCu nos Dockerfiles

### 3.1 apcu.ini

Criar os arquivos `docker/php-cli/apcu.ini` e `docker/php-fpm/apcu.ini` com o conteúdo:

```ini
extension = apcu.so
apc.enabled = 1
apc.shm_size = 64M
apc.enable_cli = 1

; Configurações específicas do APCu
apc.cache_by_default = 1
apc.ttl = 7200
apc.user_ttl = 7200
apc.gc_ttl = 3600
apc.mmap_file_mask = /tmp/apc.XXXXXX
apc.slam_defense = 1
apc.file_update_protection = 2
apc.enable_progressive_cache = 1
apc.max_file_size = 5M
apc.stat = 1
```

### 3.2 Atualizar Dockerfile do PHP-CLI

Adicionar extensão APCu no arquivo `docker/php-cli/Dockerfile` através do pacote `php7.4-apcu`

Copiar o arquivo .ini, adicionando ao Dockerfile:

```dockerfile
COPY docker/php-cli/apcu.ini /etc/php/7.4/mods-available/apcu.ini
```

Adicionar o apcu ao comando phpenmod

```dockerfile
RUN phpenmod dev apcu
```

### 3.3 Atualizar Dockerfile do PHP-FPM

Adcionar extensão APCu no arquivo `docker/php-fpm/Dockerfile` através do pacote `php7.4-apcu`

Copiar o arquivo .ini, adicionando ao Dockerfile:

```dockerfile
COPY docker/php-cli/apcu.ini /etc/php/7.4/mods-available/apcu.ini
```

Adicionar o apcu ao comando phpenmod

```dockerfile
RUN phpenmod dev apcu
```

### 3.4 Rebuild das imagens Docker

Após confirmar que o APCu está nos Dockerfiles, faça o rebuild das imagens:

```bash
docker compose build
```

## Passo 4: Configurar o Container com Build e Cache

### 4.1 Habilitar compilação e cache no container

O PHP-DI será configurado com:

- **Build compilation**: Gerar arquivo de compilação para melhor performance
- **Definition cache**: Ativar cache APCu para definições do container (melhora significativa de performance)

## Passo 5: Criar a Classe App (Singleton)

### 5.1 Estrutura da classe App

A classe `App` será implementada com padrão Singleton para inicializar o container DI:

```php
<?php

declare(strict_types=1);

namespace App;

use DI\Container;
use DI\ContainerBuilder;

final class App
{
    private static ?App $instance = null;
    private Container $container;

    private function __construct()
    {
        $this->initializeContainer();
    }

    public static function getInstance(): App
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function initializeContainer(): void
    {
        $builder = new ContainerBuilder();
        
        // Habilitar compilação
        $builder->enableCompilation(__DIR__ . '/../../cache');
        
        // Habilitar cache de definições (APCu)
        $builder->enableDefinitionCache();
        
        // Carregar definições dos serviços
        $this->loadServiceDefinitions($builder);
        
        $this->container = $builder->build();
    }

    private function loadServiceDefinitions(ContainerBuilder $builder): void
    {
        // Os service definitions serão carregados automaticamente
        $serviceDefinitions = [
            new \App\Bootstrap\Impl\ExampleServiceDefinition(),
            new \App\Bootstrap\Impl\DatabaseServiceDefinition(),
        ];

        foreach ($serviceDefinitions as $serviceDefinition) {
            $serviceDefinition->register($builder);
        }
    }

    public function container(): Container
    {
        return $this->container;
    }

    // Prevenir clonagem
    private function __clone() {}

    // Prevenir unserialize
    public function __wakeup() {}
}
```

## Passo 6: Criar Estrutura do Bootstrap

### 6.1 Interface ServiceDefinitionInterface

Criar a interface no diretório `Bootstrap/` seguindo PSR-4 Naming Conventions:

```php
<?php

declare(strict_types=1);

namespace App\Bootstrap;

use DI\ContainerBuilder;

interface ServiceDefinitionInterface
{
    public function register(ContainerBuilder $builder): void;
}
```

### 6.2 Implementações no diretório Impl/

As implementações da interface `ServiceDefinitionInterface` ficarão em `Bootstrap/Impl/`:

- `ExampleServiceDefinition.php`
- `DatabaseServiceDefinition.php`

## Passo 7: Estrutura de Diretórios

### 7.1 Organização proposta:

```
src/
├── App.php
├── Bootstrap/
│   ├── ServiceDefinitionInterface.php (interface)
│   └── Impl/
│       ├── ExampleServiceDefinition.php
│       └── DatabaseServiceDefinition.php
├── Service/
│   ├── IndexServiceInterface.php
│   ├── DatabaseTestServiceInterface.php
│   └── Impl/
│       ├── IndexService.php
│       └── DatabaseTestService.php
└── cache/ (para arquivos de compilação)
```

### 7.2 Regra de organização:

- **Interfaces**: Na raiz do seu diretório
- **Implementações**: Dentro do subdiretório `Impl/`

### 7.3 Regenerar autoload

Após criar a estrutura de diretórios, regenere o autoload:

```bash
docker compose run --rm php-cli-74 composer dump-autoload
```

## Passo 8: Criar Classes de Exemplo

### 8.1 Interface para IndexService

```php
<?php

declare(strict_types=1);

namespace App\Service;

interface IndexServiceInterface
{
    public function welcomeMessage(): string;
    public function phpVersion(): string;
    public function sessionInfo(): array;
}
```

### 8.2 Interface para DatabaseTestService

```php
<?php

declare(strict_types=1);

namespace App\Service;

interface DatabaseTestServiceInterface
{
    public function pageTitle(): string;
    public function databaseConfigurations(): array;
    public function testDatabase(string $name, string $host, string $user): string;
}
```

## Passo 9: Implementar as Classes de Serviço

### 9.1 IndexService (substituir conteúdo do index.php)

```php
<?php

declare(strict_types=1);

namespace App\Service\Impl;

use App\Service\IndexServiceInterface;

final class IndexService implements IndexServiceInterface
{
    public function welcomeMessage(): string
    {
        return 'Hello World!';
    }

    public function phpVersion(): string
    {
        return 'PHP Version: ' . phpversion();
    }

    public function sessionInfo(): array
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['random_number'])) {
            $_SESSION['random_number'] = rand(1, 100);
        }

        return [
            'session_id' => session_id(),
            'random_number' => $_SESSION['random_number'] ?? null
        ];
    }
}
```

### 9.2 DatabaseTestService (substituir conteúdo do test_database.php)

```php
<?php

declare(strict_types=1);

namespace App\Service\Impl;

use App\Service\DatabaseTestServiceInterface;
use PDO;
use Exception;

final class DatabaseTestService implements DatabaseTestServiceInterface
{
    public function pageTitle(): string
    {
        return "<h1>Teste PostgreSQL com PDO</h1>";
    }

    public function databaseConfigurations(): array
    {
        return [
            ['name' => 'Primary', 'host' => 'postgres_primary', 'user' => 'postgres'],
            ['name' => 'HAProxy', 'host' => 'haproxy', 'user' => 'postgres'],
        ];
    }

    public function testDatabase(string $name, string $host, string $user): string
    {
        $output = "<h3>{$name} Database</h3>";

        try {
            $pdo = new PDO("pgsql:host={$host};port=5432;dbname=phpoo_app", $user, 'postgres', [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 5,
            ]);

            // Verificar se é replica
            $stmt = $pdo->query("SELECT pg_is_in_recovery() as is_replica");
            $result = $stmt->fetch();
            $type = $result['is_replica'] === 't' ? 'Slave' : 'Primary';

            $output .= "✅ Conectado ({$type})<br>";

            // Testar operações
            try {
                $pdo->exec("CREATE TABLE IF NOT EXISTS simple_test (id SERIAL, data TEXT)");
                $pdo->exec("INSERT INTO simple_test (data) VALUES ('test-" . time() . "')");
                $output .= "✅ Escrita OK<br>";
            } catch (Exception $e) {
                $output .= "⚠️ Escrita bloqueada (read-only)<br>";
            }

            $stmt = $pdo->query("SELECT COUNT(*) as total FROM simple_test");
            $count = $stmt->fetch()['total'];
            $output .= "✅ Leitura OK ({$count} registros)<br>";

        } catch (Exception $e) {
            $output .= "❌ Erro: " . $e->getMessage() . "<br>";
        }

        return $output . "<br>";
    }
}
```

## Passo 10: Configurar ServiceDefinitions

### 10.1 ExampleServiceDefinition.php

```php
<?php

declare(strict_types=1);

namespace App\Bootstrap\Impl;

use App\Bootstrap\ServiceDefinitionInterface;
use App\Service\IndexServiceInterface;
use App\Service\Impl\IndexService;
use DI\ContainerBuilder;

final class ExampleServiceDefinition implements ServiceDefinitionInterface
{
    public function register(ContainerBuilder $builder): void
    {
        $builder->addDefinitions([
            IndexServiceInterface::class => \DI\autowire(IndexService::class),
        ]);
    }
}
```

### 10.2 DatabaseServiceDefinition.php

```php
<?php

declare(strict_types=1);

namespace App\Bootstrap\Impl;

use App\Bootstrap\ServiceDefinitionInterface;
use App\Service\DatabaseTestServiceInterface;
use App\Service\Impl\DatabaseTestService;
use DI\ContainerBuilder;

final class DatabaseServiceDefinition implements ServiceDefinitionInterface
{
    public function register(ContainerBuilder $builder): void
    {
        $builder->addDefinitions([
            DatabaseTestServiceInterface::class => \DI\autowire(DatabaseTestService::class),
        ]);
    }
}
```

## Passo 11: Atualizar os Arquivos Public

### 11.1 Novo public/index.php

```php
<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\App;
use App\Service\IndexServiceInterface;

$app = App::getInstance();
$container = $app->container();

$indexService = $container->get(IndexServiceInterface::class);

echo $indexService->welcomeMessage() . '<br />';
echo $indexService->phpVersion() . '<br />';

$sessionInfo = $indexService->sessionInfo();
echo 'Session ID: ' . $sessionInfo['session_id'] . '<br />';

if ($sessionInfo['random_number']) {
    echo 'Valor salvo na sessão: ' . $sessionInfo['random_number'];
} else {
    echo 'Não existe valor na sessão';
}
```

### 11.2 Novo public/test_database.php

```php
<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\App;
use App\Service\DatabaseTestServiceInterface;

$app = App::getInstance();
$container = $app->container();

$databaseService = $container->get(DatabaseTestServiceInterface::class);

echo $databaseService->pageTitle();

$databases = $databaseService->databaseConfigurations();
foreach ($databases as $db) {
    echo $databaseService->testDatabase($db['name'], $db['host'], $db['user']);
}

echo "<p>PHP " . phpversion() . " | PDO PostgreSQL: " .
    (extension_loaded('pdo_pgsql') ? 'OK' : 'Não encontrado') .
    " | " . date('Y-m-d H:i:s') . "</p>";
```

## Passo 12: Subir os Serviços Necessários

### 12.1 Executar docker compose up -d:

Antes de testar a aplicação, suba os serviços necessários:

```bash
docker compose up -d
```

## Passo 13: Comandos Docker para Teste

### 13.1 Instalar dependências:

```bash
docker compose run --rm php-cli-74 composer install
```

### 13.2 Testar a aplicação:

```bash
# Testar via curl ou browser
curl http://localhost:8080/public/index.php
curl http://localhost:8080/public/test_database.php
```

### 13.3 Comandos úteis de desenvolvimento:

```bash
# Executar comandos no container PHP 7.4
docker compose run --rm php-cli-74 php -v

# Acessar shell do container
docker compose run --rm php-cli-74 bash

# Ver logs
docker compose logs php-fpm-74
```

## Resumo das Características Implementadas

1. ✅ **PHP-DI instalado** via Composer no PHP 7.4
2. ✅ **Container com build compilation** e cache habilitados
3. ✅ **Classe App com Singleton** para inicialização do container
4. ✅ **Diretório Bootstrap** com interface ServiceDefinition
5. ✅ **Estrutura organizada**: interfaces na raiz, implementações em Impl/
6. ✅ **Classes de exemplo** substituindo echo direto dos arquivos public
7. ✅ **Injeção de dependência** funcionando nos arquivos public

## Solução de Problemas Comuns

### Cache do PHP-DI

**APCu Funcionando**: O APCu está devidamente configurado e funcionando tanto no ambiente CLI quanto no PHP-FPM. O cache
de definições (`enableDefinitionCache()`) está habilitado e proporcionando melhor performance.

**Configuração APCu**: Os arquivos `docker/php-cli/apcu.ini` e `docker/php-fpm/apcu.ini` contêm configurações
otimizadas:

- Shared memory: 64MB
- TTL configurado para 2 horas
- Garbage collection otimizado
- Proteção contra race conditions

**Verificação APCu**: Para verificar se APCu está funcionando, acesse `/test_apcu_web.php` no navegador ou execute o
teste CLI com `docker compose run --rm php-cli-74 php test_apcu.php`.

### Problemas de Sessão

**Problema**: Warning sobre headers já enviados ou sessão não funcionando

```
Warning: session_start(): Cannot send session cache limiter - headers already sent
```

**Solução**: Use verificação de status da sessão:

```php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
```

### Problemas de Conectividade de Banco

**Problema**: Erro de conexão com PostgreSQL em modo CLI

```
SQLSTATE[HY000] [2002] Name or service not known
```

**Solução**: Os testes de banco devem ser executados com os serviços Docker rodando:

```bash
docker compose up -d postgres_primary haproxy nginx_lb
```

### Problemas de Autoload

**Problema**: Classes não encontradas após criar nova estrutura

```
Fatal error: Class 'App\Service\IndexServiceInterface' not found
```

**Solução**: Regenere o autoload:

```bash
docker compose run --rm php-cli-74 composer dump-autoload
```

## Próximos Passos

Após seguir este guia, você terá:

- Um container DI funcional e otimizado
- Arquitetura limpa com separação de responsabilidades
- Código testável e extensível
- Base sólida para desenvolvimento com boas práticas de DI