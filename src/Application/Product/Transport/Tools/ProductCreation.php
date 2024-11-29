<?php
declare(strict_types=1);

namespace App\Application\Product\Transport\Tools;


use App\Domain\DTO\ShopifyProduct;

class ProductCreation
{
    public function __construct()
    {
    }

    public function prepareInputData(ShopifyProduct $product): array
    {
        return [
            'descriptionHtml' => $product->bodyHtml->de_DE,
            'files' => [
                'alt' => $product->title->de_DE,
                'contentType' => 'IMAGE',
                'originalSource' => $product->imageUrl
            ],
            'giftCard' => $product->isGiftCard,
            'handle' => $product->handle->de_DE,
            'metafields' => $this->generateMetafields($product),
            'productOptions' => $this->generateProductOptions($product),
            'productType' => $product->productType,
            'seo' => [
                'description' => $product->bodyHtml->de_DE,
                'title' => $product->title->de_DE
            ],
            'status' => $product->status,
            'tags' => $product->tags->de_DE,
            'title' => $product->title->de_DE,
            'variants' => $this->formatVariantsForShopifyInput($product),
            'vendor' => $product->vendor
        ];
    }

    public function generateMetafields(ShopifyProduct $product): array
    {
        $metafields = [];

        foreach ($product->attributes as $key => $value) {
            $metafields[] = [
                'key' => $key,
                'namespace' => 'product.attributes',
                'type' => 'single_line_text_field',
                'value' => is_array($value) ? json_encode($value, JSON_THROW_ON_ERROR) : $value,
            ];
        }

        if ($product->tags->de_DE) {
            $metafields[] = [
                'key' => 'tags',
                'namespace' => 'product.info',
                'type' => 'list.single_line_text_field',
                'value' => json_encode(explode(',', $product->tags->de_DE), JSON_THROW_ON_ERROR)
            ];
        }

        if ($product->isBundle) {
            $metafields[] = [
                'key' => 'is_bundle',
                'namespace' => 'product.info',
                'type' => 'boolean',
                'value' => $product->isBundle ? 'true' : 'false',
            ];
        }

        return $metafields;
    }


    public function generateProductOptions(ShopifyProduct $product): array
    {
        $position = 1;
        $optionSetInputs = [];
        $optionValuesMap = [];

        foreach ($product->variants as $variant) {
            if (empty($variant->option)) {
                $optionSetInputs[] = [
                    'name' => $variant->title,
                    'position' => $position++,
                    'values' => [
                        ['name' => $variant->title]
                    ],
                    'linkedMetafield' => [
                        'key' => strtolower(str_replace(' ', '_', $variant->title)),
                        'namespace' => 'product.options',
                        'values' => [$variant->title]
                    ],
                ];
            } else {
                foreach ($variant->option as $key => $value) {
                    if (!isset($optionValuesMap[$key])) {
                        $optionValuesMap[$key] = [];
                    }
                    if (!in_array($value, $optionValuesMap[$key], true)) {
                        $optionValuesMap[$key][] = $value;
                    }
                }
            }
        }

        foreach ($optionValuesMap as $key => $values) {
            $optionSetInputs[] = [
                'name' => ucfirst(str_replace('_', ' ', $key)),
                'position' => $position++,
                'values' => array_map(fn($value) => ['name' => $value], $values),
                'linkedMetafield' => [
                    'key' => strtolower(str_replace(' ', '_', $key)),
                    'namespace' => 'product.options',
                    'values' => $values
                ],
            ];
        }

        foreach ($optionSetInputs as $index => $option) {
            if (isset($option['linkedMetafield'], $option['values'])) {
                unset($optionSetInputs[$index]['linkedMetafield']);
            }
        }

        return $optionSetInputs;
    }

    private function formatVariantsForShopifyInput(ShopifyProduct $product): array
    {
        $formattedVariants = [];
        $productOptions = $this->generateProductOptions($product);

        foreach ($product->variants as $variant) {
            $optionValues = $this->mapVariantOptionValues($variant, $productOptions);

            $formattedVariant = [
                'optionValues' => $optionValues,
                'price' => $variant->price ?? '0.00',
                'inventoryPolicy' => strtoupper($variant->inventoryPolicy ?? 'DENY'),
                'taxable' => $variant->taxable ?? true,
            ];

            if (!empty($variant->concreteSku)) {
                $formattedVariant['sku'] = $variant->concreteSku;
            }

            $formattedVariants[] = $formattedVariant;
        }

        return $formattedVariants;
    }

    private function mapVariantOptionValues($variant, array $productOptions): array
    {
        $optionValues = [];

        foreach ($productOptions as $productOption) {
            if (isset($productOption['values'])) {
                $optionName = $productOption['name'];
                $linkedValues = $productOption['values'];

                if (empty($variant->option)) {
                    foreach ($linkedValues as $value) {
                        $optionValues[] = [
                            'optionName' => $optionName,
                            'name' => $value['name'],
                        ];
                    }
                } else {
                    foreach ($linkedValues as $value) {
                        if ($variant->option[strtolower(str_replace(' ', '_', $optionName))] === $value['name']) {
                            $optionValues[] = [
                                'optionName' => $optionName,
                                'name' => $value['name'],
                            ];
                            break;
                        }
                    }
                }
            }
        }

        return $optionValues;
    }


}