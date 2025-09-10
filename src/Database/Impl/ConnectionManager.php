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