<?php

declare(strict_types=1);

namespace App\Application\Product\Import;


use App\Domain\DTO\ShopifyVariant;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\File;

class ShopifyVariantImporter
{
    public function import(string $stockFilePath, string $imageFilePath, string $priceFilePath, string $concreteFilePath): array
    {
        $stockRecords = $this->parseCsv($stockFilePath);
        $imageRecords = $this->parseCsv($imageFilePath);
        $priceRecords = $this->parseCsv($priceFilePath);
        $concreteRecords = $this->parseCsv($concreteFilePath);

        $variants = [];

        foreach ($concreteRecords as $record) {
            $abstract_sku = $record['abstract_sku'];
            $concrete_sku = $record['concrete_sku'];

            $inventoryQuantity = $this->getValueFromRecords($stockRecords, 'concrete_sku', $concrete_sku, 'quantity', 'N/A');
            $inventoryLocation = $this->getValueFromRecords($stockRecords, 'concrete_sku', $concrete_sku, 'name', 'DEFAULT');
            $isNeverOutOfStock = $this->getValueFromRecords($stockRecords, 'concrete_sku', $concrete_sku, 'is_never_out_of_stock', 0);
            $price = $this->getValueFromRecords($priceRecords, 'concrete_sku', $concrete_sku, 'value_gross', '0.00');
            $imageUrl = $this->getValueFromRecords($imageRecords, 'concrete_sku', $concrete_sku, 'external_url_large', null);
            $compareAtPrice = $this->getMultipleValuesFromRecords($priceRecords, 'concrete_sku', $concrete_sku, 'price_type', 'ORIGINAL', 'value_gross', '0.00');
            $option = $this->getOption($record);


            if (str_contains($record['name.en_US'], 'Gift Card')) {
                $requiresShipping = false;
            } else {
                $requiresShipping = true;
            }

            if (str_contains($record['attribute_key_2'], 'upcs')) {
                $upc = $record['value_2'];
            } else {
                $upc = 'Not Available';
            }

            $variant = new ShopifyVariant(
                $abstract_sku,
                $concrete_sku,
                $record['name.en_US'],
                0,
                $inventoryQuantity,
                $inventoryLocation,
                $isNeverOutOfStock,
                $price,
                'Shopify',
                'DENY',
                true,
                true,
                $requiresShipping,
                null,
                null,
                $upc,
                $compareAtPrice,
                $option,
                date('Y-m-d H:i:s'),
                null,
                $imageUrl,
                null,
            );

            $variants[] = $variant;
        }

        return $variants;
    }

    private function parseCsv(string $filePath): array
    {
        $file = $this->isValidFile($filePath);

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

    private function getOption(array $record): array
    {
        $options = [];

        if (!empty($record['attribute_key_1'])) {
            $options[$record['attribute_key_1']] = $record['value_1'];
            return $options;
        }

        if (!empty($record['attribute_key_2'] && !str_contains($record['attribute_key_2'], 'upcs'))) {
            $options[$record['attribute_key_2']] = $record['value_2'];
            return $options;
        }

        return $options;
    }

    private function getValueFromRecords(array $records, string $keyField, string $keyValue, string $valueField, $default = null)
    {
        foreach ($records as $record) {
            if (isset($record[$keyField]) && $record[$keyField] === $keyValue) {
                return $record[$valueField] ?? $default;
            }
        }

        return $default;
    }

    private function getMultipleValuesFromRecords(array $records, string $keyField1, string $keyValue1, string $keyField2, string $keyValue2, string $valueField, $default = null)
    {
        foreach ($records as $record) {
            if (isset($record[$keyField1]) && $record[$keyField1] === $keyValue1 && isset($record[$keyField2]) && $record[$keyField2] === $keyValue2) {
                return $record[$valueField] ?? $default;
            }
        }

        return $default;
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
