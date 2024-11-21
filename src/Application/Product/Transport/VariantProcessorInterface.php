<?php
declare(strict_types=1);

namespace App\Application\Product\Transport;


use App\Domain\DTO\ConcreteProductDTO;

interface VariantProcessorInterface
{
    public function processVariant(ConcreteProductDTO $concreteProductDTO, array $inheritanceInformation): void;

}