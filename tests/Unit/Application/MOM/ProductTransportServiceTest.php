<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\MOM;

use App\Application\MOM\ProductTransportService;
use App\Domain\DTO\ShopifyProduct;
use App\Domain\Message\MessengerInterface;
use App\Domain\Message\ProductMessage;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class ProductTransportServiceTest extends TestCase
{
    /** @var MessengerInterface|MockObject */
    private MessengerInterface $messengerMock;

    /** @var ProductTransportService */
    private ProductTransportService $productTransportService;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a mock for the MessengerInterface
        $this->messengerMock = $this->createMock(MessengerInterface::class);

        // Instantiate the ProductTransportService with the mocked messenger
        $this->productTransportService = new ProductTransportService($this->messengerMock);
    }

    public function testDispatchCallsMessengerWithProductMessage(): void
    {
        // Arrange
        /** @var ShopifyProduct|MockObject $shopifyProduct */
        $shopifyProduct = $this->createMock(ShopifyProduct::class);

        // Expect that messenger's dispatch method will be called once with a ProductMessage containing $shopifyProduct
        $this->messengerMock->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function ($message) use ($shopifyProduct) {
                return $message instanceof ProductMessage
                    && $message->getContent() === $shopifyProduct;
            }))
            ->willReturn(true);

        // Act
        $result = $this->productTransportService->dispatch($shopifyProduct);

        // Assert
        $this->assertTrue($result, 'Dispatch should return true when messenger dispatch returns true.');
    }

    public function testDispatchReturnsFalseWhenMessengerReturnsFalse(): void
    {
        /** @var ShopifyProduct|MockObject $shopifyProduct */
        $shopifyProduct = $this->createMock(ShopifyProduct::class);

        $this->messengerMock->expects($this->once())
            ->method('dispatch')
            ->willReturn(false);

        $result = $this->productTransportService->dispatch($shopifyProduct);

        $this->assertFalse($result, 'Dispatch should return false when messenger dispatch returns false.');
    }

    public function testDispatchPropagatesExceptionsFromMessenger(): void
    {
        /** @var ShopifyProduct|MockObject $shopifyProduct */
        $shopifyProduct = $this->createMock(ShopifyProduct::class);

        $exception = new RuntimeException('Dispatch failed');

        $this->messengerMock->expects($this->once())
            ->method('dispatch')
            ->willThrowException($exception);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Dispatch failed');

        $this->productTransportService->dispatch($shopifyProduct);
    }

    public function testDispatchWithInvalidShopifyProductThrowsTypeError(): void
    {
        $invalidProduct = null;

        $this->expectException(\TypeError::class);

        $this->productTransportService->dispatch($invalidProduct);
    }
}
