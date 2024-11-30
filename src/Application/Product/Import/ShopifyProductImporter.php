<?php
declare(strict_types=1);

namespace App\Application\Product\Import;

use App\Application\Product\Import\Tools\CsvParser;
use App\Application\Product\Import\Tools\ProductRecordProcessor;

class ShopifyProductImporter
{
    public function __construct(
        private readonly CsvParser              $csvParser,
        private readonly ProductRecordProcessor $recordProcessor,
    )
    {
    }

    public function import(string $abstractProductFilePath, string $priceFilePath, string $imageFilePath): array
    {
        $abstractProductRecords = $this->csvParser->parse($abstractProductFilePath);
        $priceRecords = $this->csvParser->parse($priceFilePath);
        $imageRecords = $this->csvParser->parse($imageFilePath);

        return $this->recordProcessor->processProducts(
            $abstractProductRecords,
            $priceRecords,
            $imageRecords
        );
    }
}
