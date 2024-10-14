<?php
declare(strict_types=1);

namespace App\Infrastructure\Logger;

use App\Infrastructure\LoggerInterface;
use Exception;

class Logger implements LoggerInterface
{
    private string $logDirectory;
    private array $statistics = [];
    private int $warningCount = 0;
    private int $errorCount = 0;

    public function __construct(string $logDirectory = __DIR__ . '/../../../logs')
    {
        $this->logDirectory = $logDirectory;
        $this->createLogDirectory();
    }

    private function createLogDirectory(): void
    {
        if (!is_dir($this->logDirectory) && !mkdir($concurrentDirectory = $this->logDirectory, 0777, true) && !is_dir($concurrentDirectory)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $concurrentDirectory));
        }
    }

    public function logException(Exception $exception): void
    {
        $filename = sprintf('%s/exceptions_%s.log', $this->logDirectory, date('Ymd'));
        $message = sprintf(
            "[%s] %s: %s in %s on line %d\nStack trace:\n%s\n\n",
            date('Y-m-d H:i:s'),
            get_class($exception),
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine(),
            $exception->getTraceAsString()
        );
        file_put_contents($filename, $message, FILE_APPEND);
        $this->errorCount++;
    }

    public function logSuccess(string $message): void
    {
        $this->log('SUCCESS', $message);
    }

    public function logWarning(string $message): void
    {
        $this->log('WARNING', $message);
        $this->warningCount++;
    }

    public function logError(string $message): void
    {
        $this->log('ERROR', $message);
        $this->errorCount++;
    }

    private function log(string $level, string $message): void
    {
        $filename = sprintf('%s/app_%s.log', $this->logDirectory, date('Ymd'));
        $logMessage = sprintf("[%s] [%s] %s\n", date('Y-m-d H:i:s'), $level, $message);
        file_put_contents($filename, $logMessage, FILE_APPEND);
    }

    public function logStatistics(array $stats): void
    {
        $this->statistics = array_merge($this->statistics, $stats);
    }

    public function getStatistics(): array
    {
        return array_merge($this->statistics, [
            'warnings' => $this->warningCount,
            'errors' => $this->errorCount,
        ]);
    }

    public function writeStatistics(): void
    {
        $filename = sprintf('%s/statistics_%s.log', $this->logDirectory, date('Ymd'));
        $formattedStats = sprintf("[%s] Stats:\n%s\n\n", date('Y-m-d H:i:s'), json_encode($this->getStatistics(), JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));
        file_put_contents($filename, $formattedStats, FILE_APPEND);
    }
}