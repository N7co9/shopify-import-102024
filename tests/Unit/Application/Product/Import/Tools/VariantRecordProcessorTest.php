<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Product\Import\Tools;

use App\Application\Product\Import\Tools\VariantRecordProcessor;
use App\Domain\DTO\ShopifyVariant;
use PHPUnit\Framework\TestCase;

class VariantRecordProcessorTest extends TestCase
{
    private VariantRecordProcessor $processor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->processor = new VariantRecordProcessor();
    }

    public function testGetOptionWithMultipleAttributes(): void
    {
        $record = [
            'attribute_key_1' => 'Size',
            'value_1' => 'M',
            'attribute_key_2' => 'Color',
            'value_2' => 'Red',
        ];

        $processor = new VariantRecordProcessor();
        $ref = new \ReflectionClass($processor);
        $method = $ref->getMethod('getOption');

        $options = $method->invoke($processor, $record);

        $expectedOptions = [
            'Size' => 'M',
            'Color' => 'Red',
        ];

        $this->assertEquals($expectedOptions, $options);
    }

    public function testProcessVariantsWithValidData(): void
    {
        $stockRecords = [
            [
                'concrete_sku' => 'SKU123',
                'quantity' => '10',
                'name' => 'Main Warehouse',
                'is_never_out_of_stock' => '0',
            ],
        ];

        $imageRecords = [
            [
                'concrete_sku' => 'SKU123',
                'external_url_large' => 'http://example.com/variant_image.jpg',
            ],
        ];

        $priceRecords = [
            [
                'abstract_sku' => 'AB123',
                'value_gross' => '99.99',
                'price_type' => 'DEFAULT',
            ],
            [
                'concrete_sku' => 'SKU123',
                'value_gross' => '89.99',
                'price_type' => 'DEFAULT',
            ],
            [
                'abstract_sku' => 'AB123',
                'value_gross' => '119.99',
                'price_type' => 'ORIGINAL',
            ],
        ];

        $concreteRecords = [
            [
                'abstract_sku' => 'AB123',
                'concrete_sku' => 'SKU123',
                'name.en_US' => 'Variant Product',
                'attribute_key_1' => 'Size',
                'value_1' => 'M',
                'attribute_key_2' => 'Color',
                'value_2' => 'Red',
            ],
        ];

        // Act
        $variants = $this->processor->processVariants($stockRecords, $imageRecords, $priceRecords, $concreteRecords);

        // Assert
        $expectedVariant = new ShopifyVariant(
            'AB123',
            'SKU123',
            'Variant Product',
            0,
            '10',
            ['name' => 'Main Warehouse'],
            '0',
            '89.99',
            'Shopify',
            'DENY',
            true,
            true,
            true,
            null,
            null,
            'Not Available',
            '119.99',
            ['Size' => 'M',
                'Color' => 'Red'],
            date('Y-m-d H:i:s'),
            null,
            'http://example.com/variant_image.jpg',
            null
        );

        $this->assertCount(1, $variants);
        $this->assertEquals($expectedVariant, $variants[0]);
    }

    public function testProcessVariantsWithMissingStockRecords(): void
    {
        // Arrange
        $stockRecords = []; // Empty stock records

        $imageRecords = [];

        $priceRecords = [
            [
                'abstract_sku' => 'AB124',
                'value_gross' => '59.99',
                'price_type' => 'DEFAULT',
            ],
        ];

        $concreteRecords = [
            [
                'abstract_sku' => 'AB124',
                'concrete_sku' => 'SKU124',
                'name.en_US' => 'Variant Without Stock',
                'attribute_key_1' => 'Size',
                'value_1' => 'L',
            ],
        ];

        // Act
        $variants = $this->processor->processVariants($stockRecords, $imageRecords, $priceRecords, $concreteRecords);

        // Assert
        $expectedVariant = new ShopifyVariant(
            'AB124',
            'SKU124',
            'Variant Without Stock',
            0,
            'N/A', // inventory quantity default
            ['name' => 'DEFAULT'], // inventory location default
            '0',
            '59.99', // price from parent (abstract) price
            'Shopify',
            'DENY',
            true,
            true,
            true,
            null,
            null,
            'Not Available',
            '0.00', // compare at price default
            ['Size' => 'L'],
            date('Y-m-d H:i:s'),
            null,
            null, // imageUrl
            null
        );

        $this->assertCount(1, $variants);
        $this->assertEquals($expectedVariant, $variants[0]);
    }

    public function testProcessVariantsWithGiftCardProduct(): void
    {
        // Arrange
        $stockRecords = [
            [
                'concrete_sku' => 'SKU125',
                'quantity' => '0',
                'name' => 'Gift Card Warehouse',
                'is_never_out_of_stock' => '1',
            ],
        ];

        $imageRecords = [];

        $priceRecords = [
            [
                'abstract_sku' => 'AB125',
                'value_gross' => '25.00',
                'price_type' => 'DEFAULT',
            ],
        ];

        $concreteRecords = [
            [
                'abstract_sku' => 'AB125',
                'concrete_sku' => 'SKU125',
                'name.en_US' => 'Gift Card',
                // No options
            ],
        ];

        // Act
        $variants = $this->processor->processVariants($stockRecords, $imageRecords, $priceRecords, $concreteRecords);

        // Assert
        $expectedVariant = new ShopifyVariant(
            'AB125',
            'SKU125',
            'Gift Card',
            0,
            '0',
            ['name' => 'Gift Card Warehouse'],
            '1',
            '25.00',
            'Shopify',
            'DENY',
            true,
            true,
            false, // requires shipping false
            null,
            null,
            'Not Available',
            '0.00', // compare at price default
            [], // options empty
            date('Y-m-d H:i:s'),
            null,
            null, // imageUrl
            null
        );

        $this->assertCount(1, $variants);
        $this->assertEquals($expectedVariant, $variants[0]);
    }

    public function testProcessVariantsWithMissingFields(): void
    {
        // Arrange
        $stockRecords = [];
        $imageRecords = [];
        $priceRecords = [];
        $concreteRecords = [
            [
                // Missing 'abstract_sku' and 'concrete_sku'
                'name.en_US' => 'Incomplete Variant',
            ],
        ];

        // Act & Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required fields: abstract_sku, concrete_sku');

        $this->processor->processVariants($stockRecords, $imageRecords, $priceRecords, $concreteRecords);
    }


    public function testProcessVariantsWithEmptyInputs(): void
    {
        // Arrange
        $stockRecords = [];
        $imageRecords = [];
        $priceRecords = [];
        $concreteRecords = [];

        // Act
        $variants = $this->processor->processVariants($stockRecords, $imageRecords, $priceRecords, $concreteRecords);

        // Assert
        $this->assertIsArray($variants);
        $this->assertEmpty($variants);
    }

    public function testProcessVariantsWithMultipleVariants(): void
    {
        // Arrange
        $stockRecords = [
            [
                'concrete_sku' => 'SKU126',
                'quantity' => '5',
                'name' => 'Warehouse A',
                'is_never_out_of_stock' => '0',
            ],
            [
                'concrete_sku' => 'SKU127',
                'quantity' => '15',
                'name' => 'Warehouse B',
                'is_never_out_of_stock' => '0',
            ],
        ];

        $imageRecords = [
            [
                'concrete_sku' => 'SKU126',
                'external_url_large' => 'http://example.com/image126.jpg',
            ],
            [
                'concrete_sku' => 'SKU127',
                'external_url_large' => 'http://example.com/image127.jpg',
            ],
        ];

        $priceRecords = [
            [
                'abstract_sku' => 'AB126',
                'value_gross' => '49.99',
                'price_type' => 'DEFAULT',
            ],
            [
                'concrete_sku' => 'SKU127',
                'value_gross' => '59.99',
                'price_type' => 'DEFAULT',
            ],
        ];

        $concreteRecords = [
            [
                'abstract_sku' => 'AB126',
                'concrete_sku' => 'SKU126',
                'name.en_US' => 'Variant One',
                'attribute_key_1' => 'Size',
                'value_1' => 'S',
            ],
            [
                'abstract_sku' => 'AB126',
                'concrete_sku' => 'SKU127',
                'name.en_US' => 'Variant Two',
                'attribute_key_1' => 'Size',
                'value_1' => 'M',
            ],
        ];

        // Act
        $variants = $this->processor->processVariants($stockRecords, $imageRecords, $priceRecords, $concreteRecords);

        // Assert
        $this->assertCount(2, $variants);

        $expectedVariant1 = new ShopifyVariant(
            'AB126',
            'SKU126',
            'Variant One',
            0,
            '5',
            ['name' => 'Warehouse A'],
            '0',
            '49.99', // Price from abstract since concrete price is not provided
            'Shopify',
            'DENY',
            true,
            true,
            true,
            null,
            null,
            'Not Available',
            '0.00', // Default compare at price
            ['Size' => 'S'],
            date('Y-m-d H:i:s'),
            null,
            'http://example.com/image126.jpg',
            null
        );

        $expectedVariant2 = new ShopifyVariant(
            'AB126',
            'SKU127',
            'Variant Two',
            0,
            '15',
            ['name' => 'Warehouse B'],
            '0',
            '59.99', // Price from concrete price
            'Shopify',
            'DENY',
            true,
            true,
            true,
            null,
            null,
            'Not Available',
            '0.00', // Default compare at price
            ['Size' => 'M'],
            date('Y-m-d H:i:s'),
            null,
            'http://example.com/image127.jpg',
            null
        );

        $this->assertEquals($expectedVariant1, $variants[0]);
        $this->assertEquals($expectedVariant2, $variants[1]);
    }

    public function testProcessVariantsWithNoOptions(): void
    {
        // Arrange
        $stockRecords = [
            [
                'concrete_sku' => 'SKU128',
                'quantity' => '20',
                'name' => 'Warehouse',
                'is_never_out_of_stock' => '0',
            ],
        ];

        $imageRecords = [];

        $priceRecords = [
            [
                'abstract_sku' => 'AB128',
                'value_gross' => '39.99',
                'price_type' => 'DEFAULT',
            ],
        ];

        $concreteRecords = [
            [
                'abstract_sku' => 'AB128',
                'concrete_sku' => 'SKU128',
                'name.en_US' => 'Variant Without Options',
                // No attributes
            ],
        ];

        // Act
        $variants = $this->processor->processVariants($stockRecords, $imageRecords, $priceRecords, $concreteRecords);

        // Assert
        $expectedVariant = new ShopifyVariant(
            'AB128',
            'SKU128',
            'Variant Without Options',
            0,
            '20',
            ['name' => 'Warehouse'],
            '0',
            '39.99',
            'Shopify',
            'DENY',
            true,
            true,
            true,
            null,
            null,
            'Not Available',
            '0.00',
            [], // No options
            date('Y-m-d H:i:s'),
            null,
            null,
            null
        );

        $this->assertCount(1, $variants);
        $this->assertEquals($expectedVariant, $variants[0]);
    }

    public function testProcessVariantsWithMissingPriceRecords(): void
    {
        // Arrange
        $stockRecords = [
            [
                'concrete_sku' => 'SKU129',
                'quantity' => '5',
                'name' => 'Warehouse',
                'is_never_out_of_stock' => '0',
            ],
        ];

        $imageRecords = [];

        $priceRecords = []; // No price records

        $concreteRecords = [
            [
                'abstract_sku' => 'AB129',
                'concrete_sku' => 'SKU129',
                'name.en_US' => 'Variant Without Price',
                'attribute_key_1' => 'Size',
                'value_1' => 'L',
            ],
        ];

        // Act
        $variants = $this->processor->processVariants($stockRecords, $imageRecords, $priceRecords, $concreteRecords);

        // Assert
        $expectedVariant = new ShopifyVariant(
            'AB129',
            'SKU129',
            'Variant Without Price',
            0,
            '5',
            ['name' => 'Warehouse'],
            '0',
            '0.00', // Default price
            'Shopify',
            'DENY',
            true,
            true,
            true,
            null,
            null,
            'Not Available',
            '0.00', // Default compare at price
            ['Size' => 'L'],
            date('Y-m-d H:i:s'),
            null,
            null,
            null
        );

        $this->assertCount(1, $variants);
        $this->assertEquals($expectedVariant, $variants[0]);
    }

    public function testProcessVariantsWithUPCField(): void
    {
        // Arrange
        $stockRecords = [
            [
                'concrete_sku' => 'SKU130',
                'quantity' => '8',
                'name' => 'Warehouse',
                'is_never_out_of_stock' => '0',
            ],
        ];

        $imageRecords = [];

        $priceRecords = [
            [
                'abstract_sku' => 'AB130',
                'value_gross' => '79.99',
                'price_type' => 'DEFAULT',
            ],
        ];

        $concreteRecords = [
            [
                'abstract_sku' => 'AB130',
                'concrete_sku' => 'SKU130',
                'name.en_US' => 'Variant with UPC',
                'attribute_key_1' => 'Size',
                'value_1' => 'XL',
                'attribute_key_2' => 'upcs',
                'value_2' => '123456789012',
            ],
        ];

        // Act
        $variants = $this->processor->processVariants($stockRecords, $imageRecords, $priceRecords, $concreteRecords);

        // Assert
        $expectedVariant = new ShopifyVariant(
            'AB130',
            'SKU130',
            'Variant with UPC',
            0,
            '8',
            ['name' => 'Warehouse'],
            '0',
            '79.99',
            'Shopify',
            'DENY',
            true,
            true,
            true,
            null,
            null,
            '123456789012', // upc from value_2
            '0.00',
            ['Size' => 'XL'], // Option does not include 'upcs'
            date('Y-m-d H:i:s'),
            null,
            null,
            null
        );

        $this->assertCount(1, $variants);
        $this->assertEquals($expectedVariant, $variants[0]);
    }
    public function testProcessVariantsWithAttributeKey2Option(): void
    {
        // Arrange
        $stockRecords = [
            [
                'concrete_sku' => 'SKU131',
                'quantity' => '12',
                'name' => 'Warehouse',
                'is_never_out_of_stock' => '0',
            ],
        ];

        $imageRecords = [];

        $priceRecords = [
            [
                'abstract_sku' => 'AB131',
                'value_gross' => '49.99',
                'price_type' => 'DEFAULT',
            ],
        ];

        $concreteRecords = [
            [
                'abstract_sku' => 'AB131',
                'concrete_sku' => 'SKU131',
                'name.en_US' => 'Variant with Attribute Key 2',
                // 'attribute_key_1' is not set or is empty
                'attribute_key_2' => 'Material',
                'value_2' => 'Cotton',
            ],
        ];

        // Act
        $variants = $this->processor->processVariants($stockRecords, $imageRecords, $priceRecords, $concreteRecords);

        // Assert
        $expectedVariant = new ShopifyVariant(
            'AB131',
            'SKU131',
            'Variant with Attribute Key 2',
            0,
            '12',
            ['name' => 'Warehouse'],
            '0',
            '49.99',
            'Shopify',
            'DENY',
            true,
            true,
            true,
            null,
            null,
            'Not Available',
            '0.00',
            ['Material' => 'Cotton'], // Options from attribute_key_2 and value_2
            date('Y-m-d H:i:s'),
            null,
            null,
            null
        );

        $this->assertCount(1, $variants);
        $this->assertEquals($expectedVariant, $variants[0]);
    }

}
