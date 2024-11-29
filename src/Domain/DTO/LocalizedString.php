<?php
declare(strict_types=1);

namespace App\Domain\DTO;

use Symfony\Component\Serializer\Annotation\Groups;

readonly class LocalizedString
{
    public function __construct(
        #[Groups(['shopify'])]
        public string $en_US,

        #[Groups(['shopify'])]
        public string $de_DE,
    ) {}
}