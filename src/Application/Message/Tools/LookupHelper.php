<?php
declare(strict_types=1);

namespace App\Application\Message\Tools;

use App\Domain\API\GraphQLInterface;

class LookupHelper
{
    public function __construct
    (
        private GraphQLInterface $graphQL
    )
    {
    }

    public function fetchExistingProductInformation(string $title): array
    {
        $response = $this->checkProductExistence($title);

        if (!empty($response['products']['edges'])) {
            $PID = $response['products']['edges']['0']['node']['id'];
            $variantResult = $this->checkVariantExistence($PID);
        } else {
            return [];
        }

        if (!empty($variantResult['product']['variants']['edges'])) {
            $variants = [];
            foreach ($variantResult['product']['variants']['edges'] as $variant) {
                $variants[] = $variant['node'];
            }
        }

        return [
            'PID' => $PID,
            'Variants' => $variants
        ];
    }

    public function checkProductExistence(string $title): array
    {
        $query = <<<'GRAPHQL'
        query ($title: String!) {
            products(first: 1, query: $title) {
                edges {
                    node {
                        id
                        title
                    }
                }
            }
        }
        GRAPHQL;

        return $this->graphQL->executeQuery($query, ['title' => $title]);
    }

    public function checkVariantExistence(string $PID): array
    {
        $query = <<<'GRAPHQL'
        query ($productId: ID!) {
          product(id: $productId) {
            id
            title
            variants(first: 100) {
              edges {
                node {
                  id
                  sku
                  price
                  inventoryQuantity
                  title
                }
              }
            }
          }
        }
        GRAPHQL;

        return $this->graphQL->executeQuery($query, ['productId' => $PID]);
    }

    public function checkVariantInventory(string $PID): array
    {
        $query = <<<'GRAPHQL'
        query GetInventoryItemsForProduct($productId: ID!) {
          product(id: $productId) {
            variants(first: 10) {
              edges {
                node {
                  id
                  inventoryItem {
                    id
                  }
                }
              }
            }
          }
        }
        GRAPHQL;

        return $this->graphQL->executeQuery($query, ['productId' => $PID]);
    }
}