<?php
declare(strict_types=1);

namespace App\Domain\DTO;

use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\SerializedName;

class ShopifyVariant
{
    public function __construct(
        #[Groups(['shopify'])]
        public string  $abstractSku,

        #[Groups(['shopify'])]
        public string  $concreteSku,

        #[Groups(['shopify'])]
        public string  $title,

        #[Groups(['shopify'])]
        public int     $position,

        #[Groups(['shopify'])]
        #[SerializedName('inventory_quantity')]
        public string  $inventoryQuantity,

        #[Groups(['shopify'])]
        public array  $inventoryLocation,

        #[Groups(['shopify'])]
        #[SerializedName('is_never_out_of_stock')]
        public string  $isNeverOutOfStock,

        #[Groups(['shopify'])]
        public string  $price,

        #[Groups(['shopify'])]
        #[SerializedName('inventory_management')]
        public string  $inventoryManagement = 'shopify',

        #[Groups(['shopify'])]
        #[SerializedName('inventory_policy')]
        public string  $inventoryPolicy = 'DENY',

        #[Groups(['shopify'])]
        public bool    $taxable = true,

        #[Groups(['shopify'])]
        public bool    $available = true,

        #[Groups(['shopify'])]
        #[SerializedName('requires_shipping')]
        public bool    $requiresShipping = true,

        #[Groups(['shopify'])]
        public ?string $id = null,

        #[Groups(['shopify'])]
        #[SerializedName('product_id')]
        public ?string $productId = null,

        #[Groups(['shopify'])]
        public ?string $upc = null,

        #[Groups(['shopify'])]
        #[SerializedName('compare_at_price')]
        public ?string $compareAtPrice = null,

        #[Groups(['shopify'])]
        public ?array $option = [],

        #[Groups(['shopify'])]
        #[SerializedName('created_at')]
        public ?string $createdAt = null,

        #[Groups(['shopify'])]
        #[SerializedName('updated_at')]
        public ?string $updatedAt = null,

        #[Groups(['shopify'])]
        public ?string $imageUrl = null,

        #[Groups(['shopify'])]
        #[SerializedName('inventory_item_id')]
        public ?string $inventoryItemId = null,
    )
    {
    }
}
