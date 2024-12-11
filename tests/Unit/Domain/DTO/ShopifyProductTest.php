<?php
declare(strict_types=1);

namespace App\Tests\Unit\Domain\DTO;

use App\Domain\DTO\LocalizedString;
use App\Domain\DTO\ShopifyProduct;
use App\Domain\DTO\ShopifyVariant;
use PHPUnit\Framework\TestCase;

class ShopifyProductTest extends TestCase
{
    public function testConstructorAssignsAllProperties(): void
    {
        $abstractSku = 'abstract-sku';
        $title = new LocalizedString('Test Title', 'test-handle');
        $bodyHtml = new LocalizedString('Body content', 'test-handle');
        $vendor = 'Test Vendor';
        $price = '19.99';
        $compareAtPrice = '24.99';
        $productType = 'Test Type';
        $isGiftCard = true;
        $handle = new LocalizedString('test-handle', 'test-handle');
        $status = 'active';
        $publishedScope = 'global';
        $variants = [
            new ShopifyVariant(
                'abstract-sku',
                'concrete-sku',
                'Test Variant',
                1,
                '10',
                ['location'],
                'true',
                '19.99'
            )
        ];
        $imageUrl = 'https://example.com/image.jpg';
        $attributes = ['color' => 'red', 'size' => 'M'];
        $tags = new LocalizedString('tag1,tag2', 'test-handle');
        $id = '123';
        $createdAt = '2024-12-11T12:00:00Z';
        $updatedAt = '2024-12-12T12:00:00Z';
        $publishedAt = '2024-12-13T12:00:00Z';
        $categoryProductOrder = 'order-123';
        $taxSetName = 'default-tax';
        $isBundle = true;
        $newFrom = '2024-12-01';
        $newTo = '2024-12-31';

        $product = new ShopifyProduct(
            $abstractSku,
            $title,
            $bodyHtml,
            $vendor,
            $price,
            $compareAtPrice,
            $productType,
            $isGiftCard,
            $handle,
            $status,
            $publishedScope,
            $variants,
            $imageUrl,
            $attributes,
            $tags,
            $id,
            $createdAt,
            $updatedAt,
            $publishedAt,
            $categoryProductOrder,
            $taxSetName,
            $isBundle,
            $newFrom,
            $newTo
        );

        $this->assertSame($abstractSku, $product->abstractSku);
        $this->assertSame($title, $product->title);
        $this->assertSame($bodyHtml, $product->bodyHtml);
        $this->assertSame($vendor, $product->vendor);
        $this->assertSame($price, $product->price);
        $this->assertSame($compareAtPrice, $product->compareAtPrice);
        $this->assertSame($productType, $product->productType);
        $this->assertSame($isGiftCard, $product->isGiftCard);
        $this->assertSame($handle, $product->handle);
        $this->assertSame($status, $product->status);
        $this->assertSame($publishedScope, $product->publishedScope);
        $this->assertSame($variants, $product->variants);
        $this->assertSame($imageUrl, $product->imageUrl);
        $this->assertSame($attributes, $product->attributes);
        $this->assertSame($tags, $product->tags);
        $this->assertSame($id, $product->id);
        $this->assertSame($createdAt, $product->createdAt);
        $this->assertSame($updatedAt, $product->updatedAt);
        $this->assertSame($publishedAt, $product->publishedAt);
        $this->assertSame($categoryProductOrder, $product->categoryProductOrder);
        $this->assertSame($taxSetName, $product->taxSetName);
        $this->assertSame($isBundle, $product->isBundle);
        $this->assertSame($newFrom, $product->newFrom);
        $this->assertSame($newTo, $product->newTo);
    }

    public function testConstructorAssignsDefaultValues(): void
    {
        $product = new ShopifyProduct(
            'abstract-sku',
            new LocalizedString('Test Title', 'test-handle'),
            new LocalizedString('Body content', 'test-handle'),
            'Test Vendor',
            '19.99',
            null,
            'Test Type',
            null,
            null,
            null,
            null,
            null,
            null,
            [],
            new LocalizedString('dfwe', 'test-handle'),
        );

        $this->assertNull($product->compareAtPrice);
        $this->assertNull($product->isGiftCard);
        $this->assertNull($product->handle);
        $this->assertNull($product->status);
        $this->assertNull($product->publishedScope);
        $this->assertNull($product->variants);
        $this->assertNull($product->imageUrl);
        $this->assertSame([], $product->attributes);
        $this->assertNull($product->id);
        $this->assertNull($product->createdAt);
        $this->assertNull($product->updatedAt);
        $this->assertNull($product->publishedAt);
        $this->assertNull($product->categoryProductOrder);
        $this->assertNull($product->taxSetName);
        $this->assertFalse($product->isBundle);
        $this->assertNull($product->newFrom);
        $this->assertNull($product->newTo);
    }
}
