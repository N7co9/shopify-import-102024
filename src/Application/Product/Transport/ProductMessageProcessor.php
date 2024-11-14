<?php
declare(strict_types=1);

namespace App\Application\Product\Transport;

use App\Application\Logger\LoggerInterface;
use App\Application\Mapper\ProductToShopifyMapper;
use App\Application\Product\Transport\Tools\ProductCreation;
use App\Domain\API\GraphQLInterface;
use App\Domain\DTO\AbstractProductDTO;
use App\Domain\DTO\ShopifyProductDTO;
use App\Domain\DTO\ShopifyResponseDTO;
use RuntimeException;

class ProductMessageProcessor implements ProductProcessorInterface
{
    public function __construct(
        private ProductToShopifyMapper        $mapper,
        private GraphQLInterface              $graphQLInterface,
        private LoggerInterface               $logger,
        private ProductCreation               $helper,
        private ManagementAttributesProcessor $attributesProcessor
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

        $response = $this->sendProductToShopify($shopifyProductDTO);
        $this->attributesProcessor->processAttributes($shopifyProductDTO, $response);
    }

    private function sendProductToShopify(ShopifyProductDTO $shopifyProductDTO): ShopifyResponseDTO
    {
        try {
            $productData = $this->helper->formatProductData($shopifyProductDTO);
            $mutation = $this->helper->getProductCreateMutation();
            $variables = ['input' => $productData];

            $response = $this->graphQLInterface->executeQuery($mutation, $variables);

            if ($this->hasGraphQLErrors($response)) {
                $errorMessage = json_encode($response, JSON_THROW_ON_ERROR);
                throw new RuntimeException(sprintf('GraphQL response contains errors: %s', $errorMessage));
            }

            $this->logger->logSuccess(
                sprintf('Successfully created product with ID: %s', $response['productCreate']['product']['id'] ?? 'unknown')
            );
            return new ShopifyResponseDTO
            (
                $response['productCreate']['product']['id'],
                true,
                $response['productCreate']['userErrors'],
                $response['productCreate']['product']['metafields']['edges']
            );
        } catch (\Throwable $e) {
            $this->logger->logException(
                new RuntimeException(
                    sprintf('Error sending product to Shopify: %s', $e->getMessage()),
                    $e->getCode(),
                    $e
                )
            );
            return new ShopifyResponseDTO
            (
                $response['productCreate']['product']['id'] ?? 'No PID in Response',
                false,
                $response['productCreate']['userErrors'] ?? [],
                $response['productCreate']['product']['metafields']['edges'] ?? []
            );
        }
    }

    private function hasGraphQLErrors(array $response): bool
    {
        return
            !empty($response['errors']) ||
            !empty($response['data']['productCreate']['userErrors']);
    }
}
