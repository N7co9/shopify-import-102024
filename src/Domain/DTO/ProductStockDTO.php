<?php
declare(strict_types=1);

namespace App\Domain\DTO;

class ProductStockDTO
{
    private string $sku;
    private int $quantity;
    private bool $isNeverOutOfStock;

    public function __construct(
        string $sku,
        int $quantity,
        bool $isNeverOutOfStock
    ) {
        $this->sku = $sku;
        $this->quantity = $quantity;
        $this->isNeverOutOfStock = $isNeverOutOfStock;
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
