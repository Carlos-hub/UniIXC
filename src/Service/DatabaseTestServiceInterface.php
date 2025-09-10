<?php

declare(strict_types=1);

namespace App\Service;

interface DatabaseTestServiceInterface
{
    public function pageTitle(): string;
    public function databaseConfigurations(): array;
    public function testDatabase(string $name, string $host, string $user): string;
}