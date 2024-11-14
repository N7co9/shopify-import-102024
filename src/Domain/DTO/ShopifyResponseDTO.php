<?php
declare(strict_types=1);

namespace App\Domain\DTO;

class ShopifyResponseDTO
{
    private string $PID;
    private bool $success;
    private array $userErrors;
    private array $metafields;

    public function __construct(string $PID, bool $success, array $userErrors, array $metafields)
    {
        $this->PID = $PID;
        $this->success = $success;
        $this->userErrors = $userErrors;
        $this->metafields = $metafields;
    }

    public function getPID(): string
    {
        return $this->PID;
    }

    public function setPID(string $PID): void
    {
        $this->PID = $PID;
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function setSuccess(bool $success): void
    {
        $this->success = $success;
    }

    public function getUserErrors(): array
    {
        return $this->userErrors;
    }

    public function setUserErrors(array $userErrors): void
    {
        $this->userErrors = $userErrors;
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