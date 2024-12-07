<?php

declare(strict_types=1);

namespace App\Tests\Integration\Application\Product\Import;

use App\Application\Product\Import\ImportProcessor;
use App\Application\Product\Import\ShopifyProductImporter;
use App\Application\Product\Import\ShopifyVariantImporter;
use App\Application\Product\Import\Tools\CsvParser;
use App\Application\Product\Import\Tools\ProductRecordProcessor;
use App\Application\Product\Import\Tools\VariantRecordProcessor;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class ImportProcessorTest extends TestCase
{
    private ImportProcessor $importProcessor;
    private string $testDirectory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->testDirectory = sys_get_temp_dir() . '/import_processor_test_' . uniqid('', true);
        mkdir($this->testDirectory);

        $requiredFiles = [
            'product_abstract.csv',
            'product_concrete.csv',
            'product_price.csv',
            'product_stock.csv',
            'product_image.csv',
        ];

        foreach ($requiredFiles as $fileName) {
            file_put_contents($this->testDirectory . '/' . $fileName, 'dummy data');
        }

        $csvParser = new CsvParser();
        $variantRecordProcessor = new VariantRecordProcessor();
        $productRecordProcessor = new ProductRecordProcessor();

        $variantImporter = new ShopifyVariantImporter($csvParser, $variantRecordProcessor);
        $productImporter = new ShopifyProductImporter($csvParser, $productRecordProcessor);

        $logger = $this->createMock(LoggerInterface::class);

        $this->importProcessor = new ImportProcessor(
            $variantImporter,
            $productImporter,
            $logger
        );
    }

    protected function tearDown(): void
    {
        array_map('unlink', glob($this->testDirectory . '/*.csv'));
        rmdir($this->testDirectory);

        unset($this->importProcessor);

        parent::tearDown();
    }

    public function testProcessImportWithValidData(): void
    {
        $result = $this->importProcessor->processImport($this->testDirectory);

        $this->assertIsArray($result);
    }

    public function testProcessImportWithMissingFile(): void
    {
        unlink($this->testDirectory . '/product_price.csv');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Required CSV file "product_price.csv" is missing.');

        $this->importProcessor->processImport($this->testDirectory);
    }
}
