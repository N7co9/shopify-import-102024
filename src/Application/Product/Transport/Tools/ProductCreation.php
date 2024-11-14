<?php
declare(strict_types=1);

namespace App\Application\Product\Transport\Tools;

use App\Domain\DTO\ShopifyProductDTO;
use App\Domain\DTO\ShopifyResponseDTO;

class ProductCreation
{
    public function getProductCreateMutation(): string
    {
        return <<<'GRAPHQL'
            mutation createProductMetafields($input: ProductInput!) {
                productCreate(input: $input) {
                    product {
                        id
                        metafields(first: 3) {
                            edges {
                                node {
                                    id
                                    namespace
                                    key
                                    value
                                }
                            }
                        }
                    }
                    userErrors {
                        message
                        field
                    }
                }
            }
        GRAPHQL;
    }

    public function getProductOptionsCreateMutation(): string
    {
        return <<<'GRAPHQL'
             mutation createOptions($productId: ID!, $options: [OptionCreateInput!]!, $variantStrategy: ProductOptionCreateVariantStrategy) {
                productOptionsCreate(productId: $productId, options: $options, variantStrategy: $variantStrategy) {
                  userErrors {
                    field
                    message
                    code
                  }
                  product {
                    id
                    variants(first: 10) {
                      nodes {
                        id
                        title
                        selectedOptions {
                          name
                          value
                        }
                      }
                    }
                    options {
                      id
                      name
                      values
                      position
                      optionValues {
                        id
                        name
                        hasVariants
                      }
                    }
                  }
                }
              }
    GRAPHQL;
    }


    public function formatProductOptions(ShopifyProductDTO $shopifyProductDTO, ShopifyResponseDTO $responseDTO, string $variantStrategy = null): array
    {
        $options = [];
        $productOptions = $shopifyProductDTO->getProductOptions();

        $limitedOptions = array_slice($productOptions, 0, 3);

        foreach ($limitedOptions as $key => $value) {
            $formattedValues = array_map(function($val) {
                return ['name' => $val];
            }, is_array($value) ? $value : [$value]);

            $options[] = [
                'name' => ucfirst(str_replace('_', ' ', $key)),
                'values' => $formattedValues
            ];
        }

        $variables = [
            'productId' => $responseDTO->getPID(),
            'options' => $options
        ];

        if ($variantStrategy) {
            $variables['variantStrategy'] = $variantStrategy;
        }

        return $variables;
    }



    public function formatProductData(ShopifyProductDTO $dto): array
    {
        $productData = [
            'title' => $dto->getTitle(),
            'descriptionHtml' => $dto->getDescriptionHtml(),
            'productType' => $dto->getProductType(),
            'status' => 'ACTIVE',
        ];

        if (!empty($dto->getMetafields())) {
            $productData['metafields'] = $this->formatMetafields($dto->getMetafields());
        }

        return $productData;
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