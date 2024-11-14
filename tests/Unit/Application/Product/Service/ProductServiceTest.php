<?php
declare(strict_types=1);

namespace App\Tests\Unit\Application\Product\Service;

use App\Application\Product\Cache\ProductCacheInterface;
use App\Application\Product\Transport\ProductProcessorInterface;
use App\Application\Product\Service\ProductService;
use App\Domain\DTO\AbstractProductDTO;
use App\Domain\DTO\ConcreteProductDTO;
use App\Domain\Message\ProductMessage;
use PHPUnit\Framework\TestCase;

class ProductServiceTest extends TestCase
{
    public function testHandleProductMessageWithAbstractProductDTO(): void
    {
        $abstractSku = 'TEST_SKU';
        $abstractProductDTO = $this->createMock(AbstractProductDTO::class);
        $abstractProductDTO->method('getAbstractSku')->willReturn($abstractSku);

        $productMessage = new ProductMessage($abstractProductDTO);

        $productCache = $this->createMock(ProductCacheInterface::class);
        $productCache->expects($this->once())
            ->method('saveAbstractProduct')
            ->with($abstractSku, $abstractProductDTO, 300);

        $productProcessor = $this->createMock(ProductProcessorInterface::class);
        $productProcessor->expects($this->once())
            ->method('processProduct')
            ->with($abstractSku);

        $productService = new ProductService($productCache, $productProcessor, 300);

        $productService->handleProductMessage($productMessage);
    }

    public function testHandleProductMessageWithConcreteProductDTO(): void
    {
        $abstractSku = 'TEST_SKU';
        $concreteProductDTO = $this->createMock(ConcreteProductDTO::class);
        $concreteProductDTO->method('getAbstractSku')->willReturn($abstractSku);

        $productMessage = new ProductMessage($concreteProductDTO);

        $productCache = $this->createMock(ProductCacheInterface::class);
        $productCache->expects($this->once())
            ->method('saveConcreteProduct')
            ->with($abstractSku, $concreteProductDTO, 300);

        $productProcessor = $this->createMock(ProductProcessorInterface::class);
        $productProcessor->expects($this->once())
            ->method('processProduct')
            ->with($abstractSku);

        $productService = new ProductService($productCache, $productProcessor, 300);

        $productService->handleProductMessage($productMessage);
    }

    public function testHandleProductMessageWithUnknownDTO(): void
    {
        $unknownProductDTO = $this->getMockBuilder(\stdClass::class)
            ->setMethods(['getAbstractSku'])
            ->getMock();
        $unknownProductDTO->method('getAbstractSku')->willReturn('UNKNOWN_SKU');

        $this->expectException(\TypeError::class);

        $productMessage = new ProductMessage($unknownProductDTO);

        $productCache = $this->createMock(ProductCacheInterface::class);
        $productProcessor = $this->createMock(ProductProcessorInterface::class);

        $productService = new ProductService($productCache, $productProcessor, 300);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown product DTO type');

        $productService->handleProductMessage($productMessage);
    }
}