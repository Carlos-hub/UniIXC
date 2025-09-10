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