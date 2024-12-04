<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Message;

use App\Application\Message\ProductMessageHandler;
use App\Application\Product\Transport\ProductProcessorInterface;
use App\Domain\DTO\ShopifyProduct;
use App\Domain\Message\ProductMessage;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ProductMessageHandlerTest extends TestCase
{
    public function testInvokeCallsProcessProductOnProductProcessor(): void
    {
        $shopifyProduct = $this->createMock(ShopifyProduct::class);
        $productMessage = new ProductMessage($shopifyProduct);

        /** @var ProductProcessorInterface|MockObject $productProcessorMock */
        $productProcessorMock = $this->createMock(ProductProcessorInterface::class);

        $productProcessorMock->expects($this->once())
            ->method('processProduct')
            ->with($this->identicalTo($shopifyProduct));

        $handler = new ProductMessageHandler($productProcessorMock);

        $handler($productMessage);
    }
}
