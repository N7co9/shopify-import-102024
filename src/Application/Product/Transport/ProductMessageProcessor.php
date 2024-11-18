<?php
declare(strict_types=1);

namespace App\Application\Product\Transport;

use App\Application\Logger\LoggerInterface;
use App\Application\Mapper\ProductToShopifyMapper;
use App\Application\Product\Transport\Tools\Mutation;
use App\Application\Product\Transport\Tools\ProductCreation;
use App\Domain\API\GraphQLInterface;
use App\Domain\DTO\AbstractProductDTO;
use App\Domain\DTO\ShopifyProductDTO;
use App\Domain\DTO\ShopifyResponseDTO;
use RuntimeException;

class ProductMessageProcessor implements ProductProcessorInterface
{
    public function __construct(
        private ProductToShopifyMapper $mapper,
        private GraphQLInterface       $graphQLInterface,
        private LoggerInterface        $logger,
        private ProductCreation        $helper,
        private Mutation               $mutation
    )
    {
    }

    public function processProduct(AbstractProductDTO $abstractProductDTO): void
    {
        $shopifyProductDTO = $this->mapper->mapToShopifyProductDTO($abstractProductDTO);

        if ($shopifyProductDTO === null) {
            $this->logger->logError('Mapper returned a ShopifyProductDTO as null');
            return;
        }

        if (empty($shopifyProductDTO->getProductOptions())) {
            $this->sendProductToShopifyWithoutOptions($shopifyProductDTO);
        } else {
            $this->sendProductToShopifyWithOptions($shopifyProductDTO);
        }
    }

    private function sendProductToShopifyWithOptions(ShopifyProductDTO $shopifyProductDTO): void
    {
        try {
            $productData = $this->helper->formatProductData($shopifyProductDTO);

            if (empty($productData['productOptions']) || !is_array($productData['productOptions'])) {
                throw new RuntimeException('Product options are missing or not properly structured');
            }

            $mutation = $this->mutation->getProductSetMutation();
            $variables = [
                'synchronous' => true,
                'productSet' => $productData,
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


    private function sendProductToShopifyWithoutOptions(ShopifyProductDTO $shopifyProductDTO): ShopifyResponseDTO
    {
        try {
            $productData = $this->helper->formatProductDataWithoutOptions($shopifyProductDTO);
            $mutation = $this->mutation->getProductCreateMutation();
            $variables = ['input' => $productData];

            $response = $this->graphQLInterface->executeQuery($mutation, $variables);

            if ($this->hasGraphQLErrors($response)) {
                $errorMessage = json_encode($response, JSON_THROW_ON_ERROR);
                throw new RuntimeException(sprintf('GraphQL response contains errors: %s', $errorMessage));
            }

            $productId = $response['productCreate']['product']['id'] ?? 'unknown';

            $this->logger->logSuccess(
                sprintf('Successfully created product without options, ID: %s', $productId)
            );

            return new ShopifyResponseDTO(
                $productId,
                true,
                $response['productCreate']['userErrors'] ?? [],
                []
            );
        } catch (\Throwable $e) {
            $this->logger->logException(
                new RuntimeException(
                    sprintf('Error sending product without options to Shopify: %s', $e->getMessage()),
                    $e->getCode(),
                    $e
                )
            );

            return new ShopifyResponseDTO(
                'No PID in Response',
                false,
                $response['productCreate']['userErrors'] ?? [],
                []
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
