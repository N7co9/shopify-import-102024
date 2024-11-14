<?php
declare(strict_types=1);

namespace App\Tests\Unit\Application\Mapper;

use App\Application\Mapper\ProductToShopifyMapper;
use App\Application\Product\Cache\ProductCacheInterface;
use App\Domain\DTO\AbstractProductDTO;
use App\Domain\DTO\ConcreteProductDTO;
use App\Domain\DTO\ShopifyProductDTO;
use PHPUnit\Framework\TestCase;

class ProductToShopifyMapperTest extends TestCase
{
    public function testMapToShopifyProductDTOWithCompleteData(): void
    {
        $abstractSku = 'TEST_SKU';

        $abstractProductDTO = $this->createMock(AbstractProductDTO::class);
        $abstractProductDTO->method('getAbstractSku')->willReturn($abstractSku);
        $abstractProductDTO->method('getNameEn')->willReturn('Product Name');
        $abstractProductDTO->method('getDescriptionEn')->willReturn('Product Description');
        $abstractProductDTO->method('getCategoryKey')->willReturn('Category');
        $abstractProductDTO->method('getLabels')->willReturn(['Tag1', 'Tag2']);

        $concreteProductDTO = $this->createMock(ConcreteProductDTO::class);
        $concreteProductDTO->method('getConcreteSku')->willReturn('SKU123');
        $concreteProductDTO->method('getPriceGross')->willReturn(99.99);
        $concreteProductDTO->method('getQuantity')->willReturn(10);
        $concreteProductDTO->method('isNeverOutOfStock')->willReturn(false);
        $concreteProductDTO->method('isSearchableEn')->willReturn(true);
        $concreteProductDTO->method('getImageUrl')->willReturn('http://example.com/image.jpg');

        $productCache = $this->createMock(ProductCacheInterface::class);
        $productCache->method('getAbstractProduct')->with($abstractSku)->willReturn($abstractProductDTO);
        $productCache->method('getConcreteProducts')->with($abstractSku)->willReturn([$concreteProductDTO]);

        $mapper = new ProductToShopifyMapper($productCache);

        $shopifyProductDTO = $mapper->mapToShopifyProductDTO($abstractSku);

        $this->assertInstanceOf(ShopifyProductDTO::class, $shopifyProductDTO);
        $this->assertEquals('Product Name', $shopifyProductDTO->getTitle());
        $this->assertCount(1, $shopifyProductDTO->getVariants());
    }

    public function testMapToShopifyProductDTOWithIncompleteData(): void
    {
        $abstractSku = 'TEST_SKU';

        $productCache = $this->createMock(ProductCacheInterface::class);
        $productCache->method('getAbstractProduct')->with($abstractSku)->willReturn(null);
        $productCache->method('getConcreteProducts')->with($abstractSku)->willReturn([]);

        $mapper = new ProductToShopifyMapper($productCache);

        $shopifyProductDTO = $mapper->mapToShopifyProductDTO($abstractSku);

        $this->assertNull($shopifyProductDTO);
    }
}