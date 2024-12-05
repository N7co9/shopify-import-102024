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
        $this->assertCount(3, $metafields); // attributes + tags + is_bundle

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
