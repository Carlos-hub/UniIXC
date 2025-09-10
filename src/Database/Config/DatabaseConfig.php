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