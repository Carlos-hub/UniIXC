<?php

declare(strict_types=1);

namespace App\Bootstrap\Impl;

use App\Bootstrap\ServiceDefinitionInterface;
use App\Service\IndexServiceInterface;
use App\Service\Impl\IndexService;
use DI\ContainerBuilder;

final class ExampleServiceDefinition implements ServiceDefinitionInterface
{
    public function register(ContainerBuilder $builder): void
    {
        $builder->addDefinitions([
            IndexServiceInterface::class => \DI\autowire(IndexService::class),
        ]);
    }
}