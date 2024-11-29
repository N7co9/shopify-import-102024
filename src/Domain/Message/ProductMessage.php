<?php
declare(strict_types=1);

namespace App\Domain\Message;


use App\Domain\DTO\ShopifyProduct;

class ProductMessage
{
    public function __construct(
        private ShopifyProduct $content,
    )
    {
    }

    public function getContent(): ShopifyProduct
    {
        return $this->content;
    }
}
