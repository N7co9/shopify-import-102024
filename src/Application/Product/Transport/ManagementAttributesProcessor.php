<?php
declare(strict_types=1);

namespace App\Application\Product\Transport;

use App\Application\Logger\LoggerInterface;
use App\Application\Product\Transport\Tools\ProductCreation;
use App\Domain\API\GraphQLInterface;
use App\Domain\DTO\ShopifyProductDTO;
use App\Domain\DTO\ShopifyResponseDTO;
use RuntimeException;

class ManagementAttributesProcessor
{
    public function __construct
    (
        private GraphQLInterface $graphQLInterface,
        private LoggerInterface  $logger,
        private ProductCreation  $helper
    )
    {
    }

    public function processAttributes(ShopifyProductDTO $shopifyProductDTO, ShopifyResponseDTO $shopifyResponseDTO): void
    {
        $result = $this->sendAttributesToShopify($shopifyProductDTO, $shopifyResponseDTO);
    }

    private function sendAttributesToShopify(ShopifyProductDTO $shopifyProductDTO, ShopifyResponseDTO $shopifyResponseDTO): void
    {
        try {
            $productOptions = $this->helper->formatProductOptions($shopifyProductDTO, $shopifyResponseDTO);
            $mutation = $this->helper->getProductOptionsCreateMutation();
            $response = $this->graphQLInterface->executeQuery($mutation, $productOptions);


            if ($this->hasGraphQLErrors($response)) {
                $errorMessage = json_encode($response, JSON_THROW_ON_ERROR);
                throw new RuntimeException(sprintf('GraphQL response contains errors: %s', $errorMessage));
            }


        } catch (\Throwable $e) {
            $this->logger->logException(
                new RuntimeException(
                    sprintf('Error sending product to Shopify: %s', $e->getMessage()),
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
            !empty($response['productOptionsCreate']['userErrors']);
    }
}

