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
    public function __construct
    (
        private ProductCreation  $productCreationHelper,
        private Mutation         $mutation,
        private GraphQLInterface $graphQLInterface,
        private LoggerInterface  $logger
    )
    {
    }

    public function processProduct(ShopifyProduct $product): void
    {
        $input = $this->productCreationHelper->prepareInputData($product);
        $mutation = $this->mutation->getProductSetMutation();

        $this->sendProductToShopify($input, $mutation);
    }

    public function sendProductToShopify(array $input, string $mutation): void
    {
        try {
            $variables =
                [
                    'synchronous' => true,
                    'productSet' => $input
                ];

            $response = $this->graphQLInterface->executeQuery($mutation, $variables);

            if ($this->hasGraphQLErrors($response)) {
                $errorMessage = json_encode($response, JSON_THROW_ON_ERROR);
                throw new RuntimeException(sprintf('GraphQL response contains errors: %s', $errorMessage));
            }

            $productId = $response['productSet']['product']['id'] ?? 'unknown';

            $this->logger->logSuccess(
                sprintf('Successfully created product with options, ID: %s', $productId)
            );

        } catch (\Throwable $e) {
            $this->logger->logException(
                new RuntimeException(
                    sprintf('Error sending product with options to Shopify: %s', $e->getMessage()),
                    $e->getCode(),
                    $e
                )
            );
        }
    }

    private function hasGraphQLErrors(array $response): bool
    {
        return
            !empty($response['errors']) ||
            !empty($response['productSet']['userErrors']);
    }
}
