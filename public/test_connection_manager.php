<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\App;
use App\Database\ConnectionManagerInterface;
use Doctrine\DBAL\Connection;

$app = App::getInstance();
$container = $app->container();

$connectionManager = $container->get(ConnectionManagerInterface::class);

echo "<h1>Doctrine DBAL Connection Manager Test</h1>";

// Testar conexão primária
echo "<h2>Primary Connection Test</h2>";
try {
    $primary = $connectionManager->getPrimaryConnection();
    $result = $primary->fetchAssociative("SELECT 'Primary DB' as db_type, version() as version");
    echo "? Primary: " . $result['db_type'] . " - " . $result['version'] . "<br>";
} catch (Exception $e) {
    echo "? Primary Error: " . $e->getMessage() . "<br>";
}

// Testar conexão slave (HAProxy)
echo "<h2>Slave Connection Test (HAProxy)</h2>";
try {
    $slave = $connectionManager->getSlaveConnection('haproxy');
    $result = $slave->fetchAssociative("SELECT 'Slave DB' as db_type, version() as version");
    echo "? Slave: " . $result['db_type'] . " - " . $result['version'] . "<br>";
} catch (Exception $e) {
    echo "? Slave Error: " . $e->getMessage() . "<br>";
}

// Mostrar estatísticas
echo "<h2>Connection Statistics</h2>";
$stats = $connectionManager->getConnectionStats();
echo "<pre>" . json_encode($stats, JSON_PRETTY_PRINT) . "</pre>";

// Testar reutilização de conexões
echo "<h2>Connection Reuse Test</h2>";
$primary1 = $connectionManager->getPrimaryConnection();
$primary2 = $connectionManager->getPrimaryConnection();
echo "Primary connections are the same: " . ($primary1 === $primary2 ? "? Yes" : "? No") . "<br>";

$slave1 = $connectionManager->getSlaveConnection('haproxy');
$slave2 = $connectionManager->getSlaveConnection('haproxy');
echo "Slave connections are the same: " . ($slave1 === $slave2 ? "? Yes" : "? No") . "<br>";

// Testar funcionalidades do Doctrine DBAL
echo "<h2>Doctrine DBAL Features Test</h2>";
try {
    $primary = $connectionManager->getPrimaryConnection();

    // Testar Query Builder
    $qb = $primary->createQueryBuilder();
    $qb->select('COUNT(*) as total')
        ->from('connection_test');

    $count = $qb->executeQuery()->fetchOne();
    echo "? Query Builder: {$count} registros<br>";

    // Testar Schema Manager
    $schemaManager = $primary->createSchemaManager();
    $tables = $schemaManager->listTableNames();
    echo "? Schema Manager: " . count($tables) . " tabelas encontradas<br>";

} catch (Exception $e) {
    echo "? Doctrine DBAL Features Error: " . $e->getMessage() . "<br>";
}

echo "<p>PHP " . phpversion() . " | " . date('Y-m-d H:i:s') . "</p>";