<?php
declare(strict_types=1);

namespace App\Application\Product\Transport;

use App\Domain\DTO\ShopifyProduct;

interface ProductProcessorInterface
{
    public function processProduct(ShopifyProduct $product): void;
}