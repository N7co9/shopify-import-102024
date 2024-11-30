<?php
declare(strict_types=1);

namespace App\Application\Product\Import;

use App\Application\Product\Import\Tools\CsvParser;
use App\Application\Product\Import\Tools\VariantRecordProcessor;

class ShopifyVariantImporter
{
    public function __construct(
        private readonly CsvParser              $csvParser,
        private readonly VariantRecordProcessor $recordProcessor
    )
    {
    }

    public function import(string $stockFilePath, string $imageFilePath, string $priceFilePath, string $concreteFilePath): array
    {
        $stockRecords = $this->csvParser->parse($stockFilePath);
        $imageRecords = $this->csvParser->parse($imageFilePath);
        $priceRecords = $this->csvParser->parse($priceFilePath);
        $concreteRecords = $this->csvParser->parse($concreteFilePath);

        return $this->recordProcessor->processVariants(
            $stockRecords,
            $imageRecords,
            $priceRecords,
            $concreteRecords
        );
    }
}
