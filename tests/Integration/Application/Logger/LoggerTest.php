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

    public function testConstructorCreatesLogDirectories(): void
    {
        $logTypes = ['import', 'transport', 'api'];
        $logLevels = ['success', 'exception', 'statistic'];

        foreach ($logTypes as $type) {
            foreach ($logLevels as $level) {
                $dirPath = "logs/$type/$level";
                $this->assertTrue(
                    $this->fileSystem->hasChild($dirPath),
                    "Directory $dirPath does not exist"
                );
            }
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

    public function testLogExceptionWritesToFile(): void
    {
        $exception = new Exception('Test exception message', 123);

        $this->logger->logException($exception, 'import');

        $filePath = 'logs/import/exception/import_exceptions_' . date('Ymd') . '.log';

        $this->assertTrue(
            $this->fileSystem->hasChild($filePath),
            "Log file $filePath does not exist"
        );

        $content = $this->fileSystem->getChild($filePath)->getContent();

        $this->assertStringContainsString('Test exception message', $content);
        $this->assertStringContainsString('Exception', $content);
    }

    public function testLogExceptionWithInvalidLogTypeThrowsException(): void
    {
        $exception = new Exception('Test exception');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid log type: invalid');

        $this->logger->logException($exception, 'invalid');
    }

    public function testLogSuccessWritesToFile(): void
    {
        $this->logger->logSuccess('Test success message', 'import');

        $filePath = 'logs/import/success/import_success_' . date('Ymd') . '.log';

        $this->assertTrue(
            $this->fileSystem->hasChild($filePath),
            "Log file $filePath does not exist"
        );

        $content = $this->fileSystem->getChild($filePath)->getContent();

        $this->assertStringContainsString('Test success message', $content);
        $this->assertStringContainsString('[SUCCESS]', $content);
    }

    public function testLogWarningWritesToFile(): void
    {
        $this->logger->logWarning('Test warning message', 'transport');

        $filePath = 'logs/transport/success/transport_warning_' . date('Ymd') . '.log';

        $this->assertTrue(
            $this->fileSystem->hasChild($filePath),
            "Log file $filePath does not exist"
        );

        $content = $this->fileSystem->getChild($filePath)->getContent();

        $this->assertStringContainsString('Test warning message', $content);
        $this->assertStringContainsString('[WARNING]', $content);
    }

    public function testLogErrorWritesToFile(): void
    {
        $this->logger->logError('Test error message', 'api');

        $filePath = 'logs/api/success/api_error_' . date('Ymd') . '.log';

        $this->assertTrue(
            $this->fileSystem->hasChild($filePath),
            "Log file $filePath does not exist"
        );

        $content = $this->fileSystem->getChild($filePath)->getContent();

        $this->assertStringContainsString('Test error message', $content);
        $this->assertStringContainsString('[ERROR]', $content);
    }

    public function testLogMethodsWithInvalidLogTypeThrowException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->logger->logSuccess('Test message', 'invalid');
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

    public function testWriteStatisticsWritesToFile(): void
    {
        $this->logger->logStatistics(['key' => 'value'], 'import');
        $this->logger->logSuccess('Test success', 'import');
        $this->logger->writeStatistics('import');

        $filePath = 'logs/import/statistic/import_statistics_' . date('Ymd') . '.log';

        $this->assertTrue(
            $this->fileSystem->hasChild($filePath),
            "Statistics file $filePath does not exist"
        );

        $content = $this->fileSystem->getChild($filePath)->getContent();

        $this->assertStringContainsString('"key": "value"', $content);
        $this->assertStringContainsString('"success": 1', $content);
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

        $filePath = 'logs/import/success/import_success_' . date('Ymd') . '.log';

        $content = $this->fileSystem->getChild($filePath)->getContent();

        $this->assertStringContainsString('First message', $content);
        $this->assertStringContainsString('Second message', $content);

        // Ensure both messages are present
        $this->assertEquals(2, substr_count($content, '[SUCCESS]'));
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

        $this->assertTrue(
            $this->fileSystem->hasChild($filePath),
            "Statistics file $filePath does not exist"
        );

        $content = $this->fileSystem->getChild($filePath)->getContent();

        $this->assertStringContainsString('"import": {', $content);
        $this->assertStringContainsString('"api": {', $content);
        $this->assertStringContainsString('"key": "value"', $content);
        $this->assertStringContainsString('"key2": "value2"', $content);
    }
    public function testCreateLogDirectoriesThrowsExceptionWhenLevelDirCannotBeCreated(): void
    {
        $this->fileSystem = vfsStream::setup('root', null, [
            'logs' => [
                'import' => [],
            ],
        ]);

        $this->logDirectory = $this->fileSystem->url() . '/logs';

        $importDir = $this->fileSystem->getChild('logs/import');
        $importDir->chmod(0500);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(sprintf('Directory "%s" was not created', $this->logDirectory . '/import/success'));

        new Logger($this->logDirectory);
    }

}