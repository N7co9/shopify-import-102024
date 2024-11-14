<?php
declare(strict_types=1);

namespace App\Application\Product\Import\Concrete;

use App\Domain\DTO\ProductImageDTO;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\File;

class ProductImageImporter
{
    public function import(string $filePath): array
    {
        $file = $this->isValidFile($filePath);

        $fileObject = $file->openFile('r');
        $fileObject->setFlags(\SplFileObject::READ_CSV | \SplFileObject::SKIP_EMPTY);
        $fileObject->setCsvControl(',', '"', "\\");

        $header = [];
        $productImageDTOs = [];

        foreach ($fileObject as $index => $row) {

            $abstractSKU = null;
            $concreteSKU = null;

            if ($index === 0) {
                $header = $row;
                continue;
            }
            if (empty($row[0])) {
                continue;
            }


            $record = array_combine($header, $row);

            if (!empty($record['abstract_sku'])) {
                $abstractSKU = $record['abstract_sku'];
            } else if (!empty($record['concrete_sku'])) {
                $concreteSKU = $record['concrete_sku'];
            }

            $productImageDTO = new ProductImageDTO(
                $record['image_set_name'],
                $record['external_url_large'],
                $record['external_url_small'],
                $record['locale'],
                $abstractSKU,
                $concreteSKU,
                (int)$record['sort_order'],
                $record['product_image_key']
            );

            $productImageDTOs[] = $productImageDTO;
        }

        return $productImageDTOs;
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
