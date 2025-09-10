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