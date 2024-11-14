<?php
declare(strict_types=1);

namespace App\Tests\Unit\Application\MOM;

use App\Application\MOM\ProductTransportService;
use App\Domain\DTO\AbstractProductDTO;
use App\Domain\DTO\ConcreteProductDTO;
use App\Domain\Message\MessengerInterface;
use App\Domain\Message\ProductMessage;
use PHPUnit\Framework\TestCase;

class ProductTransportServiceTest extends TestCase
{
    private MessengerInterface $messengerMock;
    private ProductTransportService $transportService;

    protected function setUp(): void
    {
        $this->messengerMock = $this->createMock(MessengerInterface::class);
        $this->transportService = new ProductTransportService($this->messengerMock);
    }

    public function testDispatchWithAbstractProductDTO(): void
    {
        $abstractProductDTO = $this->createMock(AbstractProductDTO::class);

        $expectedMessage = new ProductMessage($abstractProductDTO);

        $this->messengerMock->expects($this->once())
            ->method('dispatch')
            ->with($this->equalTo($expectedMessage))
            ->willReturn(true);

        $result = $this->transportService->dispatch($abstractProductDTO);

        $this->assertTrue($result);
    }

    public function testDispatchWithConcreteProductDTO(): void
    {
        $concreteProductDTO = $this->createMock(ConcreteProductDTO::class);

        $expectedMessage = new ProductMessage($concreteProductDTO);

        $this->messengerMock->expects($this->once())
            ->method('dispatch')
            ->with($this->equalTo($expectedMessage))
            ->willReturn(true);

        $result = $this->transportService->dispatch($concreteProductDTO);

        $this->assertTrue($result);
    }

    public function testDispatchReturnsFalseOnMessengerFailure(): void
    {
        $abstractProductDTO = $this->createMock(AbstractProductDTO::class);

        $expectedMessage = new ProductMessage($abstractProductDTO);

        $this->messengerMock->expects($this->once())
            ->method('dispatch')
            ->with($this->equalTo($expectedMessage))
            ->willReturn(false);

        $result = $this->transportService->dispatch($abstractProductDTO);

        $this->assertFalse($result);
    }
}