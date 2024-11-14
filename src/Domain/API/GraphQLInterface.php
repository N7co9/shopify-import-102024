<?php
declare(strict_types=1);

namespace App\Domain\API;

interface GraphQLInterface
{
    public function executeQuery(string $query, array $variables = []): array;
}