<?php

declare(strict_types=1);

namespace App\Service\Impl;

use App\Service\IndexServiceInterface;

final class IndexService implements IndexServiceInterface
{
    public function welcomeMessage(): string
    {
        return 'Hello World!';
    }

    public function phpVersion(): string
    {
        return 'PHP Version: ' . phpversion();
    }

    public function sessionInfo(): array
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['random_number'])) {
            $_SESSION['random_number'] = rand(1, 100);
        }

        return [
            'session_id' => session_id(),
            'random_number' => $_SESSION['random_number'] ?? null
        ];
    }
}