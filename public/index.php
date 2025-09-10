<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\App;
use App\Service\IndexServiceInterface;

$app = App::getInstance();
$container = $app->container();

$indexService = $container->get(IndexServiceInterface::class);

echo $indexService->welcomeMessage() . '<br />';
echo $indexService->phpVersion() . '<br />';

$sessionInfo = $indexService->sessionInfo();
echo 'Session ID: ' . $sessionInfo['session_id'] . '<br />';

if ($sessionInfo['random_number']) {
    echo 'Valor salvo na sessão: ' . $sessionInfo['random_number'];
} else {
    echo 'Não existe valor na sessão';
}