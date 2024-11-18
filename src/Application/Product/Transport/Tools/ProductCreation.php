<?php
declare(strict_types=1);

namespace App\Application\Product\Transport\Tools;

use App\Domain\DTO\ShopifyProductDTO;

class ProductCreation
{

    public function __construct
    (
        private StructureAndFormat $structureAndFormat
    )
    {
    }

    public function formatProductDataWithoutOptions(ShopifyProductDTO $dto): array
    {
        return [
            'title' => $dto->getTitle(),
            'descriptionHtml' => $dto->getDescriptionHtml(),
            'productType' => $dto->getProductType(),
            'status' => 'ACTIVE',
            'metafields' => $this->structureAndFormat->formatMetafields($dto->getMetafields())
        ];
    }

    public function formatProductData(ShopifyProductDTO $dto): array
    {
        $productData = [
            'title' => $dto->getTitle(),
            'descriptionHtml' => $dto->getDescriptionHtml(),
            'productOptions' => $this->structureAndFormat->formatProductOptions($dto->getProductOptions()),
            'productType' => $dto->getProductType(),
            'status' => 'ACTIVE',
        ];

        if (!empty($dto->getMetafields())) {
            $productData['metafields'] = $this->structureAndFormat->formatMetafields($dto->getMetafields());
        }

        if (!empty($dto->getProductOptions())) {
            $productData['variants'] = $this->generateVariants($dto->getProductOptions(), $dto->getPrice());
        }

        if (!empty($dto->getMedia())) {
            $productData['files'] = $this->structureAndFormat->formatMedia($dto->getMedia());
        }

        return $productData;
    }


    private function generateVariants(array $productOptions, array $prices = []): array
    {
        $options = [];
        foreach ($productOptions as $option) {
            $values = array_map(function ($value) use ($option) {
                if (is_array($value) && isset($value['name'])) {
                    return ['optionName' => $option['name'], 'name' => $value['name']];
                }
                if (is_string($value)) {
                    return ['optionName' => $option['name'], 'name' => $value];
                }
                throw new \UnexpectedValueException("Unexpected value format in product options.");
            }, $option['values']);
            $options[] = $values;
        }

        $combinations = $this->combineOptions($options);

        $variants = [];
        foreach ($combinations as $index => $combination) {
            $variant = ['optionValues' => $combination];

            $filteredPrices = array_filter($prices, fn($price) => $price['currency'] === 'EUR');

            $defaultPrice = null;
            $compareAtPrice = null;

            foreach ($filteredPrices as $price) {
                if ($price['priceType'] === 'DEFAULT') {
                    $defaultPrice = $price['priceGross'];
                } elseif ($price['priceType'] === 'ORIGINAL') {
                    $compareAtPrice = $price['priceGross'];
                }
            }

            if ($defaultPrice !== null) {
                $variant['price'] = $defaultPrice;
            }
            if ($compareAtPrice !== null) {
                $variant['compareAtPrice'] = $compareAtPrice;
            }

            $variants[] = $variant;
        }

        return $variants;
    }



    private function combineOptions(array $arrays): array
    {
        $result = [[]];
        foreach ($arrays as $propertyValues) {
            $temp = [];
            foreach ($result as $resultItem) {
                foreach ($propertyValues as $propertyValue) {
                    $temp[] = array_merge($resultItem, [$propertyValue]);
                }
            }
            $result = $temp;
        }
        return $result;
    }
}