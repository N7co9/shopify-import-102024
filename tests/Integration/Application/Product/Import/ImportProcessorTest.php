<?php
declare(strict_types=1);

namespace App\Tests\Integration\Application\Product\Import;

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
        $this->tempDirectory = sys_get_temp_dir() . '/import_integration_test/';
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
            ['abstract_sku', 'concrete_sku', 'value_gross', 'currency'],
            ['CONCRETE001', '', '100.00', 'EUR'],
        ]);

        $this->createCsvFile('product_stock.csv', [
            ['concrete_sku', 'quantity', 'is_never_out_of_stock'],
            ['CONCRETE001', '10', 'false'],
        ]);

        $this->createCsvFile('product_image.csv', [
            ['image_set_name', 'external_url_large', 'external_url_small', 'locale', 'abstract_sku', 'concrete_sku', 'sort_order', 'product_image_key'],
            ['default', 'https://example.com/large1.jpg', 'https://example.com/small1.jpg', 'de_DE', 'abstract_sku_1', 'concrete_sku_1', '1', 'product_image_1'],
        ]);

        $this->createCsvFile('product_label.csv', [
            ['name', 'is_active', 'is_dynamic', 'is_exclusive', 'front_end_reference', 'name.en_US', 'name.de_DE', 'product_abstract_skus', 'priority'],
            ['Standard label', '1', '0', '0', '', 'Standard Label', 'Standard Label', '001,002,003', '1'],
        ]);

        $this->createCsvFile('product_management_attribute.csv', [
            ['key', 'input_type', 'allow_input', 'is_multiple', 'values', 'key_translation.en_US', 'key_translation.de_DE'],
            ['storage_capacity', 'text', 'no', 'no', '16 GB, 32 GB, 64 GB, 128 GB', 'Storage Capacity', 'Speichergröße'],
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
        echo "CSV-Datei erstellt: $filePath\n";
    }

    public function testProcessDirectoryWithRealData(): void
    {
        $abstractImporter = new AbstractProductImporter();
        $concreteImporter = new ConcreteProductImporter();
        $priceImporter = new ProductPriceImporter();
        $stockImporter = new ProductStockImporter();
        $imageImporter = new ProductImageImporter();
        $labelImporter = new ProductLabelImporter();
        $attributeImporter = new ProductManagementAttributeImporter();

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

        $this->assertIsArray($result['abstract_products']);
        $this->assertIsArray($result['concrete_products']);
        $this->assertNotEmpty($result['abstract_products']);
        $this->assertNotEmpty($result['concrete_products']);

        $this->assertSame('SKU001', $result['abstract_products'][0]->getAbstractSku());
        $this->assertSame('CONCRETE001', $result['concrete_products'][0]->getConcreteSku());
        $this->assertSame(100.00, $result['concrete_products'][0]->getPriceGross());
        $this->assertSame(10, $result['concrete_products'][0]->getQuantity());
    }
}