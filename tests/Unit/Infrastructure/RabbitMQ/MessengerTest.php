<?php
declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\RabbitMQ;

use App\Application\Logger\LoggerInterface;
use App\Domain\Message\ProductMessage;
use App\Infrastructure\RabbitMQ\Messenger;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

class MessengerTest extends TestCase
{
    private Messenger $messenger;
    private MessageBusInterface $busMock;
    private LoggerInterface $loggerMock;

    protected function setUp(): void
    {
        $this->busMock = $this->createMock(MessageBusInterface::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);

        $this->messenger = new Messenger(
            bus: $this->busMock,
            logger: $this->loggerMock
        );
    }

    public function testDispatchSuccess(): void
    {
        $message = $this->createMock(ProductMessage::class);
        $envelope = new Envelope($message, []);

        $this->busMock
            ->expects($this->once())
            ->method('dispatch')
            ->with($message, $this->callback(function ($stamps) {
                return isset($stamps[0]) && $stamps[0] instanceof \Symfony\Component\Messenger\Bridge\Amqp\Transport\AmqpStamp
                    && $stamps[0]->getRoutingKey() === 'shopify_product';
            }))
            ->willReturn($envelope); // Return a real Envelope instance

        $this->loggerMock
            ->expects($this->never())
            ->method('logException');

        $result = $this->messenger->dispatch($message);

        $this->assertTrue($result, 'Dispatch should return true on success.');
    }


    public function testDispatchFailure(): void
    {
        $message = $this->createMock(ProductMessage::class);
        $exception = $this->createMock(ExceptionInterface::class);

        $this->busMock
            ->expects($this->once())
            ->method('dispatch')
            ->willThrowException($exception);

        $this->loggerMock
            ->expects($this->once())
            ->method('logException')
            ->with($exception, 'transport');

        $result = $this->messenger->dispatch($message);

        $this->assertFalse($result, 'Dispatch should return false when an exception occurs.');
    }
}
