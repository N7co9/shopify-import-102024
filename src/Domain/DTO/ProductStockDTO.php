<?php
declare(strict_types=1);

namespace App\Domain\DTO;

class ProductStockDTO
{
    private string $sku;
    private string $location;
    private int $quantity;
    private bool $isNeverOutOfStock;

    public function __construct(
        string $sku,
        string $location,
        int    $quantity,
        bool   $isNeverOutOfStock
    )
    {
        $this->sku = $sku;
        $this->quantity = $quantity;
        $this->location = $location;
        $this->isNeverOutOfStock = $isNeverOutOfStock;
    }

    public function getLocation(): string
    {
        return $this->location;
    }

    public function setLocation(string $location): void
    {
        $this->location = $location;
    }

    public function getSku(): string
    {
        return $this->sku;
    }

    public function setSku(string $sku): void
    {
        $this->sku = $sku;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): void
    {
        $this->quantity = $quantity;
    }

    public function isNeverOutOfStock(): bool
    {
        return $this->isNeverOutOfStock;
    }

    public function setIsNeverOutOfStock(bool $isNeverOutOfStock): void
    {
        $this->isNeverOutOfStock = $isNeverOutOfStock;
    }
}
