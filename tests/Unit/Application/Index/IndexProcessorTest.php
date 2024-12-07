<?php
declare(strict_types=1);

namespace App\Tests\Unit\Application\Index;

use App\Application\Index\IndexProcessor;
use App\Application\Index\IndexProcessorInterface;
use App\Application\Logger\LoggerInterface;
use App\Domain\Index\ElasticServiceInterface;
use PHPUnit\Framework\TestCase;

class IndexProcessorTest extends TestCase
{
    public function testIndexLogsWithEmptyLogsDoesNotCallElastic(): void
    {
        // Logger Mock, liefert leere Logs
        $loggerMock = $this->createMock(LoggerInterface::class);
        $loggerMock->expects($this->once())
            ->method('getCollectedLogs')
            ->with(null)
            ->willReturn([]);

        // ElasticService Mock, soll nicht aufgerufen werden
        $elasticServiceMock = $this->createMock(ElasticServiceInterface::class);
        $elasticServiceMock->expects($this->never())
            ->method('indexLogs');

        $processor = new IndexProcessor($loggerMock, $elasticServiceMock);
        $processor->indexLogs(); // Ruft indexLogs ohne Log-Typ auf
    }

    public function testIndexLogsWithNonEmptyLogsCallsElastic(): void
    {
        $logs = [
            ['message' => 'Test Log 1', 'level' => 'info'],
            ['message' => 'Test Log 2', 'level' => 'error']
        ];

        $loggerMock = $this->createMock(LoggerInterface::class);
        $loggerMock->expects($this->once())
            ->method('getCollectedLogs')
            ->with('custom_type')
            ->willReturn($logs);

        $elasticServiceMock = $this->createMock(ElasticServiceInterface::class);
        $elasticServiceMock->expects($this->once())
            ->method('indexLogs')
            ->with($logs);

        $processor = new IndexProcessor($loggerMock, $elasticServiceMock);
        $processor->indexLogs('custom_type'); // Ruft indexLogs mit speziellem Log-Typ auf
    }

    public function testImplementsInterface(): void
    {
        $processor = new IndexProcessor(
            $this->createMock(LoggerInterface::class),
            $this->createMock(ElasticServiceInterface::class)
        );

        $this->assertInstanceOf(IndexProcessorInterface::class, $processor);
    }
}