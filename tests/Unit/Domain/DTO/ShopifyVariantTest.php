<?php
declare(strict_types=1);

namespace App\Tests\Unit\Domain\DTO;

use App\Domain\DTO\ShopifyVariant;
use PHPUnit\Framework\TestCase;

class ShopifyVariantTest extends TestCase
{
    public function testConstructorAssignsAllProperties(): void
    {
        $abstractSku = 'abstract-sku';
        $concreteSku = 'concrete-sku';
        $title = 'Test Product';
        $position = 1;
        $inventoryQuantity = '100';
        $inventoryLocation = ['warehouse-1'];
        $isNeverOutOfStock = 'true';
        $price = '19.99';
        $inventoryManagement = 'shopify';
        $inventoryPolicy = 'DENY';
        $taxable = true;
        $available = true;
        $requiresShipping = true;
        $id = '123';
        $productId = '456';
        $upc = '789';
        $compareAtPrice = '24.99';
        $option = ['Size' => 'L'];
        $createdAt = '2024-12-11T12:00:00Z';
        $updatedAt = '2024-12-12T12:00:00Z';
        $imageUrl = 'https://example.com/image.jpg';
        $inventoryItemId = '789';

        $variant = new ShopifyVariant(
            $abstractSku,
            $concreteSku,
            $title,
            $position,
            $inventoryQuantity,
            $inventoryLocation,
            $isNeverOutOfStock,
            $price,
            $inventoryManagement,
            $inventoryPolicy,
            $taxable,
            $available,
            $requiresShipping,
            $id,
            $productId,
            $upc,
            $compareAtPrice,
            $option,
            $createdAt,
            $updatedAt,
            $imageUrl,
            $inventoryItemId
        );

        $this->assertSame($abstractSku, $variant->abstractSku);
        $this->assertSame($concreteSku, $variant->concreteSku);
        $this->assertSame($title, $variant->title);
        $this->assertSame($position, $variant->position);
        $this->assertSame($inventoryQuantity, $variant->inventoryQuantity);
        $this->assertSame($inventoryLocation, $variant->inventoryLocation);
        $this->assertSame($isNeverOutOfStock, $variant->isNeverOutOfStock);
        $this->assertSame($price, $variant->price);
        $this->assertSame($inventoryManagement, $variant->inventoryManagement);
        $this->assertSame($inventoryPolicy, $variant->inventoryPolicy);
        $this->assertSame($taxable, $variant->taxable);
        $this->assertSame($available, $variant->available);
        $this->assertSame($requiresShipping, $variant->requiresShipping);
        $this->assertSame($id, $variant->id);
        $this->assertSame($productId, $variant->productId);
        $this->assertSame($upc, $variant->upc);
        $this->assertSame($compareAtPrice, $variant->compareAtPrice);
        $this->assertSame($option, $variant->option);
        $this->assertSame($createdAt, $variant->createdAt);
        $this->assertSame($updatedAt, $variant->updatedAt);
        $this->assertSame($imageUrl, $variant->imageUrl);
        $this->assertSame($inventoryItemId, $variant->inventoryItemId);
    }

    public function testConstructorAssignsDefaultValues(): void
    {
        $variant = new ShopifyVariant(
            'abstract-sku',
            'concrete-sku',
            'Test Product',
            1,
            '100',
            ['warehouse-1'],
            'true',
            '19.99'
        );

        $this->assertSame('shopify', $variant->inventoryManagement);
        $this->assertSame('DENY', $variant->inventoryPolicy);
        $this->assertTrue($variant->taxable);
        $this->assertTrue($variant->available);
        $this->assertTrue($variant->requiresShipping);
        $this->assertNull($variant->id);
        $this->assertNull($variant->productId);
        $this->assertNull($variant->upc);
        $this->assertNull($variant->compareAtPrice);
        $this->assertSame([], $variant->option);
        $this->assertNull($variant->createdAt);
        $this->assertNull($variant->updatedAt);
        $this->assertNull($variant->imageUrl);
        $this->assertNull($variant->inventoryItemId);
    }
}
