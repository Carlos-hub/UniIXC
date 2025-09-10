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