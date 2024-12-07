<?php
declare(strict_types=1);

namespace App\Application\Logger;

use Exception;

interface LoggerInterface
{
    public function logException(Exception $exception, string $logType): void;

    public function logStatistics(array $stats, string $logType): void;

    public function logSuccess(string $message, string $logType): void;

    public function logWarning(string $message, string $logType): void;

    public function logError(string $message, string $logType): void;

    public function getStatistics(string $logType = null): array;

    public function writeStatistics(string $logType = null): void;
    public function getCollectedLogs(?string $logType = null): array;
}