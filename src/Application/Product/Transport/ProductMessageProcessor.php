<?php
declare(strict_types=1);

namespace App\Application\Product\Transport;

use App\Application\Logger\LoggerInterface;
use App\Application\Product\Transport\Tools\Mutation;
use App\Application\Product\Transport\Tools\ProductCreation;
use App\Domain\API\GraphQLInterface;
use App\Domain\DTO\ShopifyProduct;
use RuntimeException;

class ProductMessageProcessor implements ProductProcessorInterface
{
    public function __construct(
        private ProductCreation  $productCreationHelper,
        private Mutation         $mutation,
        private GraphQLInterface $graphQLInterface,
        private LoggerInterface  $logger
    )
    {
    }

    public function processProduct(ShopifyProduct $product): void
    {
        $this->attachLocationIdByName($product);

        $input = $this->productCreationHelper->prepareInputData($product);
        $mutation = $this->mutation->getProductSetMutation();

        $this->sendProductToShopify($input, $mutation);
    }

    private function attachLocationIdByName(ShopifyProduct $product): void
    {
        foreach ($product->variants as $variant) {

            if (!isset($variant->inventoryLocation['name']) || empty($variant->inventoryLocation)) {
                $this->logger->logException(new RuntimeException('No valid Inventory location found'), 'api');
                return;
            }

            $query = <<<'GRAPHQL'
            query($locationName: String!) {
                locations(first: 1, query: $locationName) {
                    edges {
                        node {
                            id
                            name
                            address {
                                address1
                                city
                                country
                                zip
                            }
                        }
                    }
                }
            }
            GRAPHQL;

            $result = $this->graphQLInterface->executeQuery($query, ['locationName' => $variant->inventoryLocation['name']]);
            $variant->inventoryLocation = $result['locations']['edges'][0]['node'];
        }
    }

    private function sendProductToShopify(array $input, string $mutation): void
    {
        try {
            $variables = $this->prepareVariables($input);
            $response = $this->graphQLInterface->executeQuery($mutation, $variables);

            if ($this->hasGraphQLErrors($response)) {
                $this->handleGraphQLError($response);
            }

            $this->logSuccess($response);

        } catch (\Throwable $e) {
            $this->logError($e);
        }
    }

    private function prepareVariables(array $input): array
    {
        return [
            'synchronous' => true,
            'productSet' => $input,
        ];
    }

    private function hasGraphQLErrors(array $response): bool
    {
        return !empty($response['errors']) || !empty($response['productSet']['userErrors']);
    }

    private function handleGraphQLError(array $response): void
    {
        try {
            $errorMessage = json_encode($response, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            $this->logger->logException($e, 'api');
        }
        throw new RuntimeException(sprintf('GraphQL response contains errors: %s', $errorMessage));
    }

    private function logSuccess(array $response): void
    {
        $productId = $response['productSet']['product']['id'] ?? 'unknown';
        $this->logger->logSuccess(
            sprintf('Successfully created product with options, ID: %s', $productId),
            'api'
        );
    }

    private function logError(\Throwable $e): void
    {
        $this->logger->logException(
            new RuntimeException(
                sprintf('Error sending product with options to Shopify: %s', $e->getMessage()),
                $e->getCode(),
                $e
            ),
            'api'
        );
    }
}
