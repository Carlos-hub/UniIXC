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
            $output .= "? Erro: " . $e->getMessage() . "<br>";
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

        $output .= "? Conectado ({$type})<br>";

        // Testar operações usando Doctrine DBAL
        try {
            $connection->executeStatement("CREATE TABLE IF NOT EXISTS connection_test (id SERIAL, data TEXT, created_at TIMESTAMP DEFAULT NOW())");
            $connection->executeStatement("INSERT INTO connection_test (data) VALUES (?)", ['test-' . time()]);
            $output .= "? Escrita OK<br>";
        } catch (Exception $e) {
            $output .= "?? Escrita bloqueada (read-only)<br>";
        }

        $count = $connection->fetchOne("SELECT COUNT(*) FROM connection_test");
        $output .= "? Leitura OK ({$count} registros)<br>";

        // Mostrar estatísticas de conexão
        $stats = $this->connectionManager->getConnectionStats();
        $output .= "? Stats: " . json_encode($stats) . "<br>";

        return $output;
    }
}