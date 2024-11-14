<?php
declare(strict_types=1);

namespace App\Tests\Unit\Application\Product\Export;

use App\Application\Mapper\ProductToShopifyMapper;
use App\Application\Product\Cache\ProductCacheInterface;
use App\Application\Product\Transport\ProductMessageProcessor;
use App\Domain\API\GraphQLInterface;
use App\Domain\DTO\ShopifyProductDTO;
use PHPUnit\Framework\TestCase;

class ProductMessageProcessorTest extends TestCase
{
    public function testProcessProductWithIncompleteData(): void
    {
        $abstractSku = 'TEST_SKU';

        $productCache = $this->createMock(ProductCacheInterface::class);

        $mapper = $this->createMock(ProductToShopifyMapper::class);
        $mapper->expects($this->once())
            ->method('mapToShopifyProductDTO')
            ->with($abstractSku)
            ->willReturn(null);

        $graphQLInterface = $this->createMock(GraphQLInterface::class);
        $graphQLInterface->expects($this->never())
            ->method('executeQuery');

        $processor = new ProductMessageProcessor($productCache, $mapper, $graphQLInterface);

        $processor->processProduct($abstractSku);
    }

    public function testProcessProductWithCompleteData(): void
    {
        $abstractSku = 'TEST_SKU';

        $shopifyProductDTO = $this->createMock(ShopifyProductDTO::class);
        $shopifyProductDTO->expects($this->once())
            ->method('toArray')
            ->willReturn(['key' => 'value']);

        $productCache = $this->createMock(ProductCacheInterface::class);
        $productCache->expects($this->once())
            ->method('clearProductCache')
            ->with($abstractSku);

        $mapper = $this->createMock(ProductToShopifyMapper::class);
        $mapper->expects($this->once())
            ->method('mapToShopifyProductDTO')
            ->with($abstractSku)
            ->willReturn($shopifyProductDTO);

        $graphQLInterface = $this->createMock(GraphQLInterface::class);
        $graphQLInterface->expects($this->once())
            ->method('executeQuery')
            ->with(
                $this->isType('string'),
                ['input' => ['key' => 'value']]
            )
            ->willReturn(['data' => ['productCreate' => ['product' => ['id' => '123']]]]);

        $processor = new ProductMessageProcessor($productCache, $mapper, $graphQLInterface);

        $processor->processProduct($abstractSku);
    }

    public function testProcessProductHandlesGraphQLErrors(): void
    {
        $abstractSku = 'TEST_SKU';

        $shopifyProductDTO = $this->createMock(ShopifyProductDTO::class);
        $shopifyProductDTO->method('toArray')->willReturn(['key' => 'value']);

        $productCache = $this->createMock(ProductCacheInterface::class);
        $productCache->expects($this->never())
            ->method('clearProductCache');

        $mapper = $this->createMock(ProductToShopifyMapper::class);
        $mapper->method('mapToShopifyProductDTO')->willReturn($shopifyProductDTO);

        $graphQLInterface = $this->createMock(GraphQLInterface::class);
        $graphQLInterface->method('executeQuery')->willReturn([
            'errors' => [['message' => 'Some error']]
        ]);

        $processor = new ProductMessageProcessor($productCache, $mapper, $graphQLInterface);

        $processor->processProduct($abstractSku);
    }
}