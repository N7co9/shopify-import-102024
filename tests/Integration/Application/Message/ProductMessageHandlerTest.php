<?php
declare(strict_types=1);

namespace App\Tests\Integration\Application\Message;


use App\Application\Message\ProductMessageHandler;
use App\Application\Product\Cache\InMemoryProductCache;
use App\Application\Product\Cache\ProductCacheInterface;
use App\Application\Product\Transport\ProductProcessorInterface;
use App\Application\Product\Service\ProductService;
use App\Application\Product\Service\ProductServiceInterface;
use App\Domain\DTO\AbstractProductDTO;
use App\Domain\DTO\ConcreteProductDTO;
use App\Domain\Message\ProductMessage;
use PHPUnit\Framework\TestCase;

class ProductMessageHandlerTest extends TestCase
{
    private ProductCacheInterface $productCache;
    private ProductProcessorInterface $productProcessor;
    private ProductService $productService;

    protected function setUp(): void
    {
        $this->productCache = new InMemoryProductCache();
        $this->productProcessor = $this->createMock(ProductProcessorInterface::class);
        $this->productService = new ProductService(
            $this->productCache,
            $this->productProcessor,
            300
        );
    }

    public function testHandleAbstractProductMessage(): void
    {
        $abstractProductDTO = $this->createMock(AbstractProductDTO::class);
        $abstractProductDTO->method('getAbstractSku')->willReturn('TEST_SKU');

        $productMessage = new ProductMessage($abstractProductDTO);

        $this->productProcessor->expects($this->once())
            ->method('processProduct')
            ->with('TEST_SKU');

        $handler = new ProductMessageHandler($this->productService);

        $handler($productMessage);

        $cachedProduct = $this->productCache->getAbstractProduct('TEST_SKU');
        $this->assertSame($abstractProductDTO, $cachedProduct);
    }

    public function testHandleConcreteProductMessage(): void
    {
        $concreteProductDTO = $this->createMock(ConcreteProductDTO::class);
        $concreteProductDTO->method('getAbstractSku')->willReturn('TEST_SKU');

        $productMessage = new ProductMessage($concreteProductDTO);

        $this->productProcessor->expects($this->once())
            ->method('processProduct')
            ->with('TEST_SKU');

        $handler = new ProductMessageHandler($this->productService);

        $handler($productMessage);

        $cachedProducts = $this->productCache->getConcreteProducts('TEST_SKU');
        $this->assertCount(1, $cachedProducts);
        $this->assertSame($concreteProductDTO, $cachedProducts[0]);
    }
}
