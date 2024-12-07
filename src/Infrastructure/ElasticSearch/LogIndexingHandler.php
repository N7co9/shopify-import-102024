<?php
declare(strict_types=1);

namespace App\Infrastructure\ElasticSearch;

use App\Domain\Index\ElasticServiceInterface;

class LogIndexingHandler implements ElasticServiceInterface
{
    public function __construct(private readonly ElasticSearchApiConnector $connector)
    {
    }

    public function indexLogs(array $logs): void
    {
        $indexName = 'app-logs-' . date('Y.m.d');

        foreach ($logs as $log) {
            $document = [
                'timestamp' => $log['timestamp'] ?? gmdate('Y-m-d\TH:i:s\Z'),
                'log_level' => $log['log_level'] ?? 'INFO',
                'log_type' => $log['log_type'] ?? 'generic',
                'message' => $log['message'] ?? '',
                'context' => $log['context'] ?? [],
                'extra' => $log['extra'] ?? [],
            ];

            $this->connector->indexDocument($indexName, $document);
        }
    }
}