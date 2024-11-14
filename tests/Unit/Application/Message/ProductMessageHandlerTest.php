<?php
declare(strict_types=1);

namespace App\Tests\Unit\Application\Message;


use App\Application\Message\ProductMessageHandler;
use App\Application\Product\Service\ProductServiceInterface;
use App\Domain\DTO\AbstractProductDTO;
use App\Domain\Message\ProductMessage;
use PHPUnit\Framework\TestCase;

class ProductMessageHandlerTest extends TestCase
{
    public function testInvokeCallsProductService(): void
    {
        $productDTO = $this->createMock(AbstractProductDTO::class);
        $productDTO->method('getAbstractSku')->willReturn('TEST_SKU');

        $productMessage = new ProductMessage($productDTO);

        $productService = $this->createMock(ProductServiceInterface::class);
        $productService->expects($this->once())
            ->method('handleProductMessage')
            ->with($productMessage);

        $handler = new ProductMessageHandler($productService);

        $handler($productMessage);
    }
}