<?php
declare(strict_types=1);

namespace App\Domain\DTO;

class ConcreteProductDTO
{
    private string $abstractSku;
    private string $concreteSku;
    private string $nameEn;
    private string $nameDe;
    private string $descriptionEn;
    private string $descriptionDe;
    private ?int $quantity;
    private ?bool $isNeverOutOfStock;
    private ?float $priceGross;
    private ?string $currency;
    private ?string $imageUrl;
    private ?bool $isSearchableEn;
    private ?bool $isSearchableDe;

    public function __construct(
        string  $abstractSku,
        string  $concreteSku,
        string  $nameEn,
        string  $nameDe,
        string  $descriptionEn,
        string  $descriptionDe,
        ?int     $quantity = null,
        ?bool    $isNeverOutOfStock = null,
        ?float  $priceGross = null,
        ?string $currency = null,
        ?string $imageUrl = '',
        ?bool   $isSearchableEn = null,
        ?bool   $isSearchableDe = null
    )
    {
        $this->abstractSku = $abstractSku;
        $this->concreteSku = $concreteSku;
        $this->nameEn = $nameEn;
        $this->nameDe = $nameDe;
        $this->descriptionEn = $descriptionEn;
        $this->descriptionDe = $descriptionDe;
        $this->quantity = $quantity;
        $this->isNeverOutOfStock = $isNeverOutOfStock;
        $this->priceGross = $priceGross;
        $this->currency = $currency;
        $this->imageUrl = $imageUrl;
        $this->isSearchableEn = $isSearchableEn;
        $this->isSearchableDe = $isSearchableDe;
    }

    public function getAbstractSku(): string
    {
        return $this->abstractSku;
    }

    public function setAbstractSku(string $abstractSku): void
    {
        $this->abstractSku = $abstractSku;
    }

    public function getConcreteSku(): string
    {
        return $this->concreteSku;
    }

    public function setConcreteSku(string $concreteSku): void
    {
        $this->concreteSku = $concreteSku;
    }

    public function getNameEn(): string
    {
        return $this->nameEn;
    }

    public function setNameEn(string $nameEn): void
    {
        $this->nameEn = $nameEn;
    }

    public function getNameDe(): string
    {
        return $this->nameDe;
    }

    public function setNameDe(string $nameDe): void
    {
        $this->nameDe = $nameDe;
    }

    public function getDescriptionEn(): string
    {
        return $this->descriptionEn;
    }

    public function setDescriptionEn(string $descriptionEn): void
    {
        $this->descriptionEn = $descriptionEn;
    }

    public function getDescriptionDe(): string
    {
        return $this->descriptionDe;
    }

    public function setDescriptionDe(string $descriptionDe): void
    {
        $this->descriptionDe = $descriptionDe;
    }

    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    public function setQuantity(?int $quantity): void
    {
        $this->quantity = $quantity;
    }

    public function isNeverOutOfStock(): ?bool
    {
        return $this->isNeverOutOfStock;
    }

    public function setIsNeverOutOfStock(?bool $isNeverOutOfStock): void
    {
        $this->isNeverOutOfStock = $isNeverOutOfStock;
    }

    public function getPriceGross(): ?float
    {
        return $this->priceGross;
    }

    public function setPriceGross(?float $priceGross): void
    {
        $this->priceGross = $priceGross;
    }

    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    public function setCurrency(?string $currency): void
    {
        $this->currency = $currency;
    }

    public function getImageUrl(): ?string
    {
        return $this->imageUrl;
    }

    public function setImageUrl(?string $imageUrl): void
    {
        $this->imageUrl = $imageUrl;
    }

    public function isSearchableEn(): ?bool
    {
        return $this->isSearchableEn;
    }

    public function setIsSearchableEn(?bool $isSearchableEn): void
    {
        $this->isSearchableEn = $isSearchableEn;
    }

    public function isSearchableDe(): ?bool
    {
        return $this->isSearchableDe;
    }

    public function setIsSearchableDe(?bool $isSearchableDe): void
    {
        $this->isSearchableDe = $isSearchableDe;
    }
}
