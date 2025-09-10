<?php

declare(strict_types=1);

namespace App\Bootstrap;

use DI\ContainerBuilder;

interface ServiceDefinitionInterface
{
    public function register(ContainerBuilder $builder): void;
}