<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Product\Import;

use App\Application\Product\Import\ShopifyProductImporter;
use App\Application\Product\Import\Tools\CsvParser;
use App\Application\Product\Import\Tools\ProductRecordProcessor;
use App\Domain\DTO\LocalizedString;
use App\Domain\DTO\ShopifyProduct;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ShopifyProductImporterTest extends TestCase
{
    /** @var CsvParser&MockObject */
    private $csvParserMock;

    /** @var ProductRecordProcessor&MockObject */
    private $productRecordProcessorMock;

    private ShopifyProductImporter $importer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->csvParserMock = $this->createMock(CsvParser::class);
        $this->productRecordProcessorMock = $this->createMock(ProductRecordProcessor::class);

        $this->importer = new ShopifyProductImporter(
            $this->csvParserMock,
            $this->productRecordProcessorMock
        );
    }

    public function testImportWithValidData(): void
    {
        // Arrange
        $abstractProductFilePath = '/path/to/abstract_products.csv';
        $priceFilePath = '/path/to/prices.csv';
        $imageFilePath = '/path/to/images.csv';

        $abstractProductRecords = [
            ['abstract_sku' => 'SKU123', 'name' => 'Product 1'],
            ['abstract_sku' => 'SKU124', 'name' => 'Product 2'],
        ];
        $priceRecords = [
            ['abstract_sku' => 'SKU123', 'price' => '99.99'],
            ['abstract_sku' => 'SKU124', 'price' => '149.99'],
        ];
        $imageRecords = [
            ['abstract_sku' => 'SKU123', 'image_url' => 'http://example.com/image1.jpg'],
            ['abstract_sku' => 'SKU124', 'image_url' => 'http://example.com/image2.jpg'],
        ];

        // Create instances of LocalizedString as needed
        $localizedTitle1 = new LocalizedString('Product 1', 'Produkt 1');
        $localizedTitle2 = new LocalizedString('Product 2', 'Produkt 2');

        $localizedBodyHtml1 = new LocalizedString('Description 1', 'Beschreibung 1');
        $localizedBodyHtml2 = new LocalizedString('Description 2', 'Beschreibung 2');

        $localizedHandle1 = new LocalizedString('product-1', 'produkt-1');
        $localizedHandle2 = new LocalizedString('product-2', 'produkt-2');

        $localizedMetafields1 = new LocalizedString('Meta Title 1', 'Meta Titel 1');
        $localizedMetafields2 = new LocalizedString('Meta Title 2', 'Meta Titel 2');

        $processedProducts = [
            new ShopifyProduct(
                'SKU123',
                $localizedTitle1,
                $localizedBodyHtml1,
                'Vendor Name',
                '99.99',
                '119.99',
                'Product Type',
                false,
                $localizedHandle1,
                'ACTIVE',
                date('Y-m-d H:i:s'),
                [],
                'http://example.com/image1.jpg',
                ['tag1', 'tag2'],
                $localizedMetafields1,
                'global', // variants
                date('Y-m-d H:i:s'),
                date('Y-m-d H:i:s'),
                null, // templateSuffix
                null, // sortOrder
                null, // taxable
                false, // isBundle
                null, // newFrom
                null  // newTo
            ),
            new ShopifyProduct(
                'SKU124',
                $localizedTitle2,
                $localizedBodyHtml2,
                'Vendor Name',
                '149.99',
                '169.99',
                'Product Type',
                false,
                $localizedHandle2,
                'ACTIVE',
                date('Y-m-d H:i:s'),
                [],
                'http://example.com/image2.jpg',
                ['tag3', 'tag4'],
                $localizedMetafields2,
                'global',
                date('Y-m-d H:i:s'),
                date('Y-m-d H:i:s'),
                null, // templateSuffix
                null, // sortOrder
                null, // taxable
                false, // isBundle
                null, // newFrom
                null  // newTo
            ),
        ];

        // Set up the mocks
        $this->csvParserMock->expects($this->exactly(3))
            ->method('parse')
            ->withConsecutive(
                [$abstractProductFilePath],
                [$priceFilePath],
                [$imageFilePath]
            )
            ->willReturnOnConsecutiveCalls(
                $abstractProductRecords,
                $priceRecords,
                $imageRecords
            );

        $this->productRecordProcessorMock->expects($this->once())
            ->method('processProducts')
            ->with($abstractProductRecords, $priceRecords, $imageRecords)
            ->willReturn($processedProducts);

        // Act
        $result = $this->importer->import($abstractProductFilePath, $priceFilePath, $imageFilePath);

        // Assert
        $this->assertSame($processedProducts, $result);
    }

    public function testImportWhenCsvParserThrowsException(): void
    {
        // Arrange
        $abstractProductFilePath = '/path/to/abstract_products.csv';
        $priceFilePath = '/path/to/prices.csv';
        $imageFilePath = '/path/to/images.csv';

        $this->csvParserMock->expects($this->once())
            ->method('parse')
            ->with($abstractProductFilePath)
            ->willThrowException(new \RuntimeException('File not found'));

        $this->productRecordProcessorMock->expects($this->never())
            ->method('processProducts');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('File not found');

        // Act
        $this->importer->import($abstractProductFilePath, $priceFilePath, $imageFilePath);
    }

    public function testImportWhenRecordProcessorThrowsException(): void
    {
        // Arrange
        $abstractProductFilePath = '/path/to/abstract_products.csv';
        $priceFilePath = '/path/to/prices.csv';
        $imageFilePath = '/path/to/images.csv';

        $abstractProductRecords = [['abstract_sku' => 'SKU123', 'name' => 'Product 1']];
        $priceRecords = [['abstract_sku' => 'SKU123', 'price' => '99.99']];
        $imageRecords = [['abstract_sku' => 'SKU123', 'image_url' => 'http://example.com/image1.jpg']];

        // Set up the mocks
        $this->csvParserMock->expects($this->exactly(3))
            ->method('parse')
            ->withConsecutive(
                [$abstractProductFilePath],
                [$priceFilePath],
                [$imageFilePath]
            )
            ->willReturnOnConsecutiveCalls(
                $abstractProductRecords,
                $priceRecords,
                $imageRecords
            );

        $this->productRecordProcessorMock->expects($this->once())
            ->method('processProducts')
            ->with($abstractProductRecords, $priceRecords, $imageRecords)
            ->willThrowException(new \RuntimeException('Processing error'));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Processing error');

        // Act
        $this->importer->import($abstractProductFilePath, $priceFilePath, $imageFilePath);
    }

    public function testImportWithEmptyFiles(): void
    {
        // Arrange
        $abstractProductFilePath = '/path/to/abstract_products.csv';
        $priceFilePath = '/path/to/prices.csv';
        $imageFilePath = '/path/to/images.csv';

        $abstractProductRecords = [];
        $priceRecords = [];
        $imageRecords = [];

        $processedProducts = [];

        // Set up the mocks
        $this->csvParserMock->expects($this->exactly(3))
            ->method('parse')
            ->withConsecutive(
                [$abstractProductFilePath],
                [$priceFilePath],
                [$imageFilePath]
            )
            ->willReturnOnConsecutiveCalls(
                $abstractProductRecords,
                $priceRecords,
                $imageRecords
            );

        $this->productRecordProcessorMock->expects($this->once())
            ->method('processProducts')
            ->with($abstractProductRecords, $priceRecords, $imageRecords)
            ->willReturn($processedProducts);

        // Act
        $result = $this->importer->import($abstractProductFilePath, $priceFilePath, $imageFilePath);

        // Assert
        $this->assertSame($processedProducts, $result);
        $this->assertEmpty($result);
    }
}
