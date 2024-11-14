<?php
declare(strict_types=1);

namespace App\Tests\Integration\Application\Product\Export;

use App\Application\Mapper\ProductToShopifyMapper;
use App\Application\Product\Cache\InMemoryProductCache;
use App\Application\Product\Cache\ProductCacheInterface;
use App\Application\Product\Transport\ProductMessageProcessor;
use App\Domain\API\GraphQLInterface;
use App\Domain\DTO\AbstractProductDTO;
use App\Domain\DTO\ConcreteProductDTO;
use PHPUnit\Framework\TestCase;

class ProductMessageProcessorTest extends TestCase
{
    private ProductCacheInterface $productCache;
    private ProductToShopifyMapper $mapper;
    private GraphQLInterface $graphQLInterface;
    private ProductMessageProcessor $processor;

    protected function setUp(): void
    {
        $this->productCache = new InMemoryProductCache();
        $this->mapper = new ProductToShopifyMapper($this->productCache);
        $this->graphQLInterface = $this->createMock(GraphQLInterface::class);

        $this->processor = new ProductMessageProcessor(
            $this->productCache,
            $this->mapper,
            $this->graphQLInterface
        );
    }

    public function testProcessProductWithCompleteData(): void
    {
        $abstractSku = 'TEST_SKU';

        $abstractProductDTO = $this->createMock(AbstractProductDTO::class);
        $abstractProductDTO->method('getAbstractSku')->willReturn($abstractSku);
        $abstractProductDTO->method('getNameEn')->willReturn('Product Name');
        $abstractProductDTO->method('getDescriptionEn')->willReturn('Product Description');
        $abstractProductDTO->method('getCategoryKey')->willReturn('Category');
        $abstractProductDTO->method('getLabels')->willReturn(['Tag1', 'Tag2']);

        $this->productCache->saveAbstractProduct($abstractSku, $abstractProductDTO, 300);

        $concreteProductDTO = $this->createMock(ConcreteProductDTO::class);
        $concreteProductDTO->method('getAbstractSku')->willReturn($abstractSku);
        $concreteProductDTO->method('getConcreteSku')->willReturn('SKU123');
        $concreteProductDTO->method('getPriceGross')->willReturn(99.99);
        $concreteProductDTO->method('getQuantity')->willReturn(10);
        $concreteProductDTO->method('isNeverOutOfStock')->willReturn(false);
        $concreteProductDTO->method('isSearchableEn')->willReturn(true);
        $concreteProductDTO->method('getImageUrl')->willReturn('http://example.com/image.jpg');

        $this->productCache->saveConcreteProduct($abstractSku, $concreteProductDTO, 300);

        $this->graphQLInterface->expects($this->once())
            ->method('executeQuery')
            ->with($this->isType('string'), $this->arrayHasKey('input'))
            ->willReturn([
                'data' => ['productCreate' => ['product' => ['id' => '123']]]
            ]);

        $this->processor->processProduct($abstractSku);

        // Assert cache is cleared
        $this->assertNull($this->productCache->getAbstractProduct($abstractSku));
        $this->assertEmpty($this->productCache->getConcreteProducts($abstractSku));
    }

    public function testProcessProductWithIncompleteData(): void
    {
        $abstractSku = 'TEST_SKU';

        $abstractProductDTO = $this->createMock(AbstractProductDTO::class);
        $abstractProductDTO->method('getAbstractSku')->willReturn($abstractSku);
        $this->productCache->saveAbstractProduct($abstractSku, $abstractProductDTO, 300);

        $this->graphQLInterface->expects($this->never())
            ->method('executeQuery');

        $this->processor->processProduct($abstractSku);

        $this->assertNotNull($this->productCache->getAbstractProduct($abstractSku));
        $this->assertEmpty($this->productCache->getConcreteProducts($abstractSku));
    }
}