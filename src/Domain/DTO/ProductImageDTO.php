<?php
declare(strict_types=1);

namespace App\Domain\DTO;

class ProductImageDTO
{
    private string $imageSetName;
    private string $externalUrlLarge;
    private string $externalUrlSmall;
    private string $locale;
    private ?string $abstractSku;
    private ?string $concreteSku;
    private int $sortOrder;
    private string $productImageKey;

    public function __construct(
        string  $imageSetName,
        string  $externalUrlLarge,
        string  $externalUrlSmall,
        string  $locale,
        ?string $abstractSku,
        ?string $concreteSku,
        int     $sortOrder,
        string  $productImageKey
    )
    {
        $this->imageSetName = $imageSetName;
        $this->externalUrlLarge = $externalUrlLarge;
        $this->externalUrlSmall = $externalUrlSmall;
        $this->locale = $locale;
        $this->abstractSku = $abstractSku;
        $this->concreteSku = $concreteSku;
        $this->sortOrder = $sortOrder;
        $this->productImageKey = $productImageKey;
    }

    public function getImageSetName(): string
    {
        return $this->imageSetName;
    }

    public function getExternalUrlLarge(): string
    {
        return $this->externalUrlLarge;
    }

    public function getExternalUrlSmall(): string
    {
        return $this->externalUrlSmall;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function getAbstractSku(): ?string
    {
        return $this->abstractSku;
    }

    public function getConcreteSku(): ?string
    {
        return $this->concreteSku;
    }

    public function getSortOrder(): int
    {
        return $this->sortOrder;
    }

    public function getProductImageKey(): string
    {
        return $this->productImageKey;
    }
}
