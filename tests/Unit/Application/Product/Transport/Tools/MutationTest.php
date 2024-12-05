<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Product\Transport\Tools;

use App\Application\Product\Transport\Tools\Mutation;
use PHPUnit\Framework\TestCase;

class MutationTest extends TestCase
{
    private Mutation $mutation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mutation = new Mutation();
    }

    protected function tearDown(): void
    {
        unset($this->mutation);

        parent::tearDown();
    }

    public function testGetProductCreateMutation(): void
    {
        // Arrange
        $expectedMutation = <<<'GRAPHQL'
            mutation CreateProductWithoutOptions($input: ProductInput!, $media: [CreateMediaInput!]) {
                productCreate(input: $input, media: $media) {
                    product {
                        id
                        title
                        media(first: 10) {
                            nodes {
                                id
                                alt
                                mediaContentType
                                status
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

        // Act
        $actualMutation = $this->mutation->getProductCreateMutation();

        // Assert
        $this->assertSame($expectedMutation, $actualMutation);
    }

    public function testGetProductSetMutation(): void
    {
        // Arrange
        $expectedMutation = <<<'GRAPHQL'
        mutation createProductWithMedia($productSet: ProductSetInput!, $synchronous: Boolean!) {
            productSet(synchronous: $synchronous, input: $productSet) {
                product {
                    id
                    media(first: 5) {
                        nodes {
                            id
                            alt
                            mediaContentType
                            status
                        }
                    }
                    variants(first: 5) {
                        nodes {                      
                            title
                            price
                            compareAtPrice
                            inventoryItem {
                                id
                                sku
                                tracked
                                inventoryLevels(first: 5) {
                                    edges {
                                        node {
                                            location {
                                                name
                                            }
                                        }
                                    }
                                }
                            }
                            media(first: 5) {
                                nodes {
                                    id
                                    alt
                                    mediaContentType
                                    status
                                }
                            }
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

        // Act
        $actualMutation = $this->mutation->getProductSetMutation();

        // Assert
        $this->assertSame($expectedMutation, $actualMutation);
    }

    public function testGetInventoryItemUpdateMutation(): void
    {
        // Arrange
        $expectedMutation = <<<'GRAPHQL'
            mutation inventoryItemUpdate($id: ID!, $input: InventoryItemInput!) {
                inventoryItemUpdate(id: $id, input: $input) {
                inventoryItem {
                id
                unitCost {
                    amount
                }
                tracked
                countryCodeOfOrigin
                provinceCodeOfOrigin
                harmonizedSystemCode
                countryHarmonizedSystemCodes(first: 1) {
                edges {
                    node {
                      harmonizedSystemCode
                      countryCode
                    }
                  }
                }
              }
              userErrors {
                    message
              }
            }
          }
         GRAPHQL;

        // Act
        $actualMutation = $this->mutation->getInventoryItemUpdateMutation();

        // Assert
        $this->assertSame($expectedMutation, $actualMutation);
    }

    public function testGetProductVariantsBulkUpdateMutation(): void
    {
        // Arrange
        $expectedMutation = <<<'GRAPHQL'
            mutation UpdateProductVariantsOptionValuesInBulk($productId: ID!, $variants: [ProductVariantsBulkInput!]!) {
                productVariantsBulkUpdate(productId: $productId, variants: $variants) {
                    product {
                        id
                        title
                        options {
                            id
                            position
                            name
                            values
                            optionValues {
                                id
                                name
                                hasVariants
                            }
                        }
                    }
                    productVariants {
                        id
                        title
                        selectedOptions {
                            name
                            value
                        }
                    }
                    userErrors {
                        field
                        message
                    }
                }
            }
        GRAPHQL;

        // Act
        $actualMutation = $this->mutation->getProductVariantsBulkUpdateMutation();

        // Assert
        $this->assertSame($expectedMutation, $actualMutation);
    }
}
