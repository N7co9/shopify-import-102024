<?php
declare(strict_types=1);

namespace App\Application\Import\Concrete;

use App\Domain\DTO\ProductPriceDTO;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\File;

class ProductPriceImporter
{
    public function import(string $filePath): array
    {
        $file = $this->isValidFile($filePath);

        $fileObject = $file->openFile('r');
        $fileObject->setFlags(\SplFileObject::READ_CSV | \SplFileObject::SKIP_EMPTY);
        $fileObject->setCsvControl(',', '"', "\\");

        $header = [];
        $productPriceDTOs = [];

        foreach ($fileObject as $index => $row) {
            if ($index === 0) {
                $header = $row;
                continue;
            }
            if (empty($row[0])) {
                continue;
            }

            $record = array_combine($header, $row);

            $sku = $this->extractSku($record);

            $productPriceDTO = new ProductPriceDTO(
                $sku,
                (float)$record['value_gross'],
                $record['currency']
            );

            $productPriceDTOs[] = $productPriceDTO;
        }

        return $productPriceDTOs;
    }

    private function isValidFile(string $filePath): File
    {
        $file = new File($filePath);

        if (!$file->isReadable()) {
            throw new FileException(sprintf('The file "%s" is not readable.', $filePath));
        }

        return $file;
    }

    private function extractSku(array $record): string
    {
        if (!empty($record['abstract_sku'])) {
            return $record['abstract_sku'];
        }

        if (!empty($record['concrete_sku'])) {
            return $record['concrete_sku'];
        }

        throw new \RuntimeException('No valid SKU found.');
    }
}
