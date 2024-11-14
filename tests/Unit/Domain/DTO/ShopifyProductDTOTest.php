<?php
declare(strict_types=1);

namespace App\Tests\Unit\Domain\DTO;

use App\Domain\DTO\ShopifyProductDTO;
use App\Domain\DTO\ShopifyVariantDTO;
use PHPUnit\Framework\TestCase;

class ShopifyProductDTOTest extends TestCase
{
    public function testToArrayWithAllPropertiesSet(): void
    {
        $variant = new ShopifyVariantDTO(
            'SKU123',
            '99.99',
            10,
            'SHOPIFY',
            'DENY',
            null,
            [
                [
                    'namespace' => 'custom',
                    'key' => 'variant_metafield_key',
                    'value' => 'variant_metafield_value',
                    'type' => 'string',
                ],
            ]
        );

        $productDTO = new ShopifyProductDTO(
            'Test Product Title',
            '<p>Test Product Description</p>',
            'Test Vendor',
            'Test Product Type',
            ['Tag1', 'Tag2'],
            [$variant],
            [
                ['src' => 'http://example.com/image1.jpg'],
                ['src' => 'http://example.com/image2.jpg'],
            ],
            [
                [
                    'namespace' => 'custom',
                    'key' => 'product_metafield_key',
                    'value' => 'product_metafield_value',
                    'type' => 'string',
                ],
            ]
        );

        $expectedArray = [
            'title' => 'Test Product Title',
            'bodyHtml' => '<p>Test Product Description</p>',
            'vendor' => 'Test Vendor',
            'productType' => 'Test Product Type',
            'tags' => ['Tag1', 'Tag2'],
            'variants' => [
                [
                    'sku' => 'SKU123',
                    'price' => '99.99',
                    'inventoryQuantity' => 10,
                    'inventoryManagement' => 'SHOPIFY',
                    'inventoryPolicy' => 'DENY',
                    'imageId' => null,
                    'metafields' => [
                        [
                            'namespace' => 'custom',
                            'key' => 'variant_metafield_key',
                            'value' => 'variant_metafield_value',
                            'type' => 'string',
                        ],
                    ],
                ],
            ],
            'images' => [
                ['src' => 'http://example.com/image1.jpg'],
                ['src' => 'http://example.com/image2.jpg'],
            ],
            'metafields' => [
                [
                    'namespace' => 'custom',
                    'key' => 'product_metafield_key',
                    'value' => 'product_metafield_value',
                    'type' => 'string',
                ],
            ],
        ];

        $actualArray = $productDTO->toArray();

        $this->assertEquals($expectedArray, $actualArray);
    }

    public function testToArrayWithOptionalPropertiesNullOrEmpty(): void
    {
        $productDTO = new ShopifyProductDTO(
            'Test Product Title',
            '<p>Test Product Description</p>',
            null, // vendor
            null, // productType
            [],   // tags
            [],   // variants
            [],   // images
            []    // metafields
        );

        $expectedArray = [
            'title' => 'Test Product Title',
            'bodyHtml' => '<p>Test Product Description</p>',
            'vendor' => null,
            'productType' => null,
            'tags' => [],
            'variants' => [],
            'images' => [],
            'metafields' => [],
        ];

        $actualArray = $productDTO->toArray();

        $this->assertEquals($expectedArray, $actualArray);
    }

    public function testToArrayWithMultipleVariants(): void
    {
        $variant1 = new ShopifyVariantDTO(
            'SKU123',
            '99.99',
            10
        );

        $variant2 = new ShopifyVariantDTO(
            'SKU456',
            '79.99',
            5
        );

        $productDTO = new ShopifyProductDTO(
            'Test Product Title',
            '<p>Test Product Description</p>',
            'Test Vendor',
            'Test Product Type',
            ['Tag1', 'Tag2'],
            [$variant1, $variant2],
            [],
            []
        );

        $expectedArray = [
            'title' => 'Test Product Title',
            'bodyHtml' => '<p>Test Product Description</p>',
            'vendor' => 'Test Vendor',
            'productType' => 'Test Product Type',
            'tags' => ['Tag1', 'Tag2'],
            'variants' => [
                [
                    'sku' => 'SKU123',
                    'price' => '99.99',
                    'inventoryQuantity' => 10,
                    'inventoryManagement' => 'SHOPIFY',
                    'inventoryPolicy' => 'DENY',
                    'imageId' => null,
                    'metafields' => [],
                ],
                [
                    'sku' => 'SKU456',
                    'price' => '79.99',
                    'inventoryQuantity' => 5,
                    'inventoryManagement' => 'SHOPIFY',
                    'inventoryPolicy' => 'DENY',
                    'imageId' => null,
                    'metafields' => [],
                ],
            ],
            'images' => [],
            'metafields' => [],
        ];

        $actualArray = $productDTO->toArray();

        $this->assertEquals($expectedArray, $actualArray);
    }

    public function testToArrayWithNestedMetafields(): void
    {
        $variant = new ShopifyVariantDTO(
            'SKU123',
            '99.99',
            10,
            'SHOPIFY',
            'DENY',
            null,
            [
                [
                    'namespace' => 'variant_namespace',
                    'key' => 'variant_key',
                    'value' => 'variant_value',
                    'type' => 'string',
                ],
            ]
        );

        $productDTO = new ShopifyProductDTO(
            'Test Product Title',
            '<p>Test Product Description</p>',
            'Test Vendor',
            'Test Product Type',
            ['Tag1', 'Tag2'],
            [$variant],
            [],
            [
                [
                    'namespace' => 'product_namespace',
                    'key' => 'product_key',
                    'value' => 'product_value',
                    'type' => 'string',
                ],
            ]
        );

        $expectedArray = [
            'title' => 'Test Product Title',
            'bodyHtml' => '<p>Test Product Description</p>',
            'vendor' => 'Test Vendor',
            'productType' => 'Test Product Type',
            'tags' => ['Tag1', 'Tag2'],
            'variants' => [
                [
                    'sku' => 'SKU123',
                    'price' => '99.99',
                    'inventoryQuantity' => 10,
                    'inventoryManagement' => 'SHOPIFY',
                    'inventoryPolicy' => 'DENY',
                    'imageId' => null,
                    'metafields' => [
                        [
                            'namespace' => 'variant_namespace',
                            'key' => 'variant_key',
                            'value' => 'variant_value',
                            'type' => 'string',
                        ],
                    ],
                ],
            ],
            'images' => [],
            'metafields' => [
                [
                    'namespace' => 'product_namespace',
                    'key' => 'product_key',
                    'value' => 'product_value',
                    'type' => 'string',
                ],
            ],
        ];

        $actualArray = $productDTO->toArray();

        $this->assertEquals($expectedArray, $actualArray);
    }
}