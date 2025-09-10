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