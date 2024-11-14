<?php
declare(strict_types=1);

namespace App\Application\Product\Transport\Tools;

use App\Domain\DTO\ShopifyProductDTO;

class ProductCreation
{
    public function getProductCreateMutation(): string
    {
        return <<<'GRAPHQL'
            mutation CreateProductWithoutOptions($input: ProductInput!) {
                productCreate(input: $input) {
                    product {
                        id
                        title
                    }
                    userErrors {
                        field
                        message
                    }
                }
            }
    GRAPHQL;
    }

    public function formatProductDataWithoutOptions(ShopifyProductDTO $dto): array
    {
        return [
            'title' => $dto->getTitle(),
            'status' => 'ACTIVE',
            'metafields' => $this->formatMetafields($dto->getMetafields())
        ];
    }

    public function getProductSetMutation(): string
    {
        return <<<'GRAPHQL'
            mutation createProductWithColorOption($productSet: ProductSetInput!, $synchronous: Boolean!) {
                productSet(synchronous: $synchronous, input: $productSet) {
                    product {
                        id
                        title
                        options(first: 5) {
                            name
                            position
                            optionValues {
                                name
                            }
                        }
                    }
                    userErrors {
                        field
                        message
                    }
                }
            }
        GRAPHQL;
    }

    public function formatProductData(ShopifyProductDTO $dto): array
    {
        $productData = [
            'title' => $dto->getTitle(),
            'productOptions' => $this->formatProductOptions($dto->getProductOptions()),
            'status' => 'ACTIVE',
        ];

        if (!empty($dto->getMetafields())) {
            $productData['metafields'] = $this->formatMetafields($dto->getMetafields());
        }

        if (!empty($dto->getProductOptions())) {
            $productData['variants'] = $this->generateVariants($dto->getProductOptions());
        }

        return $productData;
    }

    private function generateVariants(array $productOptions): array
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
        foreach ($combinations as $combination) {
            $variants[] = ['optionValues' => $combination];
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


    private function formatProductOptions(array $productOptions): array
    {
        $formattedOptions = [];
        foreach ($productOptions as $option) {
            $formattedOptions[] = [
                'name' => $option['name'],
                'position' => 1,
                'values' => array_map(fn($value) => ['name' => $value], $option['values']),
            ];
        }
        return $formattedOptions;
    }


    public function formatMetafields(array $metafields): array
    {
        $formattedMetafields = [];
        foreach ($metafields as $key => $value) {
            if ($value !== null) {
                $formattedMetafields[] = [
                    'key' => strtolower(str_replace(' ', '_', $key)),
                    'namespace' => 'global',
                    'value' => $this->convertValueToString($value),
                    'type' => 'single_line_text_field',
                ];
            }
        }
        return $formattedMetafields;
    }

    public function convertValueToString($value): string
    {
        if (is_array($value)) {
            return implode(', ', array_map([$this, 'convertValueToString'], $value));
        }

        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        return (string)$value;
    }
}
