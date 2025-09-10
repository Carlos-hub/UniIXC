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

            $output .= "? Conectado ({$type})<br>";

            // Testar operações
            try {
                $pdo->exec("CREATE TABLE IF NOT EXISTS simple_test (id SERIAL, data TEXT)");
                $pdo->exec("INSERT INTO simple_test (data) VALUES ('test-" . time() . "')");
                $output .= "? Escrita OK<br>";
            } catch (Exception $e) {
                $output .= "?? Escrita bloqueada (read-only)<br>";
            }

            $stmt = $pdo->query("SELECT COUNT(*) as total FROM simple_test");
            $count = $stmt->fetch()['total'];
            $output .= "? Leitura OK ({$count} registros)<br>";

        } catch (Exception $e) {
            $output .= "? Erro: " . $e->getMessage() . "<br>";
        }

        return $output . "<br>";
    }
}