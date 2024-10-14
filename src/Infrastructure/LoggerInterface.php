<?php
declare(strict_types=1);

namespace App\Infrastructure;

use Exception;

interface LoggerInterface
{
    public function logException(Exception $exception): void;

    public function logStatistics(array $stats): void;

    public function logSuccess(string $message): void;

}