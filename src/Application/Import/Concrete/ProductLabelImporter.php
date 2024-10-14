<?php
declare(strict_types=1);

namespace App\Application\Import\Concrete;

use App\Domain\DTO\ProductLabelDTO;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\File;

class ProductLabelImporter
{
    public function import(string $filePath): array
    {
        $file = $this->isValidFile($filePath);

        $fileObject = $file->openFile('r');
        $fileObject->setFlags(\SplFileObject::READ_CSV | \SplFileObject::SKIP_EMPTY);
        $fileObject->setCsvControl(',', '"', "\\");

        $header = [];
        $productLabelDTOs = [];

        foreach ($fileObject as $index => $row) {

            $frontEndReference = null;

            if ($index === 0) {
                $header = $row;
                continue;
            }
            if (empty($row[0])) {
                continue;
            }

            $record = array_combine($header, $row);

            $productAbstractSkus = !empty($record['product_abstract_skus']) ? explode(',', $record['product_abstract_skus']) : null;

            if (!empty($record['front_end_reference'])) {
                $frontEndReference = $record['front_end_reference'];
            }

            $productLabelDTO = new ProductLabelDTO(
                $record['name'],
                (bool)$record['is_active'],
                (bool)$record['is_dynamic'],
                (bool)$record['is_exclusive'],
                $frontEndReference,
                $record['name.en_US'],
                $record['name.de_DE'],
                $productAbstractSkus,
                (int)$record['priority']
            );

            $productLabelDTOs[] = $productLabelDTO;
        }

        return $productLabelDTOs;
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
