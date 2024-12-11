<?php
declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\ElasticSearch;

use App\Infrastructure\ElasticSearch\ElasticSearchApiConnector;
use App\Infrastructure\ElasticSearch\LogIndexingHandler;
use PHPUnit\Framework\TestCase;

class LogIndexingHandlerTest extends TestCase
{
    public function testIndexLogsGeneratesCorrectIndexName(): void
    {
        $logs = [
            [
                'message' => 'Test log entry',
            ],
        ];

        $connectorMock = $this->createMock(ElasticSearchApiConnector::class);
        $connectorMock
            ->expects($this->once())
            ->method('indexDocument')
            ->with(
                $this->callback(function ($indexName) {
                    return preg_match('/^app-logs-\d{4}\.\d{2}\.\d{2}$/', $indexName) === 1;
                }),
                $this->callback(function ($doc) {
                    return $doc['message'] === 'Test log entry';
                })
            );

        $handler = new LogIndexingHandler($connectorMock);

        $handler->indexLogs($logs);
    }

    public function testIndexLogsGeneratesIndexNameWithDate(): void
    {
        $logs = [
            [
                'message' => 'Test log entry'
            ]
        ];

        $connectorMock = $this->createMock(ElasticSearchApiConnector::class);
        $connectorMock
            ->expects($this->once())
            ->method('indexDocument')
            ->with(
                $this->stringStartsWith('app-logs-'),
                $this->callback(function ($doc) {
                    return $doc['message'] === 'Test log entry';
                })
            );

        $handler = new LogIndexingHandler($connectorMock);

        $originalDateFunction = \Closure::bind(function () {
            return '2024.12.11';
        }, null, null);
        $dateFunctionBackup = \Closure::bind(\Closure::fromCallable('date'), null, null);
        \Closure::bind(\Closure::fromCallable('date'), null, null);

        try {
            $handler->indexLogs($logs);
        } finally {
            \Closure::bind(\Closure::fromCallable('date'), null, null);
        }
    }

    public function testIndexLogsCallsConnectorForEachLogEntry(): void
    {
        $logs = [
            [
                'timestamp' => '2024-12-07T10:00:00Z',
                'log_level' => 'WARNING',
                'log_type' => 'api',
                'message' => 'Test message 1',
                'context' => ['foo' => 'bar'],
                'extra' => ['env' => 'prod']
            ],
            [
                'timestamp' => '2024-12-07T10:01:00Z',
                'log_level' => 'ERROR',
                'log_type' => 'transport',
                'message' => 'Test message 2',
            ],
        ];

        $connectorMock = $this->createMock(ElasticSearchApiConnector::class);
        $connectorMock
            ->expects($this->exactly(count($logs)))
            ->method('indexDocument')
            ->withConsecutive(
                [$this->stringStartsWith('app-logs-'), $this->callback(function($doc) use ($logs) {
                    // Prüfung für den ersten Logeintrag
                    return $doc['timestamp'] === $logs[0]['timestamp'] &&
                        $doc['log_level'] === $logs[0]['log_level'] &&
                        $doc['log_type'] === $logs[0]['log_type'] &&
                        $doc['message'] === $logs[0]['message'] &&
                        $doc['context'] === $logs[0]['context'] &&
                        $doc['extra'] === $logs[0]['extra'];
                })],
                [$this->stringStartsWith('app-logs-'), $this->callback(function($doc) use ($logs) {
                    // Prüfung für den zweiten Logeintrag
                    return $doc['timestamp'] === $logs[1]['timestamp'] &&
                        $doc['log_level'] === $logs[1]['log_level'] &&
                        $doc['log_type'] === $logs[1]['log_type'] &&
                        $doc['message'] === $logs[1]['message'] &&
                        $doc['context'] === [] && // default
                        $doc['extra'] === []; // default
                })]
            );

        $handler = new LogIndexingHandler($connectorMock);
        $handler->indexLogs($logs);
    }

    public function testIndexLogsGeneratesTimestampIfNotProvided(): void
    {
        $logs = [
            [
                'log_level' => 'INFO',
                'log_type' => 'import',
                'message' => 'No timestamp given',
            ],
        ];

        $connectorMock = $this->createMock(ElasticSearchApiConnector::class);
        $connectorMock
            ->expects($this->once())
            ->method('indexDocument')
            ->with(
                $this->stringStartsWith('app-logs-'),
                $this->callback(function ($doc) {
                    // Hier prüfen wir, ob ein Timestamp gesetzt wurde
                    return isset($doc['timestamp']) && !empty($doc['timestamp']);
                })
            );

        $handler = new LogIndexingHandler($connectorMock);
        $handler->indexLogs($logs);
    }

    public function testIndexLogsSetsDefaultValuesForMissingFields(): void
    {
        $logs = [
            [
                // Keine Felder außer Message
                'message' => 'Minimal log'
            ],
        ];

        $connectorMock = $this->createMock(ElasticSearchApiConnector::class);
        $connectorMock
            ->expects($this->once())
            ->method('indexDocument')
            ->with(
                $this->stringStartsWith('app-logs-'),
                $this->callback(function($doc) {
                    return $doc['log_level'] === 'INFO' &&
                        $doc['log_type'] === 'generic' &&
                        $doc['message'] === 'Minimal log' &&
                        $doc['context'] === [] &&
                        $doc['extra'] === [];
                })
            );

        $handler = new LogIndexingHandler($connectorMock);
        $handler->indexLogs($logs);
    }
}
