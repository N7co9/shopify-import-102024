<?php
declare(strict_types=1);

namespace App\Application\Product\Transport;

use App\Application\Logger\LoggerInterface;
use App\Application\Mapper\VariantToShopifyMapper;
use App\Domain\DTO\ConcreteProductDTO;

class VariantMessageProcessor implements VariantProcessorInterface
{

    public function __construct
    (
        private VariantToShopifyMapper $mapper,
        private LoggerInterface        $logger,

    )
    {
    }

    public function processVariant(ConcreteProductDTO $concreteProductDTO, array $inheritanceInformation): void
    {
        $shopifyVariantDTO = $this->mapper->mapToShopifyVariantDTO($concreteProductDTO, $inheritanceInformation);

        if ($shopifyVariantDTO === null) {
            $this->logger->logError('Mapper returned a ShopifyProductDTO as null');
            return;
        }
    }

}