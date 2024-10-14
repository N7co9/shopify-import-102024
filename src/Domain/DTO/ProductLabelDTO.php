<?php
declare(strict_types=1);

namespace App\Domain\DTO;

class ProductLabelDTO
{
    private string $name;
    private bool $isActive;
    private bool $isDynamic;
    private bool $isExclusive;
    private ?string $frontEndReference;
    private string $nameEn;
    private string $nameDe;
    private ?array $productAbstractSkus;
    private int $priority;

    public function __construct(
        string  $name,
        bool    $isActive,
        bool    $isDynamic,
        bool    $isExclusive,
        ?string $frontEndReference,
        string  $nameEn,
        string  $nameDe,
        ?array  $productAbstractSkus,
        int     $priority
    )
    {
        $this->name = $name;
        $this->isActive = $isActive;
        $this->isDynamic = $isDynamic;
        $this->isExclusive = $isExclusive;
        $this->frontEndReference = $frontEndReference;
        $this->nameEn = $nameEn;
        $this->nameDe = $nameDe;
        $this->productAbstractSkus = $productAbstractSkus;
        $this->priority = $priority;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function isDynamic(): bool
    {
        return $this->isDynamic;
    }

    public function isExclusive(): bool
    {
        return $this->isExclusive;
    }

    public function getFrontEndReference(): ?string
    {
        return $this->frontEndReference;
    }

    public function getNameEn(): string
    {
        return $this->nameEn;
    }

    public function getNameDe(): string
    {
        return $this->nameDe;
    }

    public function getProductAbstractSkus(): ?array
    {
        return $this->productAbstractSkus;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }
}
