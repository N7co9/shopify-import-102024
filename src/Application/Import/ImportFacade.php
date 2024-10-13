<?php
declare(strict_types=1);

namespace App\Application\Import;

class ImportFacade implements ImportFacadeInterface
{
    private ImportProcessor $importProcessor;

    public function __construct(ImportProcessor $importProcessor)
    {
        $this->importProcessor = $importProcessor;
    }

    public function processImport(string $directoryPath): array
    {
        return $this->importProcessor->processDirectory($directoryPath);
    }
}