<?php
declare(strict_types=1);

namespace App\Application\Product\Import\Tools;

use App\Domain\DTO\ShopifyVariant;

class VariantRecordProcessor
{
    public function processVariants(array $stockRecords, array $imageRecords, array $priceRecords, array $concreteRecords): array
    {
        $variants = [];

        foreach ($concreteRecords as $record) {
            $abstractSku = $record['abstract_sku'];
            $concreteSku = $record['concrete_sku'];

            $inventoryQuantity = $this->getValue($stockRecords, 'concrete_sku', $concreteSku, 'quantity', 'N/A');
            $inventoryLocation = $this->getValue($stockRecords, 'concrete_sku', $concreteSku, 'name', 'DEFAULT');
            $isNeverOutOfStock = $this->getValue($stockRecords, 'concrete_sku', $concreteSku, 'is_never_out_of_stock', 0);
            $parentPrice = $this->getValue($priceRecords, 'abstract_sku', $abstractSku, 'value_gross', '0.00');
            $parentCompareAtPrice = $this->getFilteredValue($priceRecords, 'abstract_sku', $abstractSku, 'price_type', 'ORIGINAL', 'value_gross', '0.00');
            $price = $this->getValue($priceRecords, 'concrete_sku', $concreteSku, 'value_gross', $parentPrice);
            $imageUrl = $this->getValue($imageRecords, 'concrete_sku', $concreteSku, 'external_url_large', null);
            $compareAtPrice = $this->getFilteredValue(
                $priceRecords, 'concrete_sku', $concreteSku, 'price_type', 'ORIGINAL', 'value_gross', $parentCompareAtPrice
            );
            $option = $this->getOption($record);

            $requiresShipping = !str_contains($record['name.en_US'], 'Gift Card');
            $upc = str_contains($record['attribute_key_2'] ?? '', 'upcs') ? $record['value_2'] : 'Not Available';

            $variant = new ShopifyVariant(
                $abstractSku,
                $concreteSku,
                $record['name.en_US'],
                0,
                $inventoryQuantity,
                ['name' => $inventoryLocation],
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

    private function getValue(array $records, string $keyField, string $keyValue, string $valueField, $default = null)
    {
        foreach ($records as $record) {
            if (isset($record[$keyField]) && $record[$keyField] === $keyValue) {
                return $record[$valueField] ?? $default;
            }
        }

        return $default;
    }

    private function getFilteredValue(array $records, string $keyField1, string $keyValue1, string $keyField2, string $keyValue2, string $valueField, $default = null)
    {
        foreach ($records as $record) {
            if ($record[$keyField1] === $keyValue1 && $record[$keyField2] === $keyValue2) {
                return $record[$valueField] ?? $default;
            }
        }

        return $default;
    }

    private function getOption(array $record): array
    {
        $options = [];

        if (!empty($record['attribute_key_1'])) {
            $options[$record['attribute_key_1']] = $record['value_1'];
        } elseif (!empty($record['attribute_key_2']) && !str_contains($record['attribute_key_2'], 'upcs')) {
            $options[$record['attribute_key_2']] = $record['value_2'];
        }

        return $options;
    }
}