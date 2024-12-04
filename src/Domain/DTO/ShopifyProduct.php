<?php
declare(strict_types=1);

namespace App\Domain\DTO;

use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\SerializedName;

class ShopifyProduct
{
    /**
     * @param ShopifyVariant[] $variants
     * @param string[] $tags
     */
    public function __construct(
        #[Groups(['shopify'])]
        #[SerializedName('abstract_sku')]
        public string           $abstractSku,

        #[Groups(['shopify'])]
        public LocalizedString  $title,

        #[Groups(['shopify'])]
        #[SerializedName('body_html')]
        public LocalizedString  $bodyHtml,

        #[Groups(['shopify'])]
        public string           $vendor,

        #[Groups(['shopify'])]
        public string           $price,

        #[Groups(['shopify'])]
        #[SerializedName('compare_at_price')]
        public ?string          $compareAtPrice,

        #[Groups(['shopify'])]
        #[SerializedName('product_type')]
        public string           $productType,

        #[Groups(['shopify'])]
        public ?bool            $isGiftCard,

        #[Groups(['shopify'])]
        public ?LocalizedString $handle,

        #[Groups(['shopify'])]
        public ?string          $status,

        #[Groups(['shopify'])]
        #[SerializedName('published_scope')]
        public ?string          $publishedScope,

        #[Groups(['shopify'])]
        public ?array           $variants,

        #[Groups(['shopify'])]
        public ?string          $imageUrl,
        #[Groups(['shopify'])]
        public array            $attributes,

        #[Groups(['shopify'])]
        public LocalizedString  $tags,

        #[Groups(['shopify'])]
        public ?string          $id = null,

        #[Groups(['shopify'])]
        #[SerializedName('created_at')]
        public ?string          $createdAt = null,

        #[Groups(['shopify'])]
        #[SerializedName('updated_at')]
        public ?string          $updatedAt = null,

        #[Groups(['shopify'])]
        #[SerializedName('published_at')]
        public ?string          $publishedAt = null,

        #[Groups(['shopify'])]
        #[SerializedName('category_product_order')]
        public ?string          $categoryProductOrder = null,

        #[Groups(['shopify'])]
        #[SerializedName('tax_set_name')]
        public ?string          $taxSetName = null,

        #[Groups(['shopify'])]
        #[SerializedName('is_bundle')]
        public bool             $isBundle = false,

        #[Groups(['shopify'])]
        #[SerializedName('new_from')]
        public ?string          $newFrom = null,

        #[Groups(['shopify'])]
        #[SerializedName('new_to')]
        public ?string          $newTo = null,
    )
    {
    }
}
