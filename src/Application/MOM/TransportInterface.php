<?php
declare(strict_types=1);

namespace App\Application\MOM;

use App\Domain\DTO\ShopifyProduct;

interface TransportInterface
{
    public function dispatch(ShopifyProduct $DTO): bool;

}
