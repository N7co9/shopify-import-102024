<?php
declare(strict_types=1);

namespace App\Application\Product\Import;

use App\Domain\DTO\LocalizedString;
use App\Domain\DTO\ShopifyProduct;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\File;

class ShopifyProductImporter
{
    public function import(string $abstractProductFilePath, string $priceFilePath, string $imageFilePath): array
    {
        $abstractProductRecords = $this->parseCsv($abstractProductFilePath);
        $priceRecords = $this->parseCsv($priceFilePath);
        $imageRecords = $this->parseCsv($imageFilePath);

        $products = [];

        foreach ($abstractProductRecords as $record) {
            $abstractSKU = $record['abstract_sku'];

            $price = $this->getValueFromRecords($priceRecords, 'abstract_sku', $abstractSKU, 'value_gross', '0.00');
            $compareAtPrice = $this->getMultipleValuesFromRecords($priceRecords, 'abstract_sku', $abstractSKU, 'price_type', 'ORIGINAL', 'value_gross', '0.00');
            $imageUrl = $this->getValueFromRecords($imageRecords, 'abstract_sku', $abstractSKU, 'external_url_large', null);
            $attributes = $this->generateAttributes($record);
            $isBundle = false;
            if ($record['attribute_key_1']) {
                $isBundle = true;
            }
            $giftCard = false;
            if (str_contains($record['name.de_DE'], 'Geschenkgutschein') || str_contains($record['name.en_US'], 'Gift Card')) {
                $giftCard = true;
            }


            $product = new ShopifyProduct(
                $record['abstract_sku'],
                new LocalizedString($record['name.en_US'], $record['name.de_DE']),
                new LocalizedString($record['description.en_US'], $record['description.de_DE']),
                'Shopify',
                $price,
                $compareAtPrice,
                $record['category_key'],
                $giftCard,
                new LocalizedString($record['url.en_US'], $record['url.de_DE']),
                'ACTIVE',
                null,
                null,
                $imageUrl,
                $attributes,
                new LocalizedString($record['meta_keywords.en_US'], $record['meta_keywords.de_DE']),
                null,
                date('Y-m-d H:i:s'),
                null,
                null,
                $record['category_product_order'] ?? '',
                $record['tax_set_name'],
                $isBundle,
                $record['new_from'] ?? '',
                $record['new_to'] ?? ''
            );

            $products[] = $product;
        }

        return $products;
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
            if (empty($row[0])) {
                continue;
            }

            $records[] = array_combine($header, $row);
        }

        return $records;
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

    private function generateAttributes(array $record): array
    {
        $managementAttributes = [];
        foreach ($record as $key => $value) {
            if (str_starts_with($key, 'attribute_key_') && !empty($value)) {
                $attributeIndex = substr($key, -1);
                $managementAttributes[$value] = $record["value_{$attributeIndex}"] ?? null;
            }
        }

        return $managementAttributes;
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