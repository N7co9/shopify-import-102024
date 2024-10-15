<?php
declare(strict_types=1);

namespace App\Application\Import;

interface ImportInterface
{
    public function processImport(string $directoryPath): array;
}