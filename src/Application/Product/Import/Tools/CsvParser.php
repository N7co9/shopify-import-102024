<?php
declare(strict_types=1);

namespace App\Application\Product\Import\Tools;

use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\File;

class CsvParser
{
    public function parse(string $filePath): array
    {
        $file = $this->validateFile($filePath);

        $fileObject = $file->openFile('r');
        $fileObject->setFlags(\SplFileObject::READ_CSV | \SplFileObject::SKIP_EMPTY);
        $fileObject->setCsvControl(',', '"', "\\");

        $header = [];
        $records = [];

        foreach ($fileObject as $index => $row) {
            if ($index === 0) {
                $header = $row;
                continue;
            }

            if (empty($row)) {
                continue;
            }

            $records[] = array_combine($header, $row);
        }

        return $records;
    }

    private function validateFile(string $filePath): File
    {
        $file = new File($filePath);

        if (!$file->isReadable()) {
            throw new FileException(sprintf('The file "%s" is not readable.', $filePath));
        }

        return $file;
    }

}