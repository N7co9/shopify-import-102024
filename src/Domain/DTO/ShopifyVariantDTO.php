<?php
declare(strict_types=1);

namespace App\Domain\DTO;

class ShopifyVariantDTO
{
    private string $productId;
    private string $sku;
    private string $price;
    private int $availableQuantity;
    private string $locationId;
    private string $inventoryManagement;
    private string $inventoryPolicy;
    private ?array $media;
    private array $metafields;

    public function __construct(
        string $productId,
        string $sku,
        string $price,
        int    $availableQuantity,
        string $locationId = 'DEFAULT',
        string $inventoryManagement = 'SHOPIFY',
        string $inventoryPolicy = 'DENY',
        ?array $media = [],
        /// ['alt' => 'associated text', 'mediaContentType' => 'image', 'originalSource' => 'www.image.com']
        ?array $metafields = []

    )
    {
        $this->productId = $productId;
        $this->sku = $sku;
        $this->price = $price;
        $this->availableQuantity = $availableQuantity;
        $this->locationId = $locationId;
        $this->inventoryManagement = $inventoryManagement;
        $this->inventoryPolicy = $inventoryPolicy;
        $this->media = $media;
        $this->metafields = $metafields;
    }

    public function getProductId(): string
    {
        return $this->productId;
    }

    public function setProductId(string $productId): void
    {
        $this->productId = $productId;
    }

    public function getSku(): string
    {
        return $this->sku;
    }

    public function setSku(string $sku): void
    {
        $this->sku = $sku;
    }

    public function getPrice(): string
    {
        return $this->price;
    }

    public function setPrice(string $price): void
    {
        $this->price = $price;
    }

    public function getAvailableQuantity(): int
    {
        return $this->availableQuantity;
    }

    public function setAvailableQuantity(int $availableQuantity): void
    {
        $this->availableQuantity = $availableQuantity;
    }

    public function getLocationId(): string
    {
        return $this->locationId;
    }

    public function setLocationId(string $locationId): void
    {
        $this->locationId = $locationId;
    }

    public function getInventoryManagement(): string
    {
        return $this->inventoryManagement;
    }

    public function setInventoryManagement(string $inventoryManagement): void
    {
        $this->inventoryManagement = $inventoryManagement;
    }

    public function getInventoryPolicy(): string
    {
        return $this->inventoryPolicy;
    }

    public function setInventoryPolicy(string $inventoryPolicy): void
    {
        $this->inventoryPolicy = $inventoryPolicy;
    }

    public function getMedia(): ?array
    {
        return $this->media;
    }

    public function setMedia(?array $media): void
    {
        $this->media = $media;
    }

    public function getMetafields(): array
    {
        return $this->metafields;
    }

    public function setMetafields(array $metafields): void
    {
        $this->metafields = $metafields;
    }

}
