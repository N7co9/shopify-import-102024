<?php
declare(strict_types=1);

namespace App\Domain\DTO;

class ProductManagementAttributeDTO
{
    private string $key;
    private string $inputType;
    private bool $allowInput;
    private ?bool $isMultiple;
    private ?string $values;
    private string $keyTranslationEn;
    private string $keyTranslationDe;

    public function __construct(
        string  $key,
        string  $inputType,
        bool    $allowInput,
        ?bool   $isMultiple,
        ?string $values,
        string  $keyTranslationEn,
        string  $keyTranslationDe
    )
    {
        $this->key = $key;
        $this->inputType = $inputType;
        $this->allowInput = $allowInput;
        $this->isMultiple = $isMultiple;
        $this->values = $values;
        $this->keyTranslationEn = $keyTranslationEn;
        $this->keyTranslationDe = $keyTranslationDe;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getInputType(): string
    {
        return $this->inputType;
    }

    public function allowsInput(): bool
    {
        return $this->allowInput;
    }

    public function isMultiple(): ?bool
    {
        return $this->isMultiple;
    }

    public function getValues(): ?string
    {
        return $this->values;
    }

    public function getKeyTranslationEn(): string
    {
        return $this->keyTranslationEn;
    }

    public function getKeyTranslationDe(): string
    {
        return $this->keyTranslationDe;
    }
}
