<?php
declare(strict_types=1);

namespace App\Tests\Integration\Application\Logger;

use App\Application\Logger\Logger;
use Exception;
use InvalidArgumentException;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class LoggerTest extends TestCase
{
    private vfsStreamDirectory $fileSystem;
    private string $logDirectory;
    private Logger $logger;

    protected function setUp(): void
    {
        $this->fileSystem = vfsStream::setup('root');
        $this->logDirectory = $this->fileSystem->url() . '/logs';
        $this->logger = new Logger($this->logDirectory);
    }

    public function testConstructorCreatesLogTypeDirectories(): void
    {
        $logTypes = ['import', 'transport', 'api'];

        foreach ($logTypes as $type) {
            $dirPath = "logs/$type";
            $this->assertTrue(
                $this->fileSystem->hasChild($dirPath),
                "Directory $dirPath does not exist"
            );
        }
    }

    public function testConstructorThrowsExceptionWhenLogDirectoryCannotBeCreated(): void
    {
        $this->fileSystem = vfsStream::setup('root', 0000);
        $this->logDirectory = $this->fileSystem->url() . '/logs';

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(sprintf('Directory "%s" was not created', $this->logDirectory));

        new Logger($this->logDirectory);
    }

    public function testLogExceptionWritesToSymfonyFormat(): void
    {
        $exception = new Exception('Test exception message', 123);
        $this->logger->logException($exception, 'import');

        $filePath = 'logs/import/import_' . date('Ymd') . '.log';
        $this->assertTrue($this->fileSystem->hasChild($filePath), "Log file $filePath does not exist");

        $lastLine = $this->getLastLogLine($filePath);
        $matches = $this->parseSymfonyLogLine($lastLine);

        $this->assertEquals('import', $matches['channel']);
        $this->assertEquals('ERROR', $matches['level_name']);
        $this->assertStringContainsString('Test exception message', $matches['message']);

        $context = json_decode($matches['context'], true);
        $this->assertStringContainsString('Exception', $context['exception_trace'] ?? '');
    }

    public function testLogExceptionWithInvalidLogTypeThrowsException(): void
    {
        $exception = new Exception('Test exception');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid log type: invalid');

        $this->logger->logException($exception, 'invalid');
    }

    public function testLogSuccessWritesToSymfonyFormat(): void
    {
        $this->logger->logSuccess('Test success message', 'import');

        $filePath = 'logs/import/import_' . date('Ymd') . '.log';
        $this->assertTrue($this->fileSystem->hasChild($filePath), "Log file $filePath does not exist");

        $lastLine = $this->getLastLogLine($filePath);
        $matches = $this->parseSymfonyLogLine($lastLine);

        $this->assertEquals('import', $matches['channel']);
        $this->assertEquals('INFO', $matches['level_name']);
        $this->assertEquals('Test success message', trim($matches['message']));
    }

    public function testLogWarningWritesToSymfonyFormat(): void
    {
        $this->logger->logWarning('Test warning message', 'transport');

        $filePath = 'logs/transport/transport_' . date('Ymd') . '.log';
        $this->assertTrue($this->fileSystem->hasChild($filePath), "Log file $filePath does not exist");

        $lastLine = $this->getLastLogLine($filePath);
        $matches = $this->parseSymfonyLogLine($lastLine);

        $this->assertEquals('transport', $matches['channel']);
        $this->assertEquals('WARNING', $matches['level_name']);
        $this->assertEquals('Test warning message', trim($matches['message']));
    }

    public function testLogErrorWritesToSymfonyFormat(): void
    {
        $this->logger->logError('Test error message', 'api');

        $filePath = 'logs/api/api_' . date('Ymd') . '.log';
        $this->assertTrue($this->fileSystem->hasChild($filePath), "Log file $filePath does not exist");

        $lastLine = $this->getLastLogLine($filePath);
        $matches = $this->parseSymfonyLogLine($lastLine);

        $this->assertEquals('api', $matches['channel']);
        $this->assertEquals('ERROR', $matches['level_name']);
        $this->assertEquals('Test error message', trim($matches['message']));
    }

    public function testLogStatisticsRecordsStatistics(): void
    {
        $stats = ['itemsProcessed' => 10, 'itemsFailed' => 2];
        $this->logger->logStatistics($stats, 'import');

        $retrievedStats = $this->logger->getStatistics('import');
        $this->assertEquals(10, $retrievedStats['itemsProcessed']);
        $this->assertEquals(2, $retrievedStats['itemsFailed']);
    }

    public function testGetStatisticsReturnsAllWhenNoLogTypeProvided(): void
    {
        $this->logger->logStatistics(['key' => 'value'], 'import');
        $this->logger->logStatistics(['key2' => 'value2'], 'api');

        $allStats = $this->logger->getStatistics();

        $this->assertArrayHasKey('import', $allStats);
        $this->assertArrayHasKey('api', $allStats);
        $this->assertEquals('value', $allStats['import']['key']);
        $this->assertEquals('value2', $allStats['api']['key2']);
    }

    public function testWriteStatisticsWritesToSymfonyFormat(): void
    {
        $this->logger->logStatistics(['key' => 'value'], 'import');
        $this->logger->logSuccess('Test success', 'import');
        $this->logger->writeStatistics('import');

        $filePath = 'logs/import/import_statistics_' . date('Ymd') . '.log';
        $this->assertTrue($this->fileSystem->hasChild($filePath), "Statistics file $filePath does not exist");

        $content = $this->fileSystem->getChild($filePath)->getContent();
        // Nur eine Zeile erwartet
        $lines = array_filter(explode("\n", $content));
        $line = end($lines);
        $matches = $this->parseSymfonyLogLine($line);

        $this->assertEquals('import', $matches['channel']);
        $this->assertEquals('INFO', $matches['level_name']);
        $this->assertStringContainsString('Statistics data', $matches['message']);

        $context = json_decode($matches['context'], true);
        $this->assertArrayHasKey('key', $context['statistics']);
        $this->assertArrayHasKey('counts', $context['statistics']);
        // success wurde durch logSuccess() hochgezählt
        $this->assertEquals(1, $context['statistics']['counts']['success']);
    }

    public function testCountsAreIncrementedCorrectly(): void
    {
        $this->logger->logSuccess('Success message', 'import');
        $this->logger->logWarning('Warning message', 'import');
        $this->logger->logError('Error message', 'import');
        $this->logger->logError('Another error message', 'import');

        $stats = $this->logger->getStatistics('import');
        $this->assertEquals(1, $stats['counts']['success']);
        $this->assertEquals(1, $stats['counts']['warning']);
        $this->assertEquals(2, $stats['counts']['error']);
    }

    public function testLogMethodsAppendToFile(): void
    {
        $this->logger->logSuccess('First message', 'import');
        $this->logger->logSuccess('Second message', 'import');

        $filePath = 'logs/import/import_' . date('Ymd') . '.log';

        $this->assertTrue($this->fileSystem->hasChild($filePath), "Log file $filePath does not exist");
        $content = $this->fileSystem->getChild($filePath)->getContent();
        $lines = array_filter(explode("\n", trim($content)));

        $this->assertCount(2, $lines);

        // Prüfe erste Zeile
        $matchesFirst = $this->parseSymfonyLogLine($lines[0]);
        $this->assertEquals('First message', trim($matchesFirst['message'] ?? ''));

        // Prüfe zweite Zeile
        $matchesSecond = $this->parseSymfonyLogLine($lines[1]);
        $this->assertEquals('Second message', trim($matchesSecond['message'] ?? ''));
    }

    public function testValidateLogTypeWithInvalidTypeThrowsException(): void
    {
        $reflection = new \ReflectionClass(Logger::class);
        $method = $reflection->getMethod('validateLogType');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid log type: invalid');

        $method->invoke($this->logger, 'invalid');
    }

    public function testLogExceptionIncreasesErrorCount(): void
    {
        $exception = new Exception('Test exception');
        $this->logger->logException($exception, 'import');

        $stats = $this->logger->getStatistics('import');
        $this->assertEquals(1, $stats['counts']['error']);
    }

    public function testWriteStatisticsWritesAllWhenNoLogTypeProvided(): void
    {
        $this->logger->logStatistics(['key' => 'value'], 'import');
        $this->logger->logStatistics(['key2' => 'value2'], 'api');
        $this->logger->writeStatistics();

        $filePath = 'logs/statistics_' . date('Ymd') . '.log';

        $this->assertTrue($this->fileSystem->hasChild($filePath), "Statistics file $filePath does not exist");
        $content = $this->fileSystem->getChild($filePath)->getContent();
        $lines = array_filter(explode("\n", $content));
        $line = end($lines);
        $matches = $this->parseSymfonyLogLine($line);

        $this->assertEquals('all', $matches['channel']);
        $context = json_decode($matches['context'], true);
        $this->assertArrayHasKey('import', $context['statistics']);
        $this->assertArrayHasKey('api', $context['statistics']);
        $this->assertEquals('value', $context['statistics']['import']['key']);
        $this->assertEquals('value2', $context['statistics']['api']['key2']);
    }

    public function testCreateLogDirectoriesThrowsException(): void
    {
        $this->fileSystem = vfsStream::setup('root', null, [
            'logs' => [],
        ]);

        $root = $this->fileSystem->getChild('logs');
        $root->chmod(0500);

        $this->logDirectory = $this->fileSystem->url() . '/logs';

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(sprintf('Directory "%s" was not created', $this->logDirectory . '/import'));

        new Logger($this->logDirectory);
    }

    public function testJsonEncodeErrorFallback(): void
    {
        $reflection = new \ReflectionClass(Logger::class);
        $property = $reflection->getProperty('hostname');
        $property->setValue($this->logger, "\xB1\x31"); // Ungültige UTF-8-Sequenz

        $this->logger->logError('Fehlerhafte Nachricht wird egal sein', 'import');

        $filePath = 'logs/import/import_' . date('Ymd') . '.log';
        $this->assertTrue(
            $this->fileSystem->hasChild($filePath),
            "Log file $filePath does not exist"
        );

        $content = $this->fileSystem->getChild($filePath)->getContent();
        $this->assertStringContainsString('JSON encode error', $content);
    }

    public function testGetCollectedLogsReturnsAllLogsIfNoLogTypeProvided(): void
    {
        $this->logger->logSuccess('Success import', 'import');
        $this->logger->logError('Error api', 'api');
        $this->logger->logWarning('Warning transport', 'transport');

        $allLogs = $this->logger->getCollectedLogs();
        $this->assertCount(3, $allLogs, 'Expected all three logged records to be returned');

        $importLogs = array_filter($allLogs, fn($log) => $log['log_type'] === 'import');
        $apiLogs = array_filter($allLogs, fn($log) => $log['log_type'] === 'api');
        $transportLogs = array_filter($allLogs, fn($log) => $log['log_type'] === 'transport');

        $this->assertCount(1, $importLogs, 'Expected one import log');
        $this->assertCount(1, $apiLogs, 'Expected one api log');
        $this->assertCount(1, $transportLogs, 'Expected one transport log');
    }

    public function testGetCollectedLogsFiltersByLogType(): void
    {
        $this->logger->logSuccess('First import message', 'import');
        $this->logger->logSuccess('Second import message', 'import');
        $this->logger->logError('API error message', 'api');

        $importLogs = $this->logger->getCollectedLogs('import');
        $apiLogs = $this->logger->getCollectedLogs('api');
        $transportLogs = $this->logger->getCollectedLogs('transport'); // Hier haben wir keine Logs

        $this->assertCount(2, $importLogs, 'Expected two import logs');
        foreach ($importLogs as $log) {
            $this->assertEquals('import', $log['log_type'], 'Filtered logs should be of type import');
        }

        $this->assertCount(1, $apiLogs, 'Expected one api log');
        $this->assertEquals('api', current($apiLogs)['log_type'], 'Filtered logs should be of type api');

        // Keine transport-Logs geloggt, daher leer
        $this->assertCount(0, $transportLogs, 'Expected no logs for transport since none were logged');
    }

    public function testGetCollectedLogsReturnsEmptyArrayWhenNoLogsExist(): void
    {
        $noLogs = $this->logger->getCollectedLogs();
        $this->assertEmpty($noLogs, 'Expected empty array when no logs have been recorded');

        $noLogsForImport = $this->logger->getCollectedLogs('import');
        $this->assertEmpty($noLogsForImport, 'Expected empty array when requesting logs of a type that has no entries');
    }


    private function parseSymfonyLogLine(string $line): array
    {
        $pattern = '/^\[(?<datetime>[^\]]+)\]\s+(?<channel>[^\.\s]+)\.(?<level_name>[A-Z]+):\s+(?<message>[^\{]+)\s+(?<context>\{.*?\})\s+(?<extra>\{.*?\})$/';
        $this->assertMatchesRegularExpression($pattern, $line, "Log line does not match the expected Symfony format.");

        preg_match($pattern, $line, $matches);

        $matches['message'] = rtrim($matches['message']);

        return $matches;
    }

    private function getLastLogLine(string $filePath): string
    {
        $content = $this->fileSystem->getChild($filePath)->getContent();
        $lines = array_filter(explode("\n", $content));
        $this->assertNotEmpty($lines, "Expected at least one log line in $filePath");
        return end($lines);
    }
}
