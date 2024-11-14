<?php
declare(strict_types=1);

namespace App\Application\Product\Import\Concrete;

use App\Domain\DTO\ConcreteProductDTO;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\File;

class ConcreteProductImporter
{
    public function import(string $filePath): array
    {
        $file = $this->isValidFile($filePath);

        $fileObject = $file->openFile('r');
        $fileObject->setFlags(\SplFileObject::READ_CSV | \SplFileObject::SKIP_EMPTY);
        $fileObject->setCsvControl(',', '"', "\\");

        $header = [];
        $concreteProductDTOs = [];

        foreach ($fileObject as $index => $row) {
            if ($index === 0) {
                $header = $row;
                continue;
            }
            if (empty($row[0])) {
                continue;
            }

            $record = array_combine($header, $row);

            $concreteProductDTO = new ConcreteProductDTO(
                $record['abstract_sku'],
                $record['concrete_sku'],
                $record['name.en_US'],
                $record['name.de_DE'],
                $record['description.en_US'],
                $record['description.de_DE'],
                null,
                null,
                null,
                null,
                null,
                (bool)$record['is_searchable.en_US'],
                (bool)$record['is_searchable.de_DE']
            );

            $concreteProductDTOs[] = $concreteProductDTO;
        }

        return $concreteProductDTOs;
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
