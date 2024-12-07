<?php
declare(strict_types=1);

namespace App\Infrastructure\RabbitMQ;

use App\Domain\Message\MessengerInterface;
use App\Domain\Message\ProductMessage;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\AmqpStamp;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class Messenger implements MessengerInterface
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
        try {
            $this->bus->dispatch($message, [new AmqpStamp('shopify_product')]);
        } catch (ExceptionInterface $e) {
            $this->logger->critical($e->getMessage());
            return false;
        }

        return true;
    }

}
