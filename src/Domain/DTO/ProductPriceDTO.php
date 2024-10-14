<?php
declare(strict_types=1);

namespace App\Domain\DTO;

class ProductPriceDTO
{
    private string $sku;
    private float $priceGross;
    private string $currency;

    public function __construct(
        string $sku,
        float $priceGross,
        string $currency
    ) {
        $this->sku = $sku;
        $this->priceGross = $priceGross;
        $this->currency = $currency;
    }

    public function getSku(): string
    {
        return $this->sku;
    }

    public function setSku(string $sku): void
    {
        $this->sku = $sku;
    }

    public function getPriceGross(): float
    {
        return $this->priceGross;
    }

    public function setPriceGross(float $priceGross): void
    {
        $this->priceGross = $priceGross;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): void
    {
        $this->currency = $currency;
    }
}
