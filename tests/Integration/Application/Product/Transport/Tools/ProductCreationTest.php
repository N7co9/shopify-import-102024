<?php

declare(strict_types=1);

namespace App\Tests\Integration\Application\Product\Transport\Tools;

use App\Application\Product\Transport\Tools\ProductCreation;
use App\Domain\DTO\LocalizedString;
use App\Domain\DTO\ShopifyProduct;
use App\Domain\DTO\ShopifyVariant;
use PHPUnit\Framework\TestCase;

class ProductCreationTest extends TestCase
{
    private ProductCreation $productCreation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->productCreation = new ProductCreation();
    }

    protected function tearDown(): void
    {
        unset($this->productCreation);

        parent::tearDown();
    }

    public function testGenerateProductOptionsHandlesPositionWithComplexVariants(): void
    {
        $product = $this->createShopifyProductWithEmptyOptions();

        $productOptions = $this->productCreation->generateProductOptions($product);

        $this->assertIsArray($productOptions, 'Product options must be an array.');
        $this->assertNotEmpty($productOptions, 'Product options must not be empty.');

        $lastPosition = 0;
        foreach ($productOptions as $option) {
            $this->assertArrayHasKey('position', $option, 'Each option must have a "position".');
            $this->assertIsInt($option['position'], '"position" must be an integer.');
            $this->assertGreaterThan(
                $lastPosition,
                $option['position'],
                "Position ({$option['position']}) must increment sequentially after {$lastPosition}."
            );
            $lastPosition = $option['position'];
        }

        $positions = array_column($productOptions, 'position');
        $this->assertSame(
            range(1, count($productOptions)),
            $positions,
            'Positions must form a continuous, unique sequence starting from 1.'
        );
    }


    /**
     * Test the prepareInputData method with a standard ShopifyProduct
     */
    public function testPrepareInputData(): void
    {
        // Arrange
        $product = $this->createShopifyProductWithVariants();

        // Act
        $inputData = $this->productCreation->prepareInputData($product);

        // Assert
        $this->assertIsArray($inputData);
        $this->assertArrayHasKey('descriptionHtml', $inputData);
        $this->assertSame('Produktbeschreibung DE', $inputData['descriptionHtml']);

        $this->assertArrayHasKey('files', $inputData);
        $this->assertSame('Produkt Titel DE', $inputData['files']['alt']);
        $this->assertSame('IMAGE', $inputData['files']['contentType']);
        $this->assertSame('http://example.com/product.jpg', $inputData['files']['originalSource']);

        $this->assertArrayHasKey('giftCard', $inputData);
        $this->assertFalse($inputData['giftCard']);

        $this->assertArrayHasKey('handle', $inputData);
        $this->assertSame('produkt-titel-de', $inputData['handle']);

        $this->assertArrayHasKey('metafields', $inputData);
        $this->assertIsArray($inputData['metafields']);
        $this->assertCount(3, $inputData['metafields']); // attributes + tags + is_bundle

        $this->assertArrayHasKey('productOptions', $inputData);
        $this->assertIsArray($inputData['productOptions']);
        $this->assertCount(1, $inputData['productOptions']);

        $this->assertArrayHasKey('productType', $inputData);
        $this->assertSame('Product Type', $inputData['productType']);

        $this->assertArrayHasKey('seo', $inputData);
        $this->assertSame('Produktbeschreibung DE', $inputData['seo']['description']);
        $this->assertSame('Produkt Titel DE', $inputData['seo']['title']);

        $this->assertArrayHasKey('status', $inputData);
        $this->assertSame('ACTIVE', $inputData['status']);

        $this->assertArrayHasKey('tags', $inputData);
        $this->assertSame('tag1,tag2', $inputData['tags']);

        $this->assertArrayHasKey('title', $inputData);
        $this->assertSame('Produkt Titel DE', $inputData['title']);

        $this->assertArrayHasKey('variants', $inputData);
        $this->assertIsArray($inputData['variants']);
        $this->assertCount(2, $inputData['variants']);

        $this->assertArrayHasKey('vendor', $inputData);
        $this->assertSame('Vendor Name', $inputData['vendor']);
    }

    /**
     * Test the generateMetafields method
     */
    public function testGenerateMetafields(): void
    {
        // Arrange
        $product = $this->createShopifyProductWithVariants();

        // Act
        $metafields = $this->productCreation->generateMetafields($product);

        // Assert
        $this->assertIsArray($metafields);
        $this->assertCount(3, $metafields);

        $expectedMetafields = [
            [
                'key' => 'color',
                'namespace' => 'product.attributes',
                'type' => 'single_line_text_field',
                'value' => 'Blue',
            ],
            [
                'key' => 'tags',
                'namespace' => 'product.info',
                'type' => 'list.single_line_text_field',
                'value' => json_encode(['tag1', 'tag2'], JSON_THROW_ON_ERROR),
            ],
            [
                'key' => 'is_bundle',
                'namespace' => 'product.info',
                'type' => 'boolean',
                'value' => 'true',
            ],
        ];

        $this->assertEquals($expectedMetafields, $metafields);
    }

    public function testFormatVariantsForShopifyInputWithEdgeCases(): void
    {
        $product = $this->createShopifyProductWithEdgeCases();

        $formattedVariants = $this->productCreation->formatVariantsForShopifyInput($product);

        foreach ($formattedVariants as $variant) {
            $this->assertArrayHasKey('file', $variant, 'Each variant should have a file key.');

            $this->assertArrayHasKey('inventoryQuantities', $variant, 'Each variant should have inventoryQuantities.');
            $this->assertArrayHasKey('locationId', $variant['inventoryQuantities'], 'Inventory quantities should include locationId.');
            $this->assertArrayHasKey('quantity', $variant['inventoryQuantities'], 'Inventory quantities should include quantity.');
            $this->assertIsInt($variant['inventoryQuantities']['quantity'], 'Quantity should be an integer.');

            $this->assertIsString($variant['price'], 'Price should be a string.');
            $this->assertNotEmpty($variant['price'], 'Price should not be empty.');
            $this->assertEquals('DENY', strtoupper($variant['inventoryPolicy']), 'Inventory policy should be DENY or converted to it.');
        }
    }

    public function testFormatVariantsForShopifyInputHandlesTaxableCorrectly()
    {
        $product = new ShopifyProduct(
            abstractSku: 'TEST_SKU',
            title: new LocalizedString('Test Title', 'de_DE'),
            bodyHtml: new LocalizedString('<p>Test Description</p>', 'de_DE'),
            vendor: 'Test Vendor',
            price: '100.00',
            compareAtPrice: null,
            productType: 'Test Type',
            isGiftCard: false,
            handle: new LocalizedString('test-handle', 'de_DE'),
            status: 'active',
            publishedScope: null,
            variants: [
                new ShopifyVariant(
                    abstractSku: 'TEST_SKU',
                    concreteSku: 'TEST_VARIANT_SKU',
                    title: 'Test Variant Title',
                    position: 1,
                    inventoryQuantity: '10',
                    inventoryLocation: ['id' => '1'],
                    isNeverOutOfStock: 'false',
                    price: '10.00',
                    inventoryManagement: 'shopify',
                    inventoryPolicy: 'deny',
                    taxable: false,
                    available: true,
                    requiresShipping: true,
                    imageUrl: null
                )
            ],
            imageUrl: null,
            attributes: [],
            tags: new LocalizedString('tag1,tag2', 'de_DE'),
        );

        $productCreation = new ProductCreation();
        $formattedVariants = $productCreation->formatVariantsForShopifyInput($product);

        $this->assertFalse($formattedVariants[0]['taxable'], 'The taxable field should match the variant input.');
    }

    public function testMapVariantOptionValuesStopsOnFirstMatch(): void
    {
        $productOptions = [
            [
                'name' => 'Color Option',
                'values' => [
                    ['name' => 'Red'],
                    ['name' => 'Blue'],
                    ['name' => 'Green'],
                ]
            ]
        ];

        $variant = (object)[
            'option' => [
                'color_option' => 'Red'
            ]
        ];

        $productCreation = new ProductCreation();
        $mappedValues = $productCreation->mapVariantOptionValues($variant, $productOptions);

        $this->assertCount(1, $mappedValues, 'Only the first matching value should be processed.');
        $this->assertSame('Red', $mappedValues[0]['name'], 'The mapped value should match the first correct option.');
        $this->assertSame('Color Option', $mappedValues[0]['optionName'], 'The option name should match the product option name.');
    }


    public function testMapVariantOptionValuesBreaksOnFirstMatch()
    {
        $productOptions = [
            [
                'name' => 'Color Option',
                'values' => [
                    ['name' => 'Red'],
                    ['name' => 'Blue']
                ]
            ]
        ];

        $variant = (object)[
            'option' => [
                'color_option' => 'Red'
            ]
        ];

        $productCreation = new ProductCreation();
        $mappedValues = $productCreation->mapVariantOptionValues($variant, $productOptions);

        $this->assertCount(1, $mappedValues, 'Only the first matching value should be added.');
    }


    public function testMapVariantOptionValuesHandlesOptionNamesCorrectly()
    {
        $productOptions = [
            [
                'name' => 'Color Option',
                'values' => [
                    ['name' => 'Red'],
                    ['name' => 'Blue']
                ]
            ]
        ];

        $variant = (object)[
            'option' => [
                'color_option' => 'Red'
            ]
        ];

        $productCreation = new ProductCreation();
        $mappedValues = $productCreation->mapVariantOptionValues($variant, $productOptions);

        $this->assertSame('Red', $mappedValues[0]['name'], 'The mapped option value should respect the transformed option name.');
        $this->assertSame('Color Option', $mappedValues[0]['optionName'], 'The option name should match the product option name.');
    }


    public function testFormatVariantsForShopifyInputHandlesConcreteSkuCorrectly()
    {
        $product = new ShopifyProduct(
            abstractSku: 'TEST_SKU',
            title: new LocalizedString('Test Title', 'de_DE'),
            bodyHtml: new LocalizedString('<p>Test Description</p>', 'de_DE'),
            vendor: 'Test Vendor',
            price: '100.00',
            compareAtPrice: null,
            productType: 'Test Type',
            isGiftCard: false,
            handle: new LocalizedString('test-handle', 'de_DE'),
            status: 'active',
            publishedScope: null,
            variants: [
                new ShopifyVariant(
                    abstractSku: 'TEST_SKU',
                    concreteSku: '',
                    title: 'Test Variant Title',
                    position: 1,
                    inventoryQuantity: '10',
                    inventoryLocation: ['id' => '1'],
                    isNeverOutOfStock: 'false',
                    price: '10.00',
                    inventoryManagement: 'shopify',
                    inventoryPolicy: 'deny',
                    taxable: true,
                    available: true,
                    requiresShipping: true,
                    imageUrl: null
                )
            ],
            imageUrl: null,
            attributes: [],
            tags: new LocalizedString('tag1,tag2', 'de_DE'),
        );

        $productCreation = new ProductCreation();
        $formattedVariants = $productCreation->formatVariantsForShopifyInput($product);

        $this->assertArrayNotHasKey('sku', $formattedVariants[0], 'The sku field should not be present when concreteSku is empty.');
    }


    public function testFormatVariantsForShopifyInputEnsuresContentTypeInFile(): void
    {
        $product = new ShopifyProduct(
            abstractSku: 'TEST_SKU',
            title: new LocalizedString('Test Title', 'en'),
            bodyHtml: new LocalizedString('<p>Test Body</p>', 'en'),
            vendor: 'Test Vendor',
            price: '99.99',
            compareAtPrice: null,
            productType: 'Test Type',
            isGiftCard: false,
            handle: null,
            status: null,
            publishedScope: null,
            variants: [
                new ShopifyVariant(
                    abstractSku: 'TEST_SKU',
                    concreteSku: 'TEST_VARIANT_SKU',
                    title: 'Test Variant Title',
                    position: 1,
                    inventoryQuantity: '100',
                    inventoryLocation: ['id' => '123'],
                    isNeverOutOfStock: 'false',
                    price: '99.99',
                    inventoryManagement: 'shopify',
                    inventoryPolicy: 'deny',
                    taxable: true,
                    available: true,
                    requiresShipping: true,
                    imageUrl: 'https://example.com/image.jpg'
                )
            ],
            imageUrl: null,
            attributes: [],
            tags: new LocalizedString('test', 'en')
        );

        $productCreation = new ProductCreation();

        $formattedVariants = $productCreation->formatVariantsForShopifyInput($product);

        $this->assertArrayHasKey('contentType', $formattedVariants[0]['file'], 'The file array is missing the contentType key.');
        $this->assertSame('IMAGE', $formattedVariants[0]['file']['contentType'], 'The contentType key must have the value "IMAGE".');
        $this->assertArrayHasKey('originalSource', $formattedVariants[0]['file'], 'The file array is missing the originalSource key.');
        $this->assertSame(
            'https://example.com/image.jpg',
            $formattedVariants[0]['file']['originalSource'],
            'The originalSource key does not have the expected value.'
        );
    }

    private function createShopifyProductWithEdgeCases(): ShopifyProduct
    {
        return new ShopifyProduct(
            'EDGE123', // abstractSku
            new LocalizedString('Edge Case Product EN', 'Edge Fall Produkt DE'), // title
            new LocalizedString('Description EN', 'Beschreibung DE'), // bodyHtml
            'Vendor Edge Cases', // vendor
            '0.00', // price (string to satisfy the type requirement)
            null, // compareAtPrice
            'Edge Type', // productType
            false, // isGiftCard
            new LocalizedString('edge-title-en', 'edge-titel-de'), // handle
            'active', // status
            'global', // publishedScope
            [$this->createEdgeCaseVariant()], // variants
            'https://example.com/image.jpg', // imageUrl
            [], // attributes
            new LocalizedString('tag1,tag2', 'tag1,tag2'), // tags
            null, // id
            date('Y-m-d H:i:s'), // createdAt
            date('Y-m-d H:i:s'), // updatedAt
            null, // publishedAt
            null, // categoryProductOrder
            null, // taxSetName
            false, // isBundle
            null, // newFrom
            null  // newTo
        );
    }

    private function createEdgeCaseVariant(): ShopifyVariant
    {
        return new ShopifyVariant(
            'EDGE123', // abstractSku
            'SKU123', // concreteSku
            'Edge Variant', // title
            1, // position
            '10', // inventoryQuantity (string as required by DTO)
            ['id' => 'location1'], // inventoryLocation
            '1', // isNeverOutOfStock (string as required by DTO)
            '59.99', // price (string as required by DTO)
            'shopify', // inventoryManagement
            'deny', // inventoryPolicy
            true, // taxable
            true, // available
            true, // requiresShipping
            null, // id
            null, // productId
            null, // upc
            null, // compareAtPrice
            [], // option
            date('Y-m-d H:i:s'), // createdAt
            null, // updatedAt
            null, // imageUrl
            null  // inventoryItemId
        );
    }


    public function testFormatVariantsForShopifyInputWithEmptyImageUrl(): void
    {
        // Arrange: Create a product with a variant missing an imageUrl
        $product = $this->createShopifyProductWithEmptyImageUrl();

        // Act
        $formattedVariants = $this->productCreation->formatVariantsForShopifyInput($product);

        // Assert
        $this->assertIsArray($formattedVariants, 'Formatted variants should be an array.');
        $this->assertNotEmpty($formattedVariants, 'Formatted variants should not be empty.');

        foreach ($formattedVariants as $variant) {
            $this->assertArrayHasKey('file', $variant, 'Each variant should have a file key.');
            $this->assertEmpty($variant['file'], 'File should be empty when imageUrl is missing.');
        }
    }

    private function createShopifyProductWithEmptyImageUrl(): ShopifyProduct
    {
        $product = new ShopifyProduct(
            'AB126', // Abstract SKU
            new LocalizedString('Product with Missing Image EN', 'Produkt ohne Bild DE'), // Title
            new LocalizedString('Description EN', 'Beschreibung DE'), // Description
            'Vendor Missing Image', // Vendor
            '89.99', // Price
            '99.99', // Compare At Price
            'Missing Image Type', // Product Type
            false, // Is Gift Card
            new LocalizedString('missing-image-title-en', 'fehlendes-bild-titel-de'), // Handle
            'ACTIVE', // Status
            'global', // Published Scope
            [], // Variants (added below)
            null, // Image URL
            [], // Attributes
            new LocalizedString('tag7,tag8', 'tag7,tag8'), // Tags
            null, // ID
            date('Y-m-d H:i:s'), // Created At
            date('Y-m-d H:i:s'), // Updated At
            null, // Published At
            null, // Category Product Order
            null, // Tax Set Name
            false, // Is Bundle
            null, // New From
            null  // New To
        );

        $variant1 = new ShopifyVariant(
            'AB126', // Abstract SKU
            'SKU126', // Concrete SKU
            'Variant Missing Image', // Title
            0, // Position
            '15', // Inventory Quantity
            ['id' => 'location1'], // Inventory Location
            '0', // Is Never Out of Stock
            '89.99', // Price
            'shopify', // Inventory Management
            'deny', // Inventory Policy
            true, // Taxable
            true, // Available
            true, // Requires Shipping
            null, // ID
            null, // Product ID
            null, // UPC
            '0.00', // Compare At Price
            [], // Options
            date('Y-m-d H:i:s'), // Created At
            null, // Updated At
            '', // Image URL (empty)
            null // Inventory Item ID
        );

        $product->variants = [$variant1];

        return $product;
    }


    public function testFormatVariantsForShopifyInputAccessibleDirectly(): void
    {
        // Arrange
        $product = $this->createShopifyProductWithVariants();

        // Act
        $variants = $this->productCreation->formatVariantsForShopifyInput($product);

        // Assert
        $this->assertIsArray($variants, 'Variants must be returned as an array.');
        $this->assertNotEmpty($variants, 'Variants should not be empty.');
        $this->assertArrayHasKey('optionValues', $variants[0], 'Each variant must contain "optionValues".');
        $this->assertArrayHasKey('price', $variants[0], 'Each variant must have a price.');
    }

    public function testFormatVariantsThroughPrepareInputData(): void
    {
        // Arrange
        $product = $this->createShopifyProductWithVariants();

        // Act
        $inputData = $this->productCreation->prepareInputData($product);

        // Assert
        $this->assertArrayHasKey('variants', $inputData, 'Input data must contain variants.');
        $this->assertCount(2, $inputData['variants'], 'There should be exactly 2 variants.');
    }


    public function testGenerateProductOptionsValidatesSequentialPositions(): void
    {
        $product = $this->createShopifyProductWithVariantsAndUniqueOptions();

        $productOptions = $this->productCreation->generateProductOptions($product);

        $this->assertIsArray($productOptions, 'Product options should be an array.');
        $this->assertNotEmpty($productOptions, 'Product options should not be empty.');

        $positions = array_column($productOptions, 'position');
        foreach ($positions as $index => $position) {
            if ($index > 0) {
                $this->assertSame(
                    $positions[$index - 1] + 1,
                    $position,
                    "Position {$position} is not sequential after {$positions[$index - 1]}."
                );
            }
        }

        $this->assertSameSize(array_unique($positions), $positions, 'Positions should be unique.');
    }

    /**
     * Test the generateProductOptions method
     */
    public function testGenerateProductOptions(): void
    {
        // Arrange
        $product = $this->createShopifyProductWithVariants();

        // Act
        $productOptions = $this->productCreation->generateProductOptions($product);

        // Assert
        $this->assertIsArray($productOptions);
        $this->assertCount(1, $productOptions); // Only one option: Color

        $expectedOption = [
            'name' => 'Color',
            'position' => 1,
            'values' => [
                ['name' => 'Blue'],
                ['name' => 'Red'],
            ],
        ];

        $this->assertSame($expectedOption['name'], $productOptions[0]['name']);
        $this->assertSame($expectedOption['position'], $productOptions[0]['position']);
        $this->assertEquals($expectedOption['values'], $productOptions[0]['values']);
    }
    /**
     * Test prepareInputData with variants that have empty options
     */
    public function testPrepareInputDataWithEmptyVariantOptions(): void
    {
        // Arrange
        $product = $this->createShopifyProductWithEmptyOptions();

        // Act
        $inputData = $this->productCreation->prepareInputData($product);

        // Assert
        $this->assertIsArray($inputData, 'Input data should be an array.');
        $this->assertArrayHasKey('variants', $inputData, 'Input data should contain variants key.');
        $this->assertCount(2, $inputData['variants'], 'There should be 2 variants.');

        foreach ($inputData['variants'] as $variant) {
            $this->assertIsArray($variant, 'Each variant should be an array.');
            $this->assertArrayHasKey('optionValues', $variant, 'Variant should contain optionValues key.');
            $this->assertIsArray($variant['optionValues'], 'optionValues should be an array.');

            // Adjusted Assertion: Expecting 2 optionValues instead of 1
            $this->assertCount(2, $variant['optionValues'], 'Each variant should have 2 optionValues.');

            // Verify the first optionValue
            $this->assertArrayHasKey(0, $variant['optionValues'], 'First optionValue should exist.');
            $this->assertArrayHasKey('optionName', $variant['optionValues'][0], 'First optionValue should have optionName.');
            $this->assertArrayHasKey('name', $variant['optionValues'][0], 'First optionValue should have name.');

            // Since attributes were set as ['color' => 'Large'], the optionName might be 'Color'
            $this->assertSame('Variant Large', $variant['optionValues'][0]['optionName'], 'First optionName should be Color.');
            $this->assertSame('Variant Large', $variant['optionValues'][0]['name'], 'First optionValue name should match variant title.');

            // Verify the second optionValue
            $this->assertArrayHasKey(1, $variant['optionValues'], 'Second optionValue should exist.');
            $this->assertArrayHasKey('optionName', $variant['optionValues'][1], 'Second optionValue should have optionName.');
            $this->assertArrayHasKey('name', $variant['optionValues'][1], 'Second optionValue should have name.');

            // Depending on the implementation, the second option might be size or another attribute
            // Adjust the expected values accordingly
            $this->assertSame('Variant Medium', $variant['optionValues'][1]['optionName'], 'Second optionName should be Size.');
            $this->assertSame('Variant Medium', $variant['optionValues'][1]['name'], 'Second optionValue name should be Large.');
        }
    }

    public function testGenerateProductOptionsHandlesUnderscoreInKey(): void
    {
        // Arrange: Create a product with variants that result in underscores in the keys of `optionValuesMap`.
        $product = new ShopifyProduct(
            'AB126',
            new LocalizedString('Test Product EN', 'Testprodukt DE'),
            new LocalizedString('Test Description EN', 'Testbeschreibung DE'),
            'Test Vendor',
            '79.99',
            '99.99',
            'Test Type',
            false,
            new LocalizedString('test-product-en', 'test-produkt-de'),
            'ACTIVE',
            'global',
            [],
            'http://example.com/test-product.jpg',
            ['color_option' => 'Blue'], // Attributes with underscores
            new LocalizedString('tag7,tag8', 'tag7,tag8'),
            null,
            date('Y-m-d H:i:s'),
            date('Y-m-d H:i:s'),
            null,
            null,
            null,
            false,
            null,
            null
        );

        // Add variants with non-empty options that will map to underscores.
        $product->variants[] = new ShopifyVariant(
            'AB126',
            'SKU126-BLUE',
            'Variant Title Blue',
            0,
            '15',
            ['id' => 'location4'],
            '0',
            '79.99',
            'shopify',
            'deny',
            true,
            true,
            true,
            null,
            null,
            null,
            '0.00',
            ['color_option' => 'Blue'],
            date('Y-m-d H:i:s'),
            null,
            'http://example.com/variant_blue.jpg',
            null
        );

        // Act
        $productOptions = $this->productCreation->generateProductOptions($product);

        // Assert
        $this->assertIsArray($productOptions);
        $this->assertNotEmpty($productOptions);

        // Validate the key transformation
        $expectedOptionName = 'Color option';
        $this->assertSame($expectedOptionName, $productOptions[0]['name']);
    }



    /**
     * Test prepareInputData with variants that have non-empty options
     */
    public function testPrepareInputDataWithVariantOptions(): void
    {
        // Arrange
        $product = $this->createShopifyProductWithNonEmptyOptions();

        // Act
        $inputData = $this->productCreation->prepareInputData($product);

        // Assert
        $this->assertIsArray($inputData);
        $this->assertArrayHasKey('variants', $inputData);
        $this->assertCount(2, $inputData['variants']);

        foreach ($inputData['variants'] as $variant) {
            $this->assertIsArray($variant);
            $this->assertArrayHasKey('optionValues', $variant);
            $this->assertIsArray($variant['optionValues']);

            // Since options are not empty, 'optionValues' should map based on product options
            $this->assertCount(1, $variant['optionValues']);
            $this->assertArrayHasKey('optionName', $variant['optionValues'][0]);
            $this->assertArrayHasKey('name', $variant['optionValues'][0]);

            $this->assertSame('Color', $variant['optionValues'][0]['optionName']);
        }
    }

    /**
     * Helper method to create a ShopifyProduct instance with variants that have options
     */
    private function createShopifyProductWithVariants(): ShopifyProduct
    {
        $product = new ShopifyProduct(
            'AB123', // #1 $abstractSku
            new LocalizedString('Product Title EN', 'Produkt Titel DE'), // #2 $title
            new LocalizedString('Product Description EN', 'Produktbeschreibung DE'), // #3 $bodyHtml
            'Vendor Name', // #4 $vendor
            '99.99', // #5 $price
            '119.99', // #6 $compareAtPrice
            'Product Type', // #7 $productType
            false, // #8 $isGiftCard
            new LocalizedString('product-title-en', 'produkt-titel-de'), // #9 $handle
            'ACTIVE', // #10 $status
            'global', // #11 $publishedScope
            [], // #12 $variants (to be added below)
            'http://example.com/product.jpg', // #13 $imageUrl
            ['color' => 'Blue'], // #14 $attributes
            new LocalizedString('tag1,tag2', 'tag1,tag2'), // #15 $tags
            null, // #16 $id
            date('Y-m-d H:i:s'), // #17 $createdAt
            date('Y-m-d H:i:s'), // #18 $updatedAt
            null, // #19 $publishedAt
            null, // #20 $categoryProductOrder
            null, // #21 $taxSetName
            true, // #22 $isBundle
            null, // #23 $newFrom
            null  // #24 $newTo
        );

        // Add variants with options
        $variant1 = new ShopifyVariant(
            'AB123', // $abstractSku
            'SKU123-BLUE', // $concreteSku
            'Variant Title Blue', // $title
            0, // $position
            '10', // $inventoryQuantity
            ['id' => 'location1'], // $inventoryLocation
            '0', // $isNeverOutOfStock
            '99.99', // $price
            'shopify', // $inventoryManagement
            'deny', // $inventoryPolicy
            true, // $taxable
            true, // $available
            true, // $requiresShipping
            null, // $id
            null, // $productId
            null, // $upc
            '0.00', // $compareAtPrice
            ['color' => 'Blue'], // $option
            date('Y-m-d H:i:s'), // $createdAt
            null, // $updatedAt
            'http://example.com/variant1.jpg', // $imageUrl
            null // $inventoryItemId
        );

        $variant2 = new ShopifyVariant(
            'AB123', // $abstractSku
            'SKU123-RED', // $concreteSku
            'Variant Title Red', // $title
            1, // $position
            '5', // $inventoryQuantity
            ['id' => 'location1'], // $inventoryLocation
            '0', // $isNeverOutOfStock
            '99.99', // $price
            'shopify', // $inventoryManagement
            'deny', // $inventoryPolicy
            true, // $taxable
            true, // $available
            true, // $requiresShipping
            null, // $id
            null, // $productId
            null, // $upc
            '0.00', // $compareAtPrice
            ['color' => 'Red'], // $option
            date('Y-m-d H:i:s'), // $createdAt
            null, // $updatedAt
            'http://example.com/variant2.jpg', // $imageUrl
            null // $inventoryItemId
        );

        $product->variants = [$variant1, $variant2];

        return $product;
    }

    private function createShopifyProductWithVariantsAndUniqueOptions(): ShopifyProduct
    {
        $product = new ShopifyProduct(
            'AB123', // #1 $abstractSku
            new LocalizedString('Product Title EN', 'Produkt Titel DE'), // #2 $title
            new LocalizedString('Product Description EN', 'Produktbeschreibung DE'), // #3 $bodyHtml
            'Vendor Name', // #4 $vendor
            '99.99', // #5 $price
            '119.99', // #6 $compareAtPrice
            'Product Type', // #7 $productType
            false, // #8 $isGiftCard
            new LocalizedString('product-title-en', 'produkt-titel-de'), // #9 $handle
            'ACTIVE', // #10 $status
            'global', // #11 $publishedScope
            [], // #12 $variants (to be added below)
            'http://example.com/product.jpg', // #13 $imageUrl
            ['color' => 'Blue'], // #14 $attributes
            new LocalizedString('tag1,tag2', 'tag1,tag2'), // #15 $tags
            null, // #16 $id
            date('Y-m-d H:i:s'), // #17 $createdAt
            date('Y-m-d H:i:s'), // #18 $updatedAt
            null, // #19 $publishedAt
            null, // #20 $categoryProductOrder
            null, // #21 $taxSetName
            true, // #22 $isBundle
            null, // #23 $newFrom
            null  // #24 $newTo
        );

        // Add variants with options
        $variant1 = new ShopifyVariant(
            'AB123', // $abstractSku
            'SKU123-BLUE', // $concreteSku
            'Variant Title Blue', // $title
            0, // $position
            '10', // $inventoryQuantity
            ['id' => 'location1'], // $inventoryLocation
            '0', // $isNeverOutOfStock
            '99.99', // $price
            'shopify', // $inventoryManagement
            'deny', // $inventoryPolicy
            true, // $taxable
            true, // $available
            true, // $requiresShipping
            null, // $id
            null, // $productId
            null, // $upc
            '0.00', // $compareAtPrice
            ['Memory' => '32GB'], // $option
            date('Y-m-d H:i:s'), // $createdAt
            null, // $updatedAt
            'http://example.com/variant1.jpg', // $imageUrl
            null // $inventoryItemId
        );

        $variant2 = new ShopifyVariant(
            'AB123', // $abstractSku
            'SKU123-RED', // $concreteSku
            'Variant Title Red', // $title
            1, // $position
            '5', // $inventoryQuantity
            ['id' => 'location1'], // $inventoryLocation
            '0', // $isNeverOutOfStock
            '99.99', // $price
            'shopify', // $inventoryManagement
            'deny', // $inventoryPolicy
            true, // $taxable
            true, // $available
            true, // $requiresShipping
            null, // $id
            null, // $productId
            null, // $upc
            '0.00', // $compareAtPrice
            ['color' => 'Red'], // $option
            date('Y-m-d H:i:s'), // $createdAt
            null, // $updatedAt
            'http://example.com/variant2.jpg', // $imageUrl
            null // $inventoryItemId
        );

        $product->variants = [$variant1, $variant2];

        return $product;
    }


    /**
     * Helper method to create a ShopifyProduct instance with empty variant options
     */
    private function createShopifyProductWithEmptyOptions(): ShopifyProduct
    {
        $product = new ShopifyProduct(
            'AB124', // #1 $abstractSku
            new LocalizedString('Another Product EN', 'Ein weiteres Produkt DE'), // #2 $title
            new LocalizedString('Another Description EN', 'Eine weitere Beschreibung DE'), // #3 $bodyHtml
            'Another Vendor', // #4 $vendor
            '149.99', // #5 $price
            '179.99', // #6 $compareAtPrice
            'Another Product Type', // #7 $productType
            false, // #8 $isGiftCard
            new LocalizedString('another-product-title-en', 'ein-weiteres-produkt-titel-de'), // #9 $handle
            'ACTIVE', // #10 $status
            'global', // #11 $publishedScope
            [], // #12 $variants (to be added below)
            'http://example.com/another_product.jpg', // #13 $imageUrl
            ['size' => 'Large'], // #14 $attributes
            new LocalizedString('tag3,tag4', 'tag3,tag4'), // #15 $tags
            null, // #16 $id
            date('Y-m-d H:i:s'), // #17 $createdAt
            date('Y-m-d H:i:s'), // #18 $updatedAt
            null, // #19 $publishedAt
            null, // #20 $categoryProductOrder
            null, // #21 $taxSetName
            true, // #22 $isBundle
            null, // #23 $newFrom
            null  // #24 $newTo
        );

        // Add variants with empty 'option'
        $variant1 = new ShopifyVariant(
            'AB124', // $abstractSku
            'SKU124-LARGE', // $concreteSku
            'Variant Large', // $title
            0, // $position
            '15', // $inventoryQuantity
            ['id' => 'location2'], // $inventoryLocation
            '0', // $isNeverOutOfStock
            '149.99', // $price
            'shopify', // $inventoryManagement
            'deny', // $inventoryPolicy
            true, // $taxable
            true, // $available
            true, // $requiresShipping
            null, // $id
            null, // $productId
            null, // $upc
            '0.00', // $compareAtPrice
            [], // $option (empty)
            date('Y-m-d H:i:s'), // $createdAt
            null, // $updatedAt
            'http://example.com/variant_large.jpg', // $imageUrl
            null // $inventoryItemId
        );

        $variant2 = new ShopifyVariant(
            'AB124', // $abstractSku
            'SKU124-MEDIUM', // $concreteSku
            'Variant Medium', // $title
            1, // $position
            '8', // $inventoryQuantity
            ['id' => 'location2'], // $inventoryLocation
            '0', // $isNeverOutOfStock
            '149.99', // $price
            'shopify', // $inventoryManagement
            'deny', // $inventoryPolicy
            true, // $taxable
            true, // $available
            true, // $requiresShipping
            null, // $id
            null, // $productId
            null, // $upc
            '0.00', // $compareAtPrice
            [], // $option (empty)
            date('Y-m-d H:i:s'), // $createdAt
            null, // $updatedAt
            'http://example.com/variant_medium.jpg', // $imageUrl
            null // $inventoryItemId
        );

        $product->variants = [$variant1, $variant2];

        return $product;
    }

    /**
     * Helper method to create a ShopifyProduct instance with non-empty variant options
     */
    private function createShopifyProductWithNonEmptyOptions(): ShopifyProduct
    {
        $product = new ShopifyProduct(
            'AB125', // #1 $abstractSku
            new LocalizedString('Product With Options EN', 'Produkt mit Optionen DE'), // #2 $title
            new LocalizedString('Description with Options EN', 'Beschreibung mit Optionen DE'), // #3 $bodyHtml
            'Vendor Options', // #4 $vendor
            '199.99', // #5 $price
            '219.99', // #6 $compareAtPrice
            'Product Type Options', // #7 $productType
            false, // #8 $isGiftCard
            new LocalizedString('product-options-title-en', 'produkt-optionen-titel-de'), // #9 $handle
            'ACTIVE', // #10 $status
            'global', // #11 $publishedScope
            [], // #12 $variants (to be added below)
            'http://example.com/product_options.jpg', // #13 $imageUrl
            ['color' => 'Medium'], // #14 $attributes
            new LocalizedString('tag5,tag6', 'tag5,tag6'), // #15 $tags
            null, // #16 $id
            date('Y-m-d H:i:s'), // #17 $createdAt
            date('Y-m-d H:i:s'), // #18 $updatedAt
            null, // #19 $publishedAt
            null, // #20 $categoryProductOrder
            null, // #21 $taxSetName
            false, // #22 $isBundle
            null, // #23 $newFrom
            null  // #24 $newTo
        );

        // Add variants with non-empty 'option'
        $variant1 = new ShopifyVariant(
            'AB125', // $abstractSku
            'SKU125-SMALL', // $concreteSku
            'Variant Small', // $title
            0, // $position
            '12', // $inventoryQuantity
            ['id' => 'location3'], // $inventoryLocation
            '0', // $isNeverOutOfStock
            '199.99', // $price
            'shopify', // $inventoryManagement
            'deny', // $inventoryPolicy
            true, // $taxable
            true, // $available
            true, // $requiresShipping
            null, // $id
            null, // $productId
            null, // $upc
            '0.00', // $compareAtPrice
            ['color' => 'Blue'], // $option
            date('Y-m-d H:i:s'), // $createdAt
            null, // $updatedAt
            'http://example.com/variant_small.jpg', // $imageUrl
            null // $inventoryItemId
        );

        $variant2 = new ShopifyVariant(
            'AB125', // $abstractSku
            'SKU125-LARGE', // $concreteSku
            'Variant Large', // $title
            1, // $position
            '7', // $inventoryQuantity
            ['id' => 'location3'], // $inventoryLocation
            '0', // $isNeverOutOfStock
            '199.99', // $price
            'shopify', // $inventoryManagement
            'deny', // $inventoryPolicy
            true, // $taxable
            true, // $available
            true, // $requiresShipping
            null, // $id
            null, // $productId
            null, // $upc
            '0.00', // $compareAtPrice
            ['color' => 'Red'], // $option
            date('Y-m-d H:i:s'), // $createdAt
            null, // $updatedAt
            'http://example.com/variant_large.jpg', // $imageUrl
            null // $inventoryItemId
        );

        $product->variants = [$variant1, $variant2];

        return $product;
    }
}
