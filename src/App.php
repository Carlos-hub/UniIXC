<?php

declare(strict_types=1);

namespace App;


use DI\Container;
use DI\ContainerBuilder;

final class App
{
    private static ?App $instance = null;
    private Container $container;

    private function __construct()
    {
        $this->initializeContainer();
    }

    public static function getInstance(): App
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function initializeContainer(): void
    {
        $builder = new ContainerBuilder();

        // Habilitar compilação
        $builder->enableCompilation(__DIR__ . '/../cache');

        // Habilitar cache de definições (APCu)
//        $builder->enableDefinitionCache();

        // Carregar definições dos serviços
        $this->loadServiceDefinitions($builder);

        $this->container = $builder->build();
    }

    private function loadServiceDefinitions(ContainerBuilder $builder): void
    {
        $serviceDefinitions = [
            new \App\Bootstrap\Impl\ExampleServiceDefinition(),
            new \App\Bootstrap\Impl\DatabaseServiceDefinition(),
            new \App\Bootstrap\Impl\DatabaseConnectionDefinition(), // Nova definição
        ];

        foreach ($serviceDefinitions as $serviceDefinition) {
            $serviceDefinition->register($builder);
        }
    }

    public function container(): Container
    {
        return $this->container;
    }

    // Prevenir clonagem
    private function __clone() {}

    // Prevenir unserialize
    public function __wakeup() {}
}