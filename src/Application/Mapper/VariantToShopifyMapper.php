<?php
declare(strict_types=1);

namespace App\Application\Mapper;

use App\Domain\DTO\ConcreteProductDTO;
use App\Domain\DTO\ShopifyVariantDTO;

class VariantToShopifyMapper
{
    public function mapToShopifyVariantDTO(ConcreteProductDTO $concreteProductDTO, array $inheritanceInformation): ?ShopifyVariantDTO
    {
        return new ShopifyVariantDTO(
            $inheritanceInformation['PID'],
            $concreteProductDTO->getConcreteSku(),
            (string)$concreteProductDTO->getPriceGross(),
            $concreteProductDTO->getQuantity(),
            $concreteProductDTO->getLocation(),
            'SHOPIFY',
            'DENY',
            $this->createMediaArray($concreteProductDTO),
        );
    }

    public function createMediaArray(ConcreteProductDTO $concreteProductDTO): array
    {
        return [
            'alt' => $concreteProductDTO->getNameEn(),
            'mediaContentType' => 'IMAGE',
            'originalSource' => $concreteProductDTO->getImageUrl()
        ];
    }

}