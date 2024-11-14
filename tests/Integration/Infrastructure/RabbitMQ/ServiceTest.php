<?php
declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\RabbitMQ;

use App\Application\Logger\LoggerInterface;
use App\Domain\DTO\AbstractProductDTO;
use App\Domain\DTO\ConcreteProductDTO;
use App\Domain\Message\ProductMessage;
use App\Infrastructure\RabbitMQ\Messenger;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Messenger\MessageBusInterface;

class ServiceTest extends KernelTestCase
{
    private MessageBusInterface $bus;
    private LoggerInterface $logger;
    private Messenger $service;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->bus = self::getContainer()->get(MessageBusInterface::class);
        $this->logger = self::getContainer()->get(LoggerInterface::class);
        $this->service = new Messenger($this->bus, $this->logger);
    }

    public function testDispatchAbstractProductMessage(): void
    {
        $abstractProduct = new AbstractProductDTO(
            '001',
            'Canon Camera',
            'Kamera',
            'Best camera',
            'Beste Kamera',
            'digital-cameras',
            'Electronics'
        );

        $message = new ProductMessage($abstractProduct);
        $result = $this->service->dispatch($message);

        $this->assertTrue($result, 'The message was not dispatched successfully.');
    }

    public function testDispatchConcreteProductMessage(): void
    {
        $concreteProduct = new ConcreteProductDTO(
            '001',
            '001_12345',
            'Canon Camera',
            'Kamera',
            'Best camera',
            'Beste Kamera',
            10,
            true,
            999.99,
            'EUR',
            'http://example.com/image.jpg',
            true,
            false
        );

        $message = new ProductMessage($concreteProduct);
        $result = $this->service->dispatch($message);

        $this->assertTrue($result, 'The message was not dispatched successfully.');
    }
}