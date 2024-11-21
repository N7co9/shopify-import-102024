<?php
declare(strict_types=1);

namespace App\Application\Product\Import\Abstract;

use App\Domain\DTO\AbstractProductDTO;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\File;

class AbstractProductImporter
{
    private array $productBuffer = [];

    public function import(string $filePath): array
    {
        $file = $this->isValidFile($filePath);

        $fileObject = $file->openFile('r');
        $fileObject->setFlags(\SplFileObject::READ_CSV | \SplFileObject::SKIP_EMPTY);
        $fileObject->setCsvControl(',', '"', "\\");

        $header = [];

        foreach ($fileObject as $index => $row) {
            if ($index === 0) {
                $header = $row;
                continue;
            }
            if (empty($row[0])) {
                continue;
            }

            $record = array_combine($header, $row);
            $managementAttributes = [];

            foreach ($record as $key => $value) {
                if (str_starts_with($key, 'attribute_key_') && !empty($value)) {
                    $attributeIndex = substr($key, -1);
                    $managementAttributes[$value] = $record["value_{$attributeIndex}"] ?? null;
                }
            }

            $abstractProductDTO = new AbstractProductDTO(
                $record['abstract_sku'],
                $record['name.en_US'],
                $record['name.de_DE'],
                $record['description.en_US'],
                $record['description.de_DE'],
                $record['category_key'],
                $record['tax_set_name'],
                $record['meta_title.en_US'],
                $record['meta_title.de_DE'],
                $managementAttributes
            );

            $this->accumulateProduct($abstractProductDTO);
        }

        return $this->getMergedProducts();
    }

    private function accumulateProduct(AbstractProductDTO $abstractProductDTO): void
    {
        $productName = $abstractProductDTO->getNameEn();

        if (isset($this->productBuffer[$productName])) {
            $existingDTO = $this->productBuffer[$productName];
            $existingAttributes = $existingDTO->getManagementAttributes();
            $newAttributes = $abstractProductDTO->getManagementAttributes();

            if (isset($newAttributes['color'])) {
                if (!isset($existingAttributes['color']) || !is_array($existingAttributes['color'])) {
                    $existingAttributes['color'] = [];
                }

                $sku = $abstractProductDTO->getAbstractSku();
                foreach ((array)$newAttributes['color'] as $color) {
                    $existingAttributes['color'][$sku] = $color;
                }
            }

            $existingDTO->setManagementAttributes($existingAttributes);
        } else {
            $managementAttributes = $abstractProductDTO->getManagementAttributes();

            if (isset($managementAttributes['color'])) {
                $mappedColors = [];
                $sku = $abstractProductDTO->getAbstractSku();

                foreach ((array)$managementAttributes['color'] as $color) {
                    $mappedColors[$sku] = $color;
                }

                $managementAttributes['color'] = $mappedColors;
                $abstractProductDTO->setManagementAttributes($managementAttributes);
            }

            $this->productBuffer[$productName] = $abstractProductDTO;
        }
    }



    private function getMergedProducts(): array
    {
        return array_values($this->productBuffer);
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
