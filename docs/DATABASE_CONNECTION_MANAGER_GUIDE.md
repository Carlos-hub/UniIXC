# Guia de Gerenciamento de Conexões de Banco de Dados

## Visão Geral

Este guia implementa um padrão de gerenciamento de conexões de banco de dados usando **Doctrine DBAL** que:
- Gerencia conexões principais e slaves de forma centralizada
- Utiliza Lazy Loading (conexões criadas apenas quando necessárias)
- Implementa Singleton pattern para reutilização de conexões
- Integra com PHP-DI usando `DI\autowire` e service definitions
- Suporta múltiplas conexões slaves (HAProxy, etc.)
- Oferece abstração de banco e funcionalidades avançadas do Doctrine DBAL

## Estrutura Final

```
src/
├── Database/
│   ├── ConnectionManagerInterface.php
│   ├── Impl/
│   │   ├── ConnectionManager.php
│   │   └── DatabaseConnectionFactory.php
│   └── Config/
│       └── DatabaseConfig.php
├── Bootstrap/
│   └── Impl/
│       └── DatabaseConnectionDefinition.php
└── Service/
    └── Impl/
        └── DatabaseTestService.php (atualizado)
```

## Passo 1: Instalar Doctrine DBAL

### 1.1 Instalação via Composer

```bash
docker compose run --rm php-cli-74 composer require doctrine/dbal
```

## Passo 2: Criar Interface do Gerenciador de Conexões

### 2.1 Interface ConnectionManagerInterface

Criar `src/Database/ConnectionManagerInterface.php`:

```php
<?php

declare(strict_types=1);

namespace App\Database;

use Doctrine\DBAL\Connection;

interface ConnectionManagerInterface
{
    public function getPrimaryConnection(): Connection;
    public function getSlaveConnection(string $name = 'default'): Connection;
    public function hasSlaveConnection(string $name): bool;
    public function getAvailableSlaves(): array;
}
```

## Passo 3: Configuração de Banco de Dados

### 3.1 Classe de Configuração

Criar `src/Database/Config/DatabaseConfig.php`:

```php
<?php

declare(strict_types=1);

namespace App\Database\Config;

final class DatabaseConfig
{
    private array $primaryConfig;
    private array $slavesConfig;

    public function __construct()
    {
        $this->primaryConfig = [
            'driver' => 'pdo_pgsql',
            'host' => 'postgres_primary',
            'port' => 5432,
            'dbname' => 'phpoo_app',
            'user' => 'postgres',
            'password' => 'postgres',
            'charset' => 'utf8',
        ];

        $this->slavesConfig = [
            'haproxy' => [
                'driver' => 'pdo_pgsql',
                'host' => 'haproxy',
                'port' => 5432,
                'dbname' => 'phpoo_app',
                'user' => 'postgres',
                'password' => 'postgres',
                'charset' => 'utf8',
            ],
            // Adicione mais slaves conforme necessário
            // 'slave2' => [...],
        ];
    }

    public function getPrimaryConfig(): array
    {
        return $this->primaryConfig;
    }

    public function getSlavesConfig(): array
    {
        return $this->slavesConfig;
    }

    public function getSlaveConfig(string $name): ?array
    {
        return $this->slavesConfig[$name] ?? null;
    }
}
```

## Passo 4: Factory de Conexões com Doctrine DBAL

### 4.1 DatabaseConnectionFactory

Criar `src/Database/Impl/DatabaseConnectionFactory.php`:

```php
<?php

declare(strict_types=1);

namespace App\Database\Impl;

use App\Database\Config\DatabaseConfig;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Exception;

final class DatabaseConnectionFactory
{
    private DatabaseConfig $config;

    public function __construct(DatabaseConfig $config)
    {
        $this->config = $config;
    }

    public function createPrimaryConnection(): Connection
    {
        $config = $this->config->getPrimaryConfig();
        return $this->createConnection($config, 'Primary');
    }

    public function createSlaveConnection(string $name): Connection
    {
        $config = $this->config->getSlaveConfig($name);
        
        if ($config === null) {
            throw new Exception("Slave connection '{$name}' not configured");
        }

        return $this->createConnection($config, "Slave-{$name}");
    }

    private function createConnection(array $config, string $type): Connection
    {
        try {
            $connection = DriverManager::getConnection($config);
            
            // Testar a conexão
            $connection->connect();
            
            // Log da conexão (opcional)
            error_log("Database connection created: {$type} -> {$config['host']}:{$config['port']}");
            
            return $connection;
        } catch (Exception $e) {
            error_log("Database connection failed: {$type} -> {$e->getMessage()}");
            throw new Exception("Failed to connect to {$type} database: {$e->getMessage()}", 0, $e);
        }
    }
}
```

## Passo 5: Implementação do Gerenciador

### 5.1 ConnectionManager

Criar `src/Database/Impl/ConnectionManager.php`:

```php
<?php

declare(strict_types=1);

namespace App\Database\Impl;

use App\Database\ConnectionManagerInterface;
use App\Database\Config\DatabaseConfig;
use Doctrine\DBAL\Connection;

final class ConnectionManager implements ConnectionManagerInterface
{
    private ?Connection $primaryConnection = null;
    private array $slaveConnections = [];
    private DatabaseConnectionFactory $factory;

    public function __construct(DatabaseConnectionFactory $factory)
    {
        $this->factory = $factory;
    }

    public function getPrimaryConnection(): Connection
    {
        if ($this->primaryConnection === null) {
            $this->primaryConnection = $this->factory->createPrimaryConnection();
        }

        return $this->primaryConnection;
    }

    public function getSlaveConnection(string $name = 'default'): Connection
    {
        if ($name === 'default') {
            $name = 'haproxy'; // Nome padrão do slave
        }

        if (!isset($this->slaveConnections[$name])) {
            $this->slaveConnections[$name] = $this->factory->createSlaveConnection($name);
        }

        return $this->slaveConnections[$name];
    }

    public function hasSlaveConnection(string $name): bool
    {
        return isset($this->slaveConnections[$name]);
    }

    public function getAvailableSlaves(): array
    {
        return array_keys($this->slaveConnections);
    }

    public function getConnectionStats(): array
    {
        return [
            'primary_connected' => $this->primaryConnection !== null,
            'slaves_connected' => array_keys($this->slaveConnections),
            'total_connections' => ($this->primaryConnection ? 1 : 0) + count($this->slaveConnections),
        ];
    }
}
```

## Passo 6: Definição de Serviços no Container

### 6.1 DatabaseConnectionDefinition

Criar `src/Bootstrap/Impl/DatabaseConnectionDefinition.php`:

```php
<?php

declare(strict_types=1);

namespace App\Bootstrap\Impl;

use App\Bootstrap\ServiceDefinitionInterface;
use App\Database\Config\DatabaseConfig;
use App\Database\ConnectionManagerInterface;
use App\Database\Impl\ConnectionManager;
use App\Database\Impl\DatabaseConnectionFactory;
use DI\ContainerBuilder;

final class DatabaseConnectionDefinition implements ServiceDefinitionInterface
{
    public function register(ContainerBuilder $builder): void
    {
        $builder->addDefinitions([
            // Configuração do banco
            DatabaseConfig::class => \DI\autowire(DatabaseConfig::class),
            
            // Factory de conexões
            DatabaseConnectionFactory::class => \DI\autowire(DatabaseConnectionFactory::class),
            
            // Gerenciador de conexões (Singleton)
            ConnectionManagerInterface::class => \DI\autowire(ConnectionManager::class),
        ]);
    }
}
```

## Passo 7: Atualizar App.php

### 7.1 Adicionar nova definição

Atualizar `src/App.php` para incluir a nova definição:

```php
private function loadServiceDefinitions(ContainerBuilder $builder): void
{
    $serviceDefinitions = [
        new \App\Bootstrap\Impl\ExampleServiceDefinition(),
        new \App\Bootstrap\Impl\DatabaseServiceDefinition(),
        new \App\Bootstrap\Impl\DatabaseConnectionDefinition(), // Nova definição
    ];

    foreach ($serviceDefinitions as $serviceDefinition) {
        $serviceDefinition->register($builder);
    }
}
```

## Passo 8: Atualizar DatabaseTestService

### 8.1 Integrar com ConnectionManager

Atualizar `src/Service/Impl/DatabaseTestService.php`:

```php
<?php

declare(strict_types=1);

namespace App\Service\Impl;

use App\Database\ConnectionManagerInterface;
use App\Service\DatabaseTestServiceInterface;
use Exception;
use Doctrine\DBAL\Connection;

final class DatabaseTestService implements DatabaseTestServiceInterface
{
    private ConnectionManagerInterface $connectionManager;

    public function __construct(ConnectionManagerInterface $connectionManager)
    {
        $this->connectionManager = $connectionManager;
    }

    public function pageTitle(): string
    {
        return "<h1>Teste PostgreSQL com Doctrine DBAL Connection Manager</h1>";
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
            $connection = $this->getConnection($name);
            $output .= $this->testConnection($connection, $name);

        } catch (Exception $e) {
            $output .= "❌ Erro: " . $e->getMessage() . "<br>";
        }

        return $output . "<br>";
    }

    private function getConnection(string $name): Connection
    {
        if ($name === 'Primary') {
            return $this->connectionManager->getPrimaryConnection();
        }

        if ($name === 'HAProxy') {
            return $this->connectionManager->getSlaveConnection('haproxy');
        }

        throw new Exception("Unknown database connection: {$name}");
    }

    private function testConnection(Connection $connection, string $name): string
    {
        $output = '';

        // Verificar se é replica usando Doctrine DBAL
        $result = $connection->fetchAssociative("SELECT pg_is_in_recovery() as is_replica");
        $type = $result['is_replica'] === 't' ? 'Slave' : 'Primary';

        $output .= "✅ Conectado ({$type})<br>";

        // Testar operações usando Doctrine DBAL
        try {
            $connection->executeStatement("CREATE TABLE IF NOT EXISTS connection_test (id SERIAL, data TEXT, created_at TIMESTAMP DEFAULT NOW())");
            $connection->executeStatement("INSERT INTO connection_test (data) VALUES (?)", ['test-' . time()]);
            $output .= "✅ Escrita OK<br>";
        } catch (Exception $e) {
            $output .= "⚠️ Escrita bloqueada (read-only)<br>";
        }

        $count = $connection->fetchOne("SELECT COUNT(*) FROM connection_test");
        $output .= "✅ Leitura OK ({$count} registros)<br>";

        // Mostrar estatísticas de conexão
        $stats = $this->connectionManager->getConnectionStats();
        $output .= "📊 Stats: " . json_encode($stats) . "<br>";

        return $output;
    }
}
```

## Passo 9: Script de Teste

### 9.1 Criar test_connection_manager.php

Criar `public/test_connection_manager.php`:

```php
<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\App;
use App\Database\ConnectionManagerInterface;
use Doctrine\DBAL\Connection;

$app = App::getInstance();
$container = $app->container();

$connectionManager = $container->get(ConnectionManagerInterface::class);

echo "<h1>Doctrine DBAL Connection Manager Test</h1>";

// Testar conexão primária
echo "<h2>Primary Connection Test</h2>";
try {
    $primary = $connectionManager->getPrimaryConnection();
    $result = $primary->fetchAssociative("SELECT 'Primary DB' as db_type, version() as version");
    echo "✅ Primary: " . $result['db_type'] . " - " . $result['version'] . "<br>";
} catch (Exception $e) {
    echo "❌ Primary Error: " . $e->getMessage() . "<br>";
}

// Testar conexão slave (HAProxy)
echo "<h2>Slave Connection Test (HAProxy)</h2>";
try {
    $slave = $connectionManager->getSlaveConnection('haproxy');
    $result = $slave->fetchAssociative("SELECT 'Slave DB' as db_type, version() as version");
    echo "✅ Slave: " . $result['db_type'] . " - " . $result['version'] . "<br>";
} catch (Exception $e) {
    echo "❌ Slave Error: " . $e->getMessage() . "<br>";
}

// Mostrar estatísticas
echo "<h2>Connection Statistics</h2>";
$stats = $connectionManager->getConnectionStats();
echo "<pre>" . json_encode($stats, JSON_PRETTY_PRINT) . "</pre>";

// Testar reutilização de conexões
echo "<h2>Connection Reuse Test</h2>";
$primary1 = $connectionManager->getPrimaryConnection();
$primary2 = $connectionManager->getPrimaryConnection();
echo "Primary connections are the same: " . ($primary1 === $primary2 ? "✅ Yes" : "❌ No") . "<br>";

$slave1 = $connectionManager->getSlaveConnection('haproxy');
$slave2 = $connectionManager->getSlaveConnection('haproxy');
echo "Slave connections are the same: " . ($slave1 === $slave2 ? "✅ Yes" : "❌ No") . "<br>";

// Testar funcionalidades do Doctrine DBAL
echo "<h2>Doctrine DBAL Features Test</h2>";
try {
    $primary = $connectionManager->getPrimaryConnection();
    
    // Testar Query Builder
    $qb = $primary->createQueryBuilder();
    $qb->select('COUNT(*) as total')
       ->from('connection_test');
    
    $count = $qb->executeQuery()->fetchOne();
    echo "✅ Query Builder: {$count} registros<br>";
    
    // Testar Schema Manager
    $schemaManager = $primary->createSchemaManager();
    $tables = $schemaManager->listTableNames();
    echo "✅ Schema Manager: " . count($tables) . " tabelas encontradas<br>";
    
} catch (Exception $e) {
    echo "❌ Doctrine DBAL Features Error: " . $e->getMessage() . "<br>";
}

echo "<p>PHP " . phpversion() . " | " . date('Y-m-d H:i:s') . "</p>";
```

## Passo 10: Executar Testes

### 10.1 Comandos de teste

```bash
# Rebuild das imagens (se necessário)
docker compose build

# Subir os serviços
docker compose up -d

# Instalar dependências
docker compose run --rm php-cli-74 composer install

# Testar o gerenciador de conexões
curl http://localhost:8080/test_connection_manager.php

# Testar o serviço atualizado
curl http://localhost:8080/test_database.php
```

## Características do Sistema com Doctrine DBAL

### ✅ **Lazy Loading**
- Conexões são criadas apenas quando solicitadas
- Reutilização de conexões existentes

### ✅ **Singleton Pattern**
- Uma instância do ConnectionManager por container
- Conexões são reutilizadas entre chamadas

### ✅ **Factory Pattern**
- DatabaseConnectionFactory cria conexões
- Configuração centralizada em DatabaseConfig

### ✅ **PHP-DI Integration**
- Uso de `DI\autowire` para criação do gerenciador
- Injeção de dependências automática

### ✅ **Doctrine DBAL Features**
- **Query Builder**: Interface fluente para consultas
- **Schema Manager**: Gerenciamento de esquemas
- **Connection Pooling**: Gerenciamento automático de conexões
- **Abstração de Banco**: Suporte a múltiplos SGBDs
- **Transaction Management**: Controle avançado de transações

### ✅ **Extensibilidade**
- Fácil adição de novos slaves
- Configuração flexível via DatabaseConfig

### ✅ **Error Handling**
- Tratamento de erros de conexão
- Logs detalhados para debugging

## Vantagens do Doctrine DBAL

1. **Performance**: Conexões reutilizadas, não recriadas
2. **Memória**: Lazy loading economiza recursos
3. **Manutenibilidade**: Código organizado e testável
4. **Flexibilidade**: Fácil configuração de múltiplas conexões
5. **Debugging**: Logs e estatísticas detalhadas
6. **Abstração**: Suporte nativo a múltiplos bancos de dados
7. **Funcionalidades Avançadas**: Query Builder, Schema Manager, etc.
