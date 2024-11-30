<?php
declare(strict_types=1);

namespace App\Application\Product\Import\Tools;

use App\Domain\DTO\LocalizedString;
use App\Domain\DTO\ShopifyProduct;

class ProductRecordProcessor
{
    public function processProducts(array $abstractProductRecords, array $priceRecords, array $imageRecords): array
    {
        $products = [];

        foreach ($abstractProductRecords as $record) {
            $abstractSKU = $record['abstract_sku'];

            $price = $this->getValue($priceRecords, 'abstract_sku', $abstractSKU, 'value_gross', '0.00');
            $compareAtPrice = $this->getFilteredValue(
                $priceRecords, 'abstract_sku', $abstractSKU, 'price_type', 'ORIGINAL', 'value_gross', '0.00'
            );
            $imageUrl = $this->getValue($imageRecords, 'abstract_sku', $abstractSKU, 'external_url_large', null);

            $attributes = $this->generateAttributes($record);
            $isBundle = !empty($record['attribute_key_1']);
            $giftCard = $this->isGiftCard($record);

            $products[] = new ShopifyProduct(
                $abstractSKU,
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
        }

        return $products;
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

    private function generateAttributes(array $record): array
    {
        $attributes = [];
        foreach ($record as $key => $value) {
            if (str_starts_with($key, 'attribute_key_') && !empty($value)) {
                $index = substr($key, -1);
                $attributes[$value] = $record["value_{$index}"] ?? null;
            }
        }

        return $attributes;
    }

    private function isGiftCard(array $record): bool
    {
        return str_contains($record['name.de_DE'], 'Geschenkgutschein') || str_contains($record['name.en_US'], 'Gift Card');
    }
}
