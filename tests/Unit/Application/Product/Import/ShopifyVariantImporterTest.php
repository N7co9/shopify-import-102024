<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Product\Import;

use App\Application\Product\Import\ShopifyVariantImporter;
use App\Application\Product\Import\Tools\CsvParser;
use App\Application\Product\Import\Tools\VariantRecordProcessor;
use App\Domain\DTO\ShopifyVariant;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ShopifyVariantImporterTest extends TestCase
{
    /** @var CsvParser&MockObject */
    private CsvParser $csvParserMock;

    /** @var VariantRecordProcessor&MockObject */
    private VariantRecordProcessor $variantRecordProcessorMock;

    private ShopifyVariantImporter $importer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->csvParserMock = $this->createMock(CsvParser::class);
        $this->variantRecordProcessorMock = $this->createMock(VariantRecordProcessor::class);

        $this->importer = new ShopifyVariantImporter(
            $this->csvParserMock,
            $this->variantRecordProcessorMock
        );
    }

    protected function tearDown(): void
    {
        unset($this->csvParserMock);
        unset($this->variantRecordProcessorMock);
        unset($this->importer);

        parent::tearDown();
    }

    public function testImportWithValidData(): void
    {
        // Arrange
        $stockFilePath = '/path/to/stock.csv';
        $imageFilePath = '/path/to/images.csv';
        $priceFilePath = '/path/to/prices.csv';
        $concreteFilePath = '/path/to/concrete.csv';

        $stockRecords = [
            ['concrete_sku' => 'SKU123', 'quantity' => '10'],
            ['concrete_sku' => 'SKU124', 'quantity' => '5'],
        ];
        $imageRecords = [
            ['concrete_sku' => 'SKU123', 'image_url' => 'http://example.com/variant1.jpg'],
            ['concrete_sku' => 'SKU124', 'image_url' => 'http://example.com/variant2.jpg'],
        ];
        $priceRecords = [
            ['concrete_sku' => 'SKU123', 'price' => '99.99'],
            ['concrete_sku' => 'SKU124', 'price' => '149.99'],
        ];
        $concreteRecords = [
            ['abstract_sku' => 'AB123', 'concrete_sku' => 'SKU123', 'name.en_US' => 'Variant 1'],
            ['abstract_sku' => 'AB123', 'concrete_sku' => 'SKU124', 'name.en_US' => 'Variant 2'],
        ];

        $shopifyVariant1 = new ShopifyVariant(
            'AB123',
            'SKU123',
            'Variant 1',
            0,
            '10',
            ['name' => 'Main Warehouse'],
            '0',
            '99.99',
            'Shopify',
            'DENY',
            true,
            true,
            true,
            null,
            null,
            'Not Available',
            '0.00',
            ['Size' => 'M'],
            date('Y-m-d H:i:s'),
            null,
            'http://example.com/variant1.jpg',
            null
        );

        $shopifyVariant2 = new ShopifyVariant(
            'AB123',
            'SKU124',
            'Variant 2',
            0,
            '5',
            ['name' => 'Main Warehouse'],
            '0',
            '149.99',
            'Shopify',
            'DENY',
            true,
            true,
            true,
            null,
            null,
            'Not Available',
            '0.00',
            ['Size' => 'L'],
            date('Y-m-d H:i:s'),
            null,
            'http://example.com/variant2.jpg',
            null
        );

        $processedVariants = [$shopifyVariant1, $shopifyVariant2];

        $this->csvParserMock->expects($this->exactly(4))
            ->method('parse')
            ->withConsecutive(
                [$stockFilePath],
                [$imageFilePath],
                [$priceFilePath],
                [$concreteFilePath]
            )
            ->willReturnOnConsecutiveCalls(
                $stockRecords,
                $imageRecords,
                $priceRecords,
                $concreteRecords
            );

        $this->variantRecordProcessorMock->expects($this->once())
            ->method('processVariants')
            ->with($stockRecords, $imageRecords, $priceRecords, $concreteRecords)
            ->willReturn($processedVariants);

        // Act
        $result = $this->importer->import($stockFilePath, $imageFilePath, $priceFilePath, $concreteFilePath);

        // Assert
        $this->assertSame($processedVariants, $result);
    }

    public function testImportWhenCsvParserThrowsException(): void
    {
        $stockFilePath = '/path/to/stock.csv';
        $imageFilePath = '/path/to/images.csv';
        $priceFilePath = '/path/to/prices.csv';
        $concreteFilePath = '/path/to/concrete.csv';

        $this->csvParserMock->expects($this->once())
            ->method('parse')
            ->with($stockFilePath)
            ->willThrowException(new \RuntimeException('Failed to parse stock CSV'));

        $this->variantRecordProcessorMock->expects($this->never())
            ->method('processVariants');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to parse stock CSV');

        // Act
        $this->importer->import($stockFilePath, $imageFilePath, $priceFilePath, $concreteFilePath);
    }

    public function testImportWhenVariantRecordProcessorThrowsException(): void
    {
        // Arrange
        $stockFilePath = '/path/to/stock.csv';
        $imageFilePath = '/path/to/images.csv';
        $priceFilePath = '/path/to/prices.csv';
        $concreteFilePath = '/path/to/concrete.csv';

        $stockRecords = [['concrete_sku' => 'SKU123', 'quantity' => '10']];
        $imageRecords = [['concrete_sku' => 'SKU123', 'image_url' => 'http://example.com/variant1.jpg']];
        $priceRecords = [['concrete_sku' => 'SKU123', 'price' => '99.99']];
        $concreteRecords = [['abstract_sku' => 'AB123', 'concrete_sku' => 'SKU123', 'name.en_US' => 'Variant 1']];

        // Set up the mocks
        $this->csvParserMock->expects($this->exactly(4))
            ->method('parse')
            ->withConsecutive(
                [$stockFilePath],
                [$imageFilePath],
                [$priceFilePath],
                [$concreteFilePath]
            )
            ->willReturnOnConsecutiveCalls(
                $stockRecords,
                $imageRecords,
                $priceRecords,
                $concreteRecords
            );

        $this->variantRecordProcessorMock->expects($this->once())
            ->method('processVariants')
            ->with($stockRecords, $imageRecords, $priceRecords, $concreteRecords)
            ->willThrowException(new \RuntimeException('Processing error'));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Processing error');

        // Act
        $this->importer->import($stockFilePath, $imageFilePath, $priceFilePath, $concreteFilePath);
    }

    public function testImportWithEmptyFiles(): void
    {
        // Arrange
        $stockFilePath = '/path/to/stock.csv';
        $imageFilePath = '/path/to/images.csv';
        $priceFilePath = '/path/to/prices.csv';
        $concreteFilePath = '/path/to/concrete.csv';

        $stockRecords = [];
        $imageRecords = [];
        $priceRecords = [];
        $concreteRecords = [];

        $processedVariants = [];

        // Set up the mocks
        $this->csvParserMock->expects($this->exactly(4))
            ->method('parse')
            ->withConsecutive(
                [$stockFilePath],
                [$imageFilePath],
                [$priceFilePath],
                [$concreteFilePath]
            )
            ->willReturnOnConsecutiveCalls(
                $stockRecords,
                $imageRecords,
                $priceRecords,
                $concreteRecords
            );

        $this->variantRecordProcessorMock->expects($this->once())
            ->method('processVariants')
            ->with($stockRecords, $imageRecords, $priceRecords, $concreteRecords)
            ->willReturn($processedVariants);

        // Act
        $result = $this->importer->import($stockFilePath, $imageFilePath, $priceFilePath, $concreteFilePath);

        // Assert
        $this->assertSame($processedVariants, $result);
        $this->assertEmpty($result);
    }
}
