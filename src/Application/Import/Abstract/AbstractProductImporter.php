<?php
declare(strict_types=1);

namespace App\Application\Import\Abstract;

use App\Shared\DTO\AbstractProductDTO;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\File;

class AbstractProductImporter
{
    public function import(string $filePath): array
    {
        $file = $this->isValidFile($filePath);

        $fileObject = $file->openFile('r');
        $fileObject->setFlags(\SplFileObject::READ_CSV | \SplFileObject::SKIP_EMPTY);
        $fileObject->setCsvControl(',', '"', "\\");

        $header = [];
        $abstractProductDTOs = [];

        foreach ($fileObject as $index => $row) {
            if ($index === 0) {
                $header = $row;
                continue;
            }
            if (empty($row[0])) {
                continue;
            }

            $record = array_combine($header, $row);

            $abstractProductDTO = new AbstractProductDTO(
                $record['abstract_sku'],
                $record['name.en_US'],
                $record['name.de_DE'],
                $record['description.en_US'],
                $record['description.de_DE'],
                $record['category_key'],
                $record['tax_set_name'],
                $record['meta_title.en_US'],
                $record['meta_title.de_DE']
            );

            $abstractProductDTOs[] = $abstractProductDTO;
        }

        return $abstractProductDTOs;
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