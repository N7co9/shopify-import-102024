<?php
declare(strict_types=1);

namespace App\Application\Product\Transport;

use App\Application\Product\Transport\Tools\Mutation;
use App\Application\Product\Transport\Tools\ProductCreation;
use App\Domain\API\GraphQLInterface;
use App\Domain\DTO\ShopifyProduct;
use Psr\Log\LoggerInterface;
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

    public function sendProductToShopify(array $input, string $mutation): void
    {
        try {
            $variables = $this->prepareVariables($input);
            $response = $this->graphQLInterface->executeQuery($mutation, $variables);

            if ($this->hasGraphQLErrors($response)) {
                $this->handleGraphQLError($response);
            }

            $this->sendTrackInventoryRequest($response);

            $this->logSuccess($response);

        } catch (\Exception $e) {
            $this->logError($e);
        }
    }

    public function attachLocationIdByName(ShopifyProduct $product): void
    {
        foreach ($product->variants as $variant) {

            if (!isset($variant->inventoryLocation['name'])) {
                $this->logger->error('No valid Inventory location found');
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

    public function sendTrackInventoryRequest(array $productSetResponse): void
    {
        foreach ($productSetResponse['productSet']['product']['variants']['nodes'] as $variant) {
            $inventoryItemId = $variant['inventoryItem']['id'];
            $variables = [
                'id' => $inventoryItemId,
                'input' => [
                    'tracked' => true,
                ],
            ];
            $query = $this->mutation->getInventoryItemUpdateMutation();
            $response = $this->graphQLInterface->executeQuery($query, $variables);
            if (!empty($response['inventoryItemUpdate']['userErrors'])) {
                $this->logger->error(sprintf('An Exception occurred while sending a TrackInventoryRequest with InventoryItemId: %s', $inventoryItemId));
                return;
            }
            $this->logger->info(sprintf('Inventory Item: %s erfolgreich Tracking aktiviert', $inventoryItemId));
        }

    }

    public function prepareVariables(array $input): array
    {
        return [
            'synchronous' => true,
            'productSet' => $input,
        ];
    }

    public function hasGraphQLErrors(array $response): bool
    {
        return !empty($response['errors']) || !empty($response['productSet']['userErrors']);
    }

    public function handleGraphQLError(array $response): void
    {
        $errorMessage = 'An Exception while handling a GraphQL Error Response occurred';

        try {
            $errorMessage = json_encode($response, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            $this->logger->critical($e->getMessage());
        }

        throw new RuntimeException(sprintf('GraphQL response contains errors: %s', $errorMessage));
    }

    public function logSuccess(array $response): void
    {
        $productId = $response['productSet']['product']['id'] ?? 'unknown';
        $this->logger->info(sprintf('Successfully created product with options, ID: %s', $productId));
    }

    public function logError(\Throwable $e): void
    {
        $this->logger->critical(sprintf('Error sending product with options to Shopify: %s', $e->getMessage()));
    }
}
