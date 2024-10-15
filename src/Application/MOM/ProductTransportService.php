<?php
declare(strict_types=1);

namespace App\Application\MOM;

use App\Domain\DTO\AbstractProductDTO;
use App\Domain\DTO\ConcreteProductDTO;
use App\Domain\Message\MessengerInterface;
use App\Domain\Message\ProductMessage;

readonly class ProductTransportService implements TransportInterface
{
    public function __construct
    (
        private MessengerInterface $messenger,
    )
    {
    }

    public function dispatch(AbstractProductDTO|ConcreteProductDTO $DTO): bool
    {
        $message = $this->configureMessage($DTO);

        return $this->messenger->dispatch($message);
    }

    private function configureMessage(AbstractProductDTO|ConcreteProductDTO $DTO): ProductMessage
    {
        return new ProductMessage($DTO);
    }
}
