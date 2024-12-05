<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Product\Import;

use App\Application\Product\Import\ImportProcessor;
use App\Domain\DTO\LocalizedString;
use App\Domain\DTO\ShopifyProduct;
use App\Domain\DTO\ShopifyVariant;
use PHPUnit\Framework\TestCase;

class ImportProcessorTest extends TestCase
{
    private ImportProcessor $importProcessor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->importProcessor = new ImportProcessor(
            $this->createMock(\App\Application\Product\Import\ShopifyVariantImporter::class),
            $this->createMock(\App\Application\Product\Import\ShopifyProductImporter::class),
            $this->createMock(\App\Application\Logger\LoggerInterface::class)
        );
    }

    protected function tearDown(): void
    {
        unset($this->importProcessor);

        parent::tearDown();
    }

    public function testMapProductsToVariants(): void
    {
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

        $product1 = new ShopifyProduct(
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
        );
        $product1->abstractSku = 'AB123';

        $product2 = new ShopifyProduct(
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
        );
        $product2->abstractSku = 'AB124';

        $products = [$product1, $product2];

        $variant1 = new ShopifyVariant(
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
        $variant1->abstractSku = 'AB123';

        $variant2 = new ShopifyVariant(
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
        $variant2->abstractSku = 'AB124';

        $variant3 = new ShopifyVariant(
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
        $variant3->abstractSku = 'AB123';

        $variants = [$variant1, $variant2, $variant3];

        // Use reflection to access the private method
        $reflection = new \ReflectionClass($this->importProcessor);
        $method = $reflection->getMethod('mapProductsToVariants');

        // Act
        $result = $method->invoke($this->importProcessor, $products, $variants);

        // Assert
        $this->assertCount(2, $result);

        foreach ($result as $product) {
            if ($product->abstractSku === 'AB123') {
                $this->assertCount(2, $product->variants);
                foreach ($product->variants as $variant) {
                    $this->assertSame('AB123', $variant->abstractSku);
                }
            } elseif ($product->abstractSku === 'AB124') {
                $this->assertCount(1, $product->variants);
                $this->assertSame('AB124', $product->variants[1]->abstractSku);
            } else {
                $this->fail('Unexpected product abstractSku: ' . $product->abstractSku);
            }
        }
    }
}
