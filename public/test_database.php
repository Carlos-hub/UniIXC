<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\App;
use App\Service\DatabaseTestServiceInterface;

$app = App::getInstance();
$container = $app->container();

$databaseService = $container->get(DatabaseTestServiceInterface::class);

echo $databaseService->pageTitle();

$databases = $databaseService->databaseConfigurations();
foreach ($databases as $db) {
    echo $databaseService->testDatabase($db['name'], $db['host'], $db['user']);
}

echo "<p>PHP " . phpversion() . " | PDO PostgreSQL: " .
    (extension_loaded('pdo_pgsql') ? 'OK' : 'Não encontrado') .
    " | " . date('Y-m-d H:i:s') . "</p>";