<?php
declare(strict_types=1);

namespace App\Application\Logger;

use Exception;
use JsonException;

class Logger implements LoggerInterface
{
    private string $logDirectory;
    private array $statistics = [];
    private array $logTypeCounts = [
        'import' => ['success' => 0, 'warning' => 0, 'error' => 0],
        'transport' => ['success' => 0, 'warning' => 0, 'error' => 0],
        'api' => ['success' => 0, 'warning' => 0, 'error' => 0]
    ];

    public function __construct(string $logDirectory = __DIR__ . '/../../../logs')
    {
        $this->logDirectory = $logDirectory;
        $this->createLogDirectories();
    }

    private function createLogDirectories(): void
    {
        $logTypes = ['import', 'transport', 'api'];
        $logLevels = ['success', 'exception', 'statistic'];

        if (!is_dir($this->logDirectory) && !mkdir($this->logDirectory, 0777, true) && !is_dir($this->logDirectory)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $this->logDirectory));
        }

        foreach ($logTypes as $type) {
            $typeDir = $this->logDirectory . '/' . $type;

            foreach ($logLevels as $level) {
                $levelDir = $typeDir . '/' . $level;

                if (!is_dir($levelDir) && !mkdir($levelDir, 0777, true) && !is_dir($levelDir)) {
                    throw new \RuntimeException(sprintf('Directory "%s" was not created', $levelDir));
                }
            }
        }
    }

    public function logException(Exception $exception, string $logType = 'default'): void
    {
        $this->validateLogType($logType);

        $filename = sprintf(
            '%s/%s/exception/%s_exceptions_%s.log',
            $this->logDirectory,
            $logType,
            $logType,
            date('Ymd')
        );

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
        $this->logTypeCounts[$logType]['error']++;
    }

    public function logSuccess(string $message, string $logType): void
    {
        $this->log('SUCCESS', $message, $logType);
        $this->logTypeCounts[$logType]['success']++;
    }

    public function logWarning(string $message, string $logType): void
    {
        $this->log('WARNING', $message, $logType);
        $this->logTypeCounts[$logType]['warning']++;
    }

    public function logError(string $message, string $logType): void
    {
        $this->log('ERROR', $message, $logType);
        $this->logTypeCounts[$logType]['error']++;
    }

    private function log(string $level, string $message, string $logType): void
    {
        $this->validateLogType($logType);

        $filename = sprintf(
            '%s/%s/success/%s_%s_%s.log',
            $this->logDirectory,
            $logType,
            $logType,
            strtolower($level),
            date('Ymd')
        );

        $logMessage = sprintf("[%s] [%s] %s\n", date('Y-m-d H:i:s'), $level, $message);
        file_put_contents($filename, $logMessage, FILE_APPEND);
    }

    public function logStatistics(array $stats, string $logType): void
    {
        $this->validateLogType($logType);
        $this->statistics[$logType] = array_merge(
            $this->statistics[$logType] ?? [],
            $stats
        );
    }

    public function getStatistics(string $logType = null): array
    {
        if ($logType) {
            $this->validateLogType($logType);
            return array_merge(
                $this->statistics[$logType] ?? [],
                ['counts' => $this->logTypeCounts[$logType]]
            );
        }

        $allStats = [];
        foreach (array_keys($this->logTypeCounts) as $type) {
            $allStats[$type] = $this->getStatistics($type);
        }

        return $allStats;
    }

    public function writeStatistics(string $logType = null): void
    {
        $statsToWrite = $this->getStatistics($logType);

        if ($logType === null) {
            $filename = sprintf(
                '%s/statistics_%s.log',
                $this->logDirectory,
                date('Ymd')
            );
        } else {
            $filename = sprintf(
                '%s/%s/statistic/%s_statistics_%s.log',
                $this->logDirectory,
                $logType,
                $logType,
                date('Ymd')
            );
        }

        try {
            $formattedStats = sprintf(
                "[%s] Stats:\n%s\n\n",
                date('Y-m-d H:i:s'),
                json_encode($statsToWrite, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT)
            );
            file_put_contents($filename, $formattedStats, FILE_APPEND);
        } catch (JsonException $e) {
            $this->logException($e);
        }
    }

    private function validateLogType(string $logType): void
    {
        $validTypes = ['import', 'transport', 'api'];
        if (!in_array($logType, $validTypes)) {
            throw new \InvalidArgumentException("Invalid log type: $logType");
        }
    }
}