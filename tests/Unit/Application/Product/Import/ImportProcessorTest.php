<?php
declare(strict_types=1);

namespace App\Tests\Unit\Application\Product\Import;

use App\Application\Product\Import\Abstract\AbstractProductImporter;
use App\Application\Product\Import\Concrete\ConcreteProductImporter;
use App\Application\Product\Import\Concrete\ProductImageImporter;
use App\Application\Product\Import\Concrete\ProductLabelImporter;
use App\Application\Product\Import\Concrete\ProductManagementAttributeImporter;
use App\Application\Product\Import\Concrete\ProductPriceImporter;
use App\Application\Product\Import\Concrete\ProductStockImporter;
use App\Application\Product\Import\ImportProcessor;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;


class ImportProcessorTest extends TestCase
{
    private string $tempDirectory;

    protected function setUp(): void
    {
        $this->tempDirectory = sys_get_temp_dir() . '/import_test/';
        $filesystem = new Filesystem();
        $filesystem->mkdir($this->tempDirectory);

        $this->createCsvFile('product_abstract.csv', [
            ['abstract_sku', 'name.en_US', 'name.de_DE', 'description.en_US', 'description.de_DE', 'category_key', 'tax_set_name', 'meta_title.en_US', 'meta_title.de_DE'],
            ['SKU001', 'Test Product EN', 'Testprodukt DE', 'This is a test product', 'Dies ist ein Testprodukt', 'category-1', 'Standard', 'Test Meta EN', 'Test Meta DE'],
        ]);

        $this->createCsvFile('product_concrete.csv', [
            ['abstract_sku', 'concrete_sku', 'name.en_US', 'name.de_DE', 'description.en_US', 'description.de_DE', 'is_searchable.en_US', 'is_searchable.de_DE'],
            ['SKU001', 'CONCRETE001', 'Test Concrete EN', 'Testbeton DE', 'This is a concrete product', 'Dies ist ein Betonprodukt', '1', '1'],
        ]);

        $this->createCsvFile('product_price.csv', [
            ['sku', 'price_gross', 'currency'],
            ['CONCRETE001', '100.00', 'EUR'],
        ]);

        $this->createCsvFile('product_stock.csv', [
            ['sku', 'quantity', 'is_never_out_of_stock'],
            ['CONCRETE001', '10', 'false'],
        ]);

        $this->createCsvFile('product_image.csv', [
            ['concrete_sku', 'external_url_large'],
            ['CONCRETE001', 'http://example.com/image.jpg'],
        ]);

        $this->createCsvFile('product_label.csv', [
            ['abstract_sku', 'label'],
            ['SKU001', 'New'],
        ]);

        $this->createCsvFile('product_management_attribute.csv', [
            ['key', 'value'],
            ['category-1', 'Attribute1'],
        ]);
    }

    protected function tearDown(): void
    {
        $filesystem = new Filesystem();
        $filesystem->remove($this->tempDirectory);
    }

    private function createCsvFile(string $filename, array $data): void
    {
        $filePath = $this->tempDirectory . $filename;
        $file = fopen($filePath, 'wb');

        foreach ($data as $row) {
            fputcsv($file, $row);
        }

        fclose($file);
    }

    public function testProcessDirectory(): void
    {
        $abstractImporter = $this->createMock(AbstractProductImporter::class);
        $concreteImporter = $this->createMock(ConcreteProductImporter::class);
        $priceImporter = $this->createMock(ProductPriceImporter::class);
        $stockImporter = $this->createMock(ProductStockImporter::class);
        $imageImporter = $this->createMock(ProductImageImporter::class);
        $labelImporter = $this->createMock(ProductLabelImporter::class);
        $attributeImporter = $this->createMock(ProductManagementAttributeImporter::class);

        $abstractImporter->expects($this->once())->method('import')->willReturn([]);
        $concreteImporter->expects($this->once())->method('import')->willReturn([]);
        $priceImporter->expects($this->once())->method('import')->willReturn([]);
        $stockImporter->expects($this->once())->method('import')->willReturn([]);
        $imageImporter->expects($this->once())->method('import')->willReturn([]);
        $labelImporter->expects($this->once())->method('import')->willReturn([]);
        $attributeImporter->expects($this->once())->method('import')->willReturn([]);

        $processor = new ImportProcessor(
            $abstractImporter,
            $concreteImporter,
            $priceImporter,
            $stockImporter,
            $imageImporter,
            $labelImporter,
            $attributeImporter
        );

        $result = $processor->processImport($this->tempDirectory);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('abstract_products', $result);
        $this->assertArrayHasKey('concrete_products', $result);
    }
}