<?php
declare(strict_types=1);

namespace App\Domain\DTO;

class ShopifyProductDTO
{
    private string $title;
    private string $descriptionHtml;
    private ?string $productType;
    private array $metafields;
    private ?array $productOptions;

    public function __construct(
        string  $title,
        string  $descriptionHtml,
        ?string $productType = null,
        array   $metafields = [],
        ?array  $productOptions = []
    )
    {
        $this->title = $title;
        $this->descriptionHtml = $descriptionHtml;
        $this->productType = $productType;
        $this->metafields = $metafields;
        $this->productOptions = $productOptions;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getDescriptionHtml(): string
    {
        return $this->descriptionHtml;
    }

    public function setDescriptionHtml(string $descriptionHtml): void
    {
        $this->descriptionHtml = $descriptionHtml;
    }

    public function getProductType(): ?string
    {
        return $this->productType;
    }

    public function setProductType(?string $productType): void
    {
        $this->productType = $productType;
    }

    public function getMetafields(): array
    {
        return $this->metafields;
    }

    public function setMetafields(array $metafields): void
    {
        $this->metafields = $metafields;
    }

    public function getProductOptions(): ?array
    {
        return $this->productOptions;
    }

    public function setProductOptions(?array $productOptions): void
    {
        $this->productOptions = $productOptions;
    }

}
