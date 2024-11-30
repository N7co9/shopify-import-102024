<?php
declare(strict_types=1);

namespace App\Application\MOM;


use App\Domain\DTO\ShopifyProduct;
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

    public function dispatch(ShopifyProduct $DTO): bool
    {
        $message = $this->configureMessage($DTO);
        return $this->messenger->dispatch($message);
    }

    private function configureMessage(ShopifyProduct $DTO): ProductMessage
    {
        return new ProductMessage($DTO);
    }
}
