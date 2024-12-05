<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Product\Import\Tools;

use App\Application\Product\Import\Tools\ProductRecordProcessor;
use App\Domain\DTO\LocalizedString;
use App\Domain\DTO\ShopifyProduct;
use PHPUnit\Framework\TestCase;

class ProductRecordProcessorTest extends TestCase
{
    private ProductRecordProcessor $processor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->processor = new ProductRecordProcessor();
    }

    public function testProcessProductsWithValidData(): void
    {
        $abstractProductRecords = [
            [
                'abstract_sku' => 'SKU123',
                'name.en_US' => 'Test Product',
                'name.de_DE' => 'Testprodukt',
                'description.en_US' => 'A great product',
                'description.de_DE' => 'Ein großartiges Produkt',
                'category_key' => 'Electronics',
                'url.en_US' => 'test-product',
                'url.de_DE' => 'testprodukt',
                'meta_keywords.en_US' => 'test, product',
                'meta_keywords.de_DE' => 'test, produkt',
                'tax_set_name' => 'Standard',
                'attribute_key_1' => 'Color',
                'value_1' => 'Red',
                'attribute_key_2' => 'Size',
                'value_2' => 'M',
                'category_product_order' => '1',
                'new_from' => '2022-01-01',
                'new_to' => '2022-12-31',
            ],
        ];

        $priceRecords = [
            [
                'abstract_sku' => 'SKU123',
                'value_gross' => '99.99',
                'price_type' => 'DEFAULT',
            ],
            [
                'abstract_sku' => 'SKU123',
                'value_gross' => '119.99',
                'price_type' => 'ORIGINAL',
            ],
        ];

        $imageRecords = [
            [
                'abstract_sku' => 'SKU123',
                'external_url_large' => 'http://example.com/image.jpg',
            ],
        ];

        $products = $this->processor->processProducts($abstractProductRecords, $priceRecords, $imageRecords);

        $expectedProduct = new ShopifyProduct(
            'SKU123',
            new LocalizedString('Test Product', 'Testprodukt'),
            new LocalizedString('A great product', 'Ein großartiges Produkt'),
            'Shopify',
            '99.99',
            '119.99',
            'Electronics',
            false,
            new LocalizedString('test-product', 'testprodukt'),
            'ACTIVE',
            null,
            null,
            'http://example.com/image.jpg',
            ['Color' => 'Red', 'Size' => 'M'],
            new LocalizedString('test, product', 'test, produkt'),
            null,
            date('Y-m-d H:i:s'),
            null,
            null,
            '1',
            'Standard',
            true,
            '2022-01-01',
            '2022-12-31'
        );

        $this->assertCount(1, $products);
        $this->assertEquals($expectedProduct, $products[0]);
    }

    public function testProcessProductsWithMissingPriceRecords(): void
    {
        $abstractProductRecords = [
            [
                'abstract_sku' => 'SKU124',
                'name.en_US' => 'Product Without Price',
                'name.de_DE' => 'Produkt ohne Preis',
                'description.en_US' => 'No price available',
                'description.de_DE' => 'Kein Preis verfügbar',
                'category_key' => 'Books',
                'url.en_US' => 'product-without-price',
                'url.de_DE' => 'produkt-ohne-preis',
                'meta_keywords.en_US' => 'book',
                'meta_keywords.de_DE' => 'buch',
                'tax_set_name' => 'Reduced',
                'attribute_key_1' => '',
                'value_1' => '',
                'category_product_order' => '2',
            ],
        ];

        $priceRecords = [];

        $imageRecords = [];

        $products = $this->processor->processProducts($abstractProductRecords, $priceRecords, $imageRecords);

        $expectedProduct = new ShopifyProduct(
            'SKU124',
            new LocalizedString('Product Without Price', 'Produkt ohne Preis'),
            new LocalizedString('No price available', 'Kein Preis verfügbar'),
            'Shopify',
            '0.00', // Default price
            '0.00', // Default compare at price
            'Books',
            false,
            new LocalizedString('product-without-price', 'produkt-ohne-preis'),
            'ACTIVE',
            null,
            null,
            null, // No image URL
            [], // No attributes
            new LocalizedString('book', 'buch'),
            null,
            date('Y-m-d H:i:s'),
            null,
            null,
            '2',
            'Reduced',
            false,
            null,
            null
        );

        $this->assertCount(1, $products);
        $this->assertEquals($expectedProduct, $products[0]);
    }

    public function testProcessProductsWithGiftCardProduct(): void
    {
        // Arrange
        $abstractProductRecords = [
            [
                'abstract_sku' => 'SKU125',
                'name.en_US' => 'Gift Card',
                'name.de_DE' => 'Geschenkgutschein',
                'description.en_US' => 'A gift card',
                'description.de_DE' => 'Ein Geschenkgutschein',
                'category_key' => 'Gift Cards',
                'url.en_US' => 'gift-card',
                'url.de_DE' => 'geschenkgutschein',
                'meta_keywords.en_US' => 'gift, card',
                'meta_keywords.de_DE' => 'geschenk, gutschein',
                'tax_set_name' => 'None',
            ],
        ];

        $priceRecords = [
            [
                'abstract_sku' => 'SKU125',
                'value_gross' => '50.00',
                'price_type' => 'DEFAULT',
            ],
        ];

        $imageRecords = [];

        // Act
        $products = $this->processor->processProducts($abstractProductRecords, $priceRecords, $imageRecords);

        // Assert
        $expectedProduct = new ShopifyProduct(
            'SKU125',
            new LocalizedString('Gift Card', 'Geschenkgutschein'),
            new LocalizedString('A gift card', 'Ein Geschenkgutschein'),
            'Shopify',
            '50.00',
            '0.00',
            'Gift Cards',
            true,
            new LocalizedString('gift-card', 'geschenkgutschein'),
            'ACTIVE',
            null,
            null,
            null, // No image URL
            [], // No attributes
            new LocalizedString('gift, card', 'geschenk, gutschein'),
            null,
            date('Y-m-d H:i:s'),
            null,
            null,
            '',
            'None',
            false, // Not a bundle
            null,
            null
        );

        $this->assertCount(1, $products);
        $this->assertEquals($expectedProduct, $products[0]);
    }

    public function testProcessProductsWithBundleProduct(): void
    {
        // Arrange
        $abstractProductRecords = [
            [
                'abstract_sku' => 'SKU126',
                'name.en_US' => 'Bundle Product',
                'name.de_DE' => 'Paketprodukt',
                'description.en_US' => 'A bundle of products',
                'description.de_DE' => 'Ein Paket von Produkten',
                'category_key' => 'Bundles',
                'url.en_US' => 'bundle-product',
                'url.de_DE' => 'paketprodukt',
                'meta_keywords.en_US' => 'bundle',
                'meta_keywords.de_DE' => 'paket',
                'tax_set_name' => 'Standard',
                'attribute_key_1' => 'Includes',
                'value_1' => 'Item1, Item2',
            ],
        ];

        $priceRecords = [
            [
                'abstract_sku' => 'SKU126',
                'value_gross' => '150.00',
                'price_type' => 'DEFAULT',
            ],
        ];

        $imageRecords = [];

        $products = $this->processor->processProducts($abstractProductRecords, $priceRecords, $imageRecords);

        $expectedProduct = new ShopifyProduct(
            'SKU126',
            new LocalizedString('Bundle Product', 'Paketprodukt'),
            new LocalizedString('A bundle of products', 'Ein Paket von Produkten'),
            'Shopify',
            '150.00',
            '0.00', // No compare at price
            'Bundles',
            false,
            new LocalizedString('bundle-product', 'paketprodukt'),
            'ACTIVE',
            null,
            null,
            null, // No image URL
            ['Includes' => 'Item1, Item2'],
            new LocalizedString('bundle', 'paket'),
            null,
            date('Y-m-d H:i:s'),
            null,
            null,
            '',
            'Standard',
            true, // Is bundle
            null,
            null
        );

        $this->assertCount(1, $products);
        $this->assertEquals($expectedProduct, $products[0]);
    }

    public function testProcessProductsWithEmptyInputs(): void
    {
        $abstractProductRecords = [];
        $priceRecords = [];
        $imageRecords = [];

        $products = $this->processor->processProducts($abstractProductRecords, $priceRecords, $imageRecords);

        // Assert
        $this->assertIsArray($products);
        $this->assertEmpty($products);
    }


    public function testProcessProductsWithMultipleProducts(): void
    {
        $abstractProductRecords = [
            [
                'abstract_sku' => 'SKU127',
                'name.en_US' => 'Product One',
                'name.de_DE' => 'Produkt Eins',
                'description.en_US' => 'First product',
                'description.de_DE' => 'Erstes Produkt',
                'category_key' => 'Category1',
                'url.en_US' => 'product-one',
                'url.de_DE' => 'produkt-eins',
                'meta_keywords.en_US' => 'first',
                'meta_keywords.de_DE' => 'erstes',
                'tax_set_name' => 'Standard',
            ],
            [
                'abstract_sku' => 'SKU128',
                'name.en_US' => 'Product Two',
                'name.de_DE' => 'Produkt Zwei',
                'description.en_US' => 'Second product',
                'description.de_DE' => 'Zweites Produkt',
                'category_key' => 'Category2',
                'url.en_US' => 'product-two',
                'url.de_DE' => 'produkt-zwei',
                'meta_keywords.en_US' => 'second',
                'meta_keywords.de_DE' => 'zweites',
                'tax_set_name' => 'Standard',
            ],
        ];

        $priceRecords = [
            [
                'abstract_sku' => 'SKU127',
                'value_gross' => '49.99',
                'price_type' => 'DEFAULT',
            ],
            [
                'abstract_sku' => 'SKU128',
                'value_gross' => '79.99',
                'price_type' => 'DEFAULT',
            ],
        ];

        $imageRecords = [
            [
                'abstract_sku' => 'SKU127',
                'external_url_large' => 'http://example.com/image1.jpg',
            ],
            [
                'abstract_sku' => 'SKU128',
                'external_url_large' => 'http://example.com/image2.jpg',
            ],
        ];

        // Act
        $products = $this->processor->processProducts($abstractProductRecords, $priceRecords, $imageRecords);

        // Assert
        $this->assertCount(2, $products);

        // Expected products
        $expectedProduct1 = new ShopifyProduct(
            'SKU127',
            new LocalizedString('Product One', 'Produkt Eins'),
            new LocalizedString('First product', 'Erstes Produkt'),
            'Shopify',
            '49.99',
            '0.00',
            'Category1',
            false,
            new LocalizedString('product-one', 'produkt-eins'),
            'ACTIVE',
            null,
            null,
            'http://example.com/image1.jpg',
            [],
            new LocalizedString('first', 'erstes'),
            null,
            date('Y-m-d H:i:s'),
            null,
            null,
            '',
            'Standard',
            false,
            null,
            null
        );

        $expectedProduct2 = new ShopifyProduct(
            'SKU128',
            new LocalizedString('Product Two', 'Produkt Zwei'),
            new LocalizedString('Second product', 'Zweites Produkt'),
            'Shopify',
            '79.99',
            '0.00',
            'Category2',
            false,
            new LocalizedString('product-two', 'produkt-zwei'),
            'ACTIVE',
            null,
            null,
            'http://example.com/image2.jpg',
            [],
            new LocalizedString('second', 'zweites'),
            null,
            date('Y-m-d H:i:s'),
            null,
            null,
            '',
            'Standard',
            false,
            null,
            null
        );

        $this->assertEquals($expectedProduct1, $products[0]);
        $this->assertEquals($expectedProduct2, $products[1]);
    }

    public function testProcessProductsWithNoImageRecord(): void
    {
        // Arrange
        $abstractProductRecords = [
            [
                'abstract_sku' => 'SKU129',
                'name.en_US' => 'No Image Product',
                'name.de_DE' => 'Produkt ohne Bild',
                'description.en_US' => 'This product has no image',
                'description.de_DE' => 'Dieses Produkt hat kein Bild',
                'category_key' => 'Misc',
                'url.en_US' => 'no-image-product',
                'url.de_DE' => 'produkt-ohne-bild',
                'meta_keywords.en_US' => 'no image',
                'meta_keywords.de_DE' => 'kein bild',
                'tax_set_name' => 'Standard',
            ],
        ];

        $priceRecords = [
            [
                'abstract_sku' => 'SKU129',
                'value_gross' => '29.99',
                'price_type' => 'DEFAULT',
            ],
        ];

        $imageRecords = []; // No image records

        // Act
        $products = $this->processor->processProducts($abstractProductRecords, $priceRecords, $imageRecords);

        // Assert
        $expectedProduct = new ShopifyProduct(
            'SKU129',
            new LocalizedString('No Image Product', 'Produkt ohne Bild'),
            new LocalizedString('This product has no image', 'Dieses Produkt hat kein Bild'),
            'Shopify',
            '29.99',
            '0.00',
            'Misc',
            false,
            new LocalizedString('no-image-product', 'produkt-ohne-bild'),
            'ACTIVE',
            null,
            null,
            null, // No image URL
            [],
            new LocalizedString('no image', 'kein bild'),
            null,
            date('Y-m-d H:i:s'),
            null,
            null,
            '',
            'Standard',
            false,
            null,
            null
        );

        $this->assertCount(1, $products);
        $this->assertEquals($expectedProduct, $products[0]);
    }
}
