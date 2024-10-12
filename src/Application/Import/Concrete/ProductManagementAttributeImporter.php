<?php
declare(strict_types=1);

namespace App\Application\Import\Concrete;

use App\Shared\DTO\ProductManagementAttributeDTO;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\File;

class ProductManagementAttributeImporter
{
    public function import(string $filePath): array
    {
        $file = $this->isValidFile($filePath);

        $fileObject = $file->openFile('r');
        $fileObject->setFlags(\SplFileObject::READ_CSV | \SplFileObject::SKIP_EMPTY);
        $fileObject->setCsvControl(',', '"', "\\");

        $header = [];
        $productManagementAttributeDTOs = [];

        foreach ($fileObject as $index => $row) {
            $isMultiple = null;
            $values = null;

            if ($index === 0) {
                $header = $row;
                continue;
            }
            if (empty($row[0])) {
                continue;
            }

            $record = array_combine($header, $row);

            if (!empty($record['is_multiple']) && str_contains($record['is_multiple'], 'yes')) {
                $isMultiple = true;
            } else if (!empty($record['is_multiple']) && str_contains($record['is_multiple'], 'no')) {
                $isMultiple = false;
            }

            if (!empty($record['values'])) {
                $values = $record['values'];
            }

            $productManagementAttributeDTO = new ProductManagementAttributeDTO(
                $record['key'],
                $record['input_type'],
                (bool)$record['allow_input'],
                $isMultiple,
                $values,
                $record['key_translation.en_US'],
                $record['key_translation.de_DE']
            );

            $productManagementAttributeDTOs[] = $productManagementAttributeDTO;
        }

        return $productManagementAttributeDTOs;
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
