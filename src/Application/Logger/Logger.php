<?php
declare(strict_types=1);

namespace App\Application\Logger;

use Exception;
use JsonException;

class Logger implements LoggerInterface
{
    private string $logDirectory;

    private array $collectedLogs = [];
    private array $statistics = [];
    private array $logTypeCounts = [
        'import' => ['success' => 0, 'warning' => 0, 'error' => 0],
        'transport' => ['success' => 0, 'warning' => 0, 'error' => 0],
        'api' => ['success' => 0, 'warning' => 0, 'error' => 0]
    ];

    private string $applicationName = 'MyApp';
    private string $environment = 'prod';
    private string $hostname;

    public function __construct(string $logDirectory = __DIR__ . '/../../../logs')
    {
        $this->logDirectory = $logDirectory;
        $this->hostname = gethostname() ?: 'unknown_host';
        $this->createLogTypeDirectories();
    }

    private function createLogTypeDirectories(): void
    {
        $logTypes = ['import', 'transport', 'api'];

        if (!is_dir($this->logDirectory) && !mkdir($this->logDirectory, 0777, true) && !is_dir($this->logDirectory)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $this->logDirectory));
        }

        foreach ($logTypes as $type) {
            $typeDir = $this->logDirectory . '/' . $type;
            if (!is_dir($typeDir) && !mkdir($typeDir, 0777, true) && !is_dir($typeDir)) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', $typeDir));
            }
        }
    }

    public function logException(Exception $exception, string $logType): void
    {
        $this->validateLogType($logType);
        $this->logTypeCounts[$logType]['error']++;

        $record = [
            'log_level' => 'ERROR',
            'log_type' => $logType,
            'message' => $exception->getMessage(),
            'context' => [
                'exception_trace' => sprintf(
                    '%s: %s in %s:%d',
                    get_class($exception),
                    $exception->getMessage(),
                    $exception->getFile(),
                    $exception->getLine()
                )
            ],
        ];

        $this->writeLogRecord($record, $logType);
    }

    public function logSuccess(string $message, string $logType): void
    {
        $this->validateLogType($logType);
        $this->logTypeCounts[$logType]['success']++;

        $record = [
            'log_level' => 'INFO',
            'log_type' => $logType,
            'message' => $message,
            'context' => [],
        ];

        $this->writeLogRecord($record, $logType);
    }

    public function logWarning(string $message, string $logType): void
    {
        $this->validateLogType($logType);
        $this->logTypeCounts[$logType]['warning']++;

        $record = [
            'log_level' => 'WARNING',
            'log_type' => $logType,
            'message' => $message,
            'context' => [],
        ];

        $this->writeLogRecord($record, $logType);
    }

    public function logError(string $message, string $logType): void
    {
        $this->validateLogType($logType);
        $this->logTypeCounts[$logType]['error']++;

        $record = [
            'log_level' => 'ERROR',
            'log_type' => $logType,
            'message' => $message,
            'context' => [],
        ];

        $this->writeLogRecord($record, $logType);
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

        $record = [
            'log_level' => 'INFO',
            'log_type' => $logType ?: 'all',
            'message' => 'Statistics data',
            'context' => [
                'statistics' => $statsToWrite
            ],
        ];

        if ($logType === null) {
            $filename = sprintf('%s/statistics_%s.log', $this->logDirectory, date('Ymd'));
        } else {
            $filename = sprintf('%s/%s/%s_statistics_%s.log', $this->logDirectory, $logType, $logType, date('Ymd'));
        }

        $this->writeLine($filename, $record);
    }

    private function writeLogRecord(array $record, string $logType): void
    {
        $filename = sprintf('%s/%s/%s_%s.log', $this->logDirectory, $logType, $logType, date('Ymd'));
        $this->writeLine($filename, $record);
    }

    private function writeLine(string $filename, array $record): void
    {
        $datetime = gmdate('Y-m-d\TH:i:s\Z');
        $channel = $record['log_type'];
        $levelName = $record['log_level'];
        $message = $record['message'];
        $context = $record['context'] ?? [];

        $extra = [
            'application_name' => $this->applicationName,
            'environment' => $this->environment,
            'hostname' => $this->hostname,
        ];

        try {
            $contextJson = empty($context) ? '{}' : json_encode($context, JSON_THROW_ON_ERROR);
            $extraJson = json_encode($extra, JSON_THROW_ON_ERROR);

            $line = sprintf(
                "[%s] %s.%s: %s %s %s\n",
                $datetime,
                $channel,
                $levelName,
                $message,
                $contextJson,
                $extraJson
            );

            file_put_contents($filename, $line, FILE_APPEND);
        } catch (JsonException $e) {
            $fallbackLine = sprintf(
                '[%s] %s.ERROR: JSON encode error: %s {} {}' . "\n",
                $datetime,
                $channel,
                $e->getMessage()
            );
            file_put_contents($filename, $fallbackLine, FILE_APPEND);
        }

        $datetime = gmdate('Y-m-d\TH:i:s\Z');
        $this->collectedLogs[] = [
            'timestamp' => $datetime,
            'log_level' => $record['log_level'] ?? 'INFO',
            'log_type' => $record['log_type'] ?? 'generic',
            'message' => $record['message'] ?? '',
            'context' => $record['context'] ?? [],
            'extra' => [
                'application_name' => $this->applicationName,
                'environment' => $this->environment,
                'hostname' => $this->hostname,
            ],
        ];
    }

    public function getCollectedLogs(?string $logType = null): array
    {
        if ($logType === null) {
            return $this->collectedLogs;
        }

        return array_filter($this->collectedLogs, fn($log) => $log['log_type'] === $logType);
    }


    private function validateLogType(string $logType): void
    {
        $validTypes = ['import', 'transport', 'api'];
        if (!in_array($logType, $validTypes, true)) {
            throw new \InvalidArgumentException("Invalid log type: $logType");
        }
    }
}
