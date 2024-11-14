<?php
declare(strict_types=1);

namespace App\Tests\Unit\Domain\DTO;

use App\Domain\DTO\ShopifyVariantDTO;
use PHPUnit\Framework\TestCase;

class ShopifyVariantDTOTest extends TestCase
{
    public function testToArrayWithAllPropertiesSet(): void
    {
        $variantDTO = new ShopifyVariantDTO(
            'SKU123',
            '99.99',
            10,
            'SHOPIFY',
            'CONTINUE',
            'IMAGE123',
            [
                [
                    'namespace' => 'variant_namespace',
                    'key' => 'variant_key',
                    'value' => 'variant_value',
                    'type' => 'string',
                ],
            ]
        );

        $expectedArray = [
            'sku' => 'SKU123',
            'price' => '99.99',
            'inventoryQuantity' => 10,
            'inventoryManagement' => 'SHOPIFY',
            'inventoryPolicy' => 'CONTINUE',
            'imageId' => 'IMAGE123',
            'metafields' => [
                [
                    'namespace' => 'variant_namespace',
                    'key' => 'variant_key',
                    'value' => 'variant_value',
                    'type' => 'string',
                ],
            ],
        ];

        $actualArray = $variantDTO->toArray();

        $this->assertEquals($expectedArray, $actualArray);
    }

    public function testToArrayWithDefaultValues(): void
    {
        $variantDTO = new ShopifyVariantDTO(
            'SKU456',
            '79.99',
            5
        );

        $expectedArray = [
            'sku' => 'SKU456',
            'price' => '79.99',
            'inventoryQuantity' => 5,
            'inventoryManagement' => 'SHOPIFY',
            'inventoryPolicy' => 'DENY',
            'imageId' => null,
            'metafields' => [],
        ];

        $actualArray = $variantDTO->toArray();

        $this->assertEquals($expectedArray, $actualArray);
    }

    public function testToArrayWithMetafields(): void
    {
        $variantDTO = new ShopifyVariantDTO(
            'SKU789',
            '59.99',
            15,
            'SHOPIFY',
            'DENY',
            null,
            [
                [
                    'namespace' => 'variant_namespace',
                    'key' => 'stock_location',
                    'value' => 'Warehouse A',
                    'type' => 'string',
                ],
                [
                    'namespace' => 'variant_namespace',
                    'key' => 'expiry_date',
                    'value' => '2024-12-31',
                    'type' => 'date',
                ],
            ]
        );

        $expectedArray = [
            'sku' => 'SKU789',
            'price' => '59.99',
            'inventoryQuantity' => 15,
            'inventoryManagement' => 'SHOPIFY',
            'inventoryPolicy' => 'DENY',
            'imageId' => null,
            'metafields' => [
                [
                    'namespace' => 'variant_namespace',
                    'key' => 'stock_location',
                    'value' => 'Warehouse A',
                    'type' => 'string',
                ],
                [
                    'namespace' => 'variant_namespace',
                    'key' => 'expiry_date',
                    'value' => '2024-12-31',
                    'type' => 'date',
                ],
            ],
        ];

        $actualArray = $variantDTO->toArray();

        $this->assertEquals($expectedArray, $actualArray);
    }
}