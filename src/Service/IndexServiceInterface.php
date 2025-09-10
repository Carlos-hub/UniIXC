<?php

declare(strict_types=1);

namespace App\Service;

interface IndexServiceInterface
{
    public function welcomeMessage(): string;
    public function phpVersion(): string;
    public function sessionInfo(): array;
}