<?php
declare(strict_types=1);

namespace App\Application\Import\Concrete;

use App\Shared\DTO\ProductStockDTO;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\File;

class ProductStockImporter
{
    public function import(string $filePath): array
    {
        $file = $this->isValidFile($filePath);

        $fileObject = $file->openFile('r');
        $fileObject->setFlags(\SplFileObject::READ_CSV | \SplFileObject::SKIP_EMPTY);
        $fileObject->setCsvControl(',', '"', "\\");

        $header = [];
        $productStockDTOs = [];

        foreach ($fileObject as $index => $row) {
            $neverOutOfStock = false;
            if ($index === 0) {
                $header = $row;
                continue;
            }
            if (empty($row[0])) {
                continue;
            }

            $record = array_combine($header, $row);
            if (isset($record['is_never_out_of_stock']) && str_contains($record['is_never_out_of_stock'], 'true')) {
                $neverOutOfStock = true;
            }


            $productStockDTO = new ProductStockDTO(
                $record['sku'],
                (int)$record['quantity'],
                $neverOutOfStock
            );

            $productStockDTOs[] = $productStockDTO;
        }

        return $productStockDTOs;
    }

    private function isValidFile(string $filePath): File
    {
        $file = new File($filePath);

        if (!$file->isReadable()) {
            throw new FileException(sprintf('The file "%s" is not readable.', $filePath));
        }

        return $file;
    }
}
