<?php
declare(strict_types=1);

namespace App\Application\Product\Transport;

use App\Domain\DTO\AbstractProductDTO;

interface ProductProcessorInterface
{
    public function processProduct(AbstractProductDTO $abstractProductDTO): void;
}