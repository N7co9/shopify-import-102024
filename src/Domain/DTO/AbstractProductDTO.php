<?php
declare(strict_types=1);

namespace App\Domain\DTO;

class AbstractProductDTO
{
    private string $abstractSku;
    private string $nameEn;
    private string $nameDe;
    private string $descriptionEn;
    private string $descriptionDe;
    private string $categoryKey;
    private string $taxSetName;
    private ?string $metaTitleEn;
    private ?string $metaTitleDe;
    private ?array $managementAttributes;
    public ?array $media;
    public ?array $price;

    public function __construct(
        string  $abstractSku,
        string  $nameEn,
        string  $nameDe,
        string  $descriptionEn,
        string  $descriptionDe,
        string  $categoryKey,
        string  $taxSetName,
        ?string $metaTitleEn = '',
        ?string $metaTitleDe = '',
        ?array  $managementAttributes = [],
        ?array  $media = [],
        ?array $price = []
    )
    {
        $this->abstractSku = $abstractSku;
        $this->nameEn = $nameEn;
        $this->nameDe = $nameDe;
        $this->descriptionEn = $descriptionEn;
        $this->descriptionDe = $descriptionDe;
        $this->categoryKey = $categoryKey;
        $this->taxSetName = $taxSetName;
        $this->metaTitleEn = $metaTitleEn;
        $this->metaTitleDe = $metaTitleDe;
        $this->managementAttributes = $managementAttributes;
        $this->media = $media;
        $this->price = $price;
    }

    public function getPrice(): ?array
    {
        return $this->price;
    }

    public function setPrice(?array $price): void
    {
        $this->price = $price;
    }

    public function getMedia(): ?array
    {
        return $this->media;
    }

    public function setMedia(?array $media): void
    {
        $this->media = $media;
    }

    public function getMetaTitleDe(): ?string
    {
        return $this->metaTitleDe;
    }

    public function setMetaTitleDe(?string $metaTitleDe): void
    {
        $this->metaTitleDe = $metaTitleDe;
    }

    public function getMetaTitleEn(): ?string
    {
        return $this->metaTitleEn;
    }

    public function setMetaTitleEn(?string $metaTitleEn): void
    {
        $this->metaTitleEn = $metaTitleEn;
    }

    public function getTaxSetName(): string
    {
        return $this->taxSetName;
    }

    public function setTaxSetName(string $taxSetName): void
    {
        $this->taxSetName = $taxSetName;
    }

    public function getCategoryKey(): string
    {
        return $this->categoryKey;
    }

    public function setCategoryKey(string $categoryKey): void
    {
        $this->categoryKey = $categoryKey;
    }

    public function getDescriptionDe(): string
    {
        return $this->descriptionDe;
    }

    public function setDescriptionDe(string $descriptionDe): void
    {
        $this->descriptionDe = $descriptionDe;
    }

    public function getDescriptionEn(): string
    {
        return $this->descriptionEn;
    }

    public function setDescriptionEn(string $descriptionEn): void
    {
        $this->descriptionEn = $descriptionEn;
    }

    public function getNameDe(): string
    {
        return $this->nameDe;
    }

    public function setNameDe(string $nameDe): void
    {
        $this->nameDe = $nameDe;
    }

    public function getNameEn(): string
    {
        return $this->nameEn;
    }

    public function setNameEn(string $nameEn): void
    {
        $this->nameEn = $nameEn;
    }

    public function getAbstractSku(): string
    {
        return $this->abstractSku;
    }

    public function setAbstractSku(string $abstractSku): void
    {
        $this->abstractSku = $abstractSku;
    }

    public function getManagementAttributes(): array
    {
        return $this->managementAttributes;
    }

    public function setManagementAttributes(array $managementAttributes): void
    {
        $this->managementAttributes = $managementAttributes;
    }

}