<?php
declare(strict_types=1);

namespace App\Infrastructure\RabbitMQ;

use App\Domain\DTO\AbstractProductDTO;
use App\Domain\Message\ProductMessage;
use App\Infrastructure\LoggerInterface;
use App\Infrastructure\MessengerInterface;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\AmqpStamp;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class Service implements MessengerInterface
{
    public function __construct
    (
        private readonly MessageBusInterface $bus,
        private readonly LoggerInterface     $logger
    )
    {
    }

    public function dispatch(ProductMessage $message): bool
    {
        $routingKey = $this->getRoutingKey($message);

        try {
            $this->bus->dispatch($message, [new AmqpStamp($routingKey)]);
        } catch (ExceptionInterface $e) {
            $this->logger->logException($e);
            return false;
        }

        return true;
    }

    private function getRoutingKey(ProductMessage $message): string
    {
        return $message->getContent() instanceof AbstractProductDTO ? 'abstract.product' : 'concrete.product';
    }
}