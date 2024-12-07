<?php
declare(strict_types=1);

namespace App\Application\Index;

use App\Application\Logger\LoggerInterface;
use App\Domain\Index\ElasticServiceInterface;

class IndexProcessor implements IndexProcessorInterface
{
    public function __construct(private LoggerInterface $logger, private ElasticServiceInterface $elasticService)
    {
    }

    public function indexLogs(?string $logType = null): void
    {
        $logs = $this->logger->getCollectedLogs($logType);
        if (!empty($logs)) {
            $this->elasticService->indexLogs($logs);
        }
    }
}